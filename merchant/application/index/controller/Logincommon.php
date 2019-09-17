<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/19
 * Time: 15:51
 */

namespace app\index\controller;
use think\Db;
use think\Exception;

require_once('../application/common.php');
class Logincommon
{
    /**
     * 注册公共调用
     */
    protected function signc($data){
        $validate=validate("User");
        $user=model("User");
        if (!checkstr($data["mobile"])) return json(["status"=>0,"msg"=>"用户名必须是字母开头，大于6位"]);
        $statusz=db("user")->where("username",$data["mobile"])->field("id")->find();
        if(!empty($statusz)) return json(["status"=>0,"msg"=>"用户已经存在"]);
        $username = $data["mobile"];
        $mobile = $data["username"];
        if($data["type"]=="shop" || $data["type"]=="exshop"){
            //商户注册
            if($data["usertype"]=="mobile"){
                if(!$validate->scene("smobilesign")->check($data))  return json(['status'=>0,"msg"=>$validate->getError()]);
            }else{
                if(!$validate->scene("semailsign")->check($data))  return json(['status'=>0,"msg"=>$validate->getError()]);
            }

            /**
             * 有邀请人检查推荐人
             */
            if(!empty($data["inviter"])) {
                $inviter_id = db("user")->where("inviter='{$data['inviter']}' or username='{$data['inviter']}'")->value("id");
                if (empty($inviter_id)) return json(["status" => 0, "msg" => "代理不存在"]);
            }else{
                $inviter_id=0;
            }
            $data["status"]="normal";
            $data["salt"]=salt(6);
            $data["password"]=md5($data["password"].md5($data["salt"]));
            $data["paypassword"]=md5($data["paypassword"].$data["salt"]);
            $data["token"]=md5($data["password"].$data["username"].$data["salt"]);
            $data["parentid"]=$inviter_id;
            $data["mobile"]=$mobile;
            $data["username"]=$username;
            Db::startTrans();
            try{
                unset($data["inviter"]);
                $info=$user->create($data);
                if(!$info->id) throw new Exception("注册失败");
                $S1=$this->apiconfig($info->id);
                if(!$S1) throw new Exception("创建API参数失败");
                Db::name('coin')->insert(['user_id'=>$info->id,'coin_name' => 'usdt']);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                return json(["status"=>0,"msg"=>$e->getMessage()]);
            }
            return json(['status' => 1, 'msg' => "注册成功"]);
        } else if($data["type"]=="agency"){
            //代理注册
            if($data["usertype"]=="mobile"){
                if(!($validate->scene("amobilesign")->check($data)))  return json(['status'=>0,"msg"=>$validate->getError()]);
            }else{
                if(!($validate->scene("aemailsign")->check($data)))   return json(['status'=>0,"msg"=>$validate->getError()]);
            }
            $data["status"]="normal";
            $data["salt"]=salt(6);
            $data["inviter"]=inviter(8);
            $data["password"]=md5($data["password"].md5($data["salt"]));
            $data["paypassword"]=md5($data["paypassword"].$data["salt"]);
            $data["token"]=md5($data["password"].$data["username"].$data["salt"]);
            $data["mobile"]=$mobile;
            $data["username"]=$username;
            Db::startTrans();
            try{
                $info=$user->create($data);
                if(!$info->id) throw new Exception("注册失败");
                $S1=$this->apiconfig($info->id);
                if(!$S1) throw new Exception("创建API参数失败");
                Db::name('coin')->insert(['user_id'=>$info->id,'coin_name' => 'usdt']);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                return json(['status' => 0, 'msg' =>$e->getMessage()]);
            }
            return  json(['status' => 1, 'msg' => "注册成功"]);
        } else if($data["type"]=="personage"){
            //个人注册
        }
    }
    /**
     * 生成商户个人配置
     */
    public function apiconfig($id)
    {
        $result=Db::name("config")
            ->where("name","aisle")
            ->value("value");
        $timeout=Db::name("config")->where("name","expiration")->value("value") ?? 30;
        $coinname=explode(",",$result);
        foreach($coinname as $val)
        {
            $data[$val."time"]=time()+$timeout*24*3600;
            $data[$val."t"]=Db::name("coin_config")->where("name",$val)->value("limit");
            $data[$val."c"]=Db::name("coin_config")->where("name",$val)->value("climit");
        }
        $data["user_id"]=$id;
        $status=Db::name("user_api")->insert($data);
        if($status){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 登录公共调用
     */
    protected function loginc($data,$ip){
        $validate=validate("User");
        if($validate->scene("login")->check($data)){
            $info=db("user")
                ->where("username",$data["username"])
                ->field("salt,password,id,status")
                ->find();
            if(empty($info)) return json(["status"=>0,"msg"=>"用户不存在"]);
            $code=db("code")->where(["username"=>$data['username']])->order("id DESC")->find();//验证验证码
           # if(empty($code)) return json(['status' => 0, 'msg' => "请发送验证码"]);
           # if(time()>$code["expiration"]) return json(['status' => 0, 'msg' => "验证码错误1"]);
           # if($data['verify']!=$code["code"]) return json(['status' => 0, 'msg' => "验证码错误2"]);
            $password=md5($data["password"].md5($info["salt"]));
            if($password==$info["password"]){
                if($info["status"]=="hidden") return json(["status"=>0,"msg"=>"账号已冻结"]);
                //记录登录信息
                user_log($data["username"],"登录",$ip);
                return "success";
            }else{
                return json(["status"=>0,"msg"=>"密码错误"]);
            }
        }else{
            return json(["status"=>0,"msg"=>$validate->getError()]);
        }
    }
    /**
     * 忘记密码公共调用
     */
    protected function resetc($data,$ip)
    {
        $validate = validate("User");
        if (!$validate->scene("reset")->check($data)) return json(["status" => 0, "msg" => $validate->getError()]);
        //短信邮箱验证码
        $code = db("code")
            ->where("username", $data["username"])
            ->field("code,expiration")
            ->order("id DESC")
            ->find();
        if (empty($code)) return json(['status' => 0, 'msg' => "请发送验证码"]);
        if (time() > $code["expiration"]) return json(['status' => 0, 'msg' => "请发送验证码"]);
        if ($data["verify"] != $code["code"]) return json(['status' => 0, 'msg' => "验证码错误"]);
        $salt=db("user")->where("username",$data["username"])->value("salt");
        $password=md5($data["password"].md5($salt));
        $res=db("user")->where("username",$data["username"])->update(["password"=>$password]);
        if($res){
            //记录修改密码
            user_log($data["username"],"登录",$ip);
            return json(["status"=>1,"msg"=>"修改密码成功"]);
        }else{
           return json(["status"=>0,"msg"=>"修改密码失败"]);
        }
    }
    /**
     * 发送手机验证码
     */
    public function sendsms(){
        $nowtime=time();
        $mobile = $username = input("post.username");
        $type=(int)input("post.type");
        if($type==1){
            $res=db("user")->where("username",$mobile)->field("id,mobile")->find();
            if(empty($res)) return json(["status"=>0,"msg"=>"账号不存在"]);
            $mobile = $res['mobile'];
        }else{
            $res=Db::name("user")->where(["username"=>$mobile])->field("id,mobile")->find();
            if(empty($res)) return json(["status"=>0,"msg"=>"账号不存在"]);
            $mobile = $res['mobile'];
        }
        $uptime=db("code")->where(["username"=>$mobile])->order("id DESC")->value("expiration");
        if($nowtime<$uptime) return json(["status"=>0,"msg"=>"验证码已发送"]);
        $code=mt_rand(111111,999999);
        $status=sms_send($mobile,$code);
        if($status==1){
            $data["username"]=$username;
            $data['code']=$code;
            $data['expiration']=$nowtime+(10*60);
            $res=db("code")->insert($data);
            if($res) return json(["status"=>1,"msg"=>"发送成功"]);
            return json(["status"=>0,"msg"=>"发送失败"]);
        }else{
            return json(["status"=>0,"msg"=>"发送失败"]);
        }
    }
    /**
     * 发送邮箱验证码
     */
    public function sendemail()
    {
        $mail["username"]=input("post.username");
        $type=(int)input("post.type");
        if($type==1){
            $res=db("user")->where("username",$mail["username"])->field("id,mobile")->find();
            if(empty($res)) return json(["status"=>0,"msg"=>"账号不存在"]);
            $mail = $res['mobile'];
        }

        $data["code"]=rand(111111,999999);
        $title=db("config")->where("name","MAIL_FROMNAME")->value("value");
        $content=db("config")->where("name","MAIL_CONTENT")->value("value");
        $content.=$data["code"];
        $status=sendMail($mail,$title,$content);
        if($status){
            $data['expiration']=time()+(10*60);
            $res=db("code")->insert($data);
            if($res) return json(["status"=>1,"msg"=>"验证码发送成功"]);
        }else{
            return json(["status"=>1,"msg"=>"验证码发送失败"]);
        }
    }







}