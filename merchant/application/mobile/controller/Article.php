<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/23
 * Time: 9:09
 */

namespace app\mobile\controller;
use think\Request;
use think\Db;
class Article
{
    protected $id;
    protected $username;
    protected function sign()
    {
        $request=request();
        $token=$request->param("token");
        $id=(int)$request->param("id");
        $user_info=db("user")->where("id",$id)->field("username,token,status")->find();
        if(empty($user_info)){
            return json(["status"=>0,"msg"=>"用户不存在"]);
        }else{
            if($token!=$user_info["token"]){
                return json(["status"=>0,"msg"=>"token验证失败"]);
            }else{
                if($user_info=="hidden")  return json(["status"=>0,"msg"=>"账号被冻结"]);
            }
        }
        $this->id=$id;
        $this->username=$user_info["username"];
        return "success";
    }
    public function test(){
        $res=Db::table("coinpay_corder")
            ->alias("a")
            ->fullJoin ("coinpay_torder b","a.id=b.id")
            ->where("a.user_id",58)
            ->select();
        halt($res);
    }
    /**
     * 通知公告
     */
    public function notice(Request $request)
    {
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with, content-type');
        if($request->isPost()) {
            $status=$this->sign();
            if($status!="success") return $status;
            $offset=$request->param("offset","","intval");
            $num=$request->param("num","","intval");
            if(!empty($offset)) $where[]=["id","<",$offset];
            $where[]=["status","=","normal"];
            $where[]=["type","=","notice"];
            $list = db("article")
                ->where($where)
                ->order("id DESC,sort DESC")
                ->limit($num)
                ->select();
            if(empty($list)) return json(["status"=>0,"msg"=>"无请求数据"]);
            foreach ($list as $key => $val) {
                $list[$key]["content"] = mb_substr(strip_tags($val["content"]), 0, 100, "UTF-8") . "....";
                $list[$key]["createtime"] = date("Y-m-d H:i", $val['createtime']);
                $list[$key]["updatetime"] = date("Y-m-d H:i", $val['updatetime']);
            }
            $offset=$list[count($list)-1]["id"];
            $data=["offset"=>$offset,"data"=>$list];
            return json(["status"=>1,"data"=>$data,"msg"=>"请求成功"]);
        }
    }
    /**
     * 新闻资讯
     */
    public function news(Request $request)
    {
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with, content-type');
        if($request->isPost()) {
            $status=$this->sign();
            if($status!="success") return $status;
            $offset=$request->param("offset","","intval");
            $num=$request->param("num","","intval");
            if(!empty($offset)) $where[]=["id","<",$offset];
            $where[]=["status","=","normal"];
            $where[]=["type","=","news"];
            $list = db("article")
                ->where($where)
                ->order("id DESC,sort DESC")
                ->limit($num)
                ->select();
            if(empty($list)) return json(["status"=>0,"msg"=>"无请求数据"]);
            foreach ($list as $key => $val) {
                $list[$key]["content"] = mb_substr(strip_tags($val["content"]), 0, 100, "UTF-8") . "....";
                $list[$key]["createtime"] = date("Y-m-d H:i", $val['createtime']);
                $list[$key]["updatetime"] = date("Y-m-d H:i", $val['updatetime']);
            }
            $offset=$list[count($list)-1]["id"];
            $data=["offset"=>$offset,"list"=>$list];
            return json(["status"=>1,"data"=>$data,"msg"=>"请求成功"]);
        }
    }
    /**
     * 公告资讯-详情
     */
    public function detail(Request $request)
    {
        if( $request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            $id=$request->param("articleid","","intval");
            $actile=db("article")->where("id",$id)->find();
            if(empty($actile)) return json(["status"=>0,"msg"=>"无请求数据"]);
            $actile["createtime"]=date("Y-m-d H:i", $actile["createtime"]);
            $actile["updatetime"]=date("Y-m-d H:i", $actile["updatetime"]);
            return json(["status"=>1,"data"=>$actile,"msg"=>"请求成功"]);
        }
    }
}