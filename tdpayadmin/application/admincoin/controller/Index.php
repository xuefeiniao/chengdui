<?php

namespace app\admincoin\controller;

use app\admincoin\model\Article;
use app\admincoin\model\Coin;
use app\admincoin\model\Corder;
use app\admincoin\model\Torder;
use think\Db;
use think\Request;

class Index extends Base
{
    /**
     * 首页
     *
     * @return \think\Response
     */
    public function index()
    {
        $timed      = strtotime(date('Y-m-d'));
        $btc        = Coin::where('coin_name','btc')->sum('coin_balance');
        $eth        = Coin::where('coin_name','eth')->sum('coin_balance');
        $usdt       = Coin::where('coin_name','usdt')->sum('coin_balance');
        $recharge   = Corder::query("SELECT coin_name,SUM(`coin_money`) AS tp_sum FROM `coinpay_corder` WHERE  `status` = 'complete' GROUP BY `coin_name`");
        $Trecharge  = Corder::query("SELECT coin_name,SUM(`coin_money`) AS tp_sum FROM `coinpay_corder` WHERE  `status` = 'complete' AND `createtime` > $timed GROUP BY `coin_name`");
        $withdraw   = Torder::query("SELECT coin_name,SUM(`coin_money`) AS tp_sum FROM `coinpay_torder` WHERE  `status` = 'success' GROUP BY `coin_name`");
        $Twithdraw  = Torder::query("SELECT coin_name,SUM(`coin_money`) AS tp_sum FROM `coinpay_torder` WHERE  `status` = 'success' AND `createtime` > $timed GROUP BY `coin_name`");
        $recharge   = array_column($recharge,null, 'coin_name');
        $Trecharge  = array_column($Trecharge,null, 'coin_name');
        $withdraw   = array_column($withdraw,null, 'coin_name');
        $Twithdraw  = array_column($Twithdraw,null, 'coin_name');
        $arr        = getTime();
        # 订单统计
        $orderLine  = [];
        foreach ($arr as $key => $value) 
        {
            $time   = strtotime($value);
            $order  = Corder::where([['status', '=', 'success'], ['createtime', '<', strtotime($value)]])->count('id');
            $orderLine[] = ['y' => $value, 'item1' => $order];
        }
        # 币种统计
        $currencyOrder  = [];
        $cArr           = ['ETH', 'BTC', 'USDT'];
        foreach ($cArr as $key => $value) 
        {
            $n = Corder::where('status', '=', 'success')->count('id');
            $currencyOrder[] = ['label' => $value, 'value' => $n];
        }
        # 平台价
        $config = Db::name("exshop_config")->where(['name'=>'usdt_price'])->find();
        $usdt_price = !empty($config['val'])?$config['val']:6.5;
        $cny_money = $usdt * $usdt_price;

        # 总收益(手续费-代理奖励)
        $matchlist = Db::name("excoin_match")->where(['status'=>2])->field('num,shop_fee')->select();
        $shop_fee = array_sum(array_column($matchlist,'shop_fee'));
        $daili_reward = Db::name("reward_zhangdan")->sum('num');
        $shouyi = $shop_fee - $daili_reward;

        # 总收入
        $shop_num = array_sum(array_column($matchlist,'num'));

        # 当天收入
        $stime = date("Y-m-d 00:00:00");
        $etime = date("Y-m-d 23:59:59");
        $sum_day = Db::name("excoin_match")->where("status=2 and queren_time between '{$stime}' and '{$etime}'")->field('num,shop_fee')->sum('num');


        # 总提现 订单状态:error:提现失败,already=提现已处理,success:提现成功,unusul:订单异常,check:待审核，pass:审核通过，refuse:已拒绝
        $torderlist = Db::name("torder")->where(['status'=>'already'])->sum('coin_aumount');
        $cnylist = Db::name("cny_tixm")->where(['status'=>1])->sum('num');
        $tixian = $torderlist + $cnylist;

        #当日
        $_stime = strtotime($stime);
        $_etime = strtotime($etime);
        $torder_day = Db::name("torder")->where("status='already' and endtime between '{$_stime}' and '{$_etime}'")->sum('coin_aumount');
        $cny_day = Db::name("cny_tixm")->where("status=1 and end_time between '{$stime}' and '{$etime}'")->sum('num');
        $tixian_day = $torder_day + $cny_day;

        $indexArr = [
            'btc'           => $btc,                                // btc总数
            'eth'           => $eth,                                // eth总数
            'usdt'          => $usdt,                               // usdt总数
            'recharge'      => $recharge,                           // 总充值
            'withdraw'      => $withdraw,                           // 总提现
            'Twithdraw'     => $Twithdraw,                          // 今日提现
            'Trecharge'     => $Trecharge,                          // 今日充值
            // 'orderLine' => $orderLine,                          // 订单k线    
            'currencyOrder' => $currencyOrder,                   // 币种统计
            'cny_money'     => $cny_money,                   // 总收益-折合平台价
            'shouyi'        => $shouyi,              // 平台收益
            'shopnum'        => $shop_num,              // 总收入
            'tixian'        => $tixian,              // 总提现
            'sum_day'        => $sum_day,              // 当日总收入
            'tixian_day'        => $tixian_day,              // 当日总收入
        ];
        $hangqing = Db::name("hangqing")->where(['coin_en'=>'usdt'])->field("price")->find();
        $usdt_cny = !empty($hangqing['price']) ? round($hangqing['price'], 2) : 6.8;
        $notice = Article::where('ar_type',2)->select();
        $market = ['BTC','ETH','EOS','XRP'];
        $this->assign('market',$market);
        $this->assign('username', $this->user['nickname']);
        $this->assign('phone', $this->user['username']);    
        $this->assign('indexArr', $indexArr); 
        $this->assign('indexArr1', json_encode($orderLine));
        $this->assign('currencyOrder', json_encode($currencyOrder));
        $this->assign('article', $notice);
        $this->assign('usdt_cny', $usdt_cny);
        return view('Index/index');
    }

