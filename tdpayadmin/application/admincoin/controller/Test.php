<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Db;

class Test extends Controller
{
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

        echo '第1步：'.$json.'<br / >';
        echo '第2步：'.$json_md5.'<br / >';
        echo '第3步：'.$json_md5_pwd.'<br / >';
        echo '第4步：'.$json_md5_pwd_md5_to_18.'<br / >';
    }


    public function aabb()
    {
        $client = new \coin\Eth('47.244.4.48',2199);
        if ($client->web3_clientVersion()) return json("false");
        $str = '0xde69d044740d2946c86110469d42f3cfcc1efa5980d457bf2c7a2232f307e6b3';

        halt($client->personal_importRawKey($str,'121212'));
    }

    public function t1()
    {
        $num = 142.85714286;
        $shopdaili = 0.006;
        $shopfee = 0.03;
        $shopzjs = 0.004;
        $exshopdaili = 0.0008;
        dump('商户手续费：'.($num*$shopfee));
        dump('商户代理奖励：'.($num*$shopdaili));
        dump('商户中间商奖励：'.($num*$shopzjs));
        dump('承兑商代理奖励：'.($num*$exshopdaili));
        dump(0.114285714288+0.85714285716+10.19);
    }

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


}