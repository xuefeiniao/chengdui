<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/23
 * Time: 9:09
 */

namespace app\index\controller;

class Article extends Indexcommon
{
    /**
     * 通知公告
     */
    public function notice()
    {
        $list=db("article")
            ->where("type","notice")    //notice公告
            ->where("status",'normal')
            ->order("sort DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        foreach($data as $key=>$val){
            //$data[$key]["content"]=mb_substr(strip_tags($val["content"]),0,100,"UTF-8")."....";
            $data[$key]["createtime"]=date("Y-m-d H:i",$val['createtime']);
            $data[$key]["updatetime"]=date("Y-m-d H:i",$val['updatetime']);
        }
        $this->assign(["page"=>$page,"data"=>$data,'count'=>$count]);
        return $this->fetch();
    }
    /**
     * 新闻资讯
     */
    public function news()
    {
        $list=db("article")
            ->where("type","news")    //news新闻资讯
            ->where("status",'normal')
            ->order("sort DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        foreach($data as $key=>$val){
            $data[$key]["content"]=mb_substr(strip_tags($val["content"]),0,100,"UTF-8")."....";
            $data[$key]["createtime"]=date("Y-m-d H:i",$val['createtime']);
            $data[$key]["updatetime"]=date("Y-m-d H:i",$val['updatetime']);
        }
        $this->assign(["page"=>$page,"data"=>$data,"count"=>$count]);
        return $this->fetch();
    }
    public function gkvi()
    {
        $list=db("article")
            ->where("type","fuwu")
            ->where("status",'normal')
            ->order("sort DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        foreach($data as $key=>$val){
            $data[$key]["content"]=mb_substr(strip_tags($val["content"]),0,100,"UTF-8")."....";
            $data[$key]["createtime"]=date("Y-m-d H:i",$val['createtime']);
            $data[$key]["updatetime"]=date("Y-m-d H:i",$val['updatetime']);
        }
        $this->assign(["page"=>$page,"data"=>$data,"count"=>$count]);
        return $this->fetch();
    }
    /**
     * 公告-资讯详情
     */
    public function detail()
    {
        $id=intval(input("param.id"));
        $actile=db("article")->where("id",$id)->find();
        $this->assign("info",$actile);
        return $this->fetch();
    }

}