<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\admincoin\model\Corder;
use app\admincoin\model\Torder;
use think\Db;
use think\route\Resource;

class OrderManage extends Base
{
    /**
     * 充值列表
     *
     * @return \think\Response
     */
    public function rechargeOrder(Request $request)
    {
        $where=[];
        $order_number=$request->param("order_number");
        $serial_number=$request->param("serial_number");
        $coin_name=$request->param("coin_name","","strtolower");
        $status=$request->param("status");
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        if($order_number || $serial_number)
        {
            $order_number && $where[]=["c.order_number","=",$order_number];
            $serial_number && $where[]=["c.serial_number","=",$serial_number];
        }else{
            $coin_name && $coin_name!="all" && $where[]=["c.coin_name","=",$coin_name];
            $status && $status!="all" && $where[]=["c.status","=",$status];
            $beigin && $where[]=["c.createtime",">=",strtotime($beigin)];
            $end && $where[]=["c.createtime","<=",strtotime($end)];
        }
        $list   = Corder::alias('c')
            ->where($where)
            ->join('coinpay_user u','u.id=c.user_id')
            ->field('c.*,u.username')
            ->paginate(10)
            ->each(function($item,$Key){
                if($item->coin_name=="usdt"){
                    $item->usdt=round($item->coin_money,2);
                }else{
                    $name=$item->coin_name."_usdt";
                    $bili=Db::name("change_info")->where("name",$name)->value("bili");
                    $item->usdt=round($item->coin_money*$bili,2);
                }
            });
        $variate=[
            "order_number"=>$order_number,
            "serial_number"=>$serial_number,
            "coin_name"=>$coin_name,
            "status"=>$status,
            "beigin"=>$beigin,"end"=>$end
        ];
        $this->assign("variate",$variate);
        $this->assign('list',$list);
        $this->assign('count',$list->total());
        return view();
    }
    /**
     * 充值详情
     * @param Request $request
     * @return \think\response\View
     */
    public function corderdetail(Request $request)
    {
        $id=$request->param("id","","intval");
        $info=Corder::where("id",$id)->field("id",true)->find();
        if(empty($info)){
            return json(["status"=>0,"data"=>$info,"msg"=>"请求成功"]);
        }else{
            return json(["status"=>1,"data"=>$info,"msg"=>"请求失败"]);
        }
    }
    /**
     * 提现列表.
     *
     * @return \think\Response
     */             
    public function withdrawOrder(Request $request)
    {
        $where=[];
        $order_number=$request->param("order_number");
        $serial_number=$request->param("serial_number");
        $coin_name=$request->param("coin_name","","strtolower");
        $status=$request->param("status");
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        if($order_number || $serial_number)
        {
            $order_number && $where[]=["t.order_number","=",$order_number];
            $serial_number && $where[]=["t.serial_number","=",$serial_number];
        }else{
            $coin_name && $coin_name!="all" && $where[]=["t.coin_name","=",$coin_name];
            $status && $status!="all" && $where[]=["t.status","=",$status];
            $beigin && $where[]=["t.createtime",">=",strtotime($beigin)];
            $end && $where[]=["t.createtime","<=",strtotime($end)];
        }
        $list   = Torder::alias('t')
            ->where($where)
            ->join('coinpay_user u','u.id=t.user_id')
            ->field('t.*,u.username')
            ->order('id desc')
            ->paginate(10)
            ->each(function($item,$key){
                if($item->coin_name=="usdt"){
                    $item->usdt=round($item->coin_money,2);
                }else{
                    $name=$item->coin_name."_usdt";
                    $bili=Db::name("change_info")->where("name",$name)->value("bili");
                    $item->usdt=round($item->coin_money*$bili,2);
                }
            });
        $variate=[
            "order_number"=>$order_number,
            "serial_number"=>$serial_number,
            "coin_name"=>$coin_name,
            "status"=>$status,
            "beigin"=>$beigin,"end"=>$end
        ];
        $this->assign('list',$list);
        $this->assign("variate",$variate);
        $this->assign('count',$list->total());
        return view();
    }
    /**
     * 提现详情
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function torderdetail(Request $request)
    {
        $id=$request->param("id","","intval");
        $info=Torder::where("id",$id)->field("id",true)->find();
        if(empty($info)){
            return json(["status"=>0,"msg"=>"请求失败"]);
        }else{
            return json(["status"=>1,"data"=>$info,"msg"=>"请求成功"]);
        }
    }
    /**
     * 处理提现
     */
    public function fangxing(Request $request)
    {
        $id=$request->param("id","","intval");
        $torder = Db::name('torder')->field('coin_name,coin_aumount,address,status')->where(['id'=>$id,'status'=>'check'])->find();
        if (empty($torder)) return json(["status"=>0,"msg"=>"异常订单"]);
        $coin_en = $torder['coin_name'];
        $to_addr = $torder['address'];
        $to_num = $torder['coin_aumount'];
        $coin_info=db("coin_config")->where("name",$coin_en)->field("id,type,hostname,port,username,password,status")->find();
        if(empty($coin_info)) return json(["status"=>0,"msg"=>"币种不存在"]);
        if($coin_info["status"]=="hidden") return json(["status"=>0,"msg"=>"币种未开放"]);

        $host = $coin_info['hostname'];
        $port = $coin_info['port'];
        $username = $coin_info['username'];
        $password = $coin_info['port'];
        if (empty($host)||empty($port)||empty($username)||empty($password)) return json(["status"=>0,"msg"=>"币种配置错误"]);
        if ($coin_en=='btc'||$coin_en='usdt'){ # 比特类
            $client = new \coin\Btc($username,$password,$host,$port);
            if (!$client->getblockchaininfo()) return json(["status"=>0,"msg"=>"{$coin_en}节点链接出错"]);
            $valid_res = $client->validateaddress($to_addr);
            if(!$valid_res['isvalid'])return json(["status"=>0,"msg"=>"无效地址"]);

            /*$btc_password = config("coin.btc_password");
            if(!empty($btc_password))
                $client->walletpassphrase($btc_password);*/

            if($coin_en == 'btc'){//主币
                $txid = $client->sendtoaddress($to_addr, (double)$to_num);
            }else if($coin_en == 'usdt'){//代币
                $pingtai_coin_addr = config("coin.pingtai_usdt_addr");
                if(empty($pingtai_coin_addr)) return json(["status"=>0,"msg"=>"{$coin_en}配置异常"]);
                $txid = $client->omni_send($pingtai_coin_addr,$to_addr,31,$to_num);
            }else{
                return json(["status"=>0,"msg"=>"{$coin_en}配置异常"]);
            }

            $client->walletlock();

        }elseif($coin_en=='eth'){ # 以太类
            $client = new \lib\Eth($host,$port);
            if(empty($client->web3_clientVersion())) return json(["status"=>0,"msg"=>"{$coin_en}节点链接出错"]);
            $pingtai_coin_addr = config("coin.pingtai_eth_addr");
            if(empty($pingtai_coin_addr)) return json(["status"=>0,"msg"=>"{$coin_en}配置异常"]);
            $txid = $client->eth_sendTransaction($pingtai_coin_addr,$to_addr,$pingtai_coin_addr,$to_num);
        }else{
            return json(["status"=>0,"msg"=>"{$coin_en}配置异常"]);
        }


        if(empty($txid))return json(["status"=>0,"msg"=>"转账失败，请稍后再试"]);

        $res = Db::name('torder')->where(["id"=>$id,'status','check'])->update(['status'=>'already','txid'=>$txid]);
        if ($res){
            return json(["status"=>1,"msg"=>"放行成功"]);
        }else{
            return json(["status"=>0,"msg"=>"放行失败"]);
        }
    }
    # 提现列表
    public function tixmcny_list(Request $request)
    {
        $where='';
        $order_no=$request->param("order_number");
        $coin_en=$request->param("coin_name","","strtolower");
        $status=$request->param("status");

        if ($order_no){
            $where=['order_no'=>$order_no];
        }

        if ($coin_en){
            $where=['coin_en'=>$coin_en];
        }

        if ($status){
            $where=['status'=>$status];
        }

        $list   = Db::name('cny_tixm')->where($where)->order('id desc')->paginate(10);
        $data = $list->all();

        if ($data){
            foreach ($data as $k=>$v){
                $user = Db::name("user")->where(['id'=>$v['uid']])->field('username,nickname')->find();
                $data[$k]['userinfo'] = $user;
                $bank =  Db::name("user_bank")->where(['uid'=>$v['uid'],'type'=>$v['pay_type']])->field('user_name,bank_name,bank_number,qrcode')->find();
                $data[$k]['bankinfo'] = $bank;
            }
        }

        $variate=[
            "order_number"=>$order_no,
            "coin_name"=>$coin_en,
            "status"=>$status,
        ];
        $this->assign('data',$data);
        $this->assign('list',$list);
        $this->assign("variate",$variate);
        $this->assign('count',$list->total());
        return view();
    }
    # 提现放行
    public function tixmcnyac(Request $request)
    {
        if($this->request->isPost())
        {
            $id = (int)input("id");
            $cny_tixm = Db::name("cny_tixm")->where(["id"=>$id])->find();
            if ($cny_tixm){
                $res = Db::name("cny_tixm")->where(["id"=>$id])->update(['status'=>1,'end_time'=>_getTime()]);
                return json(["status" => 1, "msg" => "放行成功"]);
            }
        }
        return json(["status" => 0, "msg" => "放行失败"]);
    }

    public function tixm_iakan()
    {
        if($this->request->isPost()) {
            $id = (int)input("id");
            $tixm_info = Db::name('cny_tixm')->where("id={$id}")->field("uid,pay_type")->find();
            if (empty($tixm_info)) return json(["status" => 0, "msg" => "异常订单"]);
            $user_bank = Db::name('user_bank')->where("uid={$tixm_info['uid']} and type={$tixm_info['pay_type']}")->field("type,user_name,bank_name,bank_number,qrcode")->find();
            $tmp = [1=>'银联',2=>'支付宝',3=>'微信'];
            $user_bank['bank_type_cn'] = $tmp[$user_bank['type']];
            return json($user_bank);
        }
    }
    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
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
