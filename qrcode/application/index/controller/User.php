<?php
namespace app\index\controller;

use think\Db;
use think\Request;

class User extends Base{

    # 列表
    public function lst()
    {
        $user_list = Db::name('user')->order("uid desc")->paginate(10);
        $page=$user_list->render();
        $data=$user_list->all();
        $count=$user_list->total();
        foreach ($data as $k=>$v){
            $data[$k]['type_img'] = $this->get_user_bank($v['uid']);
        }
        $res = ['data'=>$data,'page'=>$page,'count'=>$count];
        return view('lst',$res);
    }

    # 编辑
    public function edit(){
        if (Request::instance()->isPost()){
            $uid = (int)input('id');
            $type = (int)input('type');

            $data = input();
            if (empty($data['user_name'])||empty($data['bank_name'])) return json(["status" =>'error', "errormsg" => "用户名或开户银行不能为空"]);
            if ($type == 1){
                if (empty($data['bank_number']) || strlen($data['bank_number']) < 15){
                    return json(["status" => 'error', "errormsg" => "银行卡号错误"]);
                }
            }

            $file = Request::instance()->file('qrcode');
            if ($file){
                $info = $file->move( '../public/uploads');
                if($info){
                    $exe = $info->getExtension();
                    $fname = '../public/uploads/'.$info->getSaveName();
                    $fname_exe_md5 = md5(file_get_contents($fname)) . '.' . $exe; # 打开文件md5
                    $qrcode = '/uploads/pay/'. $fname_exe_md5;
                    $file_name ='../public/uploads/pay/'. $fname_exe_md5;
                    $copy_file_name = copy($fname,$file_name);
                    if (!$copy_file_name) return json(["status" =>'error', "errormsg" => "移动文件出错，请稍后再试"]);
                    $data['qrcode'] = $qrcode;
                }else{
                    return json(["status" =>'error', "errormsg" => $info->getError()]);
                }
                if ($data['type']==1 && !empty($data['bank_number'])){
                    if (strlen($data['bank_number'])<15)return json(["status" =>'error', "errormsg" => "银行卡号错误"]);
                }
            }
            $data['ip'] = Request::instance()->ip();
            $user_bank = Db::name('user_bank')->where(["uid"=>$uid,'type'=>$type])->field('id,uid')->find();
            unset($data['id']);
            if (empty($user_bank)){
                $data['uid'] = $uid;
                $res=Db::name('user_bank')->insert($data);
            }else{
                $res=Db::name('user_bank')->where("id", $user_bank['id'])->update($data);
            }
            if($res!==false){
                $repos = $this->set_curl_user($uid,$type);
                return json(["status"=>'ok',"msg"=>"修改成功"]);
            }else {
                return json(["status" =>'error', "errormsg" => "修改失败"]);
            }
        }
        $uid = (int)input('uid');
        $user = Db::name('user')->where(['uid'=>$uid])->find();
        $bank = Db::name('user_bank')->where(['uid'=>$uid])->select();
        $bank_arr = array_column($bank,null,'type');
        $res = ['user'=>$user,'bank'=>$bank_arr];
        return view('edit',$res);
    }

    # 删除支付图片
    public function del()
    {
        $id = (int)input("id");
        $res =Db::name("user_bank")->where(["id"=>$id])->delete();
        if($res){
            return json(["status"=>'ok',"msg"=>"修改成功"]);
        }else {
            return json(["status" =>'error', "errormsg" => "修改失败"]);
        }
    }

    #
    protected function get_user_bank($uid)
    {
        $tmp = ['1'=>'<img src="/assets/bank/bank.png">','2'=>'<img src="/assets/bank/alipay.png">','3'=>'<img src="/assets/bank/wechatpay.png">'];
        $bank_list = Db::name('user_bank')->where(['uid'=>$uid])->field('type')->order("type")->select();
        if (!$bank_list) return false;
        $bank_list_arr = array_column($bank_list,'type');
        $arr = array_replace($bank_list_arr,$tmp);
        if (!$arr) return false;
        $str = '';
        foreach ($bank_list_arr as $v){
            $str .= $tmp[$v];
        }
        return $str;
    }


    # 同步通知结果
    protected function set_curl_user($uid, $type)
    {
        $user_bank = Db::name("user_bank")->where(['uid' => $uid, 'type' => $type])->field("uid,type,user_name,bank_name,bank_number,qrcode")->find();
        $url = config("web.shop_url") . '/index/qrcode/set_pay_img';
        $user_bank['sign'] = md5(date("Y-m-d H") . 'bpayer');
        $res = curl($url, $user_bank);
        if ($res && isset($res['body'])) {
            $response = json_decode($res['body'], true);
            if ($response['status'] == 'ok' && $response['msg'] == 2) {
                Db::name("user_bank")->where(['uid' => $uid])->update(['status' => $response['msg']]);

                return true;
            }
        }

        return false;
    }
}
