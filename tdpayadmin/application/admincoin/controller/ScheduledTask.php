<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Request;
use think\Db;
use app\admincoin\model\Corder;
use app\admincoin\model\Coin;
use app\admincoin\model\Config;
use app\admincoin\model\CallbackLog;
use app\admincoin\model\CoinLog;
use app\admincoin\model\UserM;
use app\admincoin\model\CoinBtc;
use app\admincoin\model\CoinEth;
use app\admincoin\model\CoinConfig;
use app\admincoin\model\Test;
use app\admincoin\model\RateLog;
use Denpa\Bitcoin\Client;

class ScheduledTask extends Controller
{

    /**
     * ETH订单处理
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function doEthOrder(Request $request)
    {
        $list       = Corder::where([['status', '=', 'await'], ['id', '>=', '43'], ['address', '<>', ''], ['coin_name', '=', 'eth']])->all();
        if (!$list->toArray()) return '没有待处理ETH订单'; 
        $address    = '';
        $confirNum  = CoinConfig::where('name','eth')->value('query');
        foreach ($list as $key => $value) 
        {
            $address    = $value['address'];
            $url        = "https://api.etherscan.io/api?module=account&action=txlist&address=$address&sort=desc&tag=latest&apikey=ERXIYCNF6PP3ZNQAWICHJ6N5W7P212AHZI";
            $result     = getEthApi($url);
            if (count($result['result'])) $content = $result['result'][0]; else continue;
            if ($content['confirmations'] >= $confirNum && $content['timeStamp'] >= strtotime($value['createtime']))
            {
                $result     = $this->doOrder($content, $value);
                echo $result;
            }
            else
            {
                continue;
            }
        }
        
    }

    /**
     * BTC类订单处理
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function doBtcClassOrder(Request $request)
    {
        $list       = Corder::where([['status', '=', 'await'], ['id', '>=', '43'], ['address', '<>', ''], ['coin_name', 'in', 'usdt,btc']])->all();
        if (!$list->toArray()) return '没有待处理usdt,btc订单'; 
        $address    = '';
        $confirArr  = CoinConfig::where('name', 'in','btc,usdt')->column('query','name');
        $btcConfig  = [

        ];
        foreach ($list as $key => $value) 
        {
            $confirNum  = $confirArr[$value['coin_name']];
            $address    = $value['address'];
            $btcClient  = new Client('http://usdt:Test@126.com@47.52.93.208:2199/');
            // dump($value['coin_name'] = 'btc');
            if ($value['coin_name'] == 'usdt')
            {
                $cList = $btcClient->omni_listtransactions('*')->get();
            }
            else if ($value['coin_name'] == 'btc')
            {
                $cList = $btcClient->listtransactions('*')->get();
            }

            
            foreach ($cList as $k => $val) 
            {
                if (!array_key_exists('address', $val)) break;
                if ($value['coin_name']== 'btc' && $value['address'] == $val['address'] && $val['confirmations'] >= $confirNum && $val['category'] == 'receive') 
                {
                    $result     = $this->doOrder($val, $value);
                    echo $result;
                }
                else if ($value['coin_name'] == 'usdt' && $value['address'] == $val['referenceaddress'] && $val['confirmations'] >= $confirNum)
                {
                    $result     = $this->doOrder($val, $value);
                    echo $result;
                }
            }
        }
        
    }

    /**
    *   订单真正到账处理
    *
    * @param  array  查询结果
    * @param  array  订单信息
    * @return  string  success表示所有都成功，否则返回错误信息
    */
    public function doOrder($content, $order)
    {
        
        if ($order['coin_name'] == 'eth') $trueNum = $content['value'] / 1000000000000000000; else $trueNum  = $content['amount'];
        if ($trueNum != $order['coin_money']) $upStatus = 'unusul'; else $upStatus = 'success';
        $coinFrom   = array_key_exists('from', $content) ? $content['from'] : '';
        $txid       = array_key_exists('txid', $content) ? $content['txid'] : $content['hash'];

        # 先添加用户资金变动记录，后用户加币（避免重复加币）
        $rate       = CoinConfig::column('recharge_rate', 'name');
        $addNum     = number_format($trueNum * (1 - $rate[$order['coin_name']]/100), 8);
        $rateAmount = $trueNum - $addNum;
        // dump($content);die;
        # 组装订单更新字段数组
        $updateOrderArr = [
            'true_money'    => $addNum,
            'coin_from'     => $coinFrom,
            'txid'          => $txid,
            'status'        => $upStatus,
            'coin_fee'      => $rateAmount,
            'updatetime'    => time()
        ];
        $updateN = Corder::where('id',$order['id'])->update($updateOrderArr);
        if (!$updateN) return '订单更新失败';

        $addLog = [
            'user_id'       => $order['user_id'], 
            'coin_name'     => $order['coin_name'], 
            'coin_money'    => $addNum, 
            'type'          => 1, 
            'createtime'    => time(),
            'desc'          => '订单id:'.$order['id'],
        ];

        Db::startTrans();
        try {
            $log    = new CoinLog;
            $lN     = $log->save($addLog);
            if (!$lN) return '记录添加失败';
            $coin   = Coin::where([['user_id', '=', $order['user_id']],['coin_name', '=', $order['coin_name']]])->find();
            if (!$coin) 
            {
                # 余额表中不存在记录
                $coin = Coin::create([
                    'user_id'       => $order['user_id'],
                    'coin_name'     => $order['coin_name'],
                    'coin_balance'  => $addNum,
                ]);
                $cN = $coin->id;
            }
            else
            {
                $coin->coin_balance = $coin->coin_balance + $trueNum;
                $cN     = $coin->save();
            }
            if (!$cN) return '用户加币失败';

            # 添加手续费记录
            RateLog::create([
                'user_id'   => $order['user_id'],
                'coin_name' => $order['coin_name'],
                'amount'    => $rateAmount,
                'order_id'  => $order['id'],
                'type'      => 1,
                'time'      => date('Y-m-d H:i:s')
            ]);
            # 地址状态重置
            if ($order['coin_name'] == 'btc' || $order['coin_name'] == 'usdt')
            {
                $aN  = CoinBtc::where('address', $order['address'])->update(['use_status' => 'no']);
            }
            else if ($order['coin_name'] == 'eth')
            {
                $aN  = CoinEth::where('address', $order['address'])->update(['use_status' => 'no']);
            }
            Db::commit();
            if ($aN) return 'success'; else return '地址重置失败';
        } catch (Exception $e) {
            Db::rollback();
        }
        
    }

