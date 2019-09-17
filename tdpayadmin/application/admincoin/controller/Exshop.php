<?php

namespace app\admincoin\controller;

use app\admincoin\model\CoinLog;
use app\admincoin\model\UserM;
use think\Db;
use think\Request;

class Exshop extends Base
{
    # 承兑商奖励记录
    public function coinlog()
    {
        $admin_uid_str = $this->get_yonghu_uid();
        $where = [];
        if ($admin_uid_str){
            $where[]=['id','in',($admin_uid_str)];
        }
        $where[]=['type','in',[9]];
        $list   = CoinLog::where($where)->order('id','desc')->alias('a')->paginate(10)->each(function($item,$key)
        {
            $item->username=UserM::where("id",$item->user_id)->value("username");
        });
        $this->assign('list',$list);
        $this->assign('count',$list->total());
        return view('coinlog');
    }
    # 出售列表
    public function sell_list(Request $request)
    {
        $admin_uid_str = $this->get_yonghu_uid();
        if ($admin_uid_str){
            $where[]=['uid','in',($admin_uid_str)];
        }
        $where[] = ['type','=',0];

        $list = Db::name("excoin_sell")->where($where)->order("id DESC")->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();
        foreach ($data as $key => $val) {
            $user = Db::name('user')->where(['id'=>$val['uid']])->field('username,nickname')->find();
            $data[$key]['user']= $user;
        }
        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval")
        ];

