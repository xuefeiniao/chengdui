<?php
namespace app\index\controller;
use Coin\Eth;
use think\Db;

require_once('../application/common.php');
class Test extends Indexcommon{

    public function dhdh(){
        #merchant={}&qrtype={}&customno={}&money={}&sendtime={}&notifyurl={}&backurl={}&risklevel={}key

        $url ="http://www.168kpay.com/api/v3/cashier.php";
        $merchant = 'YB19073017194';
        $key = '7ae2d73c30247f228052d533f3591961';
        $qrtype ='wp';
        $customno ='201908050805141440';
        $money =1.00;
        $sendtime =time();
        $notifyurl = 'http://www.taida136.com/admincoin/Index/index';
        $backurl = 'http://www.taida136.com/admincoin/Index/index';
        $risklevel = '';
# merchant=YB19073017194&qrtype=wp&customno=201908050805141440&money=1&sendtime=1564986195&notifyurl=&backurl=&7ae2d73c30247f228052d533f3591961
        $sign = "merchant={$merchant}&qrtype={$qrtype}&customno={$customno}&money={$money}&sendtime={$sendtime}&notifyurl={$notifyurl}&backurl={$backurl}&{$key}";
        dump($sign);
    }

    


    public function addr()
    {
        $client = new Eth('47.244.4.48',6111);
        dump($client->web3_clientVersion());
    }

    public function ip(){
        $ip = "103.231.62.229";
        $ip = trim(iconv("GBK", "utf-8",file_get_contents("http://whois.pconline.com.cn/ip.jsp?ip={$ip}")));
        halt($ip);
    }
    public function aabb()
    {
        $stime = date("Y-m-d",strtotime("-1 day"));
        $etime = date("Y-m-d");
        $excoin_match = Db::name("excoin_match")->where("app_uid=186 and time between '{$stime}' and '{$etime}' and status=2")
            //->field("id,app_uid,num,true_num,price,shop_fee")
            ->field("num,true_num,price,shop_fee")
            ->select();
        $num = array_sum(array_column($excoin_match,'num'));
        $price = array_sum(array_column($excoin_match,'price'));
        $shop_fee = array_sum(array_column($excoin_match,'shop_fee'));
        dump('数量：' . $num);
        dump('金额：' . $price);
        dump('手续费：' . $shop_fee);

        $config = $this->get_conf_fee(186);

        $shop_fee = $num * $config['shop_fee'];
        $true_num = $num - $shop_fee;
        $true_price = $true_num * 7;

        dump('实际手续费：' . $shop_fee);
        dump('实际数量：' . $true_num);
        dump('实际金额：' . $true_price);


        foreach ($excoin_match as $k=>$v)
        {
            $_num = number_format($v['price'] / 7,8,'.','');
            $_shop_fee = $_num * $config['shop_fee'];
            $_true_num =$_num - $_shop_fee;
            $excoin_match[$k]['_num'] = $_num;
            $excoin_match[$k]['_shop_fee'] = $_shop_fee;
            $excoin_match[$k]['_true_num'] = $_true_num;
            $excoin_match[$k]['_price'] = $_true_num * 7;
        }

        $_num = array_sum(array_column($excoin_match,'_num'));
        $_shop_fee = array_sum(array_column($excoin_match,'_shop_fee'));
        $_true_num = array_sum(array_column($excoin_match,'_true_num'));
        $_price = array_sum(array_column($excoin_match,'_price'));

        dump('_实际手续费：' . $_shop_fee);
        dump('_数量：' . $_num);
        dump('_实际数量：' . $_true_num);
        dump('_实际金额：' . $_price);

        $stime_l = strtotime(date("Y-m-d",strtotime("-1 day")));
        $etime_l = strtotime(date("Y-m-d"));

        $user_shop_num = Db::name("coin_log")->where("user_id=186 and type=11 and createtime between '{$stime_l}' and '{$etime_l}'")->select();
        $user_shop_fee = Db::name("coin_log")->where("user_id=186 and type=12 and createtime between '{$stime_l}' and '{$etime_l}'")->select();

        $user_shop_num_s = array_sum(array_column($user_shop_num,'coin_money'));
        $user_shop_fee_s = array_sum(array_column($user_shop_fee,'coin_money'));
        dump('用户收币数量：' . $user_shop_num_s);
        dump('用户手续费：' . $user_shop_fee_s);


        halt($tmp);

        dump($config);
        halt($excoin_match);
    }

    public function sign()
    {
        $arr = $this->request->post();

        $where['shopid'] = $arr['shopid'];
        $shop_info = Db::name('user')->where($where)->field('id,shopid,apikey,apipassword')->find();

        if (!empty($arr['coin_cny'])){
            $arr['coin_cny'] = number_format($arr['coin_cny'], 8, '.', '');
        }

        unset($arr['sign']);
        ksort($arr);
        $arr_json_md5_pwd = md5(json_encode($arr,JSON_UNESCAPED_SLASHES)) . $shop_info['apipassword'];
        $_sign = strtoupper(substr(md5($arr_json_md5_pwd), 0, 18));

        return $_sign;
    }
    # 测试图片是否正常
    public function open()
    {
        $order_no = "201907171808086104234722";
        $order = Db::name("excoin_match")->where(['order_no' => $order_no])->find();
        $qrcode = $order['qrcode'];
        $qrcode_name = pathinfo($qrcode);
        $data = "订单号:[{$order['order_no']}],金额:[{$order['price']}],打款户名:[{$order['user_name']}],银行名称:[{$order['bank_name']}]";
        if ($order['type']==1){
            $data .=",卡号:[{$order['bank_number']}],银行名:[{$order['bank_name']}]";
        }else{
            $data .=",二维码:[{$order['qrcode']}]";
        }
        if (!$this->check_exshop_img($qrcode)){
            Db::name("jygk_log")->insert(['exshop_uid' => $order['match_uid'], 'match_id' => $order['app_uid'], 'data' => $data]);
            $path = EXIMG.'/uploads/paybak';
            if (!file_exists($path)){
                mkdir($path, 0755, true);
            }
            $qrcode_old = EXIMG.$qrcode;
            $qrcode_new = $path.'/'.$order['match_uid'].'__'.$qrcode_name['basename'];
            $copy_file_name = copy($qrcode_old,$qrcode_new);

            return json(["status" => 0, "msg" => "支付方式异常"]);
        }
    }


