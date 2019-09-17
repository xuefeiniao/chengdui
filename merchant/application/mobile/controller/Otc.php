<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/4/8
 * Time: 10:07
 */

namespace app\mobile\controller;
use think\Db;
use think\Exception;
use think\Request;
use app\mobile\model\User;

class Otc
{
    protected $id;
    protected $username;
    protected function sign()
    {
        $request=request();
        $token=$request->param("token");
        $id=(int)$request->param("id");
        $user_info=Db::name("user")->where("id",$id)->field("username,token,status")->find();
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
    /**
     *  生成买卖订单
     */
    protected function order_number($title){
        //生成买卖订单号
            while (true) {
                $str = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                $str=$title.$str;
                $status=Db::name("otc_user")->where("otc_number",$str)->field("id")->find();
                if(empty($status)) break;
                $str="";
            }
            return $str;
    }
    /**
     *  买入卖出订单列表
     */
    public function buy(Request $request){
        if($request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
        $type=$request->param("type","","strtolower");
        if($type=="buy"){
            $type=1;
        }else if($type=="sell"){
            $type=2;
        }else{
            return json(["status"=>0,"msg"=>"无效的请求类型"]);
        }
        $offset=$request->param("offset","","intval");
        $num=$request->param("num","","intval") ?? 10;
        if(!empty($offset)) $where[]=["id","<",$offset];
        $where[]=["status","=","normal"];
        $where[]=["nowstatus","=",1];       //1:挂单 2：完成
        $where[]=["type","=",$type];
        $list=Db::name("otc")
            ->where($where)
            ->order("id DESC")
            ->limit($num)
            ->select();
        if(empty($list)) return json(["status"=>0,"msg"=>"无请求数据"]);
        foreach ($list as $key=>$val){
            $list[$key]["username"]=User::where("id",$val["user_id"])->value("username");
            $list[$key]["createtime"]=date("Y-m-d H:i:s",$val["createtime"]);
        }
        $offset=$list[count($list)-1]["id"];
        $data=["offset"=>$offset,"data"=>$list];
        return json(["status"=>1,"data"=>$data,"msg"=>"请求成功"]);
        }
    }
    /**
     *  买入USDT 卖出USDT
     */
    public function buyusdt(Request $request){
        if($request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            /**
             * OTC开放时间控制
             */
                $now=date("H",time());
                $control=Db::name("config")->where("name","otctime")->value("value");
                if($control!="0/0"){
                    $otctime=explode("/",$control);
                    if($now<$otctime[0] || $now>$otctime[1])  return json(["status"=>0,"msg"=>"OTC未开放"]);
                }
                $orderid=$request->param("orderid","","intval");
                $type=$request->param("type","","strtolower");
                if($type=="buy") {        //买入USDT
                    Db::startTrans();
                    try{
                    $opting=Db::name("otc_user")
                        ->where("user_id",$this->id)
                        ->where("type",1)
                        ->where("nowstatus",["=",1],["=",2],"or")               //正在匹配和打款的订单
                        ->where("status","normal")
                        ->find();
                    if(!empty($opting)) throw new Exception("有买入订单未处理");
                    $res=Db::name("otc")
                        ->where("id",$orderid)
                        ->where("type",2)
                        ->where("nowstatus",1)
                        ->where("status","normal")
                        ->find();
                    if(empty($res)) throw new Exception("操作订单失败");
                    /**
                     * 不能买卖自己的订单
                     */
                    if($res["user_id"]==$this->id) throw new Exception("不能买入自己的挂单");
                    $shengyu=$res["number"]-$res["pipei"];
                    if($shengyu <= 0) throw new Exception("买入订单数量不足");
                    $number = $request->param("number", "", "abs");
                    if($shengyu>$res["min"]){
                        if($number<$res["min"]) throw new Exception("买入数量最少 ".$res["min"]);
                    }else{
                        if($number!=$shengyu) throw new Exception("买入数量剩余 ".$shengyu);
                    }
                    $data["otc_number"]=$this->order_number("B");
                    $data["user_id"]=$this->id;
                    $data["otc_id"]=$res["id"];
                    $data["type"]=1;
                    $data["number"]=$number;
                    $data["createtime"]=time();
                    $data["price"]=$res["price"];
                    $S1=Db::name("otc_user")->insert($data);
                    if(!$S1) throw new Exception("创建订单失败");
                    /**
                     * 匹配成功后更新挂单
                     */
                    $pipeisum=Db::name("otc")->where("id",$res["id"])->value("pipei")+$number;
                    $where[]=["id","=",$res["id"]];
                    $where[]=["nowstatus","=","1"];
                    $where[]=["number",">=",$pipeisum];
                    $S2=Db::name("otc")->where($where)->setInc("pipei",$number);
                    if(!$S2) throw new Exception("更新订单失败");
                        Db::commit();
                    }catch (\Exception $e){
                        Db::rollback();
                        return json(["status"=>0,"msg"=>$e->getMessage()]);
                    }
                    return json(["status"=>1,"msg"=>"买入USDT成功"]);
                }else if($type=="sell"){
                    /**
                     * 卖出USDT需要设置支付方式
                     */
                    $pay=Db::name("user")->where("id",$this->id)->filed()->find();
                    if($pay["wechat"] && $pay["alipay"]){
                        return json(["status"=>0,"msg"=>"请最少设置一种收款方式"]);
                    }
                    Db::startTrans();
                    try{
                        $opting=Db::name("otc_user")
                            ->where("user_id",$this->id)
                            ->where("type",2)
                            ->where("nowstatus",["=",1],["=",2],"or")   //有正在匹配和打款的订单不能再卖出
                            ->where("status","normal")
                            ->find();
                        if(!empty($opting)) throw new Exception("有卖出订单未处理");
                        $res=Db::name("otc")
                            ->where("id",$orderid)
                            ->where("type",1)
                            ->where("nowstatus",1)
                            ->where("status","normal")
                            ->find();
                        if(empty($res)) throw new Exception("操作订单失败");
                        /**
                         * 不能买卖自己的订单
                         */
                        if($res["user_id"]==$this->id) throw new Exception("不能卖出自己的挂单");
                        $shengyu=$res["number"]-$res["pipei"];
                        if($shengyu <= 0) throw new Exception("卖出订单数量不足");
                        $number = $request->param("number", "", "abs");
                        if($shengyu>$res["min"]){
                            if($number<$res["min"]) throw new Exception("卖出数量最少 ".round($res["min"],2));
                        }else{
                            if($number!=$shengyu) throw new Exception("卖出数量剩余 ".round($shengyu,2));
                        }
                        $coin_balance=Db::name("coin")
                            ->where("user_id",$this->id)
                            ->where("coin_name","usdt")
                            ->field("coin_balance")
                            ->find();
                        if(empty($coin_balance)){
                            throw new Exception("USDT余额不足");
                        }else{
                            if($coin_balance["coin_balance"]<$number) throw new Exception("USDT余额不足");
                        }
                        $S2=Db::name("coin")
                            ->where("user_id",$this->id)
                            ->where("coin_name","usdt")
                            ->setDec("coin_balance",$number);
                        if(!$S2) throw new Exception("冻结USDT失败");
                        $S3=Db::name("coin")
                            ->where("user_id",$this->id)
                            ->where("coin_name","usdt")
                            ->setInc("coin_balanced",$number);
                        if(!$S3) throw new Exception("冻结USDT失败");

                        $data["otc_number"]=$this->order_number("S");
                        $data["user_id"]=$this->id;
                        $data["otc_id"]=$res["id"];
                        $data["type"]=2;
                        $data["number"]=$number;
                        $data["createtime"]=time();
                        $data["price"]=$res["price"];
                        $S1=Db::name("otc_user")->insert($data);
                        if(!$S1) throw new Exception("创建订单失败");
                        /**
                         * 匹配成功后更新挂单
                         */
                        $pipeisum=Db::name("otc")->where("id",$res["id"])->value("pipei")+$number;
                        $where[]=["id","=",$res["id"]];
                        $where[]=["nowstatus","=","1"];
                        $where[]=["number",">=",$pipeisum];
                        $S2=Db::name("otc")->where($where)->setInc("pipei",$number);
                        if(!$S2) throw new Exception("更新订单失败");
                        Db::commit();
                    }catch (\Exception $e){
                        Db::rollback();
                        return json(["status"=>0,"msg"=>$e->getMessage()]);
                    }
                    return json(["status"=>1,"msg"=>"卖出USDT成功"]);
                }else {
                    return json(["status"=>0,"msg"=>"订单类型错误"]);
                }
        }
    }
    /**
     *  我的OTC订单列表
     */
    public function myotc(Request $request){
        if($request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            $offset=$request->param("offset","","intval");
            $num=$request->param("num","","intval") ?? 5;
            if(!empty($offset)) $where[]=["id","<",$offset];
            $where[]=["user_id","=",$this->id];
            $list=Db::name("otc_user")
                ->where($where)
                ->limit($num)
                ->order("id DESC")
                ->field("status,otc_id,",true)
                ->select();
            if(empty($list)) return json(["status"=>0,"msg"=>"无有效数据"]);
            /**
             * 获取打款时间
             */
            $outtime=Db::name("config")->where("name","outtime")->value("value");
            $couttime=$outtime*60;
            $nowtime=time();
            foreach($list as $key=>$val){
                switch($val["nowstatus"]){
                    case 1:
                        $counttime=$val["createtime"]+$couttime;
                        $list[$key]["createtime"]=date("Y-m-d H:i:s",$val["createtime"]);
                        if($counttime>$nowtime){
                            $list[$key]["outtime"]=$counttime-$nowtime;
                        }else{
                            $list[$key]["outtime"]=0;
                        }
                        break;
                    case 2:
                        $list[$key]["paytime"]=date("Y-m-d H:i:s",$val["paytime"]);
                        break;
                    case 3:
                        $list[$key]["downtime"]=date("Y-m-d H:i:s",$val["downtime"]);
                        break;
                    case 4:
                        $list[$key]["downtime"]=date("Y-m-d H:i:s",$val["downtime"]);
                        break;
                    default:
                        return json(["status"=>0,"msg"=>"类型错误"]);
                        break;
                }
            }
            $offset=$list[count($list)-1]["id"];
            $data=["offset"=>$offset,"data"=>$list];
            return json(["status"=>1,"data"=>$data,"msg"=>"请求数据成功"]);
        }
    }
    /**
     *  我的OTC订单详情
     */
    public function myotcdetails(Request $request){
        if($request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            $orderid=$request->param("orderid","","intval");
            $where[]=["user_id","=",$this->id];
            $where[]=["id","=",$orderid];
            $list=Db::name("otc_user")
                ->where($where)
                ->find();
            if(empty($list)) return json(["status"=>0,"msg"=>"无有效数据"]);
            /**
             * 获取打款时间
             */
            $outtime=Db::name("config")->where("name","outtime")->value("value");
            $couttime=$outtime*60;
            $nowtime=time();
            switch($list["nowstatus"]){
                case 1:
                    $counttime=$list["createtime"]+$couttime;
                    $list["createtime"]=date("Y-m-d H:i:s",$list["createtime"]);
                    if($counttime>$nowtime){
                        $list["outtime"]=$counttime-$nowtime;
                    }else{
                        $list["outtime"]=0;
                    }
                    break;
                case 2:
                    $list["paytime"]=date("Y-m-d H:i:s",$list["paytime"]);
                    break;
                case 3:
                    $list["downtime"]=date("Y-m-d H:i:s",$list["downtime"]);
                    break;
                case 4:
                    $list["downtime"]=date("Y-m-d H:i:s",$list["downtime"]);
                    break;
                default:
                    return json(["status"=>0,"msg"=>"类型错误"]);
                    break;
            }
            $user_id=Db::name("otc")->where("id",$list["otc_id"])->value("user_id");
            $info=Db::name("user")->where("id",$user_id)->field("username,nickname,complain")->find();
            $list["total"]=round($list["number"]*$list["price"],2);
            /**
             * SUM:总成交量
             * COUNT:成交订单数
             */
            $info["sum"]=Db::name("otc")->where("user_id",$user_id)->where("nowstatus",2)->sum("number");
            $number=Db::name("otc")->where("user_id",$user_id)->where("nowstatus",2)->select();
            $count=0;
            foreach($number as $key=>$val){
                $num=Db::name("otc_user")
                    ->where("otc_id",$val["id"])
                    ->where("nowstatus",3)
                    ->count();
                $count+=$num;
                $num=0;
            }
            $info["count"]=$count;
            $data=["data"=>$list,"info"=>$info];
            /**
             * 买入订单打款信息
             */
            if($list["type"]==1){
                $paytype=Db::name("paytype")
                    ->where("status","normal")
                    ->select();
                $exchange=$this->convertCurrency(strtoupper("USD"), "CNY", "1") ?? 6.5;
                foreach($paytype as $key=>$val){
                    $paytype[$key]["USD"]=round($list["total"]/$exchange,2);
                    $paytype[$key]["RMB"]=$list["total"];
                }
                $paytype=["paytype"=>$paytype];
                array_push($data,$paytype);
            }
            return json(["status"=>1,"data"=>$data,"msg"=>"请求数据成功"]);
        }
    }
    /**
     * 用户为买家
     * 买家打款给平台账号
     */
    public function buyconfirm(Request $request){
        if($request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            $orderid=$request->param("orderid","","intval");
            $paytype=$request->param("paytype","","intval");    //打款方式1:支付宝2：微信3：银行卡4：其他
            Db::startTrans();
            try{
                $S1=Db::name("otc_user")
                    ->where("id",$orderid)
                    ->where("nowstatus",1)
                    ->where("user_id",$this->id)
                    ->update([
                        "nowstatus"=>2,
                        "paytime"=>time(),
                        "paytype"=>$paytype
                    ]);
                if(!$S1) throw new Exception("确认支付失败");
                Db::commit();
            } catch (\Exception $e){
                Db::rollback();
                return json(["status"=>0,"msg"=>$e->getMessage()]);
            }
            return json(["status"=>1,"msg"=>"确认支付成功"]);
        }
    }
    /**
     * 用户为卖家
     * 卖家确认收款
     * 减少冻结金额
     */
    public function sellconfirm(Request $request){
        if($request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            $orderid=$request->param("orderid","","intval");
            Db::startTrans();
            try{
                $S1=Db::name("otc_user")
                    ->where("id",$orderid)
                    ->where("nowstatus",2)
                    ->where("user_id",$this->id)
                    ->filed("id,otc_id,number")
                    ->find();
                if(empty($S1)) throw new Exception("订单状态异常");
                $S2=Db::name("otc_user")
                    ->where("id",$orderid)
                    ->where("nowstatus",2)
                    ->where("user_id",$this->id)
                    ->update([
                        "nowstatus"=>3,
                        "downtime"=>time(),
                    ]);
                if(!$S2) throw new Exception("确认收款失败");
                $S3=Db::name("otc")->where("id",$S1["otc_id"])->setInc("traded",$S1["number"]);
                if(!$S3) throw new Exception("更新挂单失败");
                $maininfo=Db::name("otc")->where("id",$S1["otc_id"])->find();
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
                //减少冻结金额
                $S6=Db::name("coin")
                    ->where("user_id",$S1["user_id"])
                    ->where("coin_name","usdt")
                    ->where("coin_balanced",">=",$S1["number"])
                    ->setInc("coin_balanced",$S1["number"]);
                if(!$S6) throw new Exception("更新到账失败");
                Db::commit();
            } catch (\Exception $e){
                Db::rollback();
                return json(["status"=>0,"msg"=>$e->getMessage()]);
            }
            return json(["status"=>1,"msg"=>"确认收款成功"]);
        }
    }
    /**
     *  卖家不确认收款投诉
     */
     public function selltousu(Request $request){
         if($request->isPost()){
             $status=$this->sign();
             if($status!="success") return $status;
             $orderid=$request->param("orderid","","intval");
             Db::startTrans();
             try{
                 /**
                  * 如果订单是已打款状态，更新订单为投诉状态，由后台仲裁订单
                  */
                 $S2=Db::name("otc_user")
                     ->where("id",$orderid)
                     ->where("nowstatus",2)
                     ->where("user_id",$this->id)
                     ->update([
                         "nowstatus"=>5,
                         "downtime"=>time(),
                     ]);
                 if(!$S2) throw new Exception("确认投诉失败");
                 Db::commit();
             } catch (\Exception $e){
                 Db::rollback();
                 return json(["status"=>0,"msg"=>$e->getMessage()]);
             }
             return json(["status"=>1,"msg"=>"投诉订单成功"]);
         }
     }
    /**
     * 百度币种兑换汇率
     */
    protected function convertCurrency($from, $to, $amount){

        $data = file_get_contents("http://www.baidu.com/s?wd={$from}%20{$to}&rsv_spt={$amount}");

        preg_match("/<div>1\D*=(\d*\.\d*)\D*<\/div>/",$data, $converted);

        $converted = preg_replace("/[^0-9.]/", "", $converted[1]);

        return number_format(round($converted, 3), 1);

    }
}