        return view("sell_list", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate]);
    }

    # 购买列表
    public function buy_list(Request $request)
    {
        $where['type'] = 1;
        $list = Db::name("excoin_buy")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();
        foreach ($data as $key => $val) {
            $user = Db::name('user')->where(['id'=>$val['uid']])->field('username,nickname')->find();
            $data[$key]['user']= $user;
        }
        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval")
        ];
        return view("buy_list", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate]);
    }

    # 出售订单列表
    public function sell_listac(Request $request)
    {


        $status=input('status');
        $order_no=input('order_no');
        $liushui_no=input('liushui_no');
        $beigin=$request->param("beigin");
        $end=$request->param("end");

        $beigin && $map[]=["time",">=",$beigin];
        $end && $map[]=["time","<=",$end];

        if (!empty($order_no)){
            $map[]=['order_no','=',$order_no];
        }
        if (!empty($liushui_no)){
            $map[]=['liushui_no','=',$liushui_no];
        }

        $arr = [0,1,2,3,4,5];
        if ($status!=null && $status >= 0 && in_array($status, $arr)){
            $map[] = ['status','=',$status];
        }
        $map[] = ['buy_id','<>',0];

        $admin_uid_str = $this->get_yonghu_uid();
        if ($admin_uid_str){
            $map[]=['match_uid','in',($admin_uid_str)];
        }

        $list = Db::name("excoin_match")->where($map)->order("id DESC")->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();
        $pingtai_price = $this->get_exshop_conf('usdt_price');
        $tmp = [0=>'','1'=>'<img src="/assets/img/pay/bank.png">','2'=>'<img src="/assets/img/pay/alipay.png">','3'=>'<img src="/assets/img/pay/wechatpay.png">'];
        foreach ($data as $key => $val) {
            $user = Db::name('user')->where(['id'=>$val['match_uid']])->field('username,nickname,exshop_code')->find();
            $data[$key]['user']= $user;
            $data[$key]['type']=$tmp[$val['type']];
            $data[$key]['price_cny']=number_format($val['price']/$val['num'],6,'.','');
            $data[$key]['shop_fee_cny'] = $val['shop_fee'] * $pingtai_price;
            $data[$key]['true_num_cny'] = $val['true_num'] * $pingtai_price;
        }
        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval"),
            "status" => $request->param("status", "", "status"),
        ];
        $variate=["beigin"=>$beigin,"end"=>$end,'order_no'=>$order_no,'liushui_no'=>$liushui_no,'status'=>$status];
        $data_s = Db::name("excoin_match")->where($map)->order("id DESC")->select();
        $true_num = array_sum(array_column($data_s,'true_num'));
        $true_num_cny = $true_num * $pingtai_price;
        $shop_fee = array_sum(array_column($data_s,'shop_fee'));
        $shop_fee_cny = $shop_fee* $pingtai_price;
        $price_zj = array_sum(array_column($data_s,'price'));

        return view("sell_listac", [
            "page" => $page, "data" => $data, "count" => $count, "variate" => $variate,
            "true_num" => $true_num, "true_num_cny" => $true_num_cny,"shop_fee"=>$shop_fee,"shop_fee_cny"=>$shop_fee_cny,"price_zj"=>$price_zj
        ]);
    }

    # 订单下架
    public function sell_matchac(Request $request){
        $sell_order = false;
        if($this->request->isPost()) {
            $id = (int)input("match_id");
            $status = input("status");
            # 1上架 0下架
            if ($status=='on'){
                $sell_order = Db::name("excoin_sell")->where(['id'=>$id,'is_match'=>0])->update(['is_match'=>1]);
            }
            if ($status=='off'){
                $sell_order = Db::name("excoin_sell")->where(['id'=>$id,'is_match'=>1])->update(['is_match'=>0]);
            }
        }
        if ($sell_order){
            return json(["status" => 1, "msg" => '执行成功']);
        }else{
            return json(["status" => 0, "msg" => '执行失败']);
        }
    }
    # 回调通知
    public function callbackac(Request $request){
        if ($this->request->isPost()){
            $id = (int)input('id');
            $act = controller("Crontab");
            $rs = $act->call_back_now($id);
            if ($rs){
                return json(["status" => 1, "msg" => "成功"]);
            }else{
                return json(["status" => 0, "msg" => "失败"]);
            }
        }
    }

    # 求购订单列表
    public function buy_listac(Request $request)
    {
        $where['type'] = 1;
        $buy_data = Db::name("excoin_buy")->where($where)->select();

        $buy_id = array_column($buy_data,'id');
        $buy_id_str = implode($buy_id,',');
        $list = Db::name("excoin_match")
            ->where("buy_id in({$buy_id_str})")
            ->order("id DESC")
            ->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();

        foreach ($data as $key => $val) {
            $sellinfo = Db::name('excoin_sell')->where("id={$val['sell_id']}")->field("bank_type,bank_name,bank_user_name,bank_number,bank_qrcode")->find();
            if ($sellinfo['bank_type']==1){
                $payinfo=$sellinfo['bank_number'];
            }else{
                $payinfo=$sellinfo['bank_qrcode'];
            }
            $data[$key]['payinfo'] = $payinfo;
            $data[$key]['bank_user_name'] = $sellinfo['bank_user_name'];

            $user = Db::name('user')->where(['id'=>$val['match_uid']])->field('username,nickname')->find();
            $data[$key]['user']= $user;
        }

        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval")
        ];

        return view("buy_listac", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate]);
    }

    # 查看支付方式
    public function iakjinfo()
    {
        if($this->request->isPost()) {
            $sell_id = (int)input("sell_id");
            $sell_info = Db::name('excoin_sell')->where("id={$sell_id}")->field("bank_type,bank_user_name,bank_qrcode")->find();
            if (empty($sell_info)) return json(["status" => 0, "msg" => "异常订单"]);
            $tmp = ['银联','支付宝','微信'];
            $sell_info['bank_type_cn'] = $tmp[$sell_info['bank_type']];
            return json($sell_info);
        }
    }

    # 投诉订单列表
    public function tousu(Request $request)
    {

        $admin_uid_str = $this->get_yonghu_uid();
        if ($admin_uid_str){
            $where[]=['uid','in',($admin_uid_str)];
        }

        $list = Db::name("excoin_tousu")
            ->order("id DESC")
            ->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();
        foreach ($data as $key => $val) {
            $user = Db::name('user')->where(['id'=>$val['uid']])->field('username,nickname')->find();
            $matchinfo = Db::name('excoin_match')->where(['id'=>$val['match_id']])->field('id,order_no,type,num,price,time,dakuan_time,queren_time,status')->find();
            $data[$key]['user']= $user;
            $data[$key]['matchinfo']= $matchinfo;
            $data[$key]['pprice']= number_format($matchinfo['price']/$matchinfo['num'],2,'.','');
        }
        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval")
        ];
        return view("tousu", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate]);
    }

    # 重置订单
    public function reset(Request $request)
    {
        if($this->request->isPost()) {
            $id = (int)input("id");
            $tousu_info = Db::name('excoin_tousu')->field('id,match_id,status')->where(['id'=>$id])->find();
            if(empty($tousu_info) || $tousu_info['status'] != 0)return json(["status" => 0, "msg" => "投诉订单异常"]);

            $match_info = Db::name('excoin_match')->field('id,buy_id,sell_id,app_uid,match_uid,num,status')->where(['id'=>$tousu_info['match_id']])->find();

            if(empty($match_info) || $match_info['status'] != 5)return json(["status" => 0, "msg" => "订单异常"]);

            $sell_order = Db::name('excoin_sell')->where("id={$match_info['sell_id']}")->find();
            if(empty($sell_order))return json(["status" => 0, "msg" => "卖出订单异常"]);

            $buy_order = Db::name('excoin_buy')->where("id={$match_info['buy_id']}")->find();
            if(empty($buy_order))return ['status'=>"error","errorcode"=>"245","errormsg"=>"买入订单异常"];

            Db::startTrans();
            $rs[] = Db::name('excoin_match')->where(['id'=>$match_info['id']])->update(['status'=>4,'callback_status'=>0]);

            $rs[] = Db::name('excoin_buy')->where(['id'=>$match_info['buy_id']])->setDec('match_num',$match_info['num']);
            $rs[] = Db::name('excoin_buy')->where(['id'=>$match_info['buy_id']])->setInc('shengyu_num',$match_info['num']);

            $rs[] = Db::name('excoin_sell')->where(['id'=>$match_info['sell_id']])->setDec('match_num',$match_info['num']);
            $rs[] = Db::name('excoin_sell')->where(['id'=>$match_info['sell_id']])->setInc('shengyu_num',$match_info['num']);

            if (check_arr($rs)){
                Db::commit();
                Db::execute("UPDATE `coinpay_excoin_buy` SET `status`=2 WHERE id={$match_info['buy_id']} AND `shengyu_num`=`num`");
                Db::name('exshop_msg')->where(['match_id'=>$match_info['id']])->update(['status'=>1]);
                return json(["status" => 1, "msg" => "执行成功"]);
            }else{
                Db::rollback();
                return json(["status" => 0, "msg" => "执行失败"]);
            }
        }
    }

    # 确认订单
    public function down(Request $request)
    {
        if($this->request->isPost()) {
            $id = (int)input("id");
            $tousu_info = Db::name('excoin_tousu')->field('id,match_id,status')->where(['id'=>$id])->find();
            if(empty($tousu_info) || $tousu_info['status'] != 0)return json(["status" => 0, "msg" => "投诉订单异常"]);

            $match_info = Db::name('excoin_match')->field('id,buy_id,sell_id,app_uid,match_uid,num,status')->where(['id'=>$tousu_info['match_id'],'status'=>5])->find();
            if(empty($match_info))return json(["status" => 0, "msg" => "订单异常12"]);

            $match_id = $match_info['id'];
            $exshop_uid = $match_info['match_uid']; # 承兑商
            $exshop_info = Db::name('user')->where(['id'=>$exshop_uid,'type'=>'exshop'])->field('username,parentid')->find();
            if (empty($exshop_info)) return json(["status" => 0, "msg" => "承兑商账号异常，请联系平台管理员"]);
            $exshop_puid = $exshop_info['parentid']; # 承兑商代理

            $shop_uid = $match_info['app_uid']; # 商户
            $sell_id = $match_info['sell_id'];
            $buy_id = $match_info['buy_id'];
            $num = $match_info['num'];

            $sell_order = Db::name('excoin_sell')->where("id={$sell_id}")->find();
            if(empty($sell_order))return json(["status" => 0, "msg" => "卖出订单异常"]);

            $buy_order = Db::name('excoin_buy')->where("id={$buy_id}")->find();
            if(empty($buy_order))return ['status'=>"error","errorcode"=>"245","errormsg"=>"买入订单异常"];

            $shop_info = Db::name('user')->where(['id'=>$shop_uid])->field('username,parentid,shop_pid')->find();
            $shop_puid = $shop_info['parentid']; # 商户代理
            $shop_zjs_uid = $shop_info['shop_pid']; # 商户中间商代理

            # 手续费奖励
            $dakuan_time = $this->get_exshop_conf('dakuan_time');
            if ($dakuan_time< 0) return json(["code" => 204, "result" => "稍后再试"]);


            $exshop_arr = $this->get_conf_fee($exshop_uid); #承兑商
            if (empty($exshop_arr['exshop_reward'])) $exshop_arr['exshop_reward']=0;
            if (empty($exshop_arr['daili_reward'])) $exshop_arr['daili_reward']=0;

            $shop_arr = $this->get_conf_fee($shop_uid); #商户
            if (empty($shop_arr['shop_fee'])) $shop_arr['shop_fee']=0;
            if (empty($shop_arr['daili_reward'])) $shop_arr['daili_reward']=0;
            if (empty($shop_arr['shop_reward'])) $shop_arr['shop_reward']=0;

            $shop_parr = $this->get_conf_fee($shop_puid); # 商户代理参数
            $exshop_parr = $this->get_conf_fee($exshop_puid); # 承兑商代理参数
            $shop_zjs_parr = $this->get_conf_fee($shop_zjs_uid); # 商户中间代理商参数

            $shop_fee = $shop_arr['shop_fee'] * $num;
            $return_shop_num = $num - $shop_fee;
            $return_shop_dalli_reward = $shop_arr['daili_reward'] * $num;
            $return_shop_zjs_reward = $shop_arr['shop_reward'] * $num;
            $return_exshop_reward = $exshop_arr['exshop_reward'] * $num;
            $return_exshop_daili_reward = $exshop_arr['daili_reward'] * $num;
            $return_shop_num_t0=0;
            $return_shop_num_t1=0;
            $return_shop_dalli_reward_t0=0;
            $return_shop_dalli_reward_t1=0;
            $return_exshop_reward_t0=0;
            $return_exshop_reward_t1=0;
            $return_exshop_daili_reward_t0=0;
            $return_exshop_daili_reward_t1=0;
            $return_shop_reward_t0=0;
            $return_shop_reward_t1=0;

            if ($shop_zjs_uid>0){ # 商户中间商
                $shop_zjs_info = Db::name("user")->where(['id'=>$shop_zjs_uid])->field("id,username")->find();
                if ($shop_zjs_info && $shop_zjs_parr['day_tixm_fee']>0){
                    $return_shop_reward_t0 = $return_shop_zjs_reward * $shop_zjs_parr['day_tixm_fee'];
                    $return_shop_dalli_reward_t1 = $return_shop_zjs_reward - $return_shop_reward_t0;
                }else{
                    $return_shop_reward_t0 = $return_shop_zjs_reward;
                }
            }

            if ($shop_arr['day_tixm_fee']>0){
                $return_shop_num_t0 = $return_shop_num * $shop_arr['day_tixm_fee'];
                $return_shop_num_t1 = $return_shop_num - $return_shop_num_t0;
            }else{
                $return_shop_num_t0 = $return_shop_num;
            }

            if ($shop_parr['day_tixm_fee']>0){
                $return_shop_dalli_reward_t0 = $return_shop_dalli_reward * $shop_parr['day_tixm_fee'];
                $return_shop_dalli_reward_t1 = $return_shop_num - $return_shop_dalli_reward_t0;
            }else{
                $return_shop_dalli_reward_t0 = $return_shop_dalli_reward;
            }

            if ($exshop_arr['day_tixm_fee']>0){
                $return_exshop_reward_t0 = $return_exshop_reward * $exshop_arr['day_tixm_fee'];
                $return_exshop_reward_t1 = $return_shop_num - $return_exshop_reward_t0;
            }else{
                $return_exshop_reward_t0 = $return_exshop_reward;
            }

            if ($exshop_parr['day_tixm_fee']>0){
                $return_exshop_daili_reward_t0 = $return_exshop_daili_reward * $exshop_parr['day_tixm_fee'];
                $return_exshop_daili_reward_t1 = $return_shop_num - $return_exshop_daili_reward_t0;
            }else{
                $return_exshop_daili_reward_t0 = $return_exshop_daili_reward;
            }

            $ip = $this->request->ip();
            Db::startTrans();
            $rs[] = Db::name('coin')->where("user_id={$sell_order['uid']} and coin_balance_ex>{$num}")->setDec('coin_balance_ex',$num);

            $rs[] = Db::name('excoin_match')->where("id={$match_id} and status=5")->update([
                'shop_fee'=>$shop_fee,'true_num'=>$return_shop_num,'exshop_reward'=>$return_exshop_reward,
                'status' => 2, 'queren_time'=>_getTime(),'callback_status'=>0
            ]);

            $rs[] = Db::name('excoin_buy')->where("id={$buy_id}")->setInc('queren_num',$num);
            $rs[] = Db::execute("UPDATE `coinpay_excoin_sell` SET `queren_num`=`queren_num`+{$num},`match_num`=`match_num`+{$num} WHERE id={$sell_id}");

            # 商户
            if ($return_shop_num_t0>0){
                $shop_info = Db::name('coin')->where("user_id={$shop_uid} and coin_name='usdt'")->find();
                if (empty($shop_info)){
                    $rs[] = Db::name('coin')->insert(['user_id'=>$shop_uid,'coin_name'=>'usdt','coin_balance'=>$return_shop_num_t0,'coin_balance_t1'=>$return_shop_num_t1]);
                }else{
                    $rs[] = Db::name('coin')->where("id={$shop_info['id']}")->setInc('coin_balance',$return_shop_num_t0);
                    if ($return_shop_num_t1>0){
                        $rs[] = Db::name('coin')->where("id={$shop_info['id']}")->setInc('coin_balance_t1',$return_shop_num_t1);
                    }
                }
                $rs[] = Db::name('coin_log')->insert([
                    'user_id'=>$match_info['app_uid'],'coin_name'=>'usdt','coin_money'=>$return_shop_num,
                    'type'=>11,'action'=>'商户收款','ip'=>$ip,'createtime'=>time(),
                ]);
                if ($shop_fee > 0){
                    $rs[] = Db::name('coin_log')->insert([
                        'user_id'=>$match_info['app_uid'],'coin_name'=>'usdt','coin_money'=>$shop_fee,
                        'type'=>12,'action'=>'商户收款扣除手续费','ip'=>$ip,'createtime'=>time(),
                    ]);
                }
            }
            # 商户代理
            if ($return_shop_dalli_reward_t0 > 0 && $shop_puid > 0) {
                $shop_uinfo = Db::name('coin')->where("user_id={$shop_puid} and coin_name='usdt'")->find();
                if (empty($shop_uinfo)){
                    $rs[] = Db::name('coin')->insert(['user_id'=>$shop_puid,'coin_name'=>'usdt','coin_balance'=>$return_shop_dalli_reward_t0,'coin_balance_t1'=>$return_shop_dalli_reward_t1]);
                }else{
                    $rs[] = Db::name('coin')->where("id={$shop_uinfo['id']}")->setInc('coin_balance',$return_shop_dalli_reward_t0);
                    if ($return_shop_dalli_reward_t1>0){
                        $rs[] = Db::name('coin')->where("id={$shop_uinfo['id']}")->setInc('coin_balance_t1',$return_shop_dalli_reward_t1);
                    }
                }
                $rs[] = Db::name('coin_log')->insert([
                    'user_id'=>$shop_puid,'coin_name'=>'usdt','coin_money'=>$return_shop_dalli_reward,
                    'type'=>17,'action'=>'商户代理奖励','ip'=>$ip,'createtime'=>time(),
                ]);
                $rs[] = Db::name('reward_zhangdan')->insert([
                    'from_uid' => $shop_uid, 'to_uid' => $shop_puid, 'match_id' => $match_id,
                    'coin_en'  => 'usdt', 'num' => $return_shop_dalli_reward
                ]);
            }

            # 商户中间商代理奖励
            if ($return_shop_reward_t0 > 0 && $shop_zjs_uid > 0) {
                $shop_zjs_uinfo = Db::name('coin')->where("user_id={$shop_zjs_uid} and coin_name='usdt'")->find();
                if (empty($shop_zjs_uinfo)){
                    $rs[] = Db::name('coin')->insert(['user_id'=>$shop_zjs_uid,'coin_name'=>'usdt','coin_balance'=>$return_shop_reward_t0,'coin_balance_t1'=>$return_shop_reward_t1]);
                }else{
                    $rs[] = Db::name('coin')->where("id={$shop_zjs_uinfo['id']}")->setInc('coin_balance',$return_shop_reward_t0);
                    if ($return_shop_reward_t1>0){
                        $rs[] = Db::name('coin')->where("id={$shop_zjs_uinfo['id']}")->setInc('coin_balance_t1',$return_shop_reward_t1);
                    }
                }
                $rs[] = Db::name('coin_log')->insert([
                    'user_id'=>$shop_zjs_uid,'coin_name'=>'usdt','coin_money'=>$return_shop_zjs_reward,
                    'type'=>8,'action'=>'商户中间商奖励','ip'=>$ip,'createtime'=>time(),
                ]);
                $rs[] = Db::name('reward_zhangdan')->insert([
                    'from_uid' => $shop_uid, 'to_uid' => $shop_zjs_uid, 'match_id' => $match_id,
                    'coin_en'  => 'usdt', 'num' => $return_shop_zjs_reward,'type'=>1
                ]);
            }


            # 承兑商
            if ($return_exshop_reward_t0>0){
                $exshop_info = Db::name('coin')->where("user_id={$exshop_uid} and coin_name='usdt'")->find();
                if (empty($exshop_info)){
                    $rs[] = Db::name('coin')->insert([
                        'user_id'=>$exshop_uid,'coin_name'=>'usdt','coin_balance'=>$return_exshop_reward_t0,'coin_balance_t1'=>$return_exshop_reward_t1,
                    ]);
                }else{
                    $rs[] = Db::name('coin')->where("id={$exshop_info['id']}")->setInc('coin_balance',$return_exshop_reward_t0);
                    if ($return_exshop_reward_t1>0){
                        $rs[] = Db::name('coin')->where("id={$exshop_info['id']}")->setInc('coin_balance_t1',$return_exshop_reward_t1);
                    }
                }
                if ($return_exshop_reward>0){
                    $rs[] = Db::name('coin_log')->insert([
                        'user_id'=>$exshop_uid,'coin_name'=>'usdt','coin_money'=>$return_exshop_reward,
                        'type'=>9,'action'=>'出售订单奖励1','ip'=>$ip,'createtime'=>time(),
                    ]);
                }
            }
            # 承兑商代理
            if ($return_exshop_daili_reward_t0 > 0 && $exshop_puid > 0) {
                $puser_info = Db::name('user')->where(['id' => $exshop_puid])->field('id,username,parentid')->find();
                if ($puser_info) {
                    $parent_info = Db::name('coin')->where("user_id={$exshop_puid} and coin_name='usdt'")->find();
                    if (empty($parent_info)) {
                        $rs[] = Db::name('coin')->insert(['user_id'=>$exshop_puid,'coin_name' => 'usdt', 'coin_balance' => $return_exshop_daili_reward_t0,'coin_balance_t1' => $return_exshop_daili_reward_t1]);
                    } else {
                        $rs[] = Db::name('coin')->where("id={$parent_info['id']}")->setInc('coin_balance', $return_exshop_daili_reward_t0);
                        if ($return_exshop_daili_reward_t1>0){
                            $rs[] = Db::name('coin')->where("id={$parent_info['id']}")->setInc('coin_balance_t1', $return_exshop_daili_reward_t1);
                        }
                    }

                    $rs[] = Db::name('coin_log')->insert([
                        'user_id' => $exshop_puid, 'coin_name' => 'usdt', 'coin_money' => $return_exshop_daili_reward,
                        'type'    => 8, 'action' => '承兑商代理奖励', 'ip' => $ip, 'createtime' => time(),
                    ]);
                    $rs[] = Db::name('reward_zhangdan')->insert([
                        'from_uid' => $exshop_uid, 'to_uid' => $exshop_puid, 'match_id' => $match_id,
                        'coin_en'  => 'usdt', 'num' => $return_exshop_daili_reward
                    ]);
                }
            }

            $rs[] = Db::name('excoin_tousu')->where(['id'=>$tousu_info['id']])->update(['status'=>1,'endtime'=>_getTime()]);
            if (check_arr($rs)){
                Db::commit();
                Db::execute("UPDATE `coinpay_excoin_buy` SET `status`=4 WHERE id={$buy_id} AND `queren_num`=`num`");
                Db::execute("UPDATE `coinpay_excoin_sell` SET `status`=4 WHERE id={$sell_id} AND `queren_num`=`num`");
                Db::name('exshop_msg')->where(['match_id'=>$match_id])->update(['status'=>1]);

                $act = controller("Crontab");
                $rs = $act->call_back_now($match_info['buy_id']);

                return json(["status" => 1, "msg" => "执行成功"]);
            }else{
                Db::rollback();
                return json(["status" => 0, "msg" => "执行失败"]);
            }
        }
    }

    # 取消挂单
    public function sell_quxc(Request $request)
    {
        if($this->request->isPost()) {
            $id = (int)input("id");

            $sell_order = Db::name('excoin_sell')->where("id={$id} and status=0")->field("id,uid,name_en,num")->find();
            if(empty($sell_order))return json(["status" => 0, "msg" => "订单异常"]);

            $sell_info = Db::name('user')->where(['id'=>$sell_order['uid'],'type'=>'exshop'])->field('username,parentid')->find();
            if (empty($sell_info)) return json(["status" => 0, "msg" => "账号异常"]);

            $coin_en =  $sell_order['name_en'];
            $num = $sell_order['num'];
            $sell_coin = Db::name('coin')->where("user_id={$sell_order['uid']} and coin_name='{$coin_en}' and coin_balance_ex>={$num}")->field('id,coin_balance,coin_balance_ex')->find();
            if(empty($sell_coin))return json(["status" => 0, "msg" => "币种数量不够"]);
            $ip = $this->request->ip();

            Db::startTrans();
            $rs[] = Db::name('excoin_sell')->where(['id'=>$id,'status'=>0])->update(['status'=>2]);
            $rs[] = Db::execute("UPDATE `coinpay_coin` SET `coin_balance_ex`=`coin_balance_ex`-{$num},`coin_balance`=`coin_balance`+{$num} WHERE id={$sell_coin['id']} AND `coin_balance_ex`>={$num}");
            $rs[] = Db::name('coin_log')->insert([
                'user_id'=>$sell_order['uid'],'coin_name'=>$sell_order['name_en'],'coin_money'=>$num,
                'type'=>13,'action'=>'管理员取消出售广告','ip'=>$ip,'createtime'=>time(),
            ]);
            if (check_arr($rs)){
                Db::commit();
                return json(["status" => 1, "msg" => "取消成功"]);
            }else{
                Db::rollback();
            }
        }
        return json(["status" => 0, "msg" => "取消失败"]);
    }

    # 获取奖金手续费比例
    protected function get_conf_fee($uid)
    {
        if (empty($uid)) return false;
        $user_conf_arr = Db::name('user_api')->where(['user_id'=>$uid])->field("user_id,shop_fee,shop_reward,exshop_reward,daili_reward,day_tixm_fee")->find();
        $conf_list= Db::name('exshop_config')->select();
        $conf_list_arr = array_column($conf_list,'val','name');
        $data['shop_fee'] = !empty($user_conf_arr['shop_fee']) ? $user_conf_arr['shop_fee'] : $conf_list_arr['shop_fee'];
        $data['shop_reward'] = !empty($user_conf_arr['shop_reward']) ? $user_conf_arr['shop_reward'] : 0;
        $data['exshop_reward'] = !empty($user_conf_arr['exshop_reward']) ? $user_conf_arr['exshop_reward'] : $conf_list_arr['exshop_reward'];
        $data['daili_reward'] = !empty($user_conf_arr['daili_reward']) ? $user_conf_arr['daili_reward'] : $conf_list_arr['daili_reward'];
        $data['day_tixm_fee'] = !empty($user_conf_arr['day_tixm_fee']) ? $user_conf_arr['day_tixm_fee'] : $conf_list_arr['day_tixm_fee'];
        $data['dakuan_time'] = $conf_list_arr['dakuan_time'];
        return $data;
    }
    # 承兑系统配置
    protected function get_exshop_conf($val)
    {
        $config = Db::name("exshop_config")->where(['name'=>$val])->find();
        if ($val=='usdt_price'){
            return !empty($config['val'])?$config['val']:6.5;
        }
        return $config['val'];
    }

    # 查看消息列表
    public function msglist(Request $request)
    {
        $where=[];

        $admin_uid_str = $this->get_yonghu_uid();
        if ($admin_uid_str){
            $where[]=['uid','in',$admin_uid_str];
        }

        $val = [];
        $status = input('status/s');
        $order_no = input('order_no');

        if (!empty($order_no)){
            $excoin_match = Db::name("excoin_match")->where("order_no='{$order_no}'")->field('id')->find();
            if ($excoin_match['id']>0){
                $where[] = ['match_id', '=', $excoin_match['id']];
            }
        }

        if ($status != '' && in_array($status, [-1, 0, 2])) {
            if ($status == '-1') {
                $where[] = ['status', 'in', '0,1,2'];
            } else {
                $where[] = ['status', '=', $status];
            }
        }else{
            $status = 0;
            $where[] = ['status', '=', 0];
        }
        $exshop_msg = Db::name("exshop_msg")->where($where)->order("id desc")->paginate(10);

        $data = $exshop_msg->all();

        if ($data){
            foreach ($data as $k=>$v){
                $match_info = Db::name("excoin_match")->where(['id'=>$v['match_id']])->find();
                $minfo = Db::name("user")->where(['id'=>$match_info['match_uid']])->field('nickname,username,exshop_code')->find();
                $ainfo = Db::name("user")->where(['id'=>$match_info['app_uid']])->field('nickname,username')->find();
                $data[$k]['match_info'] = $match_info;
                $data[$k]['minfo'] = $minfo;
                $data[$k]['ainfo'] = $ainfo;
                $data[$k]['type_en'] = $this->get_user_bank($match_info['match_uid']);
            }
        }
        $page = $exshop_msg->render();
        $count = $exshop_msg->total();
        $this->assign('page',$page);
        $this->assign('list',$data);
        $this->assign('count',$count);
        $val = ['status'=>$status,'order_no'=>$order_no];
        $this->assign('val',$val);
        return view('msglist');
    }

    # 消息提醒
    public function get_exshop_msg(){
        $where = "status=0";
        $admin_uid_str = $this->get_yonghu_uid();
        if ($admin_uid_str){
            $where .= "and status in ({$admin_uid_str})";
        }

        $exshop_msg = Db::name("exshop_msg")->where($where)->count();
        if (!$exshop_msg)return json(['status'=>'error']);

        return json(['status'=>'ok','data'=>$exshop_msg]);
    }
    # 修改消息提醒
    public function exshop_msgac(){
        if($this->request->isGet())
        {
            $id = (int)input("id");
            $exshop_msg = Db::name("exshop_msg")->where(["id"=>$id])->find();
            if ($exshop_msg){
                $exshop_msg = Db::name("exshop_msg")->where(["id"=>$id])->update(['status'=>2]);
                return json(["status" => 1, "msg" => "忽略成功"]);
            }
        }
    }

    # 订单明细
    public function shop_list(Request $request)
    {
        $coin_name=$request->param("coin_name","","strtolower");
        $type=$request->param("type","","intval");
        $order_no=input('order_no');
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        ($coin_name && $coin_name!="all") && $where[]=["name_en","=",$coin_name];
        ($type && $type!=5) && $where[]=["type","=",$type];
        $beigin && $where[]=["createtime",">=",$beigin];
        $end && $where[]=["createtime","<=",$end];
        if (!empty($order_no)){
            $where[]=["order_no","=",$order_no];
        }

        $where[]=['status','=',2];
        $list=db("excoin_match")->where($where)->field("id,order_no,app_uid,match_uid,liushui_no,num,price,shop_fee,name_en,type,time,end_time")->order("id DESC")->paginate(10);

        $page=$list->render();
        $data=$list->all();

        foreach ($data as $k=>$v){
            $shop = Db::name('user')->where("id={$v['app_uid']}")->field('username,nickname')->find();
            $data[$k]['shop']=$shop;
            $exshop = Db::name('user')->where("id={$v['match_uid']}")->field('username,nickname')->find();
            $data[$k]['exshop']=$exshop;
        }
        $zprice = array_sum(array_column($data,'price'));
        $znum = array_sum(array_column($data,'num'));
        $zfee = array_sum(array_column($data,'shop_fee'));

        $coinall = Db::name("coin_config")->where(["status"=>'normal'])->select();

        $count=$list->total();
        $variate=["coin_name"=>$coin_name,"type"=>$type,"beigin"=>$beigin,"end"=>$end,'order_no'=>$order_no];
        return $this->fetch("shop_list", ["page" => $page, "data" => $data, "count" => $count, "zprice" => $zprice,"znum" => $znum,"zfee" => $zfee, "variate" =>$variate,'coinall'=>$coinall,
        ]);
    }
    # 获取支付类型展示图片
    protected function get_user_bank($uid)
    {
        $tmp = ['1'=>'<img src="/assets/img/pay/bank.png">','2'=>'<img src="/assets/img/pay/alipay.png">','3'=>'<img src="/assets/img/pay/wechatpay.png">'];
        $bank_list = Db::name('user_bank')->where(['uid'=>$uid])->field('type')->order("type")->select();
        if (!$bank_list) return false;
        $bank_list_arr = array_column($bank_list,'type');
        $arr = array_replace($bank_list_arr,$tmp);
        if (!$arr) return false;
        $str = '';
        foreach ($bank_list_arr as $v){
            $str .= $tmp[$v];
        }
        return $str;
    }
}
