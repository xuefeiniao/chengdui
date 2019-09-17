<?php
namespace app\index\controller;
use think\Db;
use think\Request;

require_once('../application/common.php');
class Shop extends Indexcommon
{
    # 出售
    public function coin_sell()
    {
        $uid = session("id");
        $buy_id = (int)input('buy_id');
        $user_info = Db::name('user')->where(['id'=>$uid])->field('id,username')->find();
        if (empty($user_info)) return json(["status" => 0, "msg" => "账号异常，请联系平台管理员"]);

        $user_pay_arr = Db::name('user_bank')->where(['uid'=>$uid])->select();
        $user_pay_list = [];
        if ($user_pay_arr){
            $user_pay_list = array_column($user_pay_arr,null,'type');
        }
        $buy_order = Db::name('excoin_buy')->where("id={$buy_id}")->find();
        if (empty($user_info)) return json(["status" => 0, "msg" => "订单异常"]);

        if($this->request->isPost()) {
            $buy_id = (int)input('buy_id');
            $bank_type = (int)input('bank_type');
            $num = number_format(abs(input('num')),8,'.','');
            $price_cny = $buy_order['price_cny'];

            if (empty($num) || $num < 0) return json(["status" => 0, "msg" => "请输入数量"]);

            $user = Db::name('coin')->where(['user_id'=>$uid,'coin_name'=>'usdt'])->field('id,coin_balance,coin_balance_ex')->find();
            if (empty($user)||$user['coin_balance']<$num) return json(["status" => 0, "msg" => "余额不足"]);

            $bank_info = $user_pay_list[$bank_type];
            if (empty($bank_info)) return json(["status" => 0, "msg" => "未设置收款方式"]);


            # 打款失效时间
            $exshop_conf_arr = Db::name('exshop_config')->select();
            $exshop_conf = array_column($exshop_conf_arr,'val','name');
            if (empty($exshop_conf)||$exshop_conf['dakuan_time']< 0) return json(["code" => 204, "result" => "稍后再试"]);
            $end_time_conf = $exshop_conf['dakuan_time'];
            $time = time();
            $end_time = $time + $end_time_conf;
            $time_s = getTime($time);
            $end_time_s = getTime($end_time);

            $ip = $this->request->ip();

            Db::startTrans();
            $user_coin_id = $user['id'];
            $rs[] = Db::execute("UPDATE `coinpay_coin` SET `coin_balance`=`coin_balance`-{$num},`coin_balance_ex`=`coin_balance_ex`+{$num}");

            $rs[] = Db::execute("UPDATE `coinpay_excoin_buy` SET `match_num`=`match_num`+{$num},`shengyu_num`=`shengyu_num`-{$num} WHERE id={$buy_order['id']}");

            $rs[] = $sell_id = Db::name('excoin_sell')->insertGetId([
                'uid'=>$uid,'name_en'=>$buy_order['name_en'],
                'min'=>$buy_order['min'],'price_cny'=>$price_cny,'type'=>1,'status'=>1,
                'num'=>$num, 'shengyu_num'=>0,'match_num'=>$num,
                'bank_type'=>$bank_type,'ip'=>$ip,
            ]);

            $rs[] = Db::name('coin_log')->insert([
                'user_id'=>$uid,'coin_name'=>$buy_order['name_en'],'coin_money'=>$num,
                'type'=>14,'action'=>'商户出售广告','ip'=>$ip,'createtime'=>time(),
            ]);

            $rs[] = Db::name('excoin_match')->insert([
                'name_en'=>$buy_order['name_en'], 'num'=>$num,
                'buy_id'=>$buy_order['id'],'sell_id'=>$sell_id,'match_uid'=>$buy_order['uid'],'app_uid'=>$uid,
                'order_no'=>makeCode(10),'liushui_no'=>makeCode(8,true),'end_time'=>$end_time_s,
                'type'=>$bank_type,'user_name'=>$bank_info['user_name'],'bank_name'=>$bank_info['bank_name'],'bank_number'=>$bank_info['bank_number'],'qrcode'=>$bank_info['qrcode'],
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

        return view("coin_sell",['pay_list'=>$user_pay_list,'buy_order'=>$buy_order]);
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
                'ip'=>$ip,
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

    # 盘口用户匹配订单
    public function match_list(Request $request)
    {
        $uid = session("id");
        $type = input('type');

        $status=input('status');
        $order_no=input('order_no');
        $liushui_no=input('liushui_no');
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        $buy_list = Db::name("excoin_buy")->where("type=1")->select();
        $buy_list_str = implode(',',array_column($buy_list,'id'));
        $where = "match_uid!=0 and app_uid={$uid}";
        $title = '盘口用户订单列表';
        /*if ($type == 1 && !empty($buy_list_str)) {
            $where .=" and buy_id in({$buy_list_str})";
        }else{
            $where .=" and buy_id not in({$buy_list_str})";
            $title = '订单列表';
        }*/

        if ($beigin && $end){
           $where .=" and time between '{$beigin}' and '{$end}'";
        }

        if (!empty($order_no)){
            $where .=" and order_no='{$order_no}'";
        }
        if (!empty($liushui_no)){
            $where .=" and liushui_no='{$liushui_no}'";
        }
        $arr = [0,1,2,3,4,5];

        if ($status!=''&&$status >= 0 && in_array($status, $arr)){
            $where .= " and status='{$status}'";
        }

        $pingtai_price = $this->get_exshop_conf('usdt_price');
        //dump($where);die;
        $list = Db::name("excoin_match")->where($where)->order("id DESC")->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();
        $tmp = [0=>'','1'=>'<img src="/static/img/pay/bank.png">','2'=>'<img src="/static/img/pay/alipay.png">','3'=>'<img src="/static/img/pay/wechatpay.png">'];
        foreach ($data as $key => $val) {
            $data[$key]['price_cny']=number_format($val['price']/$val['num'],6,'.','');
            $data[$key]['type']=$tmp[$val['type']];
            $data[$key]['shop_fee_cny'] = $val['shop_fee'] * $pingtai_price;
            $data[$key]['true_num_cny'] = $val['true_num'] * $pingtai_price;
        }

        $tongji = Db::name("excoin_match")->where($where)->select();
        $true_num = array_sum(array_column($tongji, 'true_num'));
        $true_num_cny = $true_num * $pingtai_price;
        $shop_fee = array_sum(array_column($tongji, 'shop_fee'));
        $shop_fee_cny = $shop_fee * $pingtai_price;
        $price_zj = array_sum(array_column($tongji, 'price'));

        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval"),
            "status" => $request->param("status", "", "status"),
        ];
        $variate=["beigin"=>$beigin,"end"=>$end,'order_no'=>$order_no,'liushui_no'=>$liushui_no,'status'=>$status];
        return view("match_list", [
            "page" => $page, "data" => $data, "count" => $count, "variate" => $variate,"title"=>$title,
            "true_num" => $true_num, "true_num_cny" => $true_num_cny,"shop_fee"=>$shop_fee,"shop_fee_cny"=>$shop_fee_cny,"price_zj"=>$price_zj
        ]);
    }

    public function buy_list(Request $request)
    {
        $list = Db::name("excoin_buy")->where("type = 1 and status not in (2,4,5)")->order("id DESC")->paginate(10);
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

    # 回调通知
    public function callbackac(Request $request){
        if ($this->request->isPost()){
            $id = (int)input('id');
            $act = controller("apiexshop");
            $rs = $act->call_back_now($id);
            if ($rs){
                return json(["status" => 1, "msg" => "成功"]);
            }else{
                return json(["status" => 0, "msg" => "失败"]);
            }
        }
    }
    public function tousu()
    {
        return view();
    }
}