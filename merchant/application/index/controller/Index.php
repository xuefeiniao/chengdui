<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/19
 * Time: 15:50
 */

namespace app\index\controller;
use PHPExcel_IOFactory;
use PHPExcel_Style;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Style_Fill;
use think\Db;
use think\Request;
use zb\ZbAPI;

class Index extends Indexcommon
{

    /**
     * 主页
     */
    public function index()
    {
        $userinfo=Db::name("user")
            ->where("id",$this->id)
            ->field("nickname,shopid")
            ->find();
        if(empty($userinfo["shopid"])){
            $api="no";
        }else{
            $api="yes";
        }
        $coin_name=Db::name("config")->where("name","coiname")->value("value");
        $coinname=explode(",",$coin_name);
        $data=$this->indexdata();
        $sumdata=$this->total();
        $klineData=$this->kline();

        # 平台价
        $usdt_price = $this->get_exshop_conf('usdt_price');
        # 总提现
        $uid = $this->id;
        $torderlist = Db::name("torder")->where(['user_id'=>$uid,'status'=>'already'])->sum('coin_aumount');
        $cnylist = Db::name("cny_tixm")->where(['uid'=>$uid,'status'=>1])->sum('num');
        $tixian = ($torderlist + $cnylist) * $usdt_price;

        $usercoin = Db::name("coin")->where(['user_id'=>$uid])->select();
        $usercoin_list = array_column($usercoin,null,'coin_name');
        if (!empty($usercoin_list['usdt'])&&$usercoin_list['usdt']>0){
            $usdt_coin = $usercoin_list['usdt'];
            $usdt_total = $usdt_coin['coin_balance'] + $usdt_coin['coin_balance_t1'] + $usdt_coin['coin_balance_ex'];
        }else{
            $usdt_total = 0;
        }


        $total = $usdt_total * $usdt_price;


        $data['total'] = $data['total'] * $usdt_price;
        return $this->fetch("index",[
            "coin"=>$data["coin"],              //币种数量
            "total"=>$total,            //总数量折合USDT
            "usdt_total"=>$usdt_total,            //总数量折合USDT
            "notice"=>$data['notice'],          //是否设置APIkey
            "rmb"=>$data['usdt_rmb'],           //USDT对人民币
            "api"=>$api,                        //是否设置了API
            "nickname"=>$userinfo["nickname"],  //真实姓名
            "coinname"=>$coinname,              //主页显示的币币行情,
            "sumdata"=>$sumdata,                //汇总数据
            "kdata"=>$klineData,             //K线数据
            "tixm"=>$tixian             //总提现
        ]);
    }
    /**
     * 主页公用调用方法
     */
    protected function indexdata(){
        $coininfo=db("coin_config")
            ->where("status","normal")
            ->select();
        foreach($coininfo as $vo){
            $coinname[]=strtolower($vo["name"]);
        }
        $coin=array();                          //存储币种余额
        foreach($coinname as $val)
        {
            $value=db("coin")->where("user_id",$this->id)->where("coin_name",$val)->find();

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
        $usdt_rmb=$zb->Hangqing();
        foreach($coinname as $val){
            if(strtolower($val)!="usdt") {
                $name = $val . "_usdt";
                $hangqing = Db::name("change_info")->where("name", $name)->value("bili");
                if (empty($hangqing)) {
                    $coin_last[$name] = $hangqing;
                } else {
                    $usdtprice = $zb->Hangqing($name);
                    $coin_last[$name] = $usdtprice['ticker']["last"];
                }
            }
        }
        /**
         * btc_usdt 折扣需调用第三方接口
         * eth_usdt 折扣需调用第三方接口
         */
        $total=0;
        foreach($coin as $key=>$coinval){
            if($key=="usdt"){
                $total+=$coinval;
            }else{
                $cname=$key."_usdt";
                $total+=$coinval*$coin_last[$cname];
            }
        }
        /**
         * 主页系统公告数据
         */
        $data=db("article")
            ->where("type","notice")    //notice=公告
            ->where("status",'normal')
            ->order("sort DESC")
            ->limit(3)
            ->select();
        foreach($data as $key=>$val){
            $data[$key]["content"]=mb_substr(strip_tags($val["content"]),0,50,"UTF-8")."....";
            $data[$key]["createtime"]=date("Y-m-d",$val['createtime']);
            $data[$key]["updatetime"]=date("Y-m-d",$val['updatetime']);
        }

        return ["coin"=>$coin,"total"=>$total,"notice"=>$data,"usdt_rmb"=>$usdt_rmb['ticker']["last"]];
    }
    /**
     * 统计数据
     */
    protected function total(){
        $zb=new ZbAPI();
        $btc_usdt=$zb->Hangqing("btc_usdt")['ticker']["last"];
        $eth_usdt=$zb->Hangqing("eth_usdt")['ticker']["last"];
        //获取总收入折算USDT
        $USDT=Db::name("coin_log")
            ->where("user_id",$this->id)
            ->where("coin_name","usdt")
            ->sum("coin_money");
        $ETH=Db::name("coin_log")
            ->where("user_id",$this->id)
            ->where("coin_name","eth")
            ->sum("coin_money");
        $BTC=Db::name("coin_log")
            ->where("user_id",$this->id)
            ->where("coin_name","btc")
            ->sum("coin_money");
        $total=round($USDT+$ETH*$eth_usdt+$BTC*$btc_usdt,2);
        //获取总提现折算USDT
        $TUSDT=Db::name("torder")
            ->where("user_id",$this->id)
            ->where("coin_name","usdt")
            ->where("status","success")
            ->sum("coin_money");
        $TETH=Db::name("torder")
            ->where("user_id",$this->id)
            ->where("coin_name","eth")
            ->where("status","success")
            ->sum("coin_money");
        $TBTC=Db::name("torder")
            ->where("user_id",$this->id)
            ->where("coin_name","btc")
            ->where("status","success")
            ->sum("coin_money");
        $ttotal=round($TUSDT+$TETH*$eth_usdt+$TBTC*$btc_usdt,2);
        //获取今日收入折算USDT
        $NOWUSDT=Db::name("coin_log")
            ->where("user_id",$this->id)
            ->where("coin_name","usdt")
            ->whereTime("createtime","d")
            ->sum("coin_money");
        $NOWETH=Db::name("coin_log")
            ->where("user_id",$this->id)
            ->where("coin_name","eth")
            ->whereTime("createtime","d")
            ->sum("coin_money");
        $NOWBTC=Db::name("coin_log")
            ->where("user_id",$this->id)
            ->where("coin_name","btc")
            ->whereTime("createtime","d")
            ->sum("coin_money");
        $nowtotal=round($NOWUSDT+$NOWETH*$eth_usdt+$NOWBTC*$btc_usdt,2);
        //获取今日提现折算USDT
        $NOWTUSDT=Db::name("torder")
            ->where("user_id",$this->id)
            ->where("coin_name","usdt")
            ->where("status","success")
            ->whereTime("createtime","d")
            ->sum("coin_money");
        $NOWTETH=Db::name("torder")
            ->where("user_id",$this->id)
            ->where("coin_name","eth")
            ->where("status","success")
            ->whereTime("createtime","d")
            ->sum("coin_money");
        $NOWTBTC=Db::name("torder")
            ->where("user_id",$this->id)
            ->where("coin_name","btc")
            ->where("status","success")
            ->whereTime("createtime","d")
            ->sum("coin_money");
        $nowttotal=round($NOWTUSDT+$NOWTETH*$eth_usdt+$NOWTBTC*$btc_usdt,2);
        return ["sum1"=>$total,"sum2"=>$ttotal,"sum3"=>$nowtotal,"sum4"=>$nowttotal];
    }
    /**
     * K线数据
     */
    protected function kline(){
        $timedata=array();
        for($i=0;$i<=9;$i++) {
            $timedata[$i+1]["b"] =mktime(0, 0, 0, date("m")-$i, 1, date("Y"));
            $timedata[$i+1]["e"] =mktime(23, 59, 59, date("m")-$i+1, 0, date("Y"));
        }
        $timedata=array_reverse($timedata);
       foreach($timedata as $key=>$val){
           $tcount=Db::name("torder")
               ->where("user_id",$this->id)
               ->whereTime("createtime",[$val["b"],$val["e"]])
               ->where("status","success")
               ->count();
           $ccount=Db::name("corder")
               ->where("user_id",$this->id)
               ->whereTime("createtime",[$val["b"],$val["e"]])
               ->where("status","success")
               ->count();
           $data[]=['date'=>date("Y-m",$val["b"]),'number'=>$tcount+$ccount];
        }
       return json_encode($data);
    }
    /**
     * 跳转用主页
     */
    public function index2()
    {
        # 平台价
        $usdt_price = $this->get_exshop_conf('usdt_price');

        # 总提现
        $uid = $this->id;
        $torderlist = Db::name("torder")->where(['user_id'=>$uid,'status'=>'already'])->sum('coin_aumount');
        $cnylist = Db::name("cny_tixm")->where(['uid'=>$uid,'status'=>1])->sum('num');
        $tixian = ($torderlist + $cnylist) * $usdt_price;

        $data=$this->indexdata();

        $usercoin = Db::name("coin")->where(['user_id'=>$uid])->select();
        $usercoin_list = array_column($usercoin,null,'coin_name');
        $usdt_coin = $usercoin_list['usdt'];
        $usdt_total = $usdt_coin['coin_balance'] + $usdt_coin['coin_balance_t1'] + $usdt_coin['coin_balance_ex'];
        $total = $usdt_total * $usdt_price;

        $sumdata=$this->total();
        return $this->fetch("index2",[
            "coin"=>$data["coin"],              //币种数量
            "total"=>$total,            //总数量折合USDT
            "usdt_total"=>$usdt_total,
            "notice"=>$data['notice'],          //主页文章
            "sumdata"=>$sumdata,                //汇总数据
            "tixm"=>$tixian             //总提现
        ]);
    }
    protected function upload($name)
    {

        $file=$this->request->file($name);
        $info=$file->validate(["size"=>"15678","ext"=>"jpg,png,gif"])->move("../public/uploads");
        if($info){
            $problemimage=$info->getSaveName();
            $image="/uploads/".str_replace("\\","/",$problemimage);
            return ["status"=>1,"msg"=>$image];
        }else{
            return ["status"=>0,"msg"=>$file->getError()];
        }
    }
    /**
     * 商户信息
     */
    public function info(Request $request)
    {
        $field="username,nickname,type,inviter,industry,project,wechat,wechatimage,wechatname,alipay,alipayname,alipayimage,idnumber,bankname,banknumber,bank,bankaddress";
        if($request->isPost())
        {
            $data=$request->param();
            $res=db("user")
                ->where("id",$this->id)
                ->field($field)
                ->update($data);
            if($res!==false){
                return json(["status"=>1,"msg"=>"修改成功"]);
            }else {
                return json(["status" =>0, "msg" => "修改失败"]);
            }
        }
        $info=db("user")
            ->where("id",$this->id)
            ->field($field)
            ->find();
        $this->assign("info",$info);
        return $this->fetch();
    }
    /**
     * 修改密码
     */
    public function password(Request $request)
    {
        if($request->isPost())
        {
            $data=$request->param();
            $validate=validate("User");
            if(!$validate->scene("reset")->check($data)) return json(["status"=>0,"msg"=>$validate->getError()]);
            $info=db("user")
                ->where("id",$this->id)
                ->field("salt,password,username")
                ->find();
            if(empty($info)) return json(["status"=>0,"msg"=>"修改失败"]);

            $code=db("code")                     //验证验证码
                ->where(["username"=>$info['username']])
                ->order("id DESC")
                ->find();
            if(empty($code)) return json(['status' => 0, 'msg' => "请发送验证码"]);
            if(time()>$code["expiration"]) return json(['status' => 0, 'msg' => "请发送验证码"]);
            if($data['verify']!=$code["code"]) return json(['status' => 0, 'msg' => "验证码错误"]);

            $password=md5($data["oldpassword"].md5($info["salt"]));
            if($password!=$info["password"]) return json(["status"=>0,"msg"=>"旧密码错误"]);
            $data["password"]=md5($data["password"].md5($info["salt"]));
            $data["id"]=$this->id;
            $res=db("user")->field("password")->update($data);
            if($res){
                return json(["status"=>1,"msg"=>"修改密码成功"]);
            }else{
                return json(["status"=>0,"msg"=>"修改密码失败"]);
            }
        }
        $username=db("user")->where("id",$this->id)->value("username");
        return $this->fetch("password",["username"=>$username]);
    }

    /**
     * 修改支付密码
     */
    public function repassword(Request $request)
    {
        if($request->isPost())
        {
            $data=$request->param();
            $validate=validate("User");
            if(!$validate->scene("uppay")->check($data)) return json(["status"=>0,"msg"=>$validate->getError()]);
            $info=db("user")
                ->where("id",$this->id)
                ->field("salt,paypassword,username")
                ->find();
            if(empty($info)) return json(["status"=>0,"msg"=>"修改失败"]);
            $code=db("code")                                                    //验证验证码
            ->where(["username"=>$info['username']])
                ->order("id DESC")
                ->find();
            if(empty($code)) return json(['status' => 0, 'msg' => "验证码错误"]);
            if(time()>$code["expiration"]) return json(['status' => 0, 'msg' => "验证码错误"]);
            if($data['reverify']!=$code["code"]) return json(['status' => 0, 'msg' => "验证码错误"]);
            $password=md5($data["oldpaypassword"].$info["salt"]);
            if($password!=$info["paypassword"]) return json(["status"=>0,"msg"=>"原支付密码错误"]);
            $data["paypassword"]=md5($data["paypassword"].$info["salt"]);
            $data["id"]=$this->id;
            $res=db("user")->field("paypassword")->update($data);
            if($res!==false){
                return json(["status"=>1,"msg"=>"修改支付密码成功"]);
            }else{
                return json(["status"=>0,"msg"=>"修改支付密码失败"]);
            }
        }
    }

    /**
     * 代理下商户信息
     */
    public function shoplist()
    {
        $list=db("user")
            ->where("parentid",$this->id)
            ->where("type","shop")
            ->field("id,username,nickname,")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        return $this->fetch("shoplist",["page"=>$page,"list"=>$data]);
    }
    /**
     * 代理分润列表
     */
    public function bonus()
    {
        $username=db("user")
            ->where("id",$this->id)
            ->value("username");
        $list=db("agency_bonus")
            ->where("username",$username)
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        return $this->fetch("bonus",["page"=>$page,"list"=>$data]);
    }
    /**
     * API充值订单记录
     */
    public function corder(Request $request)
    {
        $order_number=$request->param("order_number");
        $coin_name=$request->param("coin_name","","strtolower");
        $status=$request->param("status","","strtolower");
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        if(!empty($order_number))$where[]=["order_number","=",$order_number];
        if(!empty($coin_name) && ($coin_name!="all")) $where[]=["coin_name","=",$coin_name];
        if(!empty($status) && ($status!="all"))  $where[] = ["status", "=", $status];
        if(!empty($beigin))$where[]=["createtime",">=",strtotime($beigin)];
        if(!empty($end))$where[]=["createtime","<=",strtotime($end)];
        $where[]=["user_id","=",$this->id];
        $where[]=["type","=","api"];
        $list=Db::name("corder")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        foreach($data as $key=>$val)
        {
            $data[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
            $data[$key]["endtime"]=date("Y-m-d H:i",$val["endtime"]);
            if(strtolower($val["coin_name"])=="usdt"){
                $data[$key]["discount"]=$val["coin_money"];
            }else{
                $name=strtolower($val["coin_name"])."_usdt";
                $bili=Db::name("change_info")->where("name",$name)->value("bili");
                $data[$key]["discount"]=round($val["coin_money"]*$bili,2);
            }
        }
        $variate=["order_number"=>$order_number,"coin_name"=>$coin_name,"status"=>$status,"beigin"=>$beigin,"end"=>$end];
        return $this->fetch("corder",["page"=>$page,"data"=>$data,"count"=>$count,"variate"=>$variate]);
    }
    /**
     * 用户提现订单记录
     */
    public function torder(Request $request)
    {

        $order_number=$request->param("order_number");
        $coin_name=$request->param("coin_name","","strtolower");
        $status=$request->param("status","","strtolower");
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        if(!empty($order_number)) $where[]=["order_number","=",$order_number];
        if(!empty($coin_name) && ($coin_name!="all")) $where[]=["coin_name","=",$coin_name];
        if(!empty($status) && ($status!="all"))  $where[] = ["status", "=", $status];
        if(!empty($beigin))$where[]=["createtime",">=",strtotime($beigin)];
        if(!empty($end))$where[]=["createtime","<=",strtotime($end)];
        $where[]=["user_id","=",$this->id];
        $where[]=["type","=","api"];
        $list=db("torder")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        foreach($data as $key=>$val)
        {
            $data[$key]["createtime"]=date("Y-m-d H:i",$val["createtime"]);
            $data[$key]["endtime"]=date("Y-m-d H:i",$val["endtime"]);
            if(strtolower($val["coin_name"])=="usdt"){
                $data[$key]["discount"]=$val["coin_money"];
            }else{
                $name=strtolower($val["coin_name"])."_usdt";
                $bili=Db::name("change_info")->where("name",$name)->value("bili");
                $data[$key]["discount"]=round($val["coin_money"]*$bili,2);
            }
        }
        $variate=["order_number"=>$order_number,"coin_name"=>$coin_name,"status"=>$status,"beigin"=>$beigin,"end"=>$end];
        /**
         * 导出数据
         */
        $status=$request->has("type","post") ?? false;
        if($status){
//            $this->export($data,$this->id);
            $this->export();
        }else{
            return $this->fetch("torder",["page"=>$page,"data"=>$data,"count"=>$count,"variate"=>$variate]);
        }
    }
    /**
     * 订单详细信息借口哦
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
     * 退出登录
     */
    public function quit()
    {
        session(null);
        $this->redirect("login/index");
    }

    /**
     * @param $data
     * 导出API提现明细表
     */
    public function export()
    {
        //导出表格
        $where[]=["user_id","=",$this->id];
        $where[]=["type","=","api"];
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
            ->setCellValue("I".$myrow, "创建时间")
            ->setCellValue("J".$myrow, "结束时间")
            ->setCellValue("K".$myrow, "交易哈希")
            ->setCellValue("L".$myrow, "状态");
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
                ->setCellValue('J' . $myrow, $value["endtime"])
                ->setCellValue('K' . $myrow, $value["txid"])
                ->setCellValue('L' . $myrow, $value["status"]);
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
        $excel->getActiveSheet()->getColumnDimension('L')->setWidth(10);
        $excel->createSheet();
        // Redirect output to a client’s web browser (Excel2007)
        $title = "明细数据-".date("Y-m-d H:i");
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



    public function pay(Request $request)
    {
        $field="username,nickname,industry,project,wechat,wechatimage,wechatname,alipay,alipayname,alipayimage,idnumber,bankname,banknumber,bank,bankaddress";
        if($request->isPost())
        {
            $data=$request->param();
            if (empty($data['user_name'])||empty($data['bank_name'])) return json(["status" =>0, "msg" => "用户名或开户银行不能为空"]);
            if ($data['type'] == 1){
                if (empty($data['bank_number']) || strlen($data['bank_number']) < 15){
                    return json(["status" =>0, "msg" => "银行卡号错误"]);
                }
            }
            $files=$this->request->file();
            if ($files){
                foreach($files as $key=>$file){
                    $info=$file->validate(["size"=>"1567800","ext"=>"jpg,png,gif"])->move("../public/uploads");
                    if($info){
                        $problemimage=$info->getSaveName();
                        $data["qrcode"]="/uploads/".str_replace("\\","/",$problemimage);
                    }else{
                        return json(["status"=>0,"msg"=>$file->getError()]);
                    }
                }
            }
            if ($data['type']==1 && !empty($data['bank_number'])){
                if (strlen($data['bank_number'])<15){
                    return json(["status" =>0, "msg" => "银行卡号错误"]);
                }
            }
            $ip = $this->request->ip();
            $data['ip'] = $ip;
            $user_bank = Db::name('user_bank')->where(["uid"=>$this->id,'type'=>$data['type']])->field('id')->find();
            if (empty($user_bank)){
                $data['uid'] = $this->id;
                $res=db("user_bank")->insert($data);
            }else{
                $res=db("user_bank")->where("id", $user_bank['id'])->update($data);
            }
            if($res!==false){
                return json(["status"=>1,"msg"=>"修改成功"]);
            }else {
                return json(["status" =>0, "msg" => "修改失败"]);
            }
        }
        $info=db("user_bank")->where("uid",$this->id)->select();
        if (!empty($info)){
            $info = array_column($info,null,'type');
        }
        $this->assign("info",$info);
        return $this->fetch();
    }

    # 删除二维码图片
    public function delqrcode()
    {
        $id = input("id");
        $type = input("type");

        $res =Db::name("user_bank")->where(["id"=>$id,'type'=>$type])->delete();
        if($res){
            return json(["status"=>1,"msg"=>"修改成功"]);
        }else {
            return json(["status" =>0, "msg" => "修改失败"]);
        }
    }


    # 获取用户协议
    public function get_gkvi()
    {
        $uid = $this->id;
        $type = session('utype');
        $is_gkvi = session('is_gkvi');
        $arr = ['shop','exshop'];
        if (!in_array($type,$arr)) return json(['status'=>'error']);
        $user = Db::name('user')->where("id={$uid} and is_gkvi={$is_gkvi}")->field("id,is_gkvi")->find();
        if (empty($user) || $user['is_gkvi']!=0)  return json(['status'=>'error']);
        $gkvi = Db::name('article')->where(['type'=>'fuwu','id'=>8])->find();

        return json(['status'=>'ok','data'=>$gkvi]);
    }
    # 同意用户协议
    public function gkviac()
    {
        $uid = $this->id;
        $typeac = input('typeac');
        if (!in_array($typeac,[0,1,2])) return json(['status'=>'error','errormsg'=>'稍后再试']);

        Db::name('user')->where("id={$uid}")->update(['is_gkvi'=>$typeac]);
        session("is_gkvi", 1);
        return json(['status'=>'ok']);
    }
}