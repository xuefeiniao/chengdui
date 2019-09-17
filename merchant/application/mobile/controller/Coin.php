<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/21
 * Time: 10:39
 */

namespace app\mobile\controller;
use app\mobile\model\User;
use think\Db;
use think\Exception;
use think\Request;

class Coin extends Common
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
        return json([
            "coin"=>$coin,
            'data'=>$data
        ]);
    }
    /**
     * 充币记录信息
     */
    public function rechargedetail()
    {
        $list=db("corder")
            ->where("user_id",$this->id)
            ->where("type","user")
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        foreach($data as $key=>$val){
            $data[$key]["username"]=User::where("id",$val["user_id"])->value("username");
            $data[$key]["createtime"]=date("Y-m-d H:i;s",$val["createtime"]);
        }
        return json([
            "page"=>$page,
            'data'=>$data,
            'count'=>$count
        ]);
    }
    /**
     * 生成充币地址
     */
    public function newaddress()
    {
        if($this->request->isPost()) {
            $status = $this->sign();
            if ($status != "success") return $status;
            $coiname = input("post.coinname", "", "strtolower");
            $coin_info = db("coin_config")
                ->where("name", $coiname)
                ->field("id,type,hostname,port,username,password,status")
                ->find();
            if (empty($coin_info)) return json(["status" => 0, "msg" => "币种不存在"]);
            if ($coin_info["status"] == "hidden") return json(["status" => 0, "msg" => "币种未开放"]);
            $cointype = $coin_info["type"];
            $info = db("coin")
                ->where("coin_name", $coiname)
                ->where("user_id", $this->id)
                ->find();
            if (empty($info)) {
                $insert = db("coin")->insert(["user_id" => $this->id, "coin_name" => $coiname]);
                if (!$insert) return json(["status" => 0, "msg" => "生成地址错误"]);
            } else {
                if ($cointype == "bitcoin") {
                    if (preg_match('/^(1|3)[a-zA-Z\d]{24,33}$/', $info["coin_address"]) && preg_match('/^[^0OlI]{25,34}$/', $info["coin_address"])) return json(["status" => 1, "msg" => "地址已经生成", "url" => $info["coin_address"]]);
                }
                if ($cointype == "eth") {
                    if (preg_match('/^(0x)?[0-9a-fA-F]{40}$/', $info["coin_address"])) return json(["status" => 1, "msg" => "地址已经生成", "url" => $info["coin_address"]]);
                }
            }
            /**
             * 地址生成
             */
            if ($cointype == "eth") {
                Db::startTrans();
                try {
                    $addrinfo = Db::name("coin_eth")
                        ->where("status", "normal")
                        ->where("type", "always")
                        ->where("use_status", "no")
                        ->where("count", 0)
                        ->where("amount", 0)
                        ->field("id,address")
                        ->order("id ASC")
                        ->find();
                    if (empty($addrinfo)) throw new Exception("地址生成失败请稍后再试");
                    $S1 = Db::name("coin_eth")
                        ->where("id", $addrinfo["id"])
                        ->where("count", 0)
                        ->where("use_status", "no")
                        ->where("amount", 0)
                        ->update(["count" => 1, "use_status" => "yes", "lasttime" => time(), "type" => "eonian"]);     //type:eonian:永久 always:临时
                    if (!$S1) throw new Exception("获取地址失败");
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    return json(["status" => 0, "msg" => $e->getMessage()]);
                }
                $addr = $addrinfo["address"];
            } else if ($cointype == "bitcoin") {
                if ($coiname == "btc") {
                    $address = Db::name("coin")
                        ->where("user_id", $this->id)
                        ->where("coin_name", "usdt")
                        ->value("coin_address");
                }
                if ($coiname == "usdt") {
                    $address = Db::name("coin")
                        ->where("user_id", $this->id)
                        ->where("coin_name", "btc")
                        ->value("coin_address");
                }
                if (empty($address)) {
                    Db::startTrans();
                    try {
                        $addrinfobtc = Db::name("coin_btc")
                            ->where("status", "normal")
                            ->where("type", "always")
                            ->where("use_status", "no")
                            ->where("count", 0)
                            ->where("amount", 0)
                            ->field("id,address")
                            ->order("id ASC")
                            ->find();
                        if (empty($addrinfobtc)) throw new Exception("地址生成失败请稍后再试");
                        $S1 = Db::name("coin_btc")
                            ->where("id", $addrinfobtc["id"])
                            ->where("count", 0)
                            ->where("use_status", "no")
                            ->where("amount", 0)
                            ->update(["count" => 1, "use_status" => "yes", "lasttime" => time(), "type" => "eonian"]);     //type:eonian:永久 always:临时
                        if (!$S1) throw new Exception("获取地址失败");
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        return json(["status" => 0, "msg" => $e->getMessage()]);
                    }
                    $addr = $addrinfobtc["address"];
                } else {
                    $addr = $address;
                }
            } else {
                return json(["status" => 0, "msg" => "币种类型错误"]);
            }
            if (empty($addr)) return json(["status" => 0, "msg" => "生成地址失败"]);
            $address = Db::name("coin")
                ->where("coin_name", $coiname)
                ->where("user_id", $this->id)
                ->update(["coin_address" => $addr]);
            if ($address) {
                return json(["status" => 1, "url" => $addr, "msg" => "生成地址成功"]);
            } else {
                return json(["status" => 0, "msg" => "生成地址失败，稍后再试"]);
            }
        }
    }
    /**
     * 充值地址状态
     */
    public function addressstatus()
    {
        if($this->request->isPost()) {
            $status = $this->sign();
            if ($status != "success") return $status;
            $coiname = input("post.coinname", "", "strtolower");
            $coin_info = db("coin_config")
                ->where("name", $coiname)
                ->field("id,type,hostname,port,username,password,status")
                ->find();
            if (empty($coin_info)) return json(["status" => 0, "msg" => "币种不存在"]);
            if ($coin_info["status"] == "hidden") return json(["status" => 0, "msg" => "币种未开放"]);
            $cointype = $coin_info["type"];
            $info = db("coin")
                ->where("coin_name", $coiname)
                ->where("user_id", $this->id)
                ->find();
            if (empty($info)) {
                    return json(["status"=>1,"msg"=>"地址未生成","data"=>1]);
            } else {
                if ($cointype == "bitcoin") {
                    if (preg_match('/^(1|3)[a-zA-Z\d]{24,33}$/', $info["coin_address"]) && preg_match('/^[^0OlI]{25,34}$/', $info["coin_address"])) return json(["status" => 1, "msg" => "地址已经生成", "url" => $info["coin_address"],"data"=>2]);
                }
                if ($cointype == "eth") {
                    if (preg_match('/^(0x)?[0-9a-fA-F]{40}$/', $info["coin_address"])) return json(["status" => 1, "msg" => "地址已经生成", "url" => $info["coin_address"],"data"=>2]);
                }
            }
            return json(["status"=>1,"msg"=>"地址未生成","data"=>1]);
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
        foreach($result as $vo ){
            $balance=db("coin")
                ->where("user_id",$this->id)
                ->where("coin_name",$vo["name"])
                ->value("coin_balance");
            if(isset($balance)){
                $coin[$vo["name"]]=$balance;
            }else{
                $coin[$vo["name"]]=0;
            }
        }
        $data=db("torder")
            ->where("user_id",$this->id)
            ->where("type","user")
            ->order("id DESC")
            ->limit(10)
            ->select();
        foreach($data as $key=>$val){
            $data[$key]["username"]=User::where("id",$val["user_id"])->value("username");
            $data[$key]["createtime"]=date("Y-m-d H:i;s",$val["createtime"]);
        }
        return json([
            "coin"=>$coin,
            'data'=>$data
        ]);
    }
    /**
     * 提币操作
     */
    public function withdrawoperation(Request $request)
    {
        if($request->isPost()) {
            $status = $this->sign();
            if ($status != "success") return $status;
            $number = (float)$request->post("number", "", "abs");
            $coinname = $request->post("coinname", "", "strtolower");
            $address = $request->post("address");
            $verify = (int)$request->post("verify");
            $paypassword = (int)$request->post("paypassword");
            if (preg_match('/^\d{4}$/', $verify)) return json(["status" => 0, "msg" => "请输入6位验证码"]);
            if (empty($paypassword)) return json(["status" => 0, "msg" => "请输入支付密码"]);
            /**
             * 验证验证码
             */
            $username = User::where("id", $this->id)->field("username,salt,paypassword")->find();
            $code = db("code")
                ->where(["username" => $username["username"]])
                ->whereTime("expiration",'-1 hours')
                ->order("id DESC")
                ->find();
            if (empty($code)) return json(['status' => 0, 'msg' => "请发送验证码"]);
            if (time() > $code["expiration"]) return json(['status' => 0, 'msg' => "验证码错误"]);
            if ($verify != $code["code"]) return json(['status' => 0, 'msg' => "验证码错误"]);
            /**
             * 验证支付密码
             */
            $paypassword = md5($paypassword . $username["salt"]);
            if ($paypassword != $username["paypassword"]) return json(['status' => 0, 'msg' => "支付密码错误"]);
            $coin_info = db("coin_config")
                ->where("name", $coinname)
                ->field("changfeemax,changfee,changemax,changmin,query,weight,image", true)
                ->find();
            if (empty($coin_info)) return json(["status" => 0, "msg" => "币种不存在"]);
            if ($coin_info["status"] == "hidden") return json(["status" => 0, "msg" => "币种未开放"]);
            $cointype = $coin_info["type"];
            if ($cointype == "bitcoin") {
                if (!(preg_match('/^(1|3)[a-zA-Z\d]{24,33}$/', $address) && preg_match('/^[^0OlI]{25,34}$/', $address))) return json(["status" => 0, "msg" => "地址不合法"]);
            }
            if ($cointype == "eth") {
                if (!(preg_match('/^(0x)?[0-9a-fA-F]{40}$/', $address))) return json(["status" => 0, "msg" => "地址不合法"]);
            }
            /**
             * 提现最小最大值为0则不控制
             */
            if ($coin_info["min"] > 0) {
                if ($number < $coin_info["min"]) return json(["status" => 0, "msg" => strtoupper($coinname) . "提现最小数量" . $coin_info["min"]]);
            }
            if ($coin_info["max"] > 0) {
                if ($number > $coin_info["max"]) return json(["status" => 0, "msg" => strtoupper($coinname) . "提现最大数量" . $coin_info["max"]]);
            }
            $balance = db("coin")
                ->where("user_id", $this->id)
                ->where("coin_name", $coinname)
                ->field("coin_balance")
                ->find();
            if (empty($balance)) $balance["coin_balance"] = 0;
            if ($number <= 0 || $number > $balance["coin_balance"]) return json(["status" => 0, "msg" => "钱包数量不足"]);
            $fee = round($number * $coin_info["fee"] / 100, 8);                                       //手续费
            if (($coin_info['maxfee'] > 0) && ($fee > $coin_info["maxfee"])) $fee = $coin_info["maxfee"];           //手续费最大值控制
            $endcoin = round($number - $fee, 8);                                                      //提现到账数量
            $data = [
                "user_id" => $this->id,
                "type" => "user",
                "order_number" => order_number("YT", 2),                                                  //YT是订单前缀用户手动提现，type 1=充值，2=提现;
                "coin_name" => $coinname,
                "coin_money" => $number,
                "coin_fee" => $fee,
                "coin_aumount" => $endcoin,
                "address" => $address,
                "createtime" => time(),
                "status" => "check"                                                                                 //待审核
            ];
            /**
             * 扣除提现币种数量
             */
            if (!isset($coin_info["type"])) return json(["status" => 0, "msg" => "币种类型错误"]);
            Db::startTrans();
            try {
                $S1 = Db::name("coin")
                    ->where("coin_name", $coinname)
                    ->where("user_id", $this->id)
                    ->where("coin_balance", ">=", $number)
                    ->setDec("coin_balance", $number);
                if (!$S1) throw new Exception("提现失败");
                $S2 = Db::name("torder")->insertGetId($data);
                if (!$S2) throw new Exception("提现失败");
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                return json(["status" => 0, "msg" => $e->getMEssage()]);
            }
            return json(["status" => 1, "msg" => "提币成功"]);
        }
    }
    /**
     * 提币记录
     */
    public function withdrawdetail()
    {
        $result=db("coin_config")
            ->where("status","normal")
            ->field("name")
            ->select();
        $coin=array();
        foreach($result as $vo ){
            $balance=db("coin")
                ->where("user_id",$this->id)
                ->where("coin_name",$vo["name"])
                ->value("coin_balance");
            if(isset($balance)){
                $coin[$vo["name"]]=$balance;
            }else{
                $coin[$vo["name"]]=0;
            }
        }
        $list=db("torder")
            ->where("user_id",$this->id)
            ->where("type","user")
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        foreach($data as $key=>$val){
            $data[$key]["username"]=User::where("id",$val["user_id"])->value("username");
            $data[$key]["createtime"]=date("Y-m-d H:i;s",$val["createtime"]);
        }
        return json([
            "coin"=>$coin,
            'data'=>$data,
            'page'=>$page,
            'count'=>$count
        ]);
    }
    /**
     * 实时核算兑换数量和手续费
     */
    public function checknumber()
    {
        if($this->request->isPost()) {
            $status = $this->sign();
            if ($status != "success") return $status;
            $coinname = input("post.coinname", "", "strtolower");
            $tocoinname = input("post.tocoinname", "", "strtolower");
            $num = input("post.num", "", "abs");
            if ($coinname == $tocoinname) return json(["status" => 0, "msg" => "相同的币种不能兑换"]);
            $res = db("coin_config")->where("name", $tocoinname)->field("name")->find();
            if (empty($res)) return json(["status" => 0, "msg" => "兑换币种不支持"]);
            /**
             * 获取交易对折算信息
             */
            $type = 1;                            //获取兑换比例的币种前后标记     1:正常交易对兑换运算乘法 2: 反交易对兑换运算除法
            $trading = $coinname . "_" . $tocoinname;
            $trading2 = $tocoinname . "_" . $coinname;
            $bili = Db::name("change_info")->where("name", $trading)->value("bili");
            if (empty($bili)) {
                $bili = Db::name("change_info")->where("name", $trading2)->value("bili");
                if (empty($bili)) return json(["status" => 0, "msg" => "获取兑换比例失败稍后再试"]);
                $type = 2;
            }
            /**
             * 读取币种配置信息
             * coin_h 兑换币种
             */
            $coin_h = db("coin_config")
                ->where("name", $tocoinname)
                ->field("hostname,port,username,password", true)
                ->find();
            if ($coin_h["changmin"] > 0) {
                if ($num < $coin_h["changmin"]) return json(["status" => 0, "msg" => "最少转换" . $coin_h["changmin"] . "个" . strtoupper($coinname)]);
            }
            if ($coin_h["changemax"] > 0) {
                if ($num > $coin_h["changemax"]) return json(["status" => 0, "msg" => "最多转换" . $coin_h["changemax"] . "个" . strtoupper($coinname)]);
            }
            /**
             * 查询当前币种的数量
             */
            $coinvalue = db("coin")
                ->where("user_id", $this->id)
                ->where("coin_name", $coinname)
                ->value("coin_balance");
            $value = $coinvalue ?? 0.00000000;
            if ($num > $value) return json(["status" => 0, "msg" => "账户" . strtoupper($coinname) . "不足"]);
            if ($type == 1) {
                $data["tocoin"] = round($num * $bili, 8);
            }
            if ($type == 2) {
                $data["tocoin"] = round($num / $bili, 8);
            }                                                //兑换后总量
            $data["fee"] = round($data["tocoin"] * $coin_h["changfee"] / 100, 8);                              //兑换手续费
            if ($coin_h["changfee"] > 0) {
                if ($data["fee"] > $coin_h["changfeemax"]) $data["fee"] = $coin_h["changfeemax"];
            }
            $data["endcoin"] = round($data["tocoin"] - $data["fee"], 8);                                     //实际进账数量
            $data["fee"] = $data["fee"] . strtoupper($tocoinname);
            $data["changmin"]=$coin_h["changmin"];
            $data["changemax"]=$coin_h["changemax"];
            return json(["status" => 1, "data" => $data]);
        }
    }
    /**
     * 币种转换方法
     */
    public function changecoin(Request $request)
    {
        if($request->isPost()) {
            $status = $this->sign();
            if ($status != "success") return $status;
            $coinname = input("post.coinname", "", "strtolower");
            $tocoinname = input("post.tocoinname", "", "strtolower");
            $num = input("post.num", "", "abs");
            if ($coinname == $tocoinname) return json(["status" => 0, "msg" => "相同的币种不能兑换"]);
            $res = db("coin_config")->where("name", $tocoinname)->field("name")->find();
            if (empty($res)) return json(["status" => 0, "msg" => "兑换币种不支持"]);
            /**
             * 获取兑换比例的币种前后标记
             * 1:正常交易对兑换运算乘法
             * 2:反交易对兑换运算除法
             */
            $type = 1;
            $trading = $coinname . "_" . $tocoinname;
            $trading2 = $tocoinname . "_" . $coinname;
            $bili = Db::name("change_info")->where("name", $trading)->value("bili");
            if (empty($bili)) {
                $bili = Db::name("change_info")->where("name", $trading2)->value("bili");
                if (empty($bili)) return json(["status" => 0, "msg" => "兑换比例获取失败稍后再试"]);
                $type = 2;
            }
            /**
             * 读取币种配置信息
             * coin_h 兑换币种
             */
            $coin_h = db("coin_config")
                ->where("name", $tocoinname)
                ->field("hostname,port,username,password", true)
                ->find();
            if ($coin_h["changmin"] > 0) {
                if ($num < $coin_h["changmin"]) return json(["status" => 0, "msg" => "最少转换" . $coin_h["changmin"] . "个" . strtoupper($coinname)]);
            }
            if ($coin_h["changemax"] > 0) {
                if ($num > $coin_h["changemax"]) return json(["status" => 0, "msg" => "最多转换" . $coin_h["changemax"] . "个" . strtoupper($coinname)]);
            }
            /**
             * 查询当前币种的数量
             */
            $coinvalue = db("coin")
                ->where("user_id", $this->id)
                ->where("coin_name", $coinname)
                ->value("coin_balance");
            $value = $coinvalue ?? 0.00000000;
            if ($num > $value) return json(["status" => 0, "msg" => "兑换数量不足"]);
            if ($type == 1) {
                $total = round($num * $bili, 8);
            }
            if ($type == 2) {
                $total = round($num / $bili, 8);
            }                                                                                       //兑换后总量
            $fee = round($total * $coin_h["changfee"] / 100, 8);                              //兑换手续费
            if ($coin_h["changfee"] > 0) {
                if ($fee > $coin_h["changfeemax"]) $fee = $coin_h["changfeemax"];
            }
            $endcoin = round($total - $fee, 8);                                             //实际进账数量
            /**
             * 事务处理资金流转
             */
            Db::startTrans();
            try {
                $s1 = Db::name("coin")
                    ->where("coin_name", $coinname)
                    ->where("user_id", $this->id)
                    ->setDec("coin_balance", $num);
                if (!$s1) throw new Exception("扣除币种失败");

                $res = Db::name("coin")
                    ->where("coin_name", $tocoinname)
                    ->where("user_id", $this->id)
                    ->find();
                if (empty($res)) {        //没有转换币种，生成币种
                    $s2 = db("coin")->insert(["user_id" => $this->id, "coin_name" => $tocoinname]);
                    if (!$s2) throw new Exception("兑换失败");
                }

                $s3 = Db::name("coin")
                    ->where("coin_name", $tocoinname)
                    ->where("user_id", $this->id)
                    ->setInc("coin_balance", $endcoin);
                if (!$s3) throw new Exception("兑换币种失败");

                $s4 = Db::name("change_log")->insert(
                    [
                        "user_id" => $this->id,
                        "coin_name" => $coinname,
                        "to_coinname" => $tocoinname,
                        "coin_number" => $num,
                        "coin_ratio" => $bili,
                        "coin_aumount" => $endcoin,
                        "coin_fee" => $fee,
                        "createtime" => time()
                    ]
                );
                if (!$s4) throw new Exception("记录兑换失败");
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return json(["status" => 0, "msg" => $e->getMessage()]);
            }
            return json(["status" => 1, "msg" => "兑换成功"]);
        }
    }
    /**
     * 当面收款
     */
    public function shoukuan()
    {
        if($this->request->isPost()){
            $status = $this->sign();
            if ($status != "success") return $status;
            $coinname=$this->request->param("coinname","","strtolower");
            $number=$this->request->param("number","","floatval");
            $fromtoken=$this->request->param("fromtoken");
            $userinfo = User::where("token", $fromtoken)->field("id,username")->find();
            if(empty($userinfo)) return json(["status"=>0,"msg"=>"付款用户不存在"]);
            $fromid=$userinfo["id"];
            Db::startTrans();
            try {
                $coininfo = Db::name("coin_config")
                    ->where("status", "normal")
                    ->where("name", $coinname)
                    ->field("id")
                    ->find();
                if (empty($coininfo)) throw new Exception("不支持收款币种");
                if ($number <= 0) throw new Exception("收款数量不正确");

                $tocoin=Db::name("coin")
                    ->where("user_id",$this->id)
                    ->where("coin_name",$coinname)
                    ->find();
                if(empty($tocoin)) throw new Exception("请生成对应收款币种的地址");

                $fromcoin=Db::name("coin")
                    ->where("user_id",$fromid)
                    ->where("coin_name",$coinname)
                    ->find();
                if(empty($fromcoin)) throw new Exception("付款账号余额不足");

                if($number>$fromcoin["coin_balance"]) throw new Exception("付款账号余额不足");

                $S1=DB::name("coin")
                    ->where("user_id",$fromid)
                    ->where("coin_name",$coinname)
                    ->where("coin_balance",">=",$number)
                    ->setDec("coin_balance",$number);
                if(!$S1) throw new Exception("扣款失败");

                $S2=DB::name("coin")
                    ->where("user_id",$this->id)
                    ->where("coin_name",$coinname)
                    ->setInc("coin_balance",$number);
                if(!$S2) throw new Exception("收款失败");
                $data=[
                [
                    "user_id"=>$this->id,
                    "coin_name"=>$coinname,
                    "coin_money"=>$number,
                    "type"=>5,
                    "action"=>"收款",
                    "ip"=>$this->request->ip(),
                    "createtime"=>time()
                ],
                    [
                        "user_id"=>$fromid,
                        "coin_name"=>$coinname,
                        "coin_money"=>$number,
                        "type"=>6,
                        "action"=>"付款",
                        "ip"=>$this->request->ip(),
                        "createtime"=>time()
                    ]
                ];
                $S3=Db::name("coin_log")->insertAll($data);
                if(!$S3) throw new Exception("添加收付款记录失败");
                Db::commit();
            } catch(\Exception $e){
                Db::rollback();
                return json(["status"=>0,"msg"=>$e->getMessage()]);
            }
        return json(["status"=>1,"msg"=>"收款成功"]);
        }
    }
    /**
     * 收付款记录
     */
    public function shoufuorder()
    {
        if($this->request->isPost()){
            $status = $this->sign();
            if ($status != "success") return $status;
            $type=$this->request->param("type","","intval");
            $offset=$this->request->param("offset","","intval");
            $num=$this->request->param("num","","intval") ?? 10;
            $where[]=["type","=",$type];           //5:收款 6:付款
            $offset && $where[]=["id","<",$offset];
            $where[]=["user_id","=",$this->id];
            $data=Db::name("coin_log")
                ->where($where)
                ->order("id,DESC")
                ->limit($num)
                ->select();
            if(empty($data)) return json(["status"=>0,"msg"=>"无有效数据"]);
            foreach($data as $key=>$val) {
                $data[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
                $data[$key]["coin_name"]=strtoupper($val["coin_name"]);
            }
            $offset=$data[count($data)-1]["id"];
            return json(["status"=>1,"data"=>$data,"offset"=>$offset]);
        }
    }
    /**
     * 查询余额
     */
     public function balance()
     {
         if($this->request->isPost()) {
             $status = $this->sign();
             if ($status != "success") return $status;
             $coinname = $this->request->param("coinname", "", "strtolower");
             $res = Db::name("coin")
                 ->where("coin_name", $coinname)
                 ->where("user_id", $this->id)
                 ->find();
             if (empty($res)) {
                 $rest = db("coin")->insert(["user_id" => $this->id, "coin_name" => $coinname]);
                 if ($rest) {
                     return json(["status" => 1, "msg" => "查询成功", "data" => 0]);
                 } else {
                     return json(["status" => 0, "msg" => "查询失败"]);
                 }
             }
             return json(["status" => 1, "msg" => "查询成功", "data" => $res["coin_balance"]]);
         }
     }
    /**
     *  兑换记录
     */
     public function changedetail()
     {
         if($this->request->isPost()) {
             $status = $this->sign();
             if ($status != "success") return $status;
             $offset = $this->request->param("offset", "", "intval");
             $num = $this->request->param("num", "", "intval") ?? 10;
             $offset && $where[] = ["id", "<", $offset];
             $where[] = ["user_id", "=", $this->id];
             $data = Db::name("change_log")
                 ->where($where)
                 ->order("id DESC")
                 ->limit($num)
                 ->select();
             if (empty($data)) return json(["status" => 0, "msg" => "无请求数据"]);
             foreach ($data as $key => $val) {
                 $data[$key]["createtime"] = date("Y-m-d H:i", $val["createtime"]);
                 $data[$key]["coin_name"]=strtoupper($val["coin_name"]);
                 $data[$key]["to_coinname"]=strtoupper($val["to_coinname"]);
             }
             $offset = $data[count($data) - 1]["id"];
             return json(["status" => 1, "data" => $data, "offset" => $offset]);
         }
     }

}