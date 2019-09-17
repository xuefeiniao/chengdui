<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/19
 * Time: 15:50
 */

namespace app\mobile\controller;
use think\Controller;
use think\Db;
use think\Exception;
use zb\ZbAPI;
use app\index\model\User;
class Index extends Controller
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
     * APP首页
     */
    public function index()
    {
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with, content-type');
        if($this->request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            $data=$this->indexdata();
            return json(["status"=>1,"data"=>$data,"msg"=>"请求成功"]);
        }
    }
    /**
     * 主页公用调用方法
     */
    protected function indexdata(){
        $coininfo=db("coin_config")
            ->where("status","normal")
            ->select();
        foreach($coininfo as $vo){
            $coinname[]=$vo["name"];
        }
        $coin=array();                          //存储币种余额
        foreach($coinname as $val)
        {
            $value=db("coin")
                ->where("user_id",$this->id)
                ->where("coin_name",$val)
                ->find();
            if($value) {
                $coin[$val]=$value["coin_balance"];
            } else {
                $coin[$val]=0;
            }
        }
        /**
         * 币币行情对USDT汇率
         */
        $zb=new ZbAPI();
        $coinfig=db("config")->where("name","coiname")->value("value");
        $coin_name=explode(",",$coinfig);
        $usdt_rmb=$zb->Hangqing()['ticker']['last'];
        foreach($coin_name as $val){
            $name=$val."_usdt";
            $name2=strtoupper($val)."/USDT";
            $usdtprice=$zb->Hangqing($name);
            $coin_last[$name2]=$usdtprice['ticker'];
            $coin_last[$name2]["rmb"]=round($usdtprice['ticker']["last"]*$usdt_rmb,8);
            $coin_last[$name2]["change"]=2;
        }
        /**
         * 法币行情对cny汇率
         */
        $coinfig_xj=db("config")->where("name","coiname2")->value("value");
        $coin_name2=explode(",",$coinfig_xj);
        foreach($coin_name2 as $val2){
            $name_xj=strtoupper($val2);
            $get_value=$this->convertCurrency($name_xj, "CNY", "1");
            $name_xj=$name_xj."/CNY";
            $coin_last_xj[$name_xj]["rmb"]=$get_value;
            $coin_last_xj[$name_xj]["change"]=2;
        }
        /**
         * 今日订单数
         * 今日所有币收益
         */
        $coin_sum=array();                          //当天所有币种的收入
        foreach($coinname as $vlue){
            $sum=db("corder")
                ->where("user_id",$this->id)
                ->where("status","complete")
                ->where("coin_name",strtolower($vlue))
                ->whereTime("createtime","d")
                ->sum("coin_money");
            $coin_sum[$vlue]=$sum;
        }
        $coin_count=db("corder")                //统计当前订单数
            ->where("user_id",$this->id)
            ->where("status","complete")
            ->whereTime("createtime","d")
            ->count();
        /**
         * btc_usdt 折扣需调用第三方接口
         * eth_usdt 折扣需调用第三方接口
         */
        $total=0;
        foreach($coin_sum as $key=>$val){
            if($key=="usdt"){
                $total+=$val;
            }else{
                $cname=strtoupper($key)."/USDT";
                $total+=$val*$coin_last[$cname]["last"];
            }
        }
        return [
            "coin"=>$coin,                      //所有币种钱包余额和信息
            "total"=>$total,                    //今天所有订单的总额折算USDT
            "ordersum"=>$coin_sum,              //今天所有订单的币种数量
            "coin_count"=>$coin_count,          //当日充值完成订单数
            "coin_last"=>$coin_last,            //币币交易行情
            "coin_last_xj"=>$coin_last_xj       //币币交易行情
        ];
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
    /**
     * APP资产管理
     */
    public function balance(){
        if($this->request->isPost()) {
            $status = $this->sign();
            if ($status != "success") return $status;
            $zb = new ZbAPI();
            $usdt_rmb = $zb->Hangqing()['ticker']['last'];
            $coininfo = db("coin_config")
                ->where("status", "normal")
                ->field("name")
                ->order("weight DESC")
                ->select();
            foreach ($coininfo as $vo) {
                $coinname[] = strtoupper($vo["name"]);
            }
            $coin = array();                          //存储币种余额
            $total=0;
            foreach ($coinname as $val) {
                $value = db("coin")
                    ->where("user_id", $this->id)
                    ->where("coin_name", $val)
                    ->find();
                if ($value) {
                    $coin[$val]["balance"] = $value["coin_balance"];
                    if ($val == "USDT") {
                        $coin[$val]["usdt"] = $value["coin_balance"];
                    } else {
                        $coinname = strtolower($val) . "_" . "usdt";
                        $lilv = $zb->Hangqing($coinname);
                        $coin[$val]["usdt"] = round($value["coin_balance"] * $lilv['ticker']['last'], 8);
                    }
                    $coin[$val]["rmb"] = round($coin[$val]["usdt"] * $usdt_rmb, 8);
                } else {
                    $coin[$val]["balance"] = 0;
                    $coin[$val]["usdt"] = 0;
                    $coin[$val]["rmb"] = 0;
                }
                $total+=$coin[$val]["usdt"];
                $coin[$val]["image"] = db("coin_config")->where("name", $val)->value("image");
            }
            return json([
                "status"        =>1,
                "data"          =>$coin,
                "usdttotal"     =>round($total,8),
                'rmbtotal'      =>round($total*$usdt_rmb,8),
                "msg"           =>"请求成功"
            ]);
        }
    }
    /**
     * 商户信息
     */
    public function info()
    {
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with, content-type');
        if($this->request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            $info=User::where("id",$this->id)
                ->field("username,nickname,avatar,wechat,alipay,idnumber")
                ->find();
            if(empty($info)) return json(["status"=>0,"msg"=>"无请求数据"]);
            return json(["status"=>1,"data"=>$info,"msg"=>"请求成功"]);
        }
    }
    /**
     * 商户信息修改
     */
    public function upinfo()
    {
        if($this->request->isPost())
        {
            $status=$this->sign();
            if($status!="success") return $status;
            $data=$this->request->param();
            /**
             * 微信图片修改type=wechatimage
             * 支付宝图片修改type=alipayimage
             * 头像图片修改type=avatar
             */
             if(isset($data["type"])){
                 $file=$this->request->file("image");
                 $info=$file->validate(['size'=>15678,'ext'=>'jpg,png,gif'])->move('../public/uploads');
                 if($info){
                     $data[$data["type"]]="/uploads/".str_replace("\\","/",$info->getSaveName());
                 }else{
                     return json(["status"=>0,"msg"=>$file->getError()]);
                 }
             }
            $res=Db::name("user")
                ->where("id",$this->id)
                ->field("avatar,nickname,wechat,wechatname,wechatimage,alipay,alipayname,alipayimage")
                ->update($data);
            if($res){
                return json(["status"=>1,"msg"=>"修改成功"]);
            }else {
                return json(["status" =>0, "msg" => "修改失败"]);
            }
        }
    }
    /**
     * 修改密码
     */
    public function password()
    {
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with, content-type');
        if($this->request->isPost())
        {
            $status = $this->sign();
            if ($status != "success") return $status;
            $data=$this->request->param();
            $validate=validate("User");
            if(!$validate->scene("reset")->check($data)) return json(["status"=>0,"msg"=>$validate->getError()]);
            $info=db("user")
                ->where("id",$this->id)
                ->field("salt,password,username")
                ->find();
            $code=db("code")                     //验证验证码
                ->where(["username"=>$info['username']])
                ->order("id DESC")
                ->find();
            if(empty($code)) return json(['status' => 0, 'msg' => "请发送验证码"]);
            if(time()>$code["expiration"]) return json(['status' => 0, 'msg' => "验证码错误"]);
            if($data['verify']!=$code["code"]) return json(['status' => 0, 'msg' => "验证码错误"]);
            $password=md5($data["oldpassword"].md5($info["salt"]));
            if($password!=$info["password"]) return json(["status"=>0,"msg"=>"原密码错误"]);
            $data["password"]=md5($data["password"].md5($info["salt"]));
            $data["id"]=$this->id;
            $res=db("user")->field("password")->update($data);
            if($res){
                return json(["status"=>1,"msg"=>"修改密码成功"]);
            }else{
                return json(["status"=>0,"msg"=>"修改密码失败"]);
            }
        }
    }
    /**
     * 修改支付密码
     */
    public function paypassword()
    {
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with, content-type');
        if($this->request->isPost())
        {
            $status = $this->sign();
            if ($status != "success") return $status;
            $data=$this->request->param();
            $validate=validate("User");
            if(!$validate->scene("uppay")->check($data)) return json(["status"=>0,"msg"=>$validate->getError()]);
            $info=db("user")
                ->where("id",$this->id)
                ->field("salt,paypassword,username")
                ->find();
            $code=db("code")                     //验证验证码
                ->where(["username"=>$info['username']])
                ->order("id DESC")
                ->find();
            if(empty($code)) return json(['status' => 0, 'msg' => "请发送验证码"]);
            if(time()>$code["expiration"]) return json(['status' => 0, 'msg' => "验证码错误"]);
            if($data['verify']!=$code["code"]) return json(['status' => 0, 'msg' => "验证码错误"]);
            $paypassword=md5($data["oldpaypassword"].$info["salt"]);
            if($paypassword!=$info["paypassword"]) return json(["status"=>0,"msg"=>"原支付密码错误"]);
            $data["paypassword"]=md5($data["paypassword"].$info["salt"]);
            $data["id"]=$this->id;
            $res=db("user")->field("paypassword")->update($data);
            if($res){
                return json(["status"=>1,"msg"=>"修改支付密码成功"]);
            }else{
                return json(["status"=>0,"msg"=>"修改支付密码失败"]);
            }
        }
    }
    /**
     * 代理下商户信息
     */
    public function shoplist(Request $request)
    {
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with, content-type');
        if($request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            $offset=$request->param("offset","","intval");
            $num=$request->param("num","","intval");
            if(!empty($offset)) $where[]=["<",$offset];
            $where[]=["type","=","shop"];
            $where[]=["parentid","=",$this->id];
            $list=db("user")
                ->where($where)
                ->field("id,username,nickname,createtime")
                ->limit($num)
                ->select();
            foreach($list as $key=>$val){
                $list[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
            }
            if(empty($list)) return json(["status"=>0,"msg"=>"无请求数据"]);
            $offset=$list[count($list)-1]["id"];
            $data=array("offset"=>$offset,"info"=>$list);
            return json(["status"=>1,"data"=>$data,"msg"=>"请求数据成功"]);
        }
    }
    /**
     * 提现2，分润4，充值1，兑换3 记录信息列表
     */
    public function bonus(Request $request)
    {
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with, content-type');
       if($request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            $offset=$request->param("offset","","intval");
            $num=$request->param("num","","intval");
            $type=$request->param("type","","intval");
            if(empty($type)) return json(["status"=>0,"msg"=>"记录类型不存在"]);
            if(!empty($offset)) $where[]=['id', '<', $offset];
            $where[]=["user_id","=",$this->id];
            $where[]=["type","=",$type];
            $list=db("coin_log")
                ->where($where)
                ->field("id,coin_name,coin_money,action,ip,createtime")
                ->order("id DESC")
                ->limit($num)
                ->select();
            if(empty($list)) return json(["status"=>0,"msg"=>"无请求数据"]);
            foreach($list as $k=>$val){
                $list[$k]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
                $list[$k]["coin_name"]=strtoupper($val["coin_name"]);
            }
            $offset=$list[count($list)-1]["id"];
            $data=array("offset"=>$offset,"info"=>$list);
            return json(["status"=>1,"data"=>$data,"msg"=>"请求成功"]);
        }
    }
    /**
     * API充值订单管理
     */
    public function order()
    {
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with, content-type');
        if($this->request->isPost()){
        $status=$this->sign();
        if($status!="success") return $status;
            $data=$this->request->param();
            if(isset($data["order_number"])){
                $where[]=["order_number","=",$data["order_number"]];
            }else{
                if(!empty($data["offset"])) $where[]=["id","<",$data["offset"]];                            //步调
                if(!empty($data["coinname"])) $where[]=["coin_name","=",strtolower($data["coinname"])];     //币种名字
                if(!empty($data["begintime"]) && !empty($data["endtime"])) $where[]=["createtime","between time",[$data["begintime"],$data["endtime"]]];
                if(!empty($data["status"])) $where[]=["status","=",$data["status"]];                        //订单状态
            }
            $where[]=["user_id","=",$this->id];
            if($data["type"]=="corder") {
                $list = Db::name("corder")
                    ->where($where)
                    ->order("id DESC")
                    ->limit($data["num"])
                    ->select();
            }else if($data["type"]=="torder"){
                $list = Db::name("torder")
                    ->where($where)
                    ->order("id DESC")
                    ->limit($data["num"])
                    ->select();
            }else{
                return json(["status"=>0,"msg"=>"请求类型错误"]);
            }
            if(empty($list))return json(["status"=>0,"msg"=>"无请求数据"]);
            foreach($list as $key=>$val)
            {
                $list[$key]["username"]=db("user")->where("id",$val["user_id"])->value("username");
                $list[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
                $list[$key]["endtime"]=date("Y-m-d H:i",$val["endtime"]);
                $list[$key]["image"]=Db::name("coin_config")->where("name",strtolower($val["coin_name"]))->value("image");
            }
            $offset=$list[count($list)-1]["id"];
            $data=["data"=>$list,"offset"=>$offset];
            return json(["status"=>1,"data"=>$data,"msg"=>"请求成功"]);
        }
    }
    /**
     * 订单详情
     */
    public function orderinfo()
    {
        if($this->request->isPost()) {
            $status=$this->sign();
            if($status!="success") return $status;
            $orderid=$this->request->param("orderid","","intval");
            $type=$this->request->param("type","","intval");
            if($type=="corder"){
                $res=Db::name("corder")
                    ->where("id",$orderid)
                    ->where("user_id",$this->id)
                    ->find();
            } else if($type=="torder") {
                $res=Db::name("torder")
                    ->where("id",$orderid)
                    ->where("user_id",$this->id)
                    ->find();
            }else{
                return json(["status"=>0,"msg"=>"请求类型失败"]);
            }
            if(empty($res)) return json(["status"=>0,"msg"=>"无请求数据"]);
            $res["createtime"]=date("Y-m-d H:i:s",$res["createtime"]);
            $res["endtime"]=date("Y-m-d H:i:s",$res["endtime"]);
            $res["coin_name"]=strtoupper($res["coin_name"]);
            return json(["status"=>1,"msg"=>"请求成功","data"=>$res]);
        }
    }
    /**
     * API充值订单管理中的
     * 充值总额
     * 提现总额
     * 总交易额
     */
    public function ordercoin()
    {
        if($this->request->isPost())
        {
            $status=$this->sign();
            if($status!="success") return $status;
            $coininfo=db("coin_config")
                ->where("status","normal")
                ->select();
            foreach($coininfo as $vo){
                $coinname[]=strtolower($vo["name"]);
            }
            /**
             * 币币行情对USDT汇率
             */
            $zb=new ZbAPI();
            $usdt_rmb=$zb->Hangqing();
            if(isset($usdt_rmb["errro"])) return json(["status"=>0,"msg"=>"请求失败"]);
            $usdt_rmb=$usdt_rmb['ticker']['last'];
            foreach($coinname as $val){
                if($val=="usdt") continue;
                $name=$val."_usdt";
                $name2=strtoupper($val)."/USDT";
                $usdtprice=$zb->Hangqing($name);
                if(isset($usdt_rmb["errro"])) return json(["status"=>0,"msg"=>"请求失败"]);
                $coin_last[$name2]=$usdtprice['ticker'];
                $coin_last[$name2]["rmb"]=round($usdtprice['ticker']["last"]*$usdt_rmb,8);
                $usdtprice=0;
            }
            /**
             * 所有完成充值订单总额
             */
            $ccoin_sum=array();                          //当天所有币种的收入
            foreach($coinname as $vlue){
                $sum=db("corder")
                    ->where("user_id",$this->id)
                    ->where("status","success")
                    ->where("coin_name",strtolower($vlue))
                    ->sum("coin_money");
                $ccoin_sum[$vlue]=$sum;
            }
            $ctotal=0;
            foreach($ccoin_sum as $key=>$val1){
                if($key=="usdt"){
                    $ctotal+=$val1;
                }else{
                    $cname=strtoupper($key)."/USDT";
                    $ctotal+=$val1*$coin_last[$cname]["last"];
                }
            }
            /**
             * 所有完成提现订单总额
             */
            $tcoin_sum=array();                          //当天所有币种的收入
            foreach($coinname as $vlue){
                $sum=db("torder")
                    ->where("user_id",$this->id)
                    ->where("status","success")
                    ->where("coin_name",strtolower($vlue))
                    ->sum("coin_money");
                $tcoin_sum[$vlue]=$sum;
            }
            $ttotal=0;
            foreach($tcoin_sum as $key=>$val){
                if($key=="usdt"){
                    $ttotal+=$val;
                }else{
                    $cname=strtoupper($key)."/USDT";
                    $ttotal+=$val*$coin_last[$cname]["last"];
                }
            }
            $data=["csum"=>$ctotal,"tsum"=>$ttotal,"sum"=>$ctotal+$ttotal];
            return json(["status"=>1,"data"=>$data,"msg"=>"请求成功"]);
        }
    }
    /**
     * 订单详细信息
     */
    public function getcorder()
    {
        $data=$this->request->param();
        if($this->request->isPost())
        {
            if($data["type"]==1)    //type=1代表充值订单
            {
                $res=db("corder")
                    ->where("id",intval($data["id"]))
                    ->field("order_number,coin_money,address_id,coin_from,txid,coin_affirm")
                    ->find();
                return json(["status"=>1,"data"=>$res]);
            }
            if($data["type"]==2)    //type=2代表提现订单
            {
                $res=db("torder")
                    ->where("id",intval($data["id"]))
                    ->field("order_number,coin_money,coin_fee,coin_aumount,address,txid")
                    ->find();
                return json(["status"=>1,"data"=>$res]);
            }
        }
    }
    /**
     * 推荐码
     */
    public function inviter()
    {
        if($this->request->isPost())
        {
            $status=$this->sign();
            if($status!="success") return $status;
        $userinfo=User::where("id",$this->id)->field("type,inviter")->find();
        if($userinfo["type"]!="agency") return json(["status"=>0,"msg"=>"非代理用户"]);
        $url="http://127.0.0.10/mobile/login/sign/inviter/".$userinfo["inviter"];
        $data=["invaiter"=>$userinfo["inviter"],"url"=>$url];
        return json(["status"=>1,"data"=>$data,"msg"=>"请求成功"]);
        }
    }
    /**
     * 获取签到状态和积分规则
     */
    public function signinzt()
    {
        if($this->request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            /**
             * 送积分规则
             */
            $jifen=Db::name("config")->where("name","sign")->value("value");
            $weekjifen=explode(",",$jifen);
            $i=1;
            $sign_week=array();
            foreach($weekjifen as $val){
                $sign_week[$i]=$val;
                $i++;
            }
            $nowtime=time();
            $Begin=strtotime(date("Y-m-d",$nowtime));                   //今天0点时间戳
            $uptime=Db::name("user")->where("id",$this->id)->field("signnum,signtime")->find();
            if($uptime["signtime"]>=$Begin){
                return json(["status"=>1,"msg"=>"今日已签到","today"=>$uptime["signnum"],"integral"=>$sign_week]);
            }else{
                return json(["status"=>0,"msg"=>"今日未签到",'today'=>$uptime["signnum"],"integral"=>$sign_week]);
            }
        }
    }
    /**
     * 签到操作
     */
    public function signin()
    {
        if($this->request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            /**
             * 获取签到积分
             */
            $jifen=Db::name("config")->where("name","sign")->value("value");
            $weekjifen=explode(",",$jifen);
            $i=1;
            $sign_week=array();
            foreach($weekjifen as $val){
                $sign_week[$i]=$val;
                $i++;
            }
            $nowtime=time();
            $Begin=strtotime(date("Y-m-d",$nowtime));                   //今天0点时间戳
            $zuo=strtotime(date("Y-m-d",strtotime("-1 day")));    //昨天0点时间戳
            $uptime=User::where("id",$this->id)->value("signtime");
            if($uptime>=$Begin){
                return json(["status"=>0,"msg"=>"今日已签到"]);
            }else{
                Db::startTrans();
                try{
                    if($uptime<$zuo && $uptime>0){
                        $S3=Db::name("User")->where("id",$this->id)->setField("signnum",0);
                        if(!$S3) throw new Exception("更新签到天数失败");
                    }
                    $S1=Db::name("User")->where("id",$this->id)->setField("signtime",$nowtime);
                    if(!$S1) throw new Exception("签到失败");
                    $S2=Db::name("User")->where("id",$this->id)->setInc("signnum");
                    if(!$S2) throw new Exception("签到失败");
                    $signnum=Db::name("User")->where("id",$this->id)->value("signnum");
                    $S3=Db::name("User")->where("id",$this->id)->setInc("integral",$sign_week[$signnum]);
                    if(!$S3) throw new Exception("签到送积分失败");
                    if($signnum==7){
                        $S4=Db::name("User")->where("id",$this->id)->setField("signnum",0);
                        if(!$S4) throw new Exception("更新签到天数出错");
                    }
                    Db::commit();
                }catch (\Exception $e){
                    Db::rollback();
                    return json(["status"=>0,"msg"=>$e->getMessage()]);
                }
                Db::name("integral_log")->insert([
                    "user_id"=>$this->id,
                    "number"=>$sign_week[$signnum],
                    "type"=>1,
                    "action"=>"签到",
                    "createtime"=>time()
                ]);
                return json(["status"=>1,"msg"=>"签到成功","today"=>$signnum]);
            }
        }
    }
    /**
     * 我的积分
     */
    public function mysign(){
        if($this->request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            $offset=$this->request->param("offset","","intval");
            $offset && $where[]=["id","<",$offset];
            $num=$this->request->param("num","","intval") ?? 10;
            $where[]=["user_id","=",$this->id];
            $data=Db::name("integral_log")
                ->where($where)
                ->order("ID desc")
                ->limit($num)
                ->select();
            if(empty($data)) return json(["status"=>0,"msg"=>"无有效请求数据"]);
            foreach($data as $key=>$val){
                $data[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
            }
            $offset=$data[count($data)-1]["id"];
            $sum=User::where("id",$this->id)->value("integral");
            return json(["status"=>1,"data"=>$data,"offset"=>$offset,"sum"=>$sum]);
        }
    }
    /**
     * 意见反馈
     */
     public function opinion(){
         if($this->request->isPost())
         {
             $status=$this->sign();
             if($status!="success") return $status;
             /**
              * 提交反馈内容和反馈图片
              */
                 $file=$this->request->file("image");
                 if(!empty($file)) {
                     $info = $file->validate(['size' => 15678, 'ext' => 'jpg,png,gif'])->move('../public/uploads');
                     if ($info) {
                           $problemimage=$info->getSaveName();
                           $data["problemimage"]="/uploads/".str_replace("\\","/",$problemimage);
                     } else {
                         return json(["status" => 0, "msg" => $file->getError()]);
                     }
                 }
                 $data["problem"]=$this->request->param("problem","","trim");
                 $data["user_id"]=$this->id;
                 $data["createtime"]=time();
                 $data["status"]="no";
                 $res=Db::name("opinion")->insert($data);
             if($res){
                 return json(["status"=>1,"msg"=>"提交成功等待回复"]);
             }else {
                 return json(["status" =>0, "msg" => "提交信息失败"]);
             }
         }
     }
    /**
     * 服务协议
     * 隐私条款
     */
    public function privacy(){
        if($this->request->isPost()){
            $status=$this->sign();
            if($status!="success") return $status;
            $type=$this->request->param("type");
            $res=db("article")->where("type",$type)->find();
            if(empty($res)) return json(["status"=>0,"msg"=>"无请求数据"]);
            return json(["status"=>1,"data"=>$res,"msg"=>"请求成功"]);
        }
    }
    /**
     * 退出登录
     */
    public function quit()
    {
        session(null);
    }

}