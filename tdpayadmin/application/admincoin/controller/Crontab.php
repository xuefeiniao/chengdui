<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Db;

class Crontab extends Controller {


    # 过期订单退回 +
    public function exshop_check_guoqi()
    {
        $now_time = _getTime(time());
        $matchlist = Db::name("excoin_match")->where("match_uid<>0 and status in(0,1) and end_time<'{$now_time}'")
            ->field("id,buy_id,sell_id,match_uid,app_uid,num")->select();
        if (!$matchlist)return exit("暂无数据");
            foreach ($matchlist as $k => $matchorder) {
                $id = $matchorder['id'];
                $num = $matchorder['num'];
                $buy_id = $matchorder['buy_id'];
                $sell_id = $matchorder['sell_id'];
                Db::startTrans();
                $rs[] = Db::name('excoin_match')->where("id={$id} and status in(0,1)")->update(['status' => 3]);
                $rs[] = Db::execute("UPDATE `coinpay_excoin_buy` SET `match_num`=`match_num`-{$num},`shengyu_num`=`shengyu_num`+{$num}
     WHERE id={$buy_id}");
                $rs[] = Db::execute("UPDATE `coinpay_excoin_sell` SET `match_num`=`match_num`-{$num},`shengyu_num`=`shengyu_num`+{$num}
     WHERE id={$sell_id}");
                Db::name('exshop_msg')->where(['match_id'=>$id])->update(['status'=>1]);
                if (check_arr($rs)){
                    Db::commit();
                    Db::execute("UPDATE `coinpay_excoin_buy` SET `status`=5 WHERE id={$buy_id} AND `match_num`=`shengyu_num`");
                    $data[]= $id;
                }else{
                    Db::rollback();
                }
            }
        return json($data);
    }

    # 检测消息列表状态 +
    public function check_msg_list()
    {
        $msglist = Db::name("exshop_msg")->where("status <> 1")->field("match_id")->order("match_id")->select();
        $msglist_arr = implode(',',array_column($msglist,'match_id'));
        $matchlist = Db::name("excoin_match")->where("id in({$msglist_arr})")->field("id,status")->select();
        $match_id_str = '';
        if ($matchlist){
            foreach ($matchlist as $k=>$v){
                if (in_array($v['status'],[2,3,4])){
                    $match_id_str .= $v['id'].',';
                }
            }
        }
        if (empty($match_id_str)) exit('暂无数据');

        $match_id_str=substr($match_id_str,0,-1);
        $res = Db::name("exshop_msg")->where("match_id in({$match_id_str})")->update(['status'=>1]);
        $res_cn = $res > 0 ? 'ok' : 'error';
        exit(json_encode(['status'=>$res_cn,'match_id'=>$match_id_str]));
    }


    # 回调通知
    public function call_back_now($match_id)
    {
        $match_list = Db::name('excoin_match')->where("id={$match_id} and status in(2,3,4,5) and callback_addr!='' and callback_status=0 and callback_ciuu < 10")->find();
        if ($match_list){
            $url = $match_list['callback_addr'];
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


    # 计划任务获取汇率
    public function get_hangqing($coin = '')
    {
        $coin_en = ['btc', 'usdt', 'eth', 'usd'];
        foreach ($coin_en as $k => $v) {
            $coin_en_str = $v . '_qc';
            $info = curl("http://api.zb.cn/data/v1/ticker?market={$coin_en_str}");
            $price = @json_decode($info['body'], true);
            if (!empty($price) && !empty($price['ticker'])) {
                $_price = $price['ticker']['last'];
                $data[$v] = !empty($_price) ? $_price : 1;
            }
        }

        $usd = file_get_contents("http://www.baidu.com/s?wd=usd%20cny&rsv_spt=1");
        preg_match("/<div>1\D*=(\d*\.\d*)\D*<\/div>/", $usd, $converted);
        $converted = preg_replace("/[^0-9.]/", "", $converted[1]);
        $data['usd'] = number_format($converted, 3, '.', '');
        $val = [];
        foreach ($data as $k => $v) {
            Db::name('hangqing')->where(['coin_en' => $k])->update(['price'=>$v,'addtime'=>_getTime()]);
        }

        if (!empty($coin) && in_array($coin, $coin_en)) {
            return $data[$coin];
        }

        return json($data);
    }


    # t1的余额转t0
    public function t1_to_t0()
    {
        $stime = date("Y-m-d 00:00:00");
        $etime = date("Y-m-d 23:59:59");
        $t1_log = Db::name('t1_log')->where("time between '{$stime}' and '{$etime}'")->find();
        if ($t1_log) return json(['status'=>'error','errormsg'=>'请勿重复执行！']);
        $coin_list = Db::name('coin')->where("coin_name='usdt' and coin_balance_t1>0")->select();
        if (empty($coin_list)) return json(['status'=>'ok','data'=>'暂无数据']);
        $res = [];
        foreach ($coin_list as $k=>$v){
            $exec = Db::execute("UPDATE `coinpay_coin` SET `coin_balance`=`coin_balance`+{$v['coin_balance_t1']},`coin_balance_t1`=0 WHERE id={$v['id']}");
            if ($exec){
                $res[$k]['user_id'] = $v['user_id'];
                $res[$k]['coin_balance'] = $v['coin_balance'];
                $res[$k]['coin_balance_t1'] = $v['coin_balance_t1'];
            }
        }
        $res_json = json_encode($res);
        Db::name('t1_log')->insert(['res'=>$res_json]);
        return json(['status'=>'ok']);
    }
    # 回调验签
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




    public function exshop_check_guoqi_o()
    {
        $now_time = _getTime(time());
        $matchlist = Db::name("excoin_match")->where("match_uid<>0 and status in(0,1) and end_time<'{$now_time}'")
            ->field("id,buy_id,sell_id,match_uid,app_uid,num")->select();
        if (!$matchlist)return exit("暂无数据");
        foreach ($matchlist as $k => $matchorder) {
            $id = $matchorder['id'];
            $num = $matchorder['num'];
            $buy_id = $matchorder['buy_id'];
            $sell_id = $matchorder['sell_id'];
            Db::startTrans();
            $rs[] = Db::name('excoin_match')->where("id={$id} and status in(0,1)")->update(['status' => 3]);
            $rs[] = Db::execute("UPDATE `coinpay_excoin_buy` SET `match_num`=`match_num`-{$num},`shengyu_num`=`shengyu_num`+{$num}
     WHERE id={$buy_id}");
            $rs[] = Db::execute("UPDATE `coinpay_excoin_sell` SET `match_num`=`match_num`-{$num},`shengyu_num`=`shengyu_num`+{$num}
     WHERE id={$sell_id}");
            Db::name('exshop_msg')->where(['match_id'=>$id])->update(['status'=>1]);
            if (check_arr($rs)){
                Db::commit();
                Db::execute("UPDATE `coinpay_excoin_buy` SET `status`=5 WHERE id={$buy_id} AND `match_num`=`shengyu_num`");
                $data[]= $id;
            }else{
                Db::rollback();
            }
        }
        return json($data);
    }
}
