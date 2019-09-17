<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/21
 * Time: 10:39
 */
namespace app\index\controller;
use app\index\model\User;
use PHPExcel_IOFactory;
use PHPExcel_Style;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Style_Fill;
use think\Db;
use think\Exception;
use think\Request;

require_once('../application/common.php');
class Coin extends Indexcommon
{
    /**
     * 充币页面
     */
    public function recharge()
    {
        $result=db("coin_config")->where("status","normal")->field("name")->select();
        $coin=array();
        foreach($result as $vo ){
            $coin[]=$vo["name"];
        }
        $data=db("corder")
            ->where("user_id",$this->id)
            ->where("type","user")
            ->order("id DESC")
            ->limit(10)
            ->select();
        foreach($data as $key=>$val){
            $data[$key]["username"]=User::where("id",$val["user_id"])->value("username");
            $data[$key]["createtime"]=date("Y-m-d H:i;s",$val["createtime"]);
        }
        return $this->fetch("recharge",[
            "coin"=>$coin,
            'data'=>$data
        ]);
    }
    /**
     * 充币记录信息
     */
    public function rechargedetail(Request $request)
    {
        $order_number=$request->param("order_number");
        $coin_name=$request->param("coin_name","","strtolower");
        $address=$request->param("address");
        if(empty($order_number)){
            $coin_name && $coin_name!="all" && $where[]=["coin_name","=",$coin_name];
            $address && $where[]=["address","=",$address];
        }else{
            $where[]=["order_number","=",$order_number];
        }
        $where[]=["user_id","=",$this->id];
        $where[]=["type","=","user"];
        $list=db("corder")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        $variate=["coin_name"=>$coin_name,"address"=>$address,"order_number"=>$order_number];
        return $this->fetch("rechargedetail",[
            "page"=>$page,
            'data'=>$data,
            'count'=>$count,
            "variate"=>$variate
        ]);
    }
    /**
     * 生成充币地址
     */
    public function newaddress()
    {
        $coiname=input("post.coinname","","strtolower");
        $coin_info=db("coin_config")
            ->where("name",$coiname)
            ->field("id,type,hostname,port,username,password,status")
            ->find();
        if(empty($coin_info)) return json(["status"=>0,"msg"=>"币种不存在"]);
        if($coin_info["status"]=="hidden") return json(["status"=>0,"msg"=>"币种未开放"]);
        $cointype=$coin_info["type"];
        $info=db("coin")
            ->where("coin_name",$coiname)
            ->where("user_id",$this->id)
            ->find();
        if(empty($info)){
            $insert=db("coin")->insert(["user_id"=>$this->id,"coin_name"=>$coiname]);
            if(!$insert) return json(["status"=>0,"msg"=>"生成地址错误"]);
        }else{
            if($cointype=="bitcoin"){
                if (preg_match('/^(1|3)[a-zA-Z\d]{24,33}$/', $info["coin_address"]) && preg_match('/^[^0OlI]{25,34}$/', $info["coin_address"])) return json(["status"=>1,"msg"=>"地址已经生成","url"=>$info["coin_address"]]);
            }
            if($cointype=="eth"){
                if(preg_match('/^(0x)?[0-9a-fA-F]{40}$/', $info["coin_address"])) return json(["status"=>1,"msg"=>"地址已经生成","url"=>$info["coin_address"]]);
            }
        }

        if (!empty($info["coin_address"])){
            return json(["status"=>1,"msg"=>"地址已经生成","url"=>$info["coin_address"]]);
        }
        # 地址生成
       $cointype = 'orter'; # 上线后真实地址去掉这个

        switch ($cointype) {
            case "eth":
                $host = $coin_info['hostname'];
                $port = $coin_info['port'];
                $accountOrPassword = config("app.eth_password");
                if (empty($host)||empty($port)||empty($accountOrPassword)) return json(["status"=>0,"msg"=>"币种配置错误"]);

                $coin_arr =['cate'=>'eth','host'=>$host,'port'=>$port];
                $addr = $this->createAddr($coin_arr,$accountOrPassword);
                break;
            case "bitcoin":
                $username = $coin_info['username'];
                $password = $coin_info['password'];
                $host = $coin_info['hostname'];
                $port = $coin_info['port'];
                $accountOrPassword = $this->id.'_'.config("app.btc_password");
                if (empty($username)||empty($password)||empty($host)||empty($port)||empty($accountOrPassword)) return json(["status"=>0,"msg"=>"币种配置错误"]);
                $coin_arr =['cate'=>'btc','host'=>$host,'port'=>$port,'username'=>$username,'password'=>$password];
                $addr = $this->createAddr($coin_arr,$accountOrPassword);
                break;
            case "orter":
                $coin_arr = ['cate'=>'other'];
                $addr = $this->createAddr($coin_arr,42);
                break;
            default:
                return json(["status"=>0,"msg"=>"未知币种"]);
        }
        if(empty($addr))return json(["status"=>0,"msg"=>"生成地址失败"]);
        $address=Db::name("coin")
            ->where("coin_name",$coiname)
            ->where("user_id",$this->id)
            ->update(["coin_address"=>$addr]);
        if($address){
            return json(["status"=>1,"url"=>$addr,"msg"=>"生成地址成功"]);
        }else{
            return json(["status"=>0,"msg"=>"生成地址失败，稍后再试"]);
        }
    }
    /**
     * 加载页面显示地址
     */
    public function address()
    {
        $coiname=input("post.coinname","","strtolower");
        $result=db("coin")
            ->where("user_id",$this->id)
            ->where("coin_name",$coiname)
            ->find();
        if(empty($result)) return json(["status"=>0,"url"=>"","msg"=>"没有币种信息"]);
        return json(["status"=>1,"url"=>$result["coin_address"]]);
    }
    /**
     * 提币页面
     */
    public function withdraw()
    {
        $username=User::where('id',$this->id)->value('username');
        $status=is_email($username);
        $status=$status ? "email" : "mobile";
        $this->assign(["usertype"=>$status,"username"=>$username]);

        $result=db("coin_config")
            ->where("status","normal")
            ->field("name")
            ->select();
        $coin=array();

        $exshop_conf = $this->get_conf_fee($this->id);
        $day_tixm_fee = $exshop_conf['day_tixm_fee'] > 0 ? $exshop_conf['day_tixm_fee'] : 1;
        $price_cny = $this->get_exshop_conf("usdt_price");
        foreach($result as $vo ){
            $user_coin=db("coin")->where("user_id",$this->id)->where("coin_name",$vo["name"])->field("coin_balance,coin_balance_t1")->find();
            if(isset($user_coin)){
                $user_coin['coin_balance_cny'] = $user_coin['coin_balance'] * $price_cny;
                $user_coin['coin_balance_t1_cny'] = $user_coin['coin_balance_t1'] * $price_cny;
                $user_coin['coin_balance_sum'] = $user_coin['coin_balance'] + $user_coin['coin_balance_t1'];
                $user_coin['coin_balance_sum_cny'] = $user_coin['coin_balance_sum']*$price_cny;
                $coin[$vo["name"]] = $user_coin;
            }
        }
        $data=db("torder")
            ->where("user_id",$this->id)
            ->where("type","user")
            ->order("id DESC")
            ->limit(10)
            ->select();

        return $this->fetch("withdraw",[
            "coin"=>$coin,
            'data'=>$data
        ]);
    }
    /**
     * 提币操作
     */
    public function withdrawoperation(Request $request)
    {

        $utype = session('utype');
        if ($utype == 'exshop') return json(["status" => 0, "msg" => "暂未开放"]);

        $number=(float)$request->post("number","","abs");
        $coinname=$request->post("coinname","","strtolower");
        $address=$request->post("address");
        $verify=(int)$request->post("verify");
        $paypassword=(int)$request->post("paypassword");
        if(preg_match('/^\d{4}$/',$verify)) return json(["status"=>0,"msg"=>"请输入6位验证码"]);
        if(empty($paypassword)) return json(["status"=>0,"msg"=>"请输入支付密码"]);
        /**
         * 验证验证码
         */
         $username=User::where("id",$this->id)->field("username,salt,paypassword")->find();
         $code=db("code")->where(["username"=>$username["username"]])->order("id DESC")->find();
         if(empty($code)) return json(['status' => 0, 'msg' => "验证码错误"]);
         if(time()>$code["expiration"]) return json(['status' => 0, 'msg' => "验证码错误"]);
         if($verify!=$code["code"]) return json(['status' => 0, 'msg' => "验证码错误"]);
        /**
         * 验证支付密码
         */
        $paypassword=md5($paypassword.$username["salt"]);
        if($paypassword!=$username["paypassword"]) return json(['status' => 0, 'msg' => "支付密码错误"]);
        $coin_info=db("coin_config")
            ->where("name",$coinname)
            ->field("changfeemax,changfee,changemax,changmin,query,weight,image",true)
            ->find();
        if(empty($coin_info)) return json(["status"=>0,"msg"=>"币种不存在"]);
        if($coin_info["status"]=="hidden") return json(["status"=>0,"msg"=>"币种未开放"]);
        $cointype=$coin_info["type"];
        if($cointype=="bitcoin"){
           # if (!(preg_match('/^(1|3)[a-zA-Z\d]{24,33}$/', $address) && preg_match('/^[^0OlI]{25,34}$/', $address))) return json(["status"=>0,"msg"=>"地址不合法"]);
        }
        if($cointype=="eth"){
          #  if(!(preg_match('/^(0x)?[0-9a-fA-F]{40}$/', $address))) return json(["status"=>0,"msg"=>"地址不合法"]);
        }
        /**
         * 提现最小最大值为0则不控制
         */
        if($coin_info["min"]>0){
            if ($number < $coin_info["min"]) return json(["status" => 0, "msg" => strtoupper($coinname) . "提现最小数量" . $coin_info["min"]]);
        }
        if($coin_info["max"]>0){
            if ($number > $coin_info["max"]) return json(["status" => 0, "msg" => strtoupper($coinname) . "提现最大数量" . $coin_info["max"]]);
        }


        $balance=db("coin")
            ->where("user_id",$this->id)
            ->where("coin_name",$coinname)
            ->field("coin_balance")
            ->find();
        if(empty($balance)) $balance["coin_balance"]=0;
        if($number<=0 || $number>$balance["coin_balance"]) return json(["status"=>0,"msg"=>"钱包数量不足"]);

        $exshop_conf = $this->get_conf_fee($this->id);
        $day_tixm_fee = $exshop_conf['day_tixm_fee'] > 0 ? $exshop_conf['day_tixm_fee'] : 1;

        $tmxnum = $balance["coin_balance"] * $day_tixm_fee;
        if ($number > $tmxnum) return json(["status" => 0, "msg" => "超过今日提现额度"]);

        $fee=round($number*$coin_info["fee"]/100,8);                                //手续费
        if(($coin_info['maxfee']>0)&&($fee>$coin_info["maxfee"])) $fee=$coin_info["maxfee"];      //手续费最大值控制
        $endcoin=round($number-$fee,8);                                             //提现到账数量
        $data=[
            "user_id"=>$this->id,
            "type"=>"user",
            "order_number"=>order_number("YT",2),                                       //YT是订单前缀用户手动提现，type 1=充值，2=提现;
            "coin_name"=>$coinname,
            "coin_money"=>$number,
            "coin_fee"=>$fee,
            "coin_aumount"=>$endcoin,
            "address"=>$address,
            "createtime"=>time(),
            "status"=>"check"                                                                       //待审核
        ];
        /**
         * 扣除提现币种数量
         */
            if(!isset($coin_info["type"])) return json(["status"=>0,"msg"=>"币种类型错误"]);
            Db::startTrans();
            try{
                $S1=Db::name("coin")
                    ->where("coin_name",$coinname)
                    ->where("user_id",$this->id)
                    ->where("coin_balance",">",$number)
                    ->setDec("coin_balance",$number);
                if(!$S1) throw new Exception("提现失败");
                $S2=Db::name("torder")->insertGetId($data);
                if(!$S2) throw new Exception("提现失败");
//                if($endcoin<=$coin_info["limit"] && $coin_info["limit"]>0) {
//                    $connect = new Connectclient();
//                    if ($cointype == "bitcoin") {                                                   //比特类币种
//                        $client = $connect->ltc($coin_info["username"], $coin_info["password"], $coin_info["hostname"], $coin_info["port"]);
//                        if (!$client->getblockchaininfo()) throw new Exception(strtoupper($coinname) . "节点服务器连接失败");
//                        $valid_res = $client->validateaddress($address);
//                        if (!$valid_res['isvalid']) throw new Exception("无效的比特类地址:" . $address);
//                        $btc_password = 1111;                                               //拿取密码
//                        if (!empty($btc_password)) $client->walletpassphrase($btc_password);
//                        $txid = $client->sendtoaddress($address, (double)$endcoin);
//                        $client->walletlock();
//                        if (empty($txid)) throw new Exception(strtoupper($coinname) . "转账失败,数量" . $endcoin);
//                    } else if ($cointype == "eth" && $coin_info["name"] == "eth") {               //ETH主币
//                        $client = $connect->eth($coin_info["hostname"], $coin_info["port"]);
//                        if (empty($client->web3_clientVersion())) throw new Exception(strtoupper($coinname) . "节点服务器连接失败");
//                        $txid = $client->eth_sendTransaction($coin_info["username"], $address, $coin_info["password"], $endcoin);
//                        if (empty($txid)) throw new Exception(strtoupper($coinname) . "转账失败,数量" . $endcoin);
//                    } else if ($cointype == "eth") {                                             //ETH代币
//                        $client = $connect->eth($coin_info["hostname"], $coin_info["port"]);
//                        if (empty($client->web3_clientVersion())) throw new Exception(strtoupper($coinname) . "节点服务器连接失败");
//                        $to_num_raw = dechex($endcoin * $coin_info['precision']);
//                        $amounthex = sprintf("%064s", $to_num_raw);      //转换为64位
//                        $to_addr_res = explode('0x', $address)[1]; //接受地址
//                        $dataraw = '0xa9059cbb000000000000000000000000' . $to_addr_res . $amounthex;//拼接data
//                        $txid = $client->eth_sendTransactionraw($coin_info['username'], $coin_info['hyaddress'], $coin_info['password'], $dataraw);//转出账户,合约地址,转出账户解锁密码,data值
//                        if (empty($txid)) throw new \Exception(strtoupper($coinname) . "转账失败,数量" . $endcoin);
//                        $S3 = Db::name("torder")->where("id", $S2)->update(["status" => "success"]);
//                        if (!$S3) throw new Exception("提币失败");
//                    } else {
//                        throw new Exception("操作失败");
//                    }
//
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                return json(["status"=>0,"msg"=>$e->getMEssage()]);
            }
            return json(["status"=>1,"msg"=>"提币成功"]);
    }
    /**
     * 提币记录
     */
    public function withdrawdetail(Request $request)
    {
        $order_number=$request->param("order_number");
        $coin_name=$request->param("coin_name","","strtolower");
        $address=$request->param("address");
        if(empty($order_number)) {
            $coin_name && $coin_name != "all" && $where[] = ["coin_name", "=", $coin_name];
            $address && $where[] = ["address", "=", $address];
        }else{
            $where[]=["order_number","=",$order_number];
        }
        $where[]=["type","=","user"];
        $where[]=["user_id","=",$this->id];
        $list=Db::name("torder")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        $variate=["coin_name"=>$coin_name,"address"=>$address,"order_number"=>$order_number];
        return $this->fetch("withdrawdetail",[
            'data'=>$data,
            'page'=>$page,
            'count'=>$count,
            'variate'=>$variate
        ]);
    }
    /**
     * 币种转换页面
     */
    public function change()
    {
        $result=db("coin_config")
            ->where("status","normal")
            ->field("name")
            ->select();
        $coin=array();
        foreach($result as $vo ){
            $coin[]=$vo["name"];
        }
        $data=db("change_log")
            ->where("user_id",$this->id)
            ->order("id DESC")
            ->limit(10)
            ->select();
        /**
         * 查询默认币种BTC的余额
         */
        $res=db("coin")
            ->where("user_id",$this->id)
            ->where("coin_name","btc")
            ->value("coin_balance");
        $btc=$res ?? 0.00000000;
        return $this->fetch("change",[
            "coin"=>$coin,
            'data'=>$data,
            'btc'=>$btc
        ]);
    }
    /**
     * 实时核算兑换数量和手续费
     */
    public function checknumber()
    {
        $coinname=input("post.coinname","","strtolower");
        $tocoinname=input("post.tocoinname","","strtolower");
        $num=input("post.num","","abs");
        if($coinname==$tocoinname) return json(["status"=>0,"msg"=>"相同的币种不能兑换"]);
        $res=db("coin_config")->where("name",$tocoinname)->field("name")->find();
        if(empty($res)) return json(["status"=>0,"msg"=>"兑换币种不支持"]);
        /**
         * 获取交易对折算信息
         */
        $type=1;                            //获取兑换比例的币种前后标记     1:正常交易对兑换运算乘法 2: 反交易对兑换运算除法
        $trading=$coinname."_".$tocoinname;
        $trading2=$tocoinname."_".$coinname;
        $bili=Db::name("change_info")->where("name",$trading)->value("bili");
        if(empty($bili)) {
            $bili=Db::name("change_info")->where("name",$trading2)->value("bili");
            if(empty($bili)) return json(["status"=>0,"msg"=>"获取兑换比例失败稍后再试"]);
            $type=2;
        }
        /**
         * 读取币种配置信息
         * coin_h 兑换币种
         */
        $coin_h=db("coin_config")
            ->where("name",$tocoinname)
            ->field("hostname,port,username,password",true)
            ->find();
        if($coin_h["changmin"]>0){
            if($num<$coin_h["changmin"]) return json(["status"=>0,"msg"=>"最少转换".$coin_h["changmin"]."个".strtoupper($coinname)]);
        }
        if($coin_h["changemax"]>0){
            if ($num > $coin_h["changemax"]) return json(["status" => 0, "msg" => "最多转换" . $coin_h["changemax"] . "个" . strtoupper($coinname)]);
        }
        /**
         * 查询当前币种的数量
         */
        $coinvalue=db("coin")
            ->where("user_id",$this->id)
            ->where("coin_name",$coinname)
            ->value("coin_balance");
        $value=$coinvalue ?? 0.00000000;
        if($num>$value) return json(["status"=>0,"msg"=>"账户".strtoupper($coinname)."不足"]);
        if($type==1){
            $data["tocoin"]=round($num*$bili,8);
        }
        if($type==2){
            $data["tocoin"]=round($num/$bili,8);
        }                                                //兑换后总量
        $data["fee"]=round($data["tocoin"]*$coin_h["changfee"]/100,8);                              //兑换手续费
        if($coin_h["changfee"]>0){
            if($data["fee"]>$coin_h["changfeemax"]) $data["fee"]=$coin_h["changfeemax"];
        }
        $data["endcoin"]=round($data["tocoin"]-$data["fee"],8);                                     //实际进账数量
        $data["fee"]=$data["fee"].strtoupper($tocoinname);
        return json(["status"=>1,"data"=>$data]);
    }
    /**
     * 币种转换方法
     */
     public function changecoin()
     {
         $utype = session('utype');
         if ($utype == 'exshop') return json(["status" => 0, "msg" => "暂未开放"]);

         $coinname=input("post.coinname","","strtolower");
         $tocoinname=input("post.tocoinname","","strtolower");
         $num=input("post.num","","abs");
         if($coinname==$tocoinname) return json(["status"=>0,"msg"=>"相同的币种不能兑换"]);
         $res=db("coin_config")->where("name",$tocoinname)->field("name")->find();
         if(empty($res)) return json(["status"=>0,"msg"=>"兑换币种不支持"]);
         /**
          * 获取兑换比例的币种前后标记
          * 1:正常交易对兑换运算乘法
          * 2:反交易对兑换运算除法
          */
         $type=1;
         $trading=$coinname."_".$tocoinname;
         $trading2=$tocoinname."_".$coinname;
         $bili=Db::name("change_info")->where("name",$trading)->value("bili");
         if(empty($bili)) {
             $bili=Db::name("change_info")->where("name",$trading2)->value("bili");
             if(empty($bili)) return json(["status"=>0,"msg"=>"兑换比例获取失败稍后再试"]);
             $type=2;
         }
         /**
          * 读取币种配置信息
          * coin_h 兑换币种
          */
         $coin_h=db("coin_config")
             ->where("name",$tocoinname)
             ->field("hostname,port,username,password",true)
             ->find();
         if($coin_h["changmin"]>0){
             if($num<$coin_h["changmin"]) return json(["status"=>0,"msg"=>"最少转换".$coin_h["changmin"]."个".strtoupper($coinname)]);
         }
         if($coin_h["changemax"]>0){
             if ($num > $coin_h["changemax"]) return json(["status" => 0, "msg" => "最多转换" . $coin_h["changemax"] . "个" . strtoupper($coinname)]);
         }
         /**
          * 查询当前币种的数量
          */
         $coinvalue=db("coin")
             ->where("user_id",$this->id)
             ->where("coin_name",$coinname)
             ->value("coin_balance");
         $value=$coinvalue ?? 0.00000000;
         if($num>$value) return json(["status"=>0,"msg"=>"兑换数量不足"]);
         if($type==1){
             $total=round($num*$bili,8);
         }
         if($type==2){
             $total=round($num/$bili,8);
         }                                                //兑换后总量
         $fee=round($total*$coin_h["changfee"]/100,8);                              //兑换手续费
         if($coin_h["changfee"]>0){
             if($fee>$coin_h["changfeemax"]) $fee=$coin_h["changfeemax"];
         }
         $endcoin=round($total-$fee,8);                                             //实际进账数量
         /**
          * 事务处理资金流转
          */
         Db::startTrans();
         try{
            $s1=Db::name("coin")
                ->where("coin_name",$coinname)
                ->where("user_id",$this->id)
                ->setDec("coin_balance",$num);
            if(!$s1) throw new Exception("扣除币种失败");

            $res=Db::name("coin")
                ->where("coin_name",$tocoinname)
                ->where("user_id",$this->id)
                ->find();
            if(empty($res)){        //没有转换币种，生成币种
                $s2=db("coin")->insert(["user_id"=>$this->id,"coin_name"=>$tocoinname]);
                if(!$s2) throw new Exception("兑换失败");
            }

             $s3=Db::name("coin")
                 ->where("coin_name",$tocoinname)
                 ->where("user_id",$this->id)
                 ->setInc("coin_balance",$endcoin);
             if(!$s3) throw new Exception("兑换币种失败");

             $s4=Db::name("change_log")->insert(
                [
                    "user_id"=>$this->id,
                    "coin_name"=>$coinname,
                    "to_coinname"=>$tocoinname,
                    "coin_number"=>$num,
                    "coin_ratio"=>$bili,
                    "coin_aumount"=>$endcoin,
                    "coin_fee"=>$fee,
                    "createtime"=>time()
                ]
             );
             if(!$s4) throw new Exception("记录兑换失败");
             Db::commit();
         }catch(\Exception $e){
             Db::rollback();
             return json(["status"=>0,"msg"=>$e->getMessage()]);
         }
         return json(["status"=>1,"msg"=>"兑换成功"]);
     }
    /**
     * 查询余额
     */
     public function balance()
     {
         $coinname=input("post.coinname","","strtolower");
         $res=Db::name("coin")
             ->where("coin_name",$coinname)
             ->where("user_id",$this->id)
             ->find();
         if(empty($res)){
             $rest=db("coin")->insert(["user_id"=>$this->id,"coin_name"=>$coinname]);
             if($rest){
                 return json(["status"=>1,"msg"=>"查询成功","data"=>0]);
             }else{
                 return json(["status"=>0,"msg"=>"查询失败"]);
             }
         }
         return json(["status"=>1,"msg"=>"查询成功","data"=>$res["coin_balance"]]);
     }
    /**
     * 币种转换记录
     */
    public function changedetail(Request $request)
    {
        $coin_name=$request->param("coin_name","","strtolower");
        $to_coinname=$request->param("to_coinname","","strtolower");
        $coin_name && $coin_name!="all" && $where[]=["coin_name","=",$coin_name];
        $to_coinname && $to_coinname!="all" && $where[]=["to_coinname","=",$to_coinname];
        $where[]=["user_id","=",$this->id];
        $list=db("change_log")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
       $variate=["coin_name"=>$coin_name,"to_coinname"=>$to_coinname];
        return $this->fetch("changedetail",[
            "page"=>$page,
            "data"=>$data,
            "count"=>$count,
            "variate"=>$variate
        ]);
    }

