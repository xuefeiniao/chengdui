<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/4/11
 * Time: 18:51
 */

namespace app\index\controller;
use think\Db;
use think\Request;
use think\Exception;

class Otcmanage
{
    /**
     * 系统OTC订单列表
     */
    public function otcorder(Request $request){
        if($request->has("username","post")){
            $user_id=Db::name("user")
                ->where("username",$request->param("username"))
                ->value("id");
            $user_id && $where[]=["user_id","=",$user_id];
        }
        if($request->has("type","post") && $request->param("type","","intval")!="all")$where[]=["type","=",$request->param("type","","intval")];
        if($request->has("nowstatus","post") && $request->param("nowstatus","","intval")!="all")$where[]=["nowstatus","=",$request->param("nowstatus","","intval")];
        $where[]=["status","=","normal"];
        $list=Db::name("otc")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        foreach($data as $key=>$val){
            $data[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
            if($val["downtime"]>0)$data[$key]["downtime"]=date("Y-m-d H:i",$val["downtime"]);
            $data[$key]["username"]=Db::name("user")->where("id",$val["user_id"])->value("username");
        }
        $variate=[
            "username"=>$request->param("username","","trim"),
            "type"=>$request->param("type","","intval"),
            "nowstatus"=>$request->param("nowstatus","","intval")
        ];
        return view("otcorder",["page"=>$page,"data"=>$data,"count"=>$count,"variate"=>$variate]);
    }
    /**
     * 用户OTC订单列表
     */
    public function userotcorder(Request $request){

        $username=$request->param("username","","trim");
        $otc_number=$request->param("otc_number","","trim");
        $type=$request->param("type","","intval");
        $nowstatus=$request->param("nowstatus","","intval");
        if(empty($otc_number)){
            $type && $where[]=["type","=",$type];
            $nowstatus && $where[]=["nowstatus","=",$nowstatus];
            if($username){
                $userid=Db::name("user")->where("username",$username)->value("id");
                $userid && $where[]=["user_id","=",$userid];
            }
        }else{
            $where[]=["otc_number","=",$otc_number];
        }
        $where[]=["status","=","normal"];
        $where[]=["nowstatus","<>",5];
        $list=Db::name("otc_user")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        /**
         * 获取订单打款时间
         */
        $outtime=Db::name("config")->where("name","outtime")->value("value");
        $couttime=$outtime*60;
        $nowtime=time();
        foreach($data as $key=>$val){
            if($val["nowstatus"]==1) {
                    $counttime = $val["createtime"] + $couttime;
                    if ($counttime > $nowtime) {
                        $data[$key]["outtime"] = $counttime - $nowtime;
                    } else {
                        $data[$key]["outtime"] = 0;
                    }
            }
            $data[$key]["createtime"] = date("Y-m-d H:i:s", $val["createtime"]);
            if($val["paytime"]!=0) $data[$key]["paytime"]=date("Y-m-d H:i:s",$val["paytime"]);
            if($val["downtime"]!=0)$data[$key]["downtime"]=date("Y-m-d H:i:s",$val["downtime"]);
            $data[$key]["username"]=Db::name("user")->where("id",$val["user_id"])->value("username");
        }
        $variate=["username"=>$username,"otc_number"=>$otc_number,"type"=>$type,"nowstatus"=>$nowstatus];
        return view("userotcorder",["page"=>$page,"data"=>$data,"count"=>$count,"variate"=>$variate]);
    }
    /**
     * 投诉订单列表
     */
    public function tousulist(){
        $where[]=["nowstatus","=",5];
        $list=Db::name("otc_user")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        foreach($list as $key=>$val){
            $list[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
            $list[$key]["paytime"]=date("Y-m-d H:i",$val["paytime"]);
        }
        return view("tousulist",["page"=>$page,"data"=>$data,"count"=>$count]);
    }
    /**
     * 后台订单处理
     * type=1用户买官方卖
     * 确认收款给用户币
     */
    public function sellconfirm(){
        $request=request();
        $orderid=$request->param("id","","intval");
        Db::startTrans();
        try{
            /**
             * 如果订单是已打款状态，更新订单为完成状态，同时更新系统挂单的成交量并判断是否完成，并把币给买用户
             */
            $S1=Db::name("otc_user")
                ->where("id",$orderid)
                ->where("nowstatus",2)
                ->field("id,otc_id,number,user_id")
                ->find();
            if(empty($S1)) throw new Exception("订单异常");
            $S2=Db::name("otc_user")
                ->where("id",$orderid)
                ->where("nowstatus",2)
                ->update([
                    "nowstatus"=>3,
                    "downtime"=>time(),
                ]);
            if(!$S2) throw new Exception("确认收款失败");
            $S3=Db::name("otc")->where("id",$S1["otc_id"])->setInc("traded",$S1["number"]);
            if(!$S3) throw new Exception("更新挂单失败");
            $maininfo=Db::name("otc")->where("id",$S1["otc_id"])->find();
            if($maininfo["nowstatus"]==2) throw new Exception("订单已交易完成");
            if($maininfo["number"]==$maininfo["pipei"]
                && $maininfo["number"]==$maininfo["traded"]){
                $S4=Db::name("otc")
                    ->where("id",$S1["otc_id"])
                    ->update([
                        "nowstatus"=>2,
                        "downtime"=>time(),
                    ]);
                if(!$S4) throw new Exception("更新挂单完成失败");
            }
            $coin=Db::name("coin")
                ->where("user_id",$S1["user_id"])
                ->where("coin_name","usdt")
                ->find();
            if(empty($coin)){
                $S5=Db::name("coin")->insert(["user_id"=>$S1["user_id"],"coin_name"=>"usdt"]);
                if(!$S5) throw new Exception("添加钱包失败");
            }
            $S6=Db::name("coin")
                ->where("user_id",$S1["user_id"])
                ->where("coin_name","usdt")
                ->setInc("coin_balance",$S1["number"]);
            if(!$S6) throw new Exception("更新到账失败");
            Db::commit();
        } catch (\Exception $e){
            Db::rollback();
            return json(["status"=>0,"msg"=>$e->getMessage()]);
        }
        return json(["status"=>1,"msg"=>"收款成功"]);
    }
    //查询支付方式
    public function selectalipay(Request $request)
    {
        $id=$request->param("id","","intval");
        $type=$request->param("type","","intval");
        $user_id=Db::name("otc_user")->where("id",$id)->where("nowstatus",1)->value("user_id");
        if(empty($user_id)) return json(["status"=>0,"msg"=>"不能获取支付方式"]);
        if($type==1){
            $data=Db::name("user")
                ->where("id",$user_id)
                ->field("alipay,alipayname,alipayimage")
                ->find();
            if(!$data["alipay"] && !$data["alipayimage"]){
                return json(["status"=>0,"msg"=>"没有设置支付宝支付"]);
            }
            return json(["status"=>1,"msg"=>"获取成功","data"=>$data]);
        }else if($type==2){
            $data=Db::name("user")
                ->where("id",$user_id)
                ->field("wechat,wechatname,wechatimage")
                ->find();
            if(!$data["wechatname"] && !$data["wechatimage"]){
                return json(["status"=>0,"msg"=>"没有设置微信支付"]);
            }
            return json(["status"=>1,"msg"=>"获取成功","data"=>$data]);
        }else if($type==3){
            return json(["status"=>0,"msg"=>"当前不支持银卡支付"]);
        }
    }
    /**
     * 后台订单处理
     * type=2用户卖官方买
     * 打款给用户更新打款方式
     */
    public function alipaytype(Request $request){
        $type=$request->param("type","","intval");
        $orderid=$request->param("id","","intval");
        if($type<=0) return json(["status"=>0,"msg"=>"请选择支付方式"]);
           Db::startTrans();
           try{
               $S2=Db::name("otc_user")
                   ->where("id",$orderid)
                   ->where("nowstatus","<>",1)
                   ->find();
               if(!empty($S2))throw new Exception("订单状态不符合");
               $S1=Db::name("otc_user")
                    ->where("id",$orderid)
                    ->where("nowstatus",1)
                    ->update(["nowstatus"=>2,"paytype"=>$type,"paytime"=>time()]);
                if(!$S1) throw new Exception("更新支付方式失败");
               Db::commit();
           }catch(\Exception $e){
               Db::rollback();
               return json(["status"=>0,"msg"=>$e->getMessage()]);
           }
           return json(["status"=>1,"msg"=>"支付成功"]);
    }
    /**
     * 后台买卖订单页面
     */
    public function order(Request $request)
    {
        if($request->isPost()) {
            $data = $request->param();
            $info = Db::name("user")->where("username", $data["username"])->field("id")->find();
            if (empty($info)) return json(["status" => 0, "msg" => "挂单用户不存在"]);
            $data["number"] = abs((int)($data["number"]));
            $data["min"] = abs((int)($data["min"]));
            if($data["number"]<=50) return json(["status"=>0,"msg"=>"挂单数量不低于50USDT"]);
            if ($data["min"] > $data["number"]) return json(["status" => 0, "msg" => "最小成交量不能大于挂单数量"]);
            if ($data["price"] <= 6) return json(["status" => 0, "msg" => "价格不能低于6CNY"]);
            $data["createtime"] = time();
            $data["nowstatus"] = 1;
            $data["sum"] = round($data["number"] * $data["price"], 2);
            $data["user_id"] = $info["id"];
            $S1 = Db::name("otc")
                ->field("user_id,number,min,sum,price,createtime,type,nowstatus")
                ->insert($data);
            if($S1){
                return json(["status"=>1,"msg"=>"挂单成功"]);
            }else{
                return json(["status"=>0,"msg"=>"挂单失败"]);
            }
        }
        return view("order");
    }
    /**
     * 投诉订单
     */
    public function tousu(Request $request)
    {
        $otc_number=$request->param("otc_number");
        $username=$request->param("username");
        $type=$request->param("type","","intval");
        if(!empty($otc_number)){
            $where[]=["otc_number","=",$otc_number];
        }else{
            if($username){
                $uid=Db::name("User")->where("username",$username)->value("id");
            }else{
                $uid=0;
            }
            $uid && $where[]=["user_id","=",$uid];
            $type && $where[]=["type","=",$type];
        }
        $where[]=["status","=","normal"];
        $where[]=["nowstatus","=",5];
        $list=Db::name("otc_user")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $data=$list->all();
        $page=$list->render();
        $count=$list->count();
        foreach ($data as $key=>$val){
            $val["createtime"] && $data[$key]["createtime"]=date("Y-m-d H:i:s",$val["createtime"]);
            $val["paytime"] && $data[$key]["paytime"]=date("Y-m-d H:i:s",$val["paytime"]);
            $val["downtime"] && $data[$key]["downtime"]=date("Y-m-d H:i:s",$val["downtime"]);
            $data[$key]["username"]=Db::name("user")->where("id",$val["user_id"])->value("username");
        }
        $variate=["otc_number"=>$otc_number,"username"=>$username,"type"=>$type];
        return view("tousu",["page"=>$page,"data"=>$data,"count"=>$count,"variate"=>$variate]);
    }
    /**
     * 投诉订单处理---重置订单
     */
    public function  reset(Request $request)
    {
        $id=$request->param("id","","intval");
        Db::startTrans();
        try{
            $S1=Db::name("otc_user")
                ->where("status","normal")
                ->where("id",$id)
                ->where("nowstatus",5)
                ->where("paytime",">",0)
                ->update(["paytime"=>0,"nowstatus"=>1,"paytype"=>0]);
            if(!$S1) throw new Exception("不能重置订单条件不符合");
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return json(["status"=>0,"msg"=>$e->getMessage()]);
        }
        return json(["status"=>1,"msg"=>"重置成功"]);
    }
    /**
     * 投诉订单处理---完成订单
     */
    public function down(Request $request)
    {
        $id=$request->param("id","","intval");
        Db::startTrans();
        try{
            $S1=Db::name("otc_user")
                ->where("id",$id)
                ->where("nowstatus",5)
                ->where("paytime",">",0)
                ->field("type,user_id,number,otc_id")
                ->find();
            if(empty($S1)) throw new Exception ("条件不符合不能完成订单");

            $S2=Db::name("otc_user")
                ->where("id",$id)
                ->where("nowstatus",5)
                ->update([
                    "nowstatus"=>3,
                    "downtime"=>time(),
                ]);
            if(!$S2) throw new Exception("完成订单失败");
            /**
             * 用户买入USDT
             */
            if($S1["type"]==1){
                $S3=Db::name("coin")
                    ->where("user_id",$S1["user_id"])
                    ->where("coin_name","usdt")
                    ->find();
                if(empty($S3)){
                    $S4=Db::name("coin")->insert(["user_id"=>$S1["user_id"],"coin_name"=>"usdt"]);
                    if(!$S4) throw new Exception ("添加对应币种钱包失败");
                }
                $S5=Db::name("coin")
                    ->where("user_id",$S1["user_id"])
                    ->where("coin_name","usdt")
                    ->setInc("coin_balance",$S1["number"]);

                if(!$S5)throw new Exception ("更新钱包数量失败");
            }
            /**
             * 用户卖出USDT
             */
            if($S1["type"]==2){
                $S3=Db::name("coin")
                    ->where("user_id",$S1["user_id"])
                    ->where("coin_name","usdt")
                    ->find();
                if(empty($S3))throw new Exception ("钱包信息不存在");
                $S5=Db::name("coin")
                    ->where("user_id",$S1["user_id"])
                    ->where("coin_name","usdt")
                    ->where("coin_balanced",">=",$S1["number"])
                    ->setDec("coin_balanced",$S1["number"]);
                if(!$S5)throw new Exception ("更新钱包数量失败");
            }
            $up=Db::name("otc")->where("id",$S1["otc_id"])->setInc("traded",$S1["number"]);
            if(!$up) throw new Exception("更新挂单失败");
            $maininfo=Db::name("otc")->where("id",$S1["otc_id"])->find();
            if($maininfo["number"]==$maininfo["pipei"]
                && $maininfo["number"]==$maininfo["traded"]){
                $S6=Db::name("otc")
                    ->where("id",$S1["otc_id"])
                    ->update([
                        "nowstatus"=>2,
                        "downtime"=>time(),
                    ]);
                if(!$S6) throw new Exception("更新挂单完成失败");
            }
            Db::commit();
        }catch(\Exception $e){
            Db::rollback();
            return json(["status"=>0,"msg"=>$e->getMEssage()]);
        }
        return json(["status"=>1,"msg"=>"完成订单成功"]);
    }
    /**
     * 投诉信息处理
     * @param Request $request
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function tousuinfo(Request $request){
        $id=$request->param("id","","intval");
        $where[]=["id","=",$id];
        $data=Db::name("otc_user")
            ->where($where)
            ->field("user_id,type")
            ->find();
        if($data["type"]==2){
            $data["alipay"]=Db::name("user")->where("id",$data["user_id"])->field("alipay,alipayname,alipayimage")->find();
            $data["wechat"]=Db::name("user")->where("id",$data["user_id"])->field("wechat,wechatname,wechatimage")->find();
        }
        $data["createtime"] = date("Y-m-d H:i:s", $data["createtime"]);
        if($data["paytime"]!=0) $data["paytime"]=date("Y-m-d H:i:s",$data["paytime"]);
        if($data["downtime"]!=0)$data["downtime"]=date("Y-m-d H:i:s",$data["downtime"]);
        $data["username"]=Db::name("user")->where("id",$data["user_id"])->value("username");
    }

}