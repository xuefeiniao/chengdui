<?php
namespace app\index\controller;
use think\Db;
use think\Request;

require_once('../application/common.php');
class Exshop extends Indexcommon
{
    # 出售
    public function coin_sell()
    {
        $price_cny = $this->get_exshop_conf('usdt_price');
        $data=$this->request->param();
        if($this->request->isPost()) {
            $uid = session("id");

            $user_info = Db::name('user')->where(['id'=>$uid])->field('id,username')->find();
            if (empty($user_info)) return json(["status" => 0, "msg" => "账号异常，请联系平台管理员"]);

            $bank_type = $data['bank_type'];
            if (empty($data['bank_type'])) return json(["status" => 0, "msg" => "请选择支付方式"]);

            $bank_type = array_reverse(explode(',',$bank_type));
            if (is_array($bank_type)&&$bank_type[0]=='on'){
                unset($bank_type[0]);
            }
            $bank_type_arr = implode(',',$bank_type);
            $user_bank = Db::name('user_bank')->where("uid={$uid} and type in({$bank_type_arr})")->order('type')->select();
            if (empty($user_bank)) return json(["status" => 0, "msg" => "请设置支付方式"]);
            $user_bank_arr_str = implode(',',array_column($user_bank,'type'));
            $num = number_format(abs(input('num')),8,'.','');
            $min = number_format(abs(input('min')),8,'.','');
            $price = number_format(abs(input('price')),8,'.','');

            if (!empty($price_cny) && $price_cny < $price) {
                return json(["status" => 0, "msg" => "价格区间异常【{$price_cny}】"]);
            }

            $user = Db::name('coin')->where(['user_id'=>$uid,'coin_name'=>'usdt'])->field('id,coin_balance,coin_balance_ex')->find();
            if (empty($user)||$user['coin_balance']<$num) return json(["status" => 0, "msg" => "余额不足"]);
            $ip = $this->request->ip();
            Db::startTrans();
            $user_coin_id = $user['id'];
            $rs[] = Db::name('coin')->where(['id'=>$user_coin_id])->setDec('coin_balance',$num);
            $rs[] = Db::name('coin')->where(['id'=>$user_coin_id])->setInc('coin_balance_ex',$num);
            $rs[] = Db::name('excoin_sell')->insert([
                'uid'=>$uid,'name_en'=>'usdt',
                'num'=>$num,'min'=>$min,
                'shengyu_num'=>$num,'price_cny'=>$price,
                'bank_type'=>$user_bank_arr_str, 'ip'=>$ip,
            ]);

            $rs[] = Db::name('coin_log')->insert([
                'user_id'=>$uid,'coin_name'=>'usdt','coin_money'=>$num,
                'type'=>10,'action'=>'承兑商发布出售广告','ip'=>$ip,'createtime'=>time(),
            ]);

            if (check_arr($rs)){
                Db::commit();
                user_log($user_info["username"],"出售广告",$ip);
                return json(["status" => 1, "msg" => "发布成功"]);
            }else{
                Db::rollback();
                return json(["status" => 0, "msg" => "发布失败"]);
            }
        }
        # 状态:0=未匹配,1=已匹配,2=已取消,3=未完全匹配,4=已完成
        $uid = session("id");
        $excoin_list = Db::name("excoin_sell")->where("uid={$uid} and status in(0,1,3,4)")->field('num,match_num')->select();
        $data['sell_num'] = array_sum(array_column($excoin_list,'num'));
        $data['sell_num_cny'] = array_sum(array_column($excoin_list, 'num')) * $price_cny;
        $data['match_num'] = array_sum(array_column($excoin_list,'match_num'));
        $data['match_num_cny'] = array_sum(array_column($excoin_list, 'match_num')) * $price_cny;
        $user_coin = Db::name('coin')->where("user_id={$uid} and coin_name='usdt'")->find();
        $data['coin'] = !empty($user_coin['coin_balance'])?$user_coin['coin_balance']:0;
        $data['coin_cny'] = !empty($user_coin['coin_balance'])?$user_coin['coin_balance']* $price_cny:0;

        return view("coin_sell", ["price_cny" =>$price_cny,'data'=>$data]);
    }

