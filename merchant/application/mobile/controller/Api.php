<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/23
 * Time: 17:45
 */

namespace app\mobile\controller;
use app\mobile\model\User;
use think\Request;

class Api extends Common
{
    /**
     * API设置
     */
    public function apiset()
    {

    }
    /**
     * API IP名单
     */
    public function apiblack()
    {
        $list=db("api_list")
            ->where("user_id",$this->id)
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        foreach($data as $key=>$val)
        {
            $data[$key]["username"]=User::where("id",$val["user_id"])->value("username");
            $data[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
        }
        return json([
            "page"=>$page,
            "data"=>$data,
            "count"=>$count
        ]);
    }
    /**
     * 移除删除白名单
     */
    public function delip(){
       $id=intval(input("post.id"));
       $res=db("api_list")->delete($id);
       if($res) return json(["status"=>1,"msg"=>"移除成功"]);
       return json(["status"=>0,"msg"=>"移除失败"]);
    }
    /**
     * 添加IP名单
     */
    public function addip(){
        $ip=input("post.ip");
        if(!is_ip($ip)) return json(["status"=>0,"msg"=>"IP地址不合法"]);
        $ip_config=db("config")
            ->where("name","ipnumber")
            ->value("value");
        $ip_number=db("api_list")
            ->where("user_id",$this->id)
            ->count();
        if($ip_number>$ip_config) return json(["status"=>0,"msg"=>"白名单超过配置数量"]);
        $result=db("api_list")
            ->where("user_id",$this->id)
            ->field("ip")
            ->select();
        $ips=array();
        foreach($result as $vo){
            $ips[]=$vo["ip"];
        }
        if(in_array($ip,$ips)){
            return json(["status"=>0,"msg"=>"IP地址已经存在"]);
        }else{
            $res=db("api_list")->insert(["user_id"=>$this->id,"ip"=>$ip,"createtime"=>time()]);
            if($res) return json(["status"=>1,"msg"=>"添加白名单成功"]);
            return json(["status"=>0,"msg"=>"添加白名单失败"]);
        }
    }
    /**
     * API密钥
     */
    public function apikey()
    {
        $username=User::where('id',$this->id)->value('username');
        $status=is_email($username);
        $status=$status ? "email" : "mobile";
        return json(["usertype"=>$status,"username"=>$username]);
    }
    /**
     * 生成API密钥
     */
    public function api11(Request $request)
    {
        if($request->isPost())
        {
            $type=$request->post("type");
            $verify=$request->post("verify");
            /**
             * 查看kEY和回调密码需要验证
             */
            if($type==2 || $type==3){
                $username=User::where("id",$this->id)->value("username");
                $code=db("code")->where("username",$username)->order("id DESC")->find();
                if (empty($code)) return json(['status' => 0, 'msg' => "请发送验证码"]);
                if (time() > $code["expiration"]) return json(['status' => 0, 'msg' => "请发送验证码"]);
                if ($verify != $code["code"]) return json(['status' => 0, 'msg' => "验证码错误"]);
            }
            $user=User::where("id",$this->id)
                ->field("shopid,apikey,apipassword")
                ->find();
            /**
             * 查看商户ID没有则生成
             */
            if($type==1){
                if($user->shopid){
                    return json(["status"=>1,"msg"=>$user->shopid]);
                }else{
                    $user->shopid=User::random("2019",11,1);    //"2019"+11位的随机数字字符串组成，1代表生成商户ID
                    $user->save();
                    return json(["status"=>1,"msg"=>$user->shopid]);
                }
            }
            /**
             * 查看商户API密钥没有则生成
             */
            if($type==2){
                if($user->apikey){
                    return json(["status"=>1,"msg"=>$user->apikey]);
                }else{
                    $user->apikey=User::random("K",19,2);       //"K"+19位的随机字母和数字字符串组成，2代表生成商户KEY
                    $user->save();
                    return json(["status"=>1,"msg"=>$user->apikey]);
                }
            }
            /**
             * 查看商户API回调密码没有则生成
             */
            if($type==3){
                if( $user->apipassword){
                    return json(["status"=>1,"msg"=>$user->apipassword]);
                }else{
                    $user->apipassword=User::random("",6,3);    //生成6位回调数字密码;
                    $user->save();
                    return json(["status"=>1,"msg"=>$user->apipassword]);
                }
            }
            return json(["status"=>0,"msg"=>"操作失败"]);
        }
    }
    /**
     * API访问记录
     */
    public function apirecord()
    {
        $list=db("api_log")
            ->where("user_id",$this->id)
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        foreach($data as $key=>$val)
        {
            $data[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
            $data[$key]["username"]=User::where("id",$val["user_id"])->value("username");
        }
        return json([
            "page"=>$page,
            "data"=>$data,
            "count"=>$count
        ]);
    }
}