<?php

namespace app\index\controller;

use think\Db;


require_once('../application/common.php');
class Apiexshop extends Paycommon
{

    # 充值
    /**
     * @param string $shopid 商户key
     * @param string $apikey 商户密钥
     * @param string $order_number 订单号（唯一）
     * @param string $order_title   订单标题
     * @param string $coin_cny  人民币金额
     * @param string $callback_addr 回调地址
     * @param string $sign 签名
     * @return \think\response\Json|\think\response\Redirect 成功跳转
     */
    public function isvi($shopid='', $apikey='', $order_number='', $order_title='', $coin_cny='', $callback_addr='', $sign='', $type = '')
    {
        if (!$this->request->isPost())
            return json(['status' => 'error', 'errorcode' => '1000', 'errormsg' => '非法请求']);

        if (empty($shopid) || !is_numeric($shopid) || empty($apikey) || strlen($sign) != 18 || empty($coin_cny) || $coin_cny <= 0 || empty($callback_addr) || empty($order_number) || empty($order_title))
            return json(['status' => 'error', 'errorcode' => '1001', 'errormsg' => '参数错误']);
        if (!empty($type)){
            $type = (int)$type;
            if (!in_array($type, [1, 2, 3])) {
                return json(['status' => 'error', 'errorcode' => '1001', 'errormsg' => '参数错误']);
            }
        }

        $sign_arr = $this->request->post();
        $sign_arr['coin_cny'] = number_format($sign_arr['coin_cny'], 8, '.', '');
        $shop_info = $this->auth_info($sign_arr, $sign);

        if (!$shop_info)
            return json(['status' => 'error', 'errorcode' => '1002', 'errormsg' => '签名错误']);

        # app_uid 匹配成功返还商户币的数量
        $app_uid = $shop_info['id'];
        # $ip = $this->request->ip();

        /*$api_list = Db::name('api_list')->where(['user_id'=>$app_uid])->field('id,ip')->select();
        if ($api_list){
            $api_list_arr = array_column($api_list,'ip');
            if (!in_array($ip,$api_list_arr)){
                return json(['status' => 'error', 'errorcode' => '1002', 'errormsg' => '签名错误']);
            }
        }*/

        if ($coin_cny < 10)
            return json(['status' => 'error', 'errorcode' => '4100', 'errormsg' => '单笔交易低于最小限额']);

        if ($coin_cny > 50000)
            return json(['status' => 'error', 'errorcode' => '4101', 'errormsg' => '单笔交易大于五万']);

        $match_info = Db::name('excoin_match')->where(['order_no'=>$order_number])->field('id,buy_id')->find();
        if ($match_info) return json(['status' => 'error', 'errorcode' => '1007', 'errormsg' => '订单号重复']);

        $log = @json_encode($this->request->param())."\r\n";
        error_log($log,3,'./isvi.txt');
        Db::startTrans();
        
        # 插入匹配
        $rs[] = Db::name('excoin_match')->insert([
            'name_en'=>'usdt', 'order_no'=>$order_number,
            'price'=>$coin_cny, 'status'=>0, 'app_uid'=>$app_uid,'callback_addr'=>$callback_addr,
        ]);
        session('_paytoken',makeCode(20));
        if (check_arr($rs)){
            Db::commit();
            $this->api_log($shop_info['id'], '充值', ['ok']);
            if (!empty($type) && in_array($type, [1, 2, 3])) {
                return redirect("/index/pay/pay_type/order_no/{$order_number}/type/{$type}");
            }else{
                return redirect("/index/pay/payint/order_no/{$order_number}");
            }
        }else{
            Db::rollback();
            return json(['status' => 'error', 'errorcode' => '1006', 'errormsg' => '充值失败']);
        }
    }