    /**
     * 回调通知（总通知10次，每次通知间隔:第几次*10分钟）
     *
     * @return string 成功或失败
     */
    public function callback(Request $request)
    {
        $callList   = Corder::where('status', 'in', 'success,unusul')->all();
        $logArr     = [];
        $orderArr   = [];
        foreach ($callList as $key => $value) 
        {
          	if (!$value['callback_addr']) continue;
            $callTime   = $value['callback_num'] * 600 + $value['updatetime'];
            if ($value['callback_num'] != 0 && $callTime < time()) continue;
            $callResult = $this->doCallback($value);

            # 组装回调记录数组
            $arr    = [
                    'callback_address' => $value['callback_addr'],
                    'order_id'      => $value['id'],
                    'back_msg'      => $callResult,
                    'createtime'    => date('Y-m-d H:i:s')
                ];
            array_push($logArr, $arr);

            # 组装待更新订单状态数组
            $cNum   = $value['callback_num'] + 1;
            $oArr   = [
                    'id'            => $value['id'],
                    'callback_num'  => $cNum,
                    'updatetime'    => time(),
                ];
            if ($callResult == 'SUCCESS') $oArr['status'] = 'finish';
            array_push($orderArr, $oArr);
        }
        if (!$orderArr || !$logArr) return '没有回调记录';
        $calllog    = new CallbackLog;
        $cOrder     = new Corder;
        $lN         = $calllog->saveAll($logArr);
        $oN         = $cOrder->saveAll($orderArr);
        return '添加回调记录'.count($lN->toArray()).'条，'.'更新订单状态'.count($oN->toArray()).'条';
    }

    /**
     * 执行回调通知
     *
     * @param array 订单信息
     * @return string 回调返回信息
     */
    public function doCallback($orderInfo)
    {
        $backParams   = [
                'orderNumber'   => $orderInfo['order_number'],
                'currency'      => $orderInfo['coin_name'],
                'orderAmount'   => $orderInfo['coin_money'],
                'trueNum'       => $orderInfo['true_money'],
            ];
        ksort($backParams);
        $apiPass    = UserM::where('id', $orderInfo['user_id'])->value('apipassword');
        $sign       = strtoupper(substr(md5(md5(json_encode($backParams)).$apiPass), 0, 18));

        # 拼接回传参数
        $backStr    = '';
        foreach ($backParams as $k => $val) 
        {
            $backStr .= $k.'='.$val.'&';
        }
        $backStr    = $backStr.'sign='.$sign;
        $url        = $orderInfo['callback_addr'].'?'.$backStr;
        $msg        = file_get_contents($url);
        return $msg;
    }

    /**
     * 失效订单处理
     *
     * @param array 订单信息
     * @return string 回调返回信息
     */
    public function timeoutOrder()
    {
        $id     = [];
        $ethId  = [];
        $btcId  = [];
        $ethstr = '';
        $btcstr = '';
        $where  = [
            ['status', '=', 'await'],
            ['endtime', '<', time()],
        ];
        $list   = Corder::where($where)->all();
        foreach($list as $val)
        {
            # 订单表处理数据
            array_push($id, ['id' => $val['id'], 'status' => 'error']);
            if (!$val['address']) continue;

            # 地址表处理数据
            if ($val['coin_name'] == 'eth')
            {
                $ethstr .= $val['address'].',';
            }
            else if ($val['coin_name'] == 'btc' || $val['coin_name'] == 'usdt')
            {
                $btcstr .= $val['address'].',';
            }
        } 

        Db::startTrans();
        try {
            $corder = new Corder;
            $cN     = $corder->saveAll($id)->count();
            $ethO   = new CoinEth;
            $btcO   = new CoinBtc;
            $ethids = $ethO->where('address', 'in', substr($ethstr, 0, -1))->column('id');
            $btcids = $btcO->where('address', 'in', substr($btcstr, 0, -1))->column('id');
            foreach ($ethids as $key => $value) array_push($ethId, ['id' => $value, 'status' => 'no']);
            foreach ($btcids as $key => $value) array_push($btcId, ['id' => $value, 'status' => 'no']);
            $ethN   = $ethO->saveAll($ethId)->count();
            $btcN   = $btcO->saveAll($btcId)->count();
            Db::commit();
            return '处理无效订单'.$cN.'条，重置eth类地址'.$ethN.'条，btc类地址'.$btcN.'条';
        } catch (Exception $e) {
            Db::rollback();
            return '超时订单处理失败';
        }
        
    }
}