    # 购买
    public function coin_buy()
    {
        $data=$this->request->param();
        if($this->request->isPost()) {
            $uid = session("id");
            $bank_type = (int)input('bank_type');

            $user_info = Db::name('user')->where(['id'=>$uid])->field('username,wechat,wechatname,wechatimage,alipay,alipayname,alipayimage,bankname,banknumber,bank,bankaddress')->find();
            if (empty($user_info)) return json(["status" => 0, "msg" => "账号异常，请联系平台管理员"]);

            $num = number_format(abs(input('num')),8,'.','');
            $min = number_format(abs(input('min')),8,'.','');
            $price = number_format(abs(input('price')),8,'.','');

            if ($price < 6 || $price > 9){
                return json(["status" => 0, "msg" => "价格区间异常【6-9】"]);
            }

            $ip = $this->request->ip();
            Db::startTrans();
            $rs[] = Db::name('excoin_buy')->insert([
                'uid'=>$uid,
                'name_en'=>'usdt', 'num'=>$num,'min'=>$min,
                'shengyu_num'=>$num,'price_cny'=>$price,
                'ip'=>$ip,'type'=>1,
            ]);

            if (check_arr($rs)){
                Db::commit();
                user_log($user_info["username"],"求购广告",$ip);
                return json(["status" => 1, "msg" => "发布成功"]);
            }else{
                Db::rollback();
                return json(["status" => 0, "msg" => "发布失败"]);
            }
        }
        return view();
    }