    /**
     * 账单明细
     */
    public function bill(Request $request)
    {
        $coin_name=$request->param("coin_name","","strtolower");
        $type=$request->param("type","","intval");
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        ($coin_name && $coin_name!="all") && $where[]=["coin_name","=",$coin_name];
        ($type && $type!=5) && $where[]=["type","=",$type];
        $beigin && $where[]=["createtime",">=",strtotime($beigin)];
        $end && $where[]=["createtime","<=",strtotime($end)];
        $where[]=["user_id","=",$this->id];
        //dump($where);
        _elog($where,'./debug.log');
        $list=db("coin_log")->where($where)->order("id DESC")->paginate(10);

        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        $variate=["coin_name"=>$coin_name,"type"=>$type,"beigin"=>$beigin,"end"=>$end];
        return $this->fetch("bill", [
            "page"=>$page,
            "data"=>$data,
            "count"=>$count,
            "variate"=>$variate,
            'num'=>1
            ]);
    }
    /**
     * 积分明细
     */
    public function integral(Request $request)
    {
        $type=$request->param("type","","intval");
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        ($type && $type!=5) && $where[]=["type","=",$type];
        $beigin && $where[]=["createtime",">=",strtotime($beigin)];
        $end && $where[]=["createtime","<=",strtotime($end)];
        $where[]=["user_id","=",$this->id];
        $list=Db::name("integral_log")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        $variate=["type"=>$type,"beigin"=>$beigin,"end"=>$end];
        return $this->fetch("integral", [
            "page"=>$page,
            "data"=>$data,
            "count"=>$count,
            "variate"=>$variate,
            "num"=>1
        ]);
    }
    /**
     * 导出收入明细
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \think\exception\DbException
     */
    public function export()
    {
        //导出表格
        $where[]=["user_id","=",$this->id];
        $list=Db::name("coin_log")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $data=$list->all();
        $username=Db::name("user")->where("id",$this->id)->value("username");
        foreach($data as $key=>$val){
            $data[$key]["username"]=$username;
            $data[$key]["coin_name"]=strtoupper($val["coin_name"]);
            $data[$key]["createtime"]=date("Y-m-d H:i:s",$val["createtime"]);
            switch($val["type"]){
                case 1:
                    $data[$key]["type"]="充值";
                    break;
                case 2:
                    $data[$key]["type"]="提现";
                    break;
                case 3:
                    $data[$key]["type"]="兑换";
                    break;
                case 4:
                    $data[$key]["type"]="分润";
                    break;
            }
        }
        $excel = new \PHPExcel();
        $excel->getProperties()
            ->setCreator("FastAdmin")
            ->setLastModifiedBy("FastAdmin")
            ->setTitle("标题")
            ->setSubject("Subject");
        $excel->getDefaultStyle()->getFont()->setName('Microsoft Yahei');
        $excel->getDefaultStyle()->getFont()->setSize(12);

        $this->sharedStyle = new PHPExcel_Style();
        $this->sharedStyle->applyFromArray(
            array(
                'fill'      => array(
                    'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => '000000')
                ),
                'font'      => array(
                    'color' => array('rgb' => "000000"),
                ),
                'alignment' => array(
                    'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'indent'     => 1
                ),
                'borders'   => array(
                    'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                )
            ));
        $myrow = 1;
        $worksheet = $excel->setActiveSheetIndex(0);
        $worksheet->setTitle('标题');
        $worksheet->setCellValue("A".$myrow, "序号")
            ->setCellValue("B".$myrow, "用户")
            ->setCellValue("C".$myrow, "币种")
            ->setCellValue("D".$myrow, "金额")
            ->setCellValue("E".$myrow, "类型")
            ->setCellValue("F".$myrow, "IP")
            ->setCellValue("G".$myrow, "时间")
            ->setCellValue("H".$myrow, "备注");
        $mynum=1;
        $myrow=$myrow+1;
        foreach($data as $value){
            $worksheet->setCellValue('A' . $myrow, $mynum)
                ->setCellValue('B' . $myrow, $value["username"])
                ->setCellValue('C' . $myrow, strtoupper($value['coin_name']))
                ->setCellValue('D' . $myrow, $value['coin_money'])
                ->setCellValue('E' . $myrow, $value['type'])
                ->setCellValue('F' . $myrow, $value["ip"])
                ->setCellValue('G' . $myrow, $value["createtime"])
                ->setCellValue('H' . $myrow, $value["desc"]);

            //     $worksheet->getActiveSheet()->getRowDimension('' . $myrow)->setRowHeight(20);/*设置行高 不能批量的设置 这种感觉 if（has（蛋）！=0）{疼();}*/
            $myrow++;
            $mynum++;
            ob_flush();
            flush();
        }
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
        $excel->getActiveSheet()->getColumnDimension('H')->setWidth(50);
        $excel->createSheet();
        // Redirect output to a client’s web browser (Excel2007)
        $title = "财务明细-".date("Y-m-d H:i");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $objWriter->save('php://output');
    }

    /**
     * 导出转换记录
     * @param Request $request
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function exportchangeinfo()
    {

        $where[]=["user_id","=",$this->id];
        $data=Db::name("change_log")->where($where)->select();
        $username=Db::name("user")->where("id",$this->id)->value("username");
        $excel = new \PHPExcel();
        $excel->getProperties()
            ->setCreator("FastAdmin")
            ->setLastModifiedBy("FastAdmin")
            ->setTitle("标题")
            ->setSubject("Subject");
        $excel->getDefaultStyle()->getFont()->setName('Microsoft Yahei');
        $excel->getDefaultStyle()->getFont()->setSize(12);

        $this->sharedStyle = new PHPExcel_Style();
        $this->sharedStyle->applyFromArray(
            array(
                'fill'      => array(
                    'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => '000000')
                ),
                'font'      => array(
                    'color' => array('rgb' => "000000"),
                ),
                'alignment' => array(
                    'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'indent'     => 1
                ),
                'borders'   => array(
                    'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                )
            ));
        $myrow = 1;
        $worksheet = $excel->setActiveSheetIndex(0);
        $worksheet->setTitle('标题');
        $worksheet->setCellValue("A".$myrow, "序号")
            ->setCellValue("B".$myrow, "用户")
            ->setCellValue("C".$myrow, "币种")
            ->setCellValue("D".$myrow, "兑换币种")
            ->setCellValue("E".$myrow, "兑换数量")
            ->setCellValue("F".$myrow, "兑换比例")
            ->setCellValue("G".$myrow, "手续费")
            ->setCellValue("H".$myrow, "到账数量")
            ->setCellValue("I".$myrow, "时间");
        $mynum=1;
        $myrow=$myrow+1;
        foreach($data as $value){
            $worksheet->setCellValue('A' . $myrow, $mynum)
                ->setCellValue('B' . $myrow, $username)
                ->setCellValue('C' . $myrow, strtoupper($value['coin_name']))
                ->setCellValue('D' . $myrow, strtoupper($value['to_coinname']))
                ->setCellValue('E' . $myrow, $value['coin_number'])
                ->setCellValue('F' . $myrow, $value["coin_ratio"])
                ->setCellValue('G' . $myrow, $value["coin_fee"])
                ->setCellValue('H' . $myrow, $value["coin_aumount"])
                ->setCellValue('I' . $myrow, date("Y-m-d H:i:s",$value["createtime"]));
            //     $worksheet->getActiveSheet()->getRowDimension('' . $myrow)->setRowHeight(20);/*设置行高 不能批量的设置 这种感觉 if（has（蛋）！=0）{疼();}*/
            $myrow++;
            $mynum++;
            ob_flush();
            flush();
        }
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('G')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $excel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $excel->createSheet();
        // Redirect output to a client’s web browser (Excel2007)
        $title = "兑换明细-".date("Y-m-d H:i");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $title . '.xls"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $objWriter->save('php://output');
    }

    /**
     * 导出用户提现记录
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function exporttorder()
    {
        //导出表格
        $where[]=["user_id","=",$this->id];
        $where[]=["type","=","user"];
        $data=db("torder")
            ->where($where)
            ->order("id DESC")
            ->select();
        foreach($data as $key=>$val)
        {
            $data[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
            if($val["status"]=="error"){
                $data[$key]["status"]="提现失败";
            }else if($val["status"]=="already")
            {
                $data[$key]["status"]="提现已处理";
            }else if($val["status"]=="success")
            {
                $data[$key]["status"]="提现成功";
            }else if($val["status"]=="unusul")
            {
                $data[$key]["status"]="订单异常";
            }else if($val["status"]=="check")
            {
                $data[$key]["status"]="待审核";
            }else if($val["status"]=="pass")
            {
                $data[$key]["status"]="待审核";
            }else if($val["status"]=="refuse")
            {
                $data[$key]["status"]="已拒绝";
            }
        }
        $excel = new \PHPExcel();
        $excel->getProperties()
            ->setCreator("FastAdmin")
            ->setLastModifiedBy("FastAdmin")
            ->setTitle("标题")
            ->setSubject("Subject");
        $excel->getDefaultStyle()->getFont()->setName('Microsoft Yahei');
        $excel->getDefaultStyle()->getFont()->setSize(12);

        $this->sharedStyle = new PHPExcel_Style();
        $this->sharedStyle->applyFromArray(
            array(
                'fill'      => array(
                    'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => '000000')
                ),
                'font'      => array(
                    'color' => array('rgb' => "000000"),
                ),
                'alignment' => array(
                    'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'indent'     => 1
                ),
                'borders'   => array(
                    'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                )
            ));
        $myrow = 1;
        $worksheet = $excel->setActiveSheetIndex(0);
        $worksheet->setTitle('标题');
        $worksheet->setCellValue("A".$myrow, "序号")
            ->setCellValue("B".$myrow, "用户")
            ->setCellValue("C".$myrow, "订单号")
            ->setCellValue("D".$myrow, "提现币种")
            ->setCellValue("E".$myrow, "提现金额")
            ->setCellValue("F".$myrow, "提现手续费")
            ->setCellValue("G".$myrow, "实际到账")
            ->setCellValue("H".$myrow, "提现地址")
            ->setCellValue("I".$myrow, "提现时间")
            ->setCellValue("J".$myrow, "交易哈希")
            ->setCellValue("K".$myrow, "状态");
        $mynum=1;
        $myrow=$myrow+1;
        $username=Db::name("user")->where(["id"=>$this->id])->value("username");
        foreach($data as $value){
            $worksheet->setCellValue('A' . $myrow, $mynum)
                ->setCellValue('B' . $myrow, $username)
                ->setCellValue('C' . $myrow, (string)$value['order_number'])
                ->setCellValue('D' . $myrow, strtoupper($value['coin_name']))
                ->setCellValue('E' . $myrow, $value['coin_money'])
                ->setCellValue('F' . $myrow, $value["coin_fee"])
                ->setCellValue('G' . $myrow, $value["coin_aumount"])
                ->setCellValue('H' . $myrow, $value["address"])
                ->setCellValue('I' . $myrow, $value["createtime"])
                ->setCellValue('J' . $myrow, $value["txid"])
                ->setCellValue('K' . $myrow, $value["status"]);
            //     $worksheet->getActiveSheet()->getRowDimension('' . $myrow)->setRowHeight(20);/*设置行高 不能批量的设置 这种感觉 if（has（蛋）！=0）{疼();}*/
            $myrow++;
            $mynum++;
            ob_flush();
            flush();
        }
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
        $excel->getActiveSheet()->getColumnDimension('G')->setWidth(17);
        $excel->getActiveSheet()->getColumnDimension('H')->setWidth(50);
        $excel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $excel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $excel->getActiveSheet()->getColumnDimension('K')->setWidth(50);
        $excel->createSheet();
        // Redirect output to a client’s web browser (Excel2007)
        $title = "用户手动提现数据-".date("Y-m-d H:i");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $objWriter->save('php://output');
    }

    /**
     * 导出用户充值记录
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function exportcorder()
    {
        //导出表格
        $where[]=["user_id","=",$this->id];
        $where[]=["type","=","user"];
        $data=db("corder")
            ->where($where)
            ->order("id DESC")
            ->select();
        foreach($data as $key=>$val)
        {
            $data[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
            if($val["status"]=="await")
            {
                $data[$key]["status"]="等待付款";
            }else if($val["status"]=="success")
            {
                $data[$key]["status"]="支付成功";
            }else if($val["status"]=="finish")
            {
                $data[$key]["status"]="回调完成";
            }else if($val["status"]=="error")
            {
                $data[$key]["status"]="超时失效";
            }else if($val["status"]=="unusul")
            {
                $data[$key]["status"]="订单异常";
            }
        }
        $excel = new \PHPExcel();
        $excel->getProperties()
            ->setCreator("FastAdmin")
            ->setLastModifiedBy("FastAdmin")
            ->setTitle("标题")
            ->setSubject("Subject");
        $excel->getDefaultStyle()->getFont()->setName('Microsoft Yahei');
        $excel->getDefaultStyle()->getFont()->setSize(12);

        $this->sharedStyle = new PHPExcel_Style();
        $this->sharedStyle->applyFromArray(
            array(
                'fill'      => array(
                    'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => '000000')
                ),
                'font'      => array(
                    'color' => array('rgb' => "000000"),
                ),
                'alignment' => array(
                    'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'indent'     => 1
                ),
                'borders'   => array(
                    'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                )
            ));
        $myrow = 1;
        $worksheet = $excel->setActiveSheetIndex(0);
        $worksheet->setTitle('标题');
        $worksheet->setCellValue("A".$myrow, "序号")
            ->setCellValue("B".$myrow, "用户")
            ->setCellValue("C".$myrow, "订单号")
            ->setCellValue("D".$myrow, "充值币种")
            ->setCellValue("E".$myrow, "充值金额")
            ->setCellValue("F".$myrow, "充值地址")
            ->setCellValue("G".$myrow, "创建时间")
            ->setCellValue("H".$myrow, "确认次数")
            ->setCellValue("I".$myrow, "交易哈希")
            ->setCellValue("J".$myrow, "状态");
        $mynum=1;
        $myrow=$myrow+1;
        $username=Db::name("user")->where(["id"=>$this->id])->value("username");
        foreach($data as $value){
            $worksheet->setCellValue('A' . $myrow, $mynum)
                ->setCellValue('B' . $myrow, $username)
                ->setCellValue('C' . $myrow, (string)$value['order_number'])
                ->setCellValue('D' . $myrow, strtoupper($value['coin_name']))
                ->setCellValue('E' . $myrow, $value['coin_money'])
                ->setCellValue('F' . $myrow, $value["address"])
                ->setCellValue('G' . $myrow, $value["createtime"])
                ->setCellValue('H' . $myrow, $value["coin_affirm"])
                ->setCellValue('I' . $myrow, $value["txid"])
                ->setCellValue('J' . $myrow, $value["status"]);
            //     $worksheet->getActiveSheet()->getRowDimension('' . $myrow)->setRowHeight(20);/*设置行高 不能批量的设置 这种感觉 if（has（蛋）！=0）{疼();}*/
            $myrow++;
            $mynum++;
            ob_flush();
            flush();
        }
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
        $excel->getActiveSheet()->getColumnDimension('G')->setWidth(17);
        $excel->getActiveSheet()->getColumnDimension('H')->setWidth(50);
        $excel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $excel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $excel->createSheet();
        // Redirect output to a client’s web browser (Excel2007)
        $title = "用户手动充值数据-".date("Y-m-d H:i");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $objWriter->save('php://output');
    }


    # 现金提现
    public function tixmcny()
    {
        $username=User::where('id',$this->id)->value('username');
        $status=is_email($username);
        $status=$status ? "email" : "mobile";
        $this->assign(["usertype"=>$status,"username"=>$username]);

        $result=db("coin_config")->where("status","normal")->field("name")->select();

        $hangqing = Db::name("hangqing")->where(['coin_en'=>'usdt'])->field("price")->find();
        $usdt_cny = !empty($hangqing['price']) ? round($hangqing['price'], 2) : 6.8;

        $user_bank = Db::name("user_bank")->order('type')->where(['uid' => $this->id])->select();

        $exshop_conf = $this->get_conf_fee($this->id);
        $day_tixm_fee = $exshop_conf['day_tixm_fee'] > 0 ? $exshop_conf['day_tixm_fee'] : 1;

        $price_cny = $this->get_exshop_conf('usdt_price');
        $coin=array();

        $uid = $this->id;
        foreach($result as $vo ){
            $user_coin=Db::name("coin")->where(["user_id"=>$uid,'coin_name'=>'usdt'])->field("id,user_id,coin_name,coin_balance,coin_balance_t1")->find();
            if(isset($user_coin)){
                $user_coin['coin_balance_cny'] = $user_coin['coin_balance'] * $price_cny;
                $user_coin['coin_balance_t1_cny'] = $user_coin['coin_balance_t1'] * $price_cny;
                $user_coin['coin_balance_sum'] = $user_coin['coin_balance'] + $user_coin['coin_balance_t1'];
                $user_coin['coin_balance_sum_cny'] = $user_coin['coin_balance_sum']*$price_cny;
                $coin[$vo["name"]] = $user_coin;
            }
        }

        $data=Db::name("cny_tixm")->where("uid",$this->id)->order("id DESC")->limit(10)->select();
        return $this->fetch("tixmcny",["coin" => $coin, 'data' => $data,'usdt_cny'=>$price_cny,'banklist'=>$user_bank]);
    }

    # 现金提现操作
    public function tixmcnyac(Request $request)
    {
        $return_cny_num=(float)$request->post("number","","abs"); # 人民币数量
        $coinname=$request->post("coinname","","strtolower");
        $verify=(int)$request->post("verify");
        $paypassword=(int)$request->post("paypassword");
        $pay_type=(int)$request->post("pay_type");
        if(preg_match('/^\d{4}$/',$verify)) return json(["status"=>0,"msg"=>"请输入6位验证码"]);
        if(empty($paypassword)) return json(["status"=>0,"msg"=>"请输入支付密码"]);

        /** 验证验证码 */

        $username=User::where("id",$this->id)->field("username,salt,paypassword,type")->find();
        $code=db("code")->where(["username"=>$username["username"]])->order("id DESC")->find();
        if(empty($code)) return json(['status' => 0, 'msg' => "验证码错误"]);
        if(time()>$code["expiration"]) return json(['status' => 0, 'msg' => "验证码错误"]);
        if($verify!=$code["code"]) return json(['status' => 0, 'msg' => "验证码错误"]);

        /** 验证支付密码 */

        $paypassword=md5($paypassword.$username["salt"]);
        if($paypassword!=$username["paypassword"]) return json(['status' => 0, 'msg' => "支付密码错误"]);
        $coin_info=db("coin_config")->where("name",$coinname)->field("changfeemax,changfee,changemax,changmin,query,weight,image",true)->find();
        if(empty($coin_info)) return json(["status"=>0,"msg"=>"币种不存在"]);
        if($coin_info["status"]=="hidden") return json(["status"=>0,"msg"=>"币种未开放"]);

        # 折合人民币
        $hangqing = Db::name("hangqing")->where(['coin_en'=>'usdt'])->field("price")->find();
        $usdt_cny = $hangqing['price'] > 0 ? $hangqing['price'] : 8;

        $pingtai_cny = $this->get_exshop_conf('usdt_price');

        if (empty($pingtai_cny)){
            $pingtai_cny = 7;
        }
        $number = number_format($return_cny_num/$pingtai_cny,8,'.',''); # 币数量
        $num_cny_hq = number_format($return_cny_num / $usdt_cny,8,'.',''); # 币数量

        /** 提现最小最大值为0则不控制 */

        if($coin_info["min"]>0){
            if ($number < $coin_info["min"]) return json(["status" => 0, "msg" => strtoupper($coinname) . "提现最小数量" . $coin_info["min"]]);
        }
        if($coin_info["max"]>0){
            if ($number > $coin_info["max"]) return json(["status" => 0, "msg" => strtoupper($coinname) . "提现最大数量" . $coin_info["max"]]);
        }

        $balance=db("coin")->where("user_id",$this->id)->where("coin_name",$coinname)->field("coin_balance,coin_balance_t1")->find();
        if(empty($balance)) $balance["coin_balance"]=0;

        if($number<=0 || $number>$balance["coin_balance"]) return json(["status"=>0,"msg"=>"钱包数量不足"]);

        $exshop_conf = $this->get_conf_fee($this->id);

        /*$day_tixm_fee = $exshop_conf['day_tixm_fee'] > 0 ? $exshop_conf['day_tixm_fee'] : 1;

        $tmxnum = $balance["coin_balance"] * $day_tixm_fee;
        if ($number > $tmxnum) return json(["status" => 0, "msg" => "超过今日提现额度"]);*/

        $fee=round($number*$coin_info["fee"]/100,8);                                //手续费
        if(($coin_info['maxfee']>0)&&($fee>$coin_info["maxfee"])) $fee=$coin_info["maxfee"];      //手续费最大值控制
        $endcoin = round($number + $fee, 8);                                             //提现到账数量

        $user_bank = Db::name("user_bank")->order('type')->where(['uid' => $this->id,'type'=>$pay_type])->find();
        if (empty($user_bank)) return json(["status"=>0,"msg"=>"请设置收款方式"]);

        $order_no = makeCode(20);
        $liushui_no = time().makeCode(2);
        $ip = $this->request->ip();
        Db::startTrans();
        $rs[] = Db::name("coin")->where("coin_name", $coinname)->where("user_id", $this->id)->where("coin_balance", ">", $number)->setDec("coin_balance", $endcoin);
        $rs[] = Db::name("cny_tixm")->insert([
            'uid'=>$this->id,'order_no'=>$order_no,'liushui_no'=>$liushui_no,'coin_en'=>$coinname,
            'num'=>$endcoin,'num_cny'=>$return_cny_num,'num_cny_hq'=>$num_cny_hq,'fee'=>$fee,
            'pay_type'=>$pay_type,
        ]);

        $rs[] = Db::name('coin_log')->insert([
            'user_id'=>$this->id,'coin_name'=>'usdt','coin_money'=>$return_cny_num,
            'type'=>2,'action'=>'现金提现','ip'=>$ip,'createtime'=>time(),
        ]);

        if (check_arr($rs)){
            Db::commit();
            return json(["status"=>1,"msg"=>"提现成功"]);
        }else{
            return json(["status"=>0,"msg"=>"提现失败"]);
        }
    }
    # 现金提现记录
    public function tixmcny_list(Request $request)
    {
        $order_number=$request->param("order_number");
        $coin_name=$request->param("coin_name","","strtolower");
        $address=$request->param("address");
        if(empty($order_number)) {
            $coin_name && $coin_name != "all" && $where[] = ["coin_en", "=", $coin_name];
            $address && $where[] = ["address", "=", $address];
        }else{
            $where[]=["order_no","=",$order_number];
        }
        $where[]=["uid","=",$this->id];
        $list=Db::name("cny_tixm")->where($where)->order("id DESC")->paginate(10);

        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        $variate=["coin_name"=>$coin_name,"address"=>$address,"order_number"=>$order_number];
        return $this->fetch("tixmcny_list",[
            'data'=>$data,
            'page'=>$page,
            'count'=>$count,
            'variate'=>$variate
        ]);
    }

    # 订单明细
    public function shop_list(Request $request)
    {
        $coin_name=$request->param("coin_name","","strtolower");
        $type=$request->param("type","","intval");
        $order_no=input('order_no');
        $liushui_no=input('liushui_no');
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        ($coin_name && $coin_name!="all") && $where[]=["name_en","=",$coin_name];
        ($type && $type!=5) && $where[]=["type","=",$type];
        $beigin && $where[]=["createtime",">=",$beigin];
        $end && $where[]=["createtime","<=",$end];

        if (!empty($order_no)){
            $where[]=["order_no","=",$order_no];
        }

        if (!empty($liushui_no)){
            $where[]=["liushui_no","=",$liushui_no];
        }

        $pingtai_price = $this->get_exshop_conf('usdt_price');

        $where[]=["app_uid","=",$this->id];
        $where[]=['status','=',2];

        $list=db("excoin_match")->where($where)->field("id,order_no,liushui_no,num,price,shop_fee,true_num,name_en,type,time,end_time")->order("id DESC")->paginate(10);

        $page=$list->render();
        $data=$list->all();
        foreach ($data as $k=>$v){
            # $exshopinfo = Db::name("user")->where("id={$v['match_uid']}")->field("username,nickname")->find();
            $data[$k]['price_cny']=number_format($v['price']/$v['num'],6,'.','');
            $data[$k]['shop_fee_cny'] = $v['shop_fee'] * $pingtai_price;
            $data[$k]['true_num_cny'] = $v['true_num'] * $pingtai_price;
            $data[$k]['price_cny']=number_format($v['price']/$v['num'],6,'.','');

        }
        $zprice = array_sum(array_column($data,'price'));
        $znum = array_sum(array_column($data,'num'));
        $zfee = array_sum(array_column($data,'shop_fee'));

        $count=$list->total();
        $variate=["coin_name"=>$coin_name,"type"=>$type,"beigin"=>$beigin,"end"=>$end,'order_no'=>$order_no,'liushui_no'=>$liushui_no];
        return $this->fetch("shop_list", ["page" => $page, "data" => $data, "count" => $count, "zprice" => $zprice,"znum" => $znum,"zfee" => $zfee, "variate" =>$variate,
        ]);
    }

    # 奖励明细
    public function reward_list(Request $request)
    {
        $uid = $this->id;
        $coin_en=$request->param("coin_name","","strtolower");
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        $order_no=input('order_no');
        $liushui_no=input('liushui_no');
        $type=input('type');
        $beigin && $where[]=["time",">=",$beigin];
        $end && $where[]=["time","<=",$end];
        ($coin_en && $coin_en!="all") && $where[]=["coin_en","=",$coin_en];

        if (!empty($order_no)){
            $match = Db::name("excoin_match")->where(['order_no' => $order_no])->field("id")->find();
            $where[]=["match_id","=",$match['id']];
        }

        if (!empty($liushui_no)){
            $match = Db::name("excoin_match")->where(['liushui_no' => $liushui_no])->field("id")->find();
            $where[]=["match_id","=",$match['id']];
        }
        if (!empty($type)){
            $where[]=["type","=",$type];
        }

        $where[]=["to_uid","=",$uid];

        $list = Db::name("reward_zhangdan")->where($where)->order("id DESC")->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();

        $pingtai_price = $this->get_exshop_conf('usdt_price');

        foreach ($data as $k => $v) {
            $frominfo = Db::name("user")->where("id={$v['from_uid']}")->field("username,nickname,type")->find();
            $data[$k]['from'] = $frominfo;
//            $toinfo = Db::name("user")->where("id={$v['to_uid']}")->field("username,nickname")->find();
//            $data[$k]['to'] = $toinfo;
            $matchinfo = Db::name("excoin_match")->where(['id' => $v['match_id']])->field("id,order_no,liushui_no,num,price,shop_fee,true_num")->find();
            $data[$k]['match'] = $matchinfo;
            $data[$k]['num_cny'] = $v['num'] * $pingtai_price;

        }

        $znum = $list = Db::name("reward_zhangdan")->where($where)->sum('num');
        //$znum = array_sum(array_column($data, 'num'));
        $variate = ["coin_name"=>$coin_en,"beigin"=>$beigin,"end"=>$end,'order_no'=>$order_no,'liushui_no'=>$liushui_no,'type'=>$type];
        return $this->fetch("reward_list", ["page" => $page, "data" => $data, "count" => $count, "znum" => $znum, "variate" => $variate,
        ]);
    }


    public function get_hangqing($coin_en)
    {
        $coin_en = input('coin_en');
        $hangqing = $this->get_exshop_conf("usdt_price");
        return json(["status"=>1,"data"=>$hangqing]);

    }

}