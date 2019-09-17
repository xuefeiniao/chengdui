<?php

namespace app\index\controller;

use think\Controller;
use think\Db;

require_once('../application/common.php');
class Qrcode extends Controller {

    # 获取用户
    public function user()
    {
        $user_list = input('user_list');
        $user_list_arr = json_decode($user_list);

        $where = '';
        if ($user_list_arr){
            $user_list_str = implode(',',$user_list_arr);
            $where = "id not in({$user_list_str})";
        }
        $user = Db::name("user")->field("id,username,nickname,mobile,type")->where($where)->select();
        if (!$user)return json(['status'=>'error','errormsg'=>'暂无数据']);
        return json(['status'=>'ok','data'=>$user]);
    }


    # 修改信息
    public function set_pay_img()
    {
        if (!$this->_qrcode_sign(input('sign'))) return json(['status'=>'error','errormsg'=>"签名错误"]);
        $uid = (int)input('uid');
        $type = (int)input('type');
        $qrcode = input('qrcode');
        $user_name = input('user_name');
        $bank_name = input('bank_name');
        $bank_number = (int)input('bank_number');
        if (empty($uid)||empty($type)||empty($user_name)||empty($bank_name)) return json(['status'=>'error','errormsg'=>"参数错误"]);
        $data = ['uid'=>$uid,'type'=>$type,'user_name'=>$user_name,'bank_name'=>$bank_name];
        if ($type ==1){
            $data['bank_number'] = $bank_number;
        }else{
            $data['qrcode'] = $qrcode;
        }
        $user_bank = Db::name('user_bank_ces')->where(["uid"=>$uid,'type'=>$type])->field('id,uid')->find();
        if (empty($user_bank)){
            $user_bank_res=Db::name('user_bank_ces')->insert($data);
        }else{
            $user_bank_res=Db::name('user_bank_ces')->where("id", $user_bank['id'])->update($data);
        }

        if (empty($user_bank_res))return json(['status'=>'error']);
        if (in_array($type,[2,3])){
            $qrcode_name = pathinfo($qrcode, PATHINFO_BASENAME);
            $url = "http://47.244.3.113:9090".$qrcode;
            $res = curl($url);
            $down = file_put_contents("../public/uploads/pay/".$qrcode_name,$res['body']);
            if (!$down){
                return json(['status'=>'ok','msg'=>1]);
            }
        }
        return json(['status'=>'ok','msg'=>2]);
    }

    # 遍历目录
    public function get_file()
    {
        if (!$this->_qrcode_sign(input('sign'))) return json(['status'=>'error','errormsg'=>"签名错误"]);
        $path = EXIMG.'/uploads/pay';
        $file_arr = $this->my_scandir($path);
        if ($file_arr){
            $list = [];
            foreach ($file_arr as $k=>$v){
                $file = pathinfo($v);
                $host = input('server.REQUEST_SCHEME') . '://' . input('server.SERVER_NAME');
                $url = $host . '/uploads/pay/' . $file['basename'];
                $list[$file['filename']] = $url;
            }
            return json(['status'=>'ok','data'=>$list]);
        }
        return json(['status'=>'error']);
    }


    public function my_scandir($dir)
    {
        $files = array();
        if (is_dir($dir)) {
            if ($handle = opendir($dir)) {
                while (($file = readdir($handle)) !== false) {
                    if ($file != "." && $file != "..") {
                        if (is_dir($dir . "/" . $file)) {
                            $files[$file] = my_scandir($dir . "/" . $file);
                        } else {
                            $files[] = $dir . "/" . $file;
                        }
                    }
                }
                closedir($handle);
                return $files;
            }
        }
    }

    protected function _qrcode_sign($_sign)
    {
        $sign = md5(date("Y-m-d H").'bpayer');
        if ($sign != $_sign) return false;
        return true;
    }
}