    # 卖列表
    public function sell_list(Request $request)
    {
        $where['uid'] = session("id");
        $list = Db::name("excoin_sell")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();
        $tmp = ['1'=>'<img src="/static/img/pay/bank.png">','2'=>'<img src="/static/img/pay/alipay.png">','3'=>'<img src="/static/img/pay/wechatpay.png">'];
        foreach ($data as $key => $val) {
            $bank_type_arr = [];
            $bank_type = explode(',',$val['bank_type']);

            foreach ($bank_type as $vv){
                $bank_type_arr[]= $tmp[$vv];
            }
            $data[$key]['bank_name'] = implode('',$bank_type_arr);
        }
        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval")
        ];
        return view("sell_list", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate]);
    }

    # 买列表
    public function buy_list(Request $request)
    {
        $where['uid'] = session("id");
        $where['type'] = 1;
        $list = Db::name("excoin_buy")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();
        foreach ($data as $key => $val) {

        }
        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval")
        ];

        return view("buy_list", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate]);
    }

    # 确认放行列表
    public function sell_dakuanlist(Request $request)
    {
        $uid = session("id");
        $where['uid'] = $uid;

        $sell_data = Db::name("excoin_sell")->where($where)->select();
        $sell_id = array_column($sell_data,'id');
        $sell_id_str = implode(',',$sell_id);
        $map = [];
        if ($sell_id_str){
            $map[]=['sell_id','in',$sell_id_str];
        }
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
        $map[]=['match_uid','=',$uid];
        $map[] =['sell_id','<>',0];
        $arr = [0,1,2,3,4,5];
        if ($status!=null && $status >= 0 && in_array($status, $arr)){
           $map[] = ['status','=',$status];
        }

        $list = Db::name("excoin_match")->where($map)->order("id DESC")->paginate(20);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();
        foreach ($data as $key => $val) {

        }
        $res = Db::name("excoin_match")->where($map)->order("id DESC")->select();
        $true_num = array_sum(array_column($res,'true_num'));
        $num = array_sum(array_column($res,'num'));
        $shop_fee = array_sum(array_column($res,'shop_fee'));
        $price_zj = array_sum(array_column($res,'price'));

        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval"),
            "status" => $request->param("status", "", "status"),
        ];
        $variate=["beigin"=>$beigin,"end"=>$end,'order_no'=>$order_no,'liushui_no'=>$liushui_no,'status'=>$status];

        return view("sell_dakuanlist", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate,
                    "true_num" => $true_num,"shop_fee"=>$shop_fee,"price_zj"=>$price_zj,"num"=>$num
        ]);
    }

    # 卖确认打款
    public function sell_dakuanac()
    {
        $uid = session("id");
        $id = (int)input("match_id");
        if (!empty($uid) && !empty($id)) {
            $user_info = Db::name('user')->where(['id'=>$uid,'type'=>'exshop'])->field('username,parentid')->find();
            if (empty($user_info)) return json(["status" => 0, "msg" => "账号异常，请联系平台管理员"]);
            $exshop_uid = $uid; # 承兑商
            $exshop_puid = $user_info['parentid']; # 承兑商代理

            $match_info = Db::name('excoin_match')->field('id,buy_id,sell_id,app_uid,match_uid,num,status,callback_addr')->where(['id'=>$id])->find();
            if(empty($match_info) || $match_info['status'] != 1)return json(["status" => 0, "msg" => "订单异常"]);

            $shop_uid = $match_info['app_uid']; # 商户
            $sell_id = $match_info['sell_id'];
            $buy_id = $match_info['buy_id'];
            $num = $match_info['num'];

            $sell_order = Db::name('excoin_sell')->where("id={$sell_id} AND uid={$uid}")->find();
            if(empty($sell_order))return json(["status" => 0, "msg" => "订单异常2"]);

            $buy_order = Db::name('excoin_buy')->where("id={$buy_id}")->find();
            if(empty($buy_order))return ['status'=>"error","errorcode"=>"245","errormsg"=>"订单异常:买家订单"];

            $shop_info = Db::name('user')->where(['id'=>$shop_uid])->field('username,parentid,shop_pid')->find();
            $shop_puid = $shop_info['parentid']; # 商户代理
            $shop_zjs_uid = $shop_info['shop_pid']; # 商户中间商代理

            # 手续费奖励

            $dakuan_time = $this->get_exshop_conf('dakuan_time');
            if ($dakuan_time< 0) return json(["code" => 204, "result" => "稍后再试"]);


            $exshop_arr = $this->get_conf_fee($exshop_uid); # 承兑商
            if (empty($exshop_arr['exshop_reward'])) $exshop_arr['exshop_reward']=0;
            if (empty($exshop_arr['daili_reward'])) $exshop_arr['daili_reward']=0;

            $shop_arr = $this->get_conf_fee($shop_uid); # 商户
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

            ////////////
            $ip = $this->request->ip();

            Db::startTrans();
            $rs[] = Db::name('coin')->where("user_id={$sell_order['uid']} and coin_balance_ex>{$num}")->setDec('coin_balance_ex',$num);

            $rs[] = Db::name('excoin_match')->where("id={$id} and status=1")->update([
                'shop_fee'=>$shop_fee,'true_num'=>$return_shop_num,'exshop_reward'=>$return_exshop_reward,
                'status' => 2, 'queren_time'=>getTime()
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
                    'from_uid' => $shop_uid, 'to_uid' => $shop_puid, 'match_id' => $id,
                    'coin_en'  => 'usdt', 'num' => $return_shop_dalli_reward,'type'=>1
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
                    'from_uid' => $shop_uid, 'to_uid' => $shop_zjs_uid, 'match_id' => $id,
                    'coin_en'  => 'usdt', 'num' => $return_shop_zjs_reward,'type'=>1
                ]);
            }
            # 承兑商
            if ($return_exshop_reward_t0 > 0) {
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
                if ($return_exshop_reward > 0){
                    $rs[] = Db::name('coin_log')->insert([
                        'user_id'=>$exshop_uid,'coin_name'=>'usdt','coin_money'=>$return_exshop_reward,
                        'type'=>9,'action'=>'出售订单奖励','ip'=>$ip,'createtime'=>time(),
                    ]);
                }
            }
            # 承兑商代理
            if ($return_exshop_daili_reward_t0 > 0 && $exshop_puid > 0) {
                $puser_info = Db::name('user')->where(['id' => $exshop_puid])->field('username,parentid')->find();
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
                        'from_uid' => $exshop_uid, 'to_uid' => $exshop_puid, 'match_id' => $id,
                        'coin_en'  => 'usdt', 'num' => $return_exshop_daili_reward,'type'=>2
                    ]);
                }
            }
            if (check_arr($rs)){
                Db::commit();
                $ip = $this->request->ip();
                user_log($user_info["username"],"求购广告",$ip);

                # 消息列表
                Db::name("exshop_msg")->where(["match_id"=>$id])->update(['status'=>1]);
                Db::execute("UPDATE `coinpay_excoin_buy` SET `status`=4 WHERE id={$buy_id} AND `queren_num`=`num`");
                Db::execute("UPDATE `coinpay_excoin_sell` SET `status`=4 WHERE id={$sell_id} AND `queren_num`=`num`");
                if (!empty($match_info['callback_addr'])){
                    $act = controller("apiexshop");
                    $act->call_back_now($match_info['id']);
                }
                return json(["status" => 1, "msg" => "执行成功"]);
            }else{
                Db::rollback();
                return json(["status" => 0, "msg" => "执行失败"]);
            }
        }
    }
    public function sell_dakuanac_bk()
    {
        if($this->request->isPost()) {
            $id = (int)input("match_id");
            $uid = session("id");
            $user_info = Db::name('user')->where(['id'=>$uid,'type'=>'exshop'])->field('username,parentid')->find();
            if (empty($user_info)) return json(["status" => 0, "msg" => "账号异常，请联系平台管理员"]);
            $exshop_uid = $uid; # 承兑商
            $exshop_puid = $user_info['parentid']; # 承兑商代理

            $match_info = Db::name('excoin_match')->field('id,buy_id,sell_id,app_uid,match_uid,num,status')->where(['id'=>$id])->find();
            if(empty($match_info) || $match_info['status'] != 1)return json(["status" => 0, "msg" => "订单异常"]);

            $shop_uid = $match_info['app_uid']; # 商户
            $sell_id = $match_info['sell_id'];
            $buy_id = $match_info['buy_id'];
            $num = $match_info['num'];

            $sell_order = Db::name('excoin_sell')->where("id={$sell_id} AND uid={$uid}")->find();
            if(empty($sell_order))return json(["status" => 0, "msg" => "订单异常2"]);

            $buy_order = Db::name('excoin_buy')->where("id={$buy_id}")->find();
            if(empty($buy_order))return ['status'=>"error","errorcode"=>"245","errormsg"=>"订单异常:买家订单"];

            $shop_info = Db::name('user')->where(['id'=>$shop_uid])->field('username,parentid,shop_pid')->find();
            $shop_puid = $shop_info['parentid']; # 商户代理
            $shop_zjs_uid = $shop_info['shop_pid']; # 商户中间商代理

            # 手续费奖励

            $dakuan_time = $this->get_exshop_conf('dakuan_time');
            if ($dakuan_time< 0) return json(["code" => 204, "result" => "稍后再试"]);


            $exshop_arr = $this->get_conf_fee($exshop_uid); # 承兑商
            if (empty($exshop_arr['exshop_reward'])) $exshop_arr['exshop_reward']=0;
            if (empty($exshop_arr['daili_reward'])) $exshop_arr['daili_reward']=0;

            $shop_arr = $this->get_conf_fee($shop_uid); # 商户
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

            ////////////
            $ip = $this->request->ip();

            Db::startTrans();
            $rs[] = Db::name('coin')->where("user_id={$sell_order['uid']} and coin_balance_ex>{$num}")->setDec('coin_balance_ex',$num);

            $rs[] = Db::name('excoin_match')->where("id={$id} and status=1")->update([
                'shop_fee'=>$shop_fee,'true_num'=>$return_shop_num,'exshop_reward'=>$return_exshop_reward,
                'status' => 2, 'queren_time'=>getTime()
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
                    'from_uid' => $shop_uid, 'to_uid' => $shop_puid, 'match_id' => $id,
                    'coin_en'  => 'usdt', 'num' => $return_shop_dalli_reward,'type'=>1
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
                    'from_uid' => $shop_uid, 'to_uid' => $shop_zjs_uid, 'match_id' => $id,
                    'coin_en'  => 'usdt', 'num' => $return_shop_zjs_reward,'type'=>1
                ]);
            }
            # 承兑商
            if ($return_exshop_reward_t0 > 0) {
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
                if ($return_exshop_reward > 0){
                    $rs[] = Db::name('coin_log')->insert([
                        'user_id'=>$exshop_uid,'coin_name'=>'usdt','coin_money'=>$return_exshop_reward,
                        'type'=>9,'action'=>'出售订单奖励','ip'=>$ip,'createtime'=>time(),
                    ]);
                }
            }
            # 承兑商代理
            if ($return_exshop_daili_reward_t0 > 0 && $exshop_puid > 0) {
                $puser_info = Db::name('user')->where(['id' => $exshop_puid])->field('username,parentid')->find();
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
                        'from_uid' => $exshop_uid, 'to_uid' => $exshop_puid, 'match_id' => $id,
                        'coin_en'  => 'usdt', 'num' => $return_exshop_daili_reward,'type'=>2
                    ]);
                }
            }
            if (check_arr($rs)){
                Db::commit();
                $ip = $this->request->ip();
                user_log($user_info["username"],"求购广告",$ip);

                # 消息列表
                $res = Db::name("exshop_msg")->where(["match_id"=>$id])->update(['status'=>1]);

                Db::execute("UPDATE `coinpay_excoin_buy` SET `status`=4 WHERE id={$buy_id} AND `queren_num`=`num`");
                Db::execute("UPDATE `coinpay_excoin_sell` SET `status`=4 WHERE id={$sell_id} AND `queren_num`=`num`");

                $act = controller("apiexshop");
                $act->call_back_now($match_info['id']);

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
            $uid = (int)session("id");
            $user_info = Db::name('user')->where(['id'=>$uid,'type'=>'exshop'])->field('username,parentid')->find();
            if (empty($user_info)) return json(["status" => 0, "msg" => "账号异常，请联系平台管理员"]);

            $sell_order = Db::name('excoin_sell')->where("id={$id} and uid={$uid} and status=0")->field("id,name_en,num")->find();
            if(empty($sell_order))return json(["status" => 0, "msg" => "订单异常"]);
            $coin_en =  $sell_order['name_en'];
            $num = $sell_order['num'];
            $user_coin = Db::name('coin')->where("user_id={$uid} and coin_name='{$coin_en}' and coin_balance_ex>={$num}")->field('id,coin_balance,coin_balance_ex')->find();
            if(empty($user_coin))return json(["status" => 0, "msg" => "币种数量不够"]);
            $ip = $this->request->ip();

            Db::startTrans();
            $rs[] = Db::name('excoin_sell')->where(['id'=>$id,'status'=>0])->update(['status'=>2]);
            $rs[] = Db::execute("UPDATE `coinpay_coin` SET `coin_balance_ex`=`coin_balance_ex`-{$num},`coin_balance`=`coin_balance`+{$num} WHERE id={$user_coin['id']} AND `coin_balance_ex`>={$num}");
            $rs[] = Db::name('coin_log')->insert([
                'user_id'=>$uid,'coin_name'=>$sell_order['name_en'],'coin_money'=>$num,
                'type'=>13,'action'=>'承兑商取消出售广告','ip'=>$ip,'createtime'=>time(),
            ]);
            if (check_arr($rs)){
                Db::commit();
                user_log($user_info["username"],"取消出售广告",$ip);
                return json(["status" => 1, "msg" => "取消成功"]);
            }else{
                Db::rollback();
            }
        }
        return json(["status" => 0, "msg" => "取消失败"]);
    }

    # 确认打款
    public function buy_dakuanlist(Request $request)
    {
        $where['uid'] = session("id");
        $where['type'] = 1;
        $buy_data = Db::name("excoin_buy")->where($where)->select();
        $buy_id = array_column($buy_data,'id');
        $buy_id_str = implode(',',$buy_id);
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
        }

        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval")
        ];

        return view("buy_dakuanlist", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate]);
    }

    # 买确认打款
    public function buy_dakuanac()
    {
        if($this->request->isPost()) {
            $id = (int)input("match_id");
            $uid = session("id");
            $user_info = Db::name('user')->where(['id'=>$uid,'type'=>'exshop'])->field('username,parentid')->find();
            if (empty($user_info)) return json(["status" => 0, "msg" => "账号异常，请联系平台管理员"]);

            $match_order = Db::name("excoin_match")->where(['id'=>$id,'status'=>0])->update(['status'=>1,'dakuan_time'=>getTime()]);
            if ($match_order){
                return json(["status" => 1, "msg" => '执行成功']);
            }else{
                return json(["status" => 0, "msg" => '执行失败']);
            }
        }
    }

    # 查看支付方式
    public function iakjinfo()
    {
        if($this->request->isPost()) {
            $id = (int)input("id");
            $mathch_info = Db::name('excoin_match')->where("id={$id}")->field("liushui_no,type,user_name,bank_name,qrcode")->find();
            if (empty($mathch_info)) return json(["status" => 0, "msg" => "异常订单"]);
            $tmp = [1=>'银联',2=>'支付宝',3=>'微信'];
            $mathch_info['bank_type_cn'] = $tmp[$mathch_info['type']];
            return json($mathch_info);
        }
    }

    # 投诉
    public function tousuac()
    {
        $data = $this->request->param();
        if($this->request->isPost()) {
            $id = (int)input("match_id");
            $content = input("inputtousu");
            $uid = session("id");

            if (empty($content)) return json(["status" => 0, "msg" => "请填写投诉内容！"]);

            $user_info = Db::name('user')->where(['id'=>$uid,'type'=>'exshop'])->field('username,parentid')->find();
            if (empty($user_info)) return json(["status" => 0, "msg" => "账号异常，请联系平台管理员"]);
            $match_info = Db::name('excoin_match')->field('id,buy_id,sell_id,app_uid,match_uid,num,status')->where(['id'=>$id])->find();
            if(empty($match_info) || $match_info['status'] != 1)return json(["status" => 0, "msg" => "订单异常1"]);

            $sell_order = Db::name('excoin_sell')->where("id={$match_info['sell_id']} AND uid={$uid}")->find();
            if(empty($sell_order))return json(["status" => 0, "msg" => "订单异常2"]);

            $buy_order = Db::name('excoin_buy')->where("id={$match_info['buy_id']}")->find();
            if(empty($buy_order))return ['status'=>"error","errorcode"=>"245","errormsg"=>"订单异常:买家订单"];

            $tousu_info = Db::name('excoin_tousu')->where(['match_id'=>$id])->find();
            if(!empty($tousu_info)) return json(["status" => 0, "msg" => "该订单已经处于申诉状态，请耐心等待"]);

            $match_id = $id;
            Db::startTrans();
            $rs[] = Db::name('excoin_match')->where("id={$id} and status=1")->update(['status' => 5]);
            $rs[] = Db::name('excoin_tousu')->insert([
                'uid'=>$uid,'match_id'=>$match_id,
                'content'=>$content,
            ]);
            if (check_arr($rs)){
                Db::commit();
                $ip = $this->request->ip();

                user_log($user_info["username"],"投诉订单",$ip);
                return json(["status" => 1, "msg" => "投诉成功"]);
            }else{
                Db::rollback();
                return json(["status" => 0, "msg" => "投诉失败"]);
            }
        }
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

    /*public function tousu(Request $request)
    {
        $where['uid'] = session("id");
        $list = Db::name("excoin_tousu")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();
        foreach ($data as $key => $val) {

        }
        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval")
        ];

        return view("sell_list", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate]);


        return view();
    }*/
}