     /**
     * 获取K线
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function getKine(Request $request)
    {
        //
    }

    /**
     * 首页右侧内容.
     *
     * @return \think\Response
     */
    public function index1()
    {
        /* $timed     = strtotime(date('Y-m-d'));
        $btc        = Coin::where('coin_name','btc')->sum('coin_balance');
        $eth        = Coin::where('coin_name','eth')->sum('coin_balance');
        $usdt       = Coin::where('coin_name','usdt')->sum('coin_balance');
        $recharge   = Corder::query("SELECT coin_name,SUM(`coin_money`) AS tp_sum FROM `coinpay_corder` WHERE  `status` = 'complete' GROUP BY `coin_name`");
        $Trecharge  = Corder::query("SELECT coin_name,SUM(`coin_money`) AS tp_sum FROM `coinpay_corder` WHERE  `status` = 'complete' AND `createtime` > $timed GROUP BY `coin_name`");
        $withdraw   = Torder::query("SELECT coin_name,SUM(`coin_money`) AS tp_sum FROM `coinpay_torder` WHERE  `status` = 'success' GROUP BY `coin_name`");
        $Twithdraw  = Torder::query("SELECT coin_name,SUM(`coin_money`) AS tp_sum FROM `coinpay_torder` WHERE  `status` = 'success' AND `createtime` > $timed GROUP BY `coin_name`");
        $recharge   = array_column($recharge,null, 'coin_name');
        $Trecharge  = array_column($Trecharge,null, 'coin_name');
        $withdraw   = array_column($withdraw,null, 'coin_name');
        $Twithdraw  = array_column($Twithdraw,null, 'coin_name');
        $arr        = getTime();
        # 订单统计
        $orderLine  = [];
        foreach ($arr as $key => $value) 
        {
            $time   = strtotime($value);
            $order  = Corder::where([['status', '=', 'complete'], ['createtime', '<', strtotime($value)]])->count('id');
            $orderLine[] = ['y' => $value, 'item1' => $order];
        }
        # 币种统计
        $currencyOrder  = [];
        $cArr           = ['ETH', 'BTC', 'USDT'];
        foreach ($cArr as $key => $value) 
        {
            $n = Corder::where('status', '=', 'complete')->count('id');
            $currencyOrder[] = ['lable' => $value, 'value' => $n];
        }
        $indexArr = [
            'btc'       => $btc,                                // btc总数
            'eth'       => $eth,                                // eth总数
            'usdt'      => $usdt,                               // usdt总数
            'recharge'  => $recharge,                           // 总充值
            'withdraw'  => $withdraw,                           // 总提现
            'Twithdraw' => $Twithdraw,                          // 今日提现
            'Trecharge' => $Trecharge,                          // 今日充值                            
            'orderLine' => $orderLine,                          // 订单k线    
            'currencyOrder' => $currencyOrder                   // 币种统计
        ];
        $notice = Article::where('ar_type',1)->select();
        // dump($notice);die;
        $market = ['BTC','ETH','EOS','XRP'];
        $this->assign('market',$market);
        $this->assign('username', $this->user['nickname']);
        $this->assign('phone', $this->user['username']);
        $this->assign('indexArr', $indexArr);
        $this->assign('indexArr1', json_encode($indexArr["orderLine"]));
        $this->assign('article', $notice);*/
        $this->index();
        return view('Index/index1');
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
