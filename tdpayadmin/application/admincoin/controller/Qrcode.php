<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Db;

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



}
