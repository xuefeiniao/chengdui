<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Request;
use think\Db;
use app\admincoin\model\CoinBtc;
use app\admincoin\model\CoinEth;
use app\admincoin\model\AddressLog;

class AddressManage extends Controller
{
    /**
     * 地址列表
     *
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $address=$request->param("address");
        $use_status=$request->param("use_status","","strtolower");
        $type=$request->param("type","","strtolower");
        $coinname=$request->param("coinname","","strtolower");
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        if(!empty($address)){
            $where[]=["address","=",$address];
        }else{
            $use_status && $use_status!="all" && $where[]=["use_status","=",$use_status];
            $type && $type!="all" && $where[]=["type","=",$type];
            $coinname && $coinname!="all" && $where[]=["coin_name","=",$coinname];
            $beigin && $where[]=["lasttime",">=",strtotime($beigin)];
            $end && $where[]=["lasttime","<=",strtotime($end)];
        }
        $where[]=["status","=","normal"];
        $eth=Db::name("coin_eth")->where($where)->field("*")->buildSql();
        $btc_eth   = CoinBtc::field('*')->where($where)->union($eth)->buildSql();
        $list=Db::table($btc_eth.' a')->order("amount desc")->paginate(10);
        $data=$list->all();
        $page=$list->render();
        $count=$list->total();
        $variate=["address"=>$address,"use_status"=>$use_status,"type"=>$type,"beigin"=>$beigin,"end"=>$end,"coinname"=>$coinname];
        return view('address',["page"=>$page,"data"=>$data,"count"=>$count,"variate"=>$variate]);
    }

    /**
     * 地址发行.
     *
     * @return \think\Response
     */
    public function set()
    {
        return view('address_set');
    }

    /**
     * 地址分配
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function record(Request $request)
    {
        $data["coin_addr"]=$request->param("coin_addr");
        $data["username"]=$request->param("username");
        $data["order_number"]=$request->param("order_number");
        $data["coin_name"]=$request->param("coin_name","","strtolower");
        if(!empty($data["order_number"])){
            $where[]=["order_number","=",$data["order_number"]];
        }else{
            if(!empty($data["username"])){
                $user_id=Db::name("user")->where("username",$data["username"])->value("id");
                $user_id && $where[]=["user_id","=",$user_id];
            }
            $data["coin_name"] && $data["coin_name"]!="all" && $where[]=["coin_name","=",$data["coin_name"]];
            $data["coin_addr"] && $where[]=["coin_addr","=",$data["coin_addr"]];
        }
        $where[]=["id",">",0];
        $list   = AddressLog::order('id','desc')->where($where)->paginate(5)->each(function($item,$key){
            $item->username=Db::name("user")->where("id",$item->user_id)->value("username");
        });
        $datalist=$list->all();
        $page=$list->render();
        $count=$list->total();
        $variate=[
            "coin_addr"     =>$data["coin_addr"],
            "username"      =>$data["username"],
            "coin_name"     =>$data["coin_name"],
            "order_number"  =>$data["order_number"]
        ];
        return view('address_record',["data"=>$datalist,"page"=>$page,"count"=>$count,"variate"=>$variate]);
    }
    /**
     * 地址余额
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function money()
    {
        return view('address_money');
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