    # 检测承兑商图片
    public function check_exshop_img($qrcode)
    {
        # $qrcode = "/uploads/pay/033ebbc18ee05c075181f9ec73529208.jpg";
        $qrcode_name = pathinfo($qrcode)['filename'];
        $host = input('server.REQUEST_SCHEME') . '://' . input('server.SERVER_NAME');
        $url = $host . $qrcode;
        $res = curl($url);
        if ($res && isset($res['body'])) {
            $qrcode_name_md5 = md5($res['body']);
            if ($qrcode_name == $qrcode_name_md5) {
                return true;
            }
        }
        return false;
    }


    public function get_sell_uid($price)
    {
        $stime = date("Y-m-d 00:00:00");
        $etime = date("Y-m-d 23:59:59");

        $match_info = Db::name("excoin_match")->where("match_uid<>0 and time between '{$stime}' and '{$etime}'")->group("match_uid")->fetchSql(true)->select();
        halt($match_info);

        $str = '[{"id":49,"uid":186,"shop_uid":178,"time":"2019-07-17 14:53:08"},{"id":52,"uid":186,"shop_uid":155,"time":"2019-07-22 08:29:18"},{"id":54,"uid":186,"shop_uid":158,"time":"2019-07-22 09:44:35"},{"id":56,"uid":186,"shop_uid":191,"time":"2019-07-23 10:59:37"},{"id":58,"uid":186,"shop_uid":160,"time":"2019-07-23 11:00:10"},{"id":60,"uid":186,"shop_uid":157,"time":"2019-07-23 11:00:43"},{"id":61,"uid":186,"shop_uid":154,"time":"2019-07-23 11:01:04"},{"id":66,"uid":186,"shop_uid":156,"time":"2019-07-25 09:05:45"},{"id":67,"uid":186,"shop_uid":161,"time":"2019-07-25 09:06:46"},{"id":68,"uid":186,"shop_uid":187,"time":"2019-07-25 21:38:47"},{"id":69,"uid":186,"shop_uid":188,"time":"2019-07-25 21:46:43"},{"id":71,"uid":186,"shop_uid":192,"time":"2019-07-26 09:23:38"},{"id":76,"uid":186,"shop_uid":198,"time":"2019-07-26 11:15:20"},{"id":81,"uid":186,"shop_uid":211,"time":"2019-07-26 14:48:25"},{"id":82,"uid":186,"shop_uid":212,"time":"2019-07-26 14:54:28"},{"id":84,"uid":186,"shop_uid":214,"time":"2019-07-26 15:04:03"},{"id":90,"uid":186,"shop_uid":159,"time":"2019-07-26 19:52:48"},{"id":91,"uid":186,"shop_uid":176,"time":"2019-07-26 20:04:51"},{"id":92,"uid":186,"shop_uid":219,"time":"2019-07-27 09:07:00"},{"id":93,"uid":186,"shop_uid":196,"time":"2019-07-27 09:27:36"},{"id":94,"uid":186,"shop_uid":205,"time":"2019-07-27 10:37:16"},{"id":95,"uid":186,"shop_uid":199,"time":"2019-07-27 11:20:28"},{"id":96,"uid":186,"shop_uid":200,"time":"2019-07-27 11:21:12"},{"id":97,"uid":186,"shop_uid":201,"time":"2019-07-27 11:21:20"},{"id":98,"uid":186,"shop_uid":216,"time":"2019-07-27 11:21:29"},{"id":99,"uid":186,"shop_uid":217,"time":"2019-07-27 11:21:36"},{"id":100,"uid":186,"shop_uid":218,"time":"2019-07-27 11:22:54"}]';
        $shopinfo = json_decode($str,true);
        $shopinfo_arr = array_column($shopinfo,'shop_uid');
        //dump($shopinfo_arr);
        $exshop_str = $this->check_day_match_uid($shopinfo_arr,$price);
        $where = "uid in ({$exshop_str})";
        $match_sell_list = Db::name("excoin_sell")->where($where)->select();
        $match_sell = shuffle_assoc($match_sell_list);
        halt(array_column($match_sell,'uid'));
    }



    # 检测当前承兑商匹配状态
    protected function check_day_match_uid(array $match_uid_arr,$price)
    {
        $stime = date("Y-m-d 00:00:00");
        $etime = date("Y-m-d 23:59:59");
        $match_uid_str = implode(',',$match_uid_arr);
        $excoin_match = Db::name("excoin_match")->where("match_uid in({$match_uid_str}) and price={$price} and status in(0,1) and time between '{$stime}' and '{$etime}'")->field("match_uid")->select();
        $excoin_match_arr = array_column($excoin_match,'match_uid');
        $res = array_diff($match_uid_arr,$excoin_match_arr);
        return implode(',',$res);
    }

}