    # 提现
    public function tixm_bk($shopid='', $apikey='', $sign='', $order_number='',$coin_en='', $coin_cny='',$callback_addr='',$bank_type='',$bank_user_name='',$bank_name='',$bank_qrcode='',$bank_number=null)
    {
        if (!$this->request->isPost())
            return json(['status' => 'error', 'errorcode' => '1000', 'errormsg' => '非法请求']);
        if (empty($shopid) || !is_numeric($shopid) || empty($apikey) || strlen($sign) != 18 || !is_numeric($coin_cny)||empty($coin_cny) || $coin_cny <= 0 || empty($callback_addr) || empty($order_number) || $coin_en!='usdt'|| empty($bank_type)|| empty($bank_user_name)|| empty($bank_name)||empty($bank_qrcode))
           return json(['status' => 'error', 'errorcode' => '1001', 'errormsg' => '参数错误']);


        if ($bank_type==1){
            if (empty($bank_number)) return json(['status' => 'error', 'errorcode' => '1001', 'errormsg' => '参数错误']);
        }else{
            $bank_number='';
        }

        $sign_arr = $this->request->post();

        $sign_arr['coin_cny'] = number_format($sign_arr['coin_cny'], 8, '.', '');

        $shop_info = $this->auth_info($sign_arr, $sign);
        $shop_info['id'] = 103;

        if (!$shop_info)
            return json(['status' => 'error', 'errorcode' => '1002', 'errormsg' => '签名错误']);

        if ($coin_cny < 10)
            return json(['status' => 'error', 'errorcode' => '4100', 'errormsg' => '单笔交易低于最小限额']);

        if ($coin_cny > 10000)
            return json(['status' => 'error', 'errorcode' => '4101', 'errormsg' => '单笔交易大于一万']);

        $match_buy = Db::name("excoin_buy")->order("price_cny")->select();
        $buy_info = [];
        $buy_status = false;
        $num = 0;
        foreach ($match_buy as $v){
            $num = number_format($coin_cny/$v['price_cny'],8,'.','');
            if ($v['shengyu_num']>$num && $v['min']<$num){
                $buy_info = $v;
                $buy_status = true;
                break;
            }
        }
        if (!$buy_status) return json(['status' => 'error', 'errorcode' => '1003', 'errormsg' => '稍后再试']);
        $match_info = Db::name('excoin_match')->where(['order_no'=>$order_number])->field('id,buy_id')->find();
        if ($match_info) return json(['status' => 'error', 'errorcode' => '1007', 'errormsg' => '订单号重复']);

        $ip = $this->request->ip();
        $liushui_no = makeCode(20);
        # app_uid 匹配成功扣商户币的数量
        $app_uid = $shop_info['id'];
        # 打款失效时间
        $end_time_conf = config("app.dakuan_time");
        $time = time();
        $end_time = $time + $end_time_conf;
        $time_s = getTime($time);
        $end_time_s = getTime($end_time);

        $shop_coin = Db::name('coin')->where(['user_id'=>$shop_info['id'],'coin_name'=>'usdt'])->field('id,coin_balance,coin_balance_ex')->find();

        if (empty($shop_coin)||$shop_coin['coin_balance']<$num) return json(['status' => 'error', 'errorcode' => '4003', 'errormsg' => '余额不足']);

        Db::startTrans();
        # 更新订单
        $user_coin_id = $shop_coin['id'];
        $rs[] = Db::name('coin')->where(['id'=>$user_coin_id])->setDec('coin_balance',$num);
        $rs[] = Db::name('coin')->where(['id'=>$user_coin_id])->setInc('coin_balance_ex',$num);

        $rs[] = Db::name('excoin_buy')->where("id={$buy_info['id']} and shengyu_num >= {$num}")->setDec('shengyu_num',$num);
        $rs[] = Db::name('excoin_buy')->where("id={$buy_info['id']}")->setInc('match_num',$num);

        $rs[] = $sell_id =Db::name('excoin_sell')->insertGetId([
            'uid'=>$shop_info['id'],'name_en'=>'usdt',
            'num'=>$num,'min'=>0,
            'shengyu_num'=>$num,'price_cny'=>$buy_info['price_cny'],
            'bank_type'=>$bank_type,'bank_user_name'=>$bank_user_name,'bank_name'=>$bank_name,'bank_number'=>$bank_number,'bank_qrcode'=>$bank_qrcode,
            'ip'=>$ip,
        ]);

        # 插入匹配
        $rs[] = Db::name('excoin_match')->insert([
            'buy_id'=>$buy_info['id'],
            'sell_id'=>$sell_id,'match_uid'=>$buy_info['uid'],'name_en'=>'usdt',
            'order_no'=>$order_number,'liushui_no'=>$liushui_no,
            'num'=>$num,'price'=>$coin_cny,'time'=>$time_s,'end_time'=>$end_time_s,
            'status'=>0, 'app_uid'=>$app_uid,
        ]);

        # 更新订单状态
        $buy_info_now = Db::name('excoin_buy')->where("id={$buy_info['id']}")->field('match_num,num,status')->find();
        if($buy_info_now['match_num'] < $buy_info_now['num'])$status = 3;
        else if($buy_info_now['match_num'] == $buy_info_now['num'])$status = 1;
        else{
            return json(['status' => 'error', 'errorcode' => '1003', 'errormsg' => '稍后再试']);
        }
        Db::name('excoin_buy')->where("id={$buy_info['id']}")->update(['status'=>$status]);
        if (check_arr($rs)){
            Db::commit();
            $this->api_log($shop_info['id'], '提现', ['ok']);
            $data['price_cny'] = $buy_info['price_cny'];
            return json(['status' => 'ok', 'data' => $data]);
        }else{
            Db::rollback();
            return json(['status' => 'error', 'errorcode' => '4004', 'errormsg' => '提币失败']);
        }
    }


