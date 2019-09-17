<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/19
 * Time: 8:38
 */

namespace app\mobile\controller;
use think\Request;

class Login extends Logincommon
{
    protected $request;
    public function __construct(Request $request)
    {
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with, content-type');
        $this->request = $request;
    }
    /**
     * 注册
     */
    public function sign()
    {
        $data=$this->request->param();
        if($this->request->isPost()) {
            return $this->signc($data);
        }
    }
    /**
     * 登录
     */
    public function index(){
        $data=$this->request->param();
        if($this->request->isPost()) {
            return $this->loginc($data,$this->request->ip());
        }
    }
    /**
     * 忘记密码
     */
    public function forgot()
    {
        $data=$this->request->param();
        if($this->request->isPost()){
            return $status=$this->resetc($data,$this->request->ip());
        }
    }
    /**
     * 客服
     */
    public function serve(){
        $wechat=db("config")->where("name","wechat")->value("value");
        $qq=db("config")->where("name","qq")->value("value");
        $data["wechat"]=$wechat;
        $data["qq"]=$qq;
        return json(["status"=>1,"data"=>$data,"msg"=>"请求成功"]);
    }
}