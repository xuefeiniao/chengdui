<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Request;
use think\Db;
class ApiManage extends Controller
{
    /**
     * API记录
     */
    public function apirecord(Request $request)
    {
        $where=[];
        $name=$request->param("name");
        $ip=$request->param("ip");
        $apitype=$request->param("apitype","","intval");
        $username=$request->param("username");
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        $name && $where[]=["name","=",$name];
        $ip && $where[]=["ip","=",$ip];
        if($username){
            $id=Db::name("user")->where("username",$username)->value("id");
            $id && $where[]=["user_id","=",$id];
        }
        $apitype && $apitype!=5 && $where[]=["apitype","=",$apitype];
        $beigin && $where[]=["createtime",">=",strtotime($beigin)];
        $end && $where[]=["createtime","<=",strtotime($end)];
        $list=Db::name("api_log")
            ->where($where)
            ->order("id DESC")
            ->paginate(10)
            ->each(function($item,$key){
                $item["username"]=Db::name("user")->where("id",$item["user_id"])->value("username");
                return $item;
            });
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        $variate=["name"=>$name,"ip"=>$ip,"apitype"=>$apitype,"beigin"=>$beigin,"end"=>$end,"username"=>$username];
        return $this->fetch("apirecord",[
            "page"=>$page,
            "data"=>$data,
            "count"=>$count,
            "variate"=>$variate
        ]);
    }
    /**
     * API IP名单
     */
    public function apiblack(Request $request)
    {
        $where=[];
        $username=$request->param("username");
        if($username){
            $id=Db::name("user")->where("username",$username)->value("id");
            $id && $where[]=["user_id","=",$id];
        }
        $list=Db::name("api_list")
            ->where($where)
            ->order("id desc")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        foreach($data as $key=>$val)
        {
            $data[$key]["username"]=Db::name("user")->where("id",$val["user_id"])->value("username");
            $data[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
        }
        $variate=["username"=>$username];
        return $this->fetch("apiblack",[
            "page"      =>$page,
            "data"      =>$data,
            "count"     =>$count,
            "variate"   =>$variate
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
}
