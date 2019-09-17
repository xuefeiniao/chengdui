<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/20
 * Time: 13:50
 */

namespace app\mobile\controller;
use think\Request;
use think\App;
use think\Controller;


class Common extends Controller
{
    /**
     *  验证是否登录
     */
    protected $id;
    protected $name;
    function __construct(App $app = null)
    {
        parent::__construct($app);
        $token=input("post.token");
        $id=(int)input("post.id");
        $user_info=db("user")->where("id",$id)->field("username,token")->find();
        if(empty($user_info)){
            echo  json_encode(["status"=>0,"msg"=>"用户不存在"]);
            exit;
        }else{
            if($token!=$user_info["token"]){
                echo json_encode(["status"=>0,"msg"=>"token验证失败"]);
                exit;
            }
        }
        $this->id=$id;
        $this->name=$user_info["username"];
    }

}