    # 回调
    public function call_back_status()
    {
        $match_list = Db::name('excoin_match')->where("status in(2,3,5) and callback_addr!='' and callback_status=0 and callback_ciuu < 10")->field('id,app_uid,status,order_no,num,price,callback_addr')->select();
        #$match_list = Db::name('excoin_match')->where("id=273 and callback_ciuu < 5")->field('id,app_uid,status,order_no,num,price,callback_addr')->select();

        $return_list = [];
        foreach ($match_list as $v){
            $url = $v['callback_addr'];
            $tmp =['待打款','待确认','已完成','过期订单','已取消','投诉订单'];
            $price = number_format($v['price'],8,'.','');
            $num = number_format($v['num'],8,'.','');
            $curldata=[
                'status'=>$v['status'],'order_no'=>$v['order_no'],'data'=>$tmp[$v['status']],
                'num'=>$num,'price'=>$price,
                ];
            $sign = $this->_sign($curldata,$v['app_uid']);
            $curldata['sign'] = $sign;
            $res = curl($url,$curldata);
            $result = json_decode($res['body'],true);
            if($result['status']=='ok'){
                $rs = Db::name('excoin_match')->where("id={$v['id']}")->update(['callback_status'=>1]);
                if ($rs){
                    $return_list[]=$v['id'];
                }
            }
            Db::name('excoin_match')->where("id={$v['id']}")->setInc('callback_ciuu',1);
        }
        return json($return_list);
    }

    public function call_back_now($match_id)
    {
        $match_list = Db::name('excoin_match')->where("id={$match_id} and status in(2,3,5) and callback_addr!='' and callback_status=0 and callback_ciuu < 10")->find();
        if ($match_list){
            $url = $match_list['callback_addr'];
            # 状态:0=待打款,1=待确认,2=已完成,3=>过期订单,4=已取消,5=投诉订单
            $tmp =['待打款','待确认','已完成','过期订单','已取消','投诉订单'];
            $price = number_format($match_list['price'],8,'.','');
            $num = number_format($match_list['num'],8,'.','');
            $curldata=[
                'status'=>$match_list['status'],'order_no'=>$match_list['order_no'],'data'=>$tmp[$match_list['status']],
                'num'=>$num,'price'=>$price,
            ];
            $sign = $this->_sign($curldata,$match_list['app_uid']);
            $curldata['sign'] = $sign;
            $res = curl($url,$curldata);
            $result = json_decode($res['body'],true);
            if($result['status']=='ok'){
                $rs = Db::name('excoin_match')->where("id={$match_list['id']}")->update(['callback_status'=>1]);
                if ($rs){
                    return $match_list['id'];
                }
            }
            Db::name('excoin_match')->where("id={$match_list['id']}")->setInc('callback_ciuu',1);
        }
        return false;
    }

    protected function _sign(array $arr,$shop_uid)
    {
        if (!is_array($arr) || empty($shop_uid) || !is_numeric($shop_uid)) return false;
        $shop_info = Db::name('user')->where(['id'=>$shop_uid])->field('id,shopid,apikey,apipassword')->find();
        if (empty($shop_info)) return false;
        unset($arr['sign']);
        ksort($arr);
        $arr_json_md5_pwd = md5(json_encode($arr,JSON_UNESCAPED_SLASHES)) . $shop_info['apipassword'];
        $sign = strtoupper(substr(md5($arr_json_md5_pwd), 0, 18));

        return $sign;
    }

    # 测试提交订单生成签名信息
    public function testsign()
    {
        $shopid = '10075';
        $apikey = 'KGCX4GAM4M3AJUDUUZJO';
        $apipwd = '855636';
        $order_number = 'H0716170435078000WI';
        $order_title = 'vivoPhone';
        $coin_cny = 100;
        $callback_addr = 'http://xeha7p.natappfree.cc/bpayer/callback';
        $sign = '681128A85BDDF109B0';
        $arr = [
            'shopid'=>$shopid,
            'apikey'=>$apikey,
            'order_number'=>$order_number,
            'order_title'=>$order_title,
            'coin_cny'=>number_format($coin_cny, 8, '.', ''),
            'callback_addr'=>$callback_addr,
        ];

        ksort($arr);
        $json = json_encode($arr,JSON_UNESCAPED_SLASHES);
        $json_md5 = md5($json);
        $json_md5_pwd = $json_md5 . $apipwd;
        $json_md5_pwd_md5 = md5($json_md5_pwd);
        $json_md5_pwd_md5_to_18 = strtoupper(substr($json_md5_pwd_md5, 0, 18));

        echo '第1步 正序后的json原串：'.$json.'<br / >';
        echo '第2步 加密后的值：'.$json_md5.'<br / >';
        echo '第3步 拼接商户密码：'.$json_md5_pwd.'<br / >';
        echo '第4步 md5转大写截取18位：'.$json_md5_pwd_md5_to_18.'<br / >';
    }

}