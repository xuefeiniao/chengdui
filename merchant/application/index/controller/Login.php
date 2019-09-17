<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/19
 * Time: 8:38
 */

namespace app\index\controller;
use think\Request;
use think\Db;

class Login extends Logincommon
{
    protected $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    /**
     * 注册
     */
    public function sign()
    {
        $data=$this->request->param();
        if($this->request->isAjax()) {
            return $this->signc($data);
        }
        return view();
    }
    /**
     * 登录
     */
    public function index(){
        $data=$this->request->param();
        if($this->request->isAjax()) {
            $status = $this->loginc($data,$this->request->ip());
            if ($status == "success") {
                session("username", $data["username"]);
                $user_info = db("user")->where("username", $data["username"])->field('id,type,is_gkvi,status')->find();
                session("id", $user_info['id']);
                session("utype", $user_info['type']);
                session("is_gkvi", $user_info['is_gkvi']);
                return json(["status" => 1, "msg" => "登录成功"]);
            } else {
                return $status;
            }
        }
        return view();
    }
    /**
     * 忘记密码
     */
    public function forgot()
    {
        $data=$this->request->param();
        if($this->request->isAjax()){
            return $status=$this->resetc($data,$this->request->ip());
        }
        return view();
    }

    # 总后台登录
    public function admin_login(){
        session(null);
        if (!$this->request->isGet())
            return json(['status' => 'error']);
        $uid = (int)input('uid');
        $sign = input('sign');
        $_sign = md5(md5($uid.'vifu123'.date("Y/m/d/h")));
        if ($sign!=$_sign)return json(['status' => 'error']);
        $user_info = Db::name("user")->where(['id'=>$uid])->find();
        if (empty($user_info))return json(['status' => 'error']);

        session("username", $user_info["username"]);
        session("id", $user_info['id']);
        session("utype", $user_info['type']);
        session("is_gkvi", $user_info['is_gkvi']);
        header('Location: '.url('index/index'));
    }

    # 总后台消息列表放行
    public function admin_fhxy()
    {
        session(null);
        if (!$this->request->isGet())
            return json(['status' => 'error']);
        $uid = (int)input("uid");
        $match_id = (int)input("match_id");
        $sign = input('sign');
        $_sign = md5(md5($match_id.$uid.'vifu123'.date("Y/m/d/h")));
        if ($sign!=$_sign)return json(['status' => 'error']);
        $user_info = Db::name("user")->where(['id'=>$uid])->find();
        if (empty($user_info))return json(['status' => 'error']);

        session("username", $user_info["username"]);
        session("id", $user_info['id']);
        session("utype", $user_info['type']);
        session("is_gkvi", $user_info['is_gkvi']);

        $act = controller("exshop");
        $res =  $act->sell_dakuanac($uid,$match_id);
        if ($res){
            exit("<script>window.close();</script>");
        }
    }

}