<?php

namespace app\admincoin\controller;

use think\Request;
use think\Db;
use app\admincoin\model\CoinLog;
use app\admincoin\model\UserM;
class Shop extends Base
{
    # 商户收款记录
    public function coinlog()
    {

        $admin_uid_str = $this->get_yonghu_uid();
        if ($admin_uid_str){
            $where[]=['user_id','in',($admin_uid_str)];
        }

        $list   = CoinLog::where('type in(11,12)')->order('id','desc')->alias('a')->paginate(10)->each(function($item,$key)
        {
            $item->username=UserM::where("id",$item->user_id)->value("username");
        });
        $this->assign('list',$list);
        $this->assign('count',$list->total());
        return view('coinlog');
    }

    # 商户设置
    public function shopsys(Request $request)
    {
        $uid = $this->request->param('id/d');
        if($this->request->isPost()) {
            $list = trim(input('post.list'));
            $uid = input('uid/d');
            $str = $this->get_exshop_uid($list);

            if (!empty($str)){
                $map[] =['username','in',[$str]];
                $map[] =['type','=','exshop'];
                $user = Db::name('user')->where($map)->field("id")->select();
                if (!$user) return json(["status" => 0, "msg" => "添加失败"]);
                $uid_arr = array_column($user,'id');
            }else{
                $uid_arr = [];
            }
            $rs = [];

            foreach ($uid_arr as $v)
            {
                $res = Db::name('exshop_shopinfo')->where(['uid'=>$uid,'shop_uid'=>$v])->find();
                if (empty($res)){
                    $rs[] = Db::name("exshop_shopinfo")->insert(['uid'=>$uid,'shop_uid'=>$v]);
                }
            }

            if (check_arr($rs)){
                return json(["status" => 1, "msg" => '修改成功','url'=>"/admincoin/shop/shopsys/id/{$uid}"]);
            }else{
                return json(["status" => 0, "msg" => '修改失败']);
            }
        }
        $shopinfo = Db::name("exshop_shopinfo")->where(['uid'=>$uid])->select();
        $shop_arr = implode(',',array_column($shopinfo,'shop_uid'));

        $username =input('username');
        $nickname =input('nickname');
        if($this->request->isGet()) {
            if (!empty($username)) {
                $where[] = ['username', '=', $username];
            }
            if (!empty($nickname)) {
                $where[] = ['nickname', '=', $nickname];
            }
        }
        $where[]=['id','in',$shop_arr];
        $list = Db::name('user')->where($where)->field("id,username,nickname,exshop_code,updatetime")->paginate(10);
        $data = $list->all();
        foreach ($data as $k=>$v){
            $data[$k]['type_en']=$this->get_user_bank($v['id']);
            $sell = Db::name("excoin_sell")->where("uid={$v['id']} and status<>4")->field("id,uid")->count();
            $data[$k]['sell_status'] = $sell > 0 ? "是[{$sell}]" : '否';
        }
        $page = $list->render();
        $count = $list->total();
        $path = implode(',',array_column($data,'username'));
        $variate=["usernmae"=>$username,"nickname"=>$nickname];
        return view("shopsys",["page"=>$page,"count"=>$count,"data"=>$data,'uid'=>$uid,'variate'=>$variate]);
    }
    # 删除定向承兑
    public function shopsys_delac()
    {
        $shop_uid = input('post.shop_uid/d');
        $uid = input('post.uid/d');
        $res = Db::name('exshop_shopinfo')->where(['uid'=>$uid,'shop_uid'=>$shop_uid])->delete();
        if ($res){
            return json(["status" => 1, "msg" => '删除成功']);
        }else{
            return json(["status" => 0, "msg" => '删除失败']);
        }
    }

    # 获取支付类型展示图片
    protected function get_user_bank($uid)
    {
        $tmp = ['1'=>'<img src="/assets/img/pay/bank.png">','2'=>'<img src="/assets/img/pay/alipay.png">','3'=>'<img src="/assets/img/pay/wechatpay.png">'];
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
    protected function get_exshop_uid($str)
    {
        $arr = explode(',',str_replace('，',',',$str));
        $return_arr = [];
        if (!empty($arr)){
            foreach ($arr as $v){
                if (!empty($v)){
                    $return_arr[] =trim($v);
                }
            }
        }
        return implode(',',array_filter($return_arr));
    }
}
