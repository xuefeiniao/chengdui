<?php

namespace app\index\controller;
use think\Db;

require_once('../application/common.php');
class Pay extends Paycommon
{
    # 买入库
    public function index()
    {
        session('_paytoken',makeCode(20));
        $data=$this->request->param();
        if($this->request->isPost()) {
            $order_no = input('order_no');
            $money = input('order_money');
            $msg = input('order_msg');
            $sign = input('sign');
            $_sign = md5($order_no.$money.'pcdemo');
            if ($sign!=$_sign){
                return json(["code" => 204, "result" => "操作失败"]);
            }
            $app_uid = 127;
            Db::startTrans();

            # 插入匹配
            $rs[] = Db::name('excoin_match')->insert([
                'name_en'=>'usdt', 'order_no'=>$order_no,'msg'=>$msg,
                'price'=>$money, 'status'=>0, 'app_uid'=>$app_uid,
            ]);

            if (check_arr($rs)){
                Db::commit();
                $addr = url('pay/payint',['order_no'=>$order_no]);
                //user_log($user_info["username"],"出售广告",$ip);
                return json(["code" => 200, "result" => $addr]);
            }else{
                Db::rollback();
                return json(["status" => 0, "msg" => "发布失败"]);
            }
        }

        $where = 'match_uid!=0 and app_uid=103';
        $buy_list = Db::name('excoin_buy')->where('type=1')->select();
        if ($buy_list){
            $buy_list_arr = array_column($buy_list,'id');
            $buy_list_str = implode(',',$buy_list_arr);
            $where .=" and buy_id not in({$buy_list_str})";
        }

        $list = Db::name("excoin_match")->where($where)->order("id DESC")->paginate(10);
        $page = $list->render();
        $order_num = 'ceui_'.date("YmdHis").makeCode(10);
        return view("index", ["order_num"=>$order_num,"list" => $list,'page'=>$page]);
    }

    # 选择支付页面
    public function payint()
    {
        $data=$this->request->param();
        if (empty($data['order_no'])) return json(["status" => 'error', "errormsg" => '非法订单']);
        $order = Db::name("excoin_match")->where(['order_no'=>$data['order_no']])->find();
        if (empty($order))
            return json(["status" => 'error', "errormsg" => '非法订单1']);

        if ($order['type']!=0){
            $this->redirect(url('pay/paydetail',['order_no'=>$order['order_no']]));
        }
        $userapp = Db::name("user")->where(['id'=>$order['app_uid']])->field("id,username,api_return_url")->find();
        if (empty($userapp)) return json(["status" => 'error', "errorcode" => '102',"errormsg" => '非法订单']);
        $api_return_url = !empty($userapp['api_return_url'])?$userapp['api_return_url']:'';

        session('paytoken',makeCode(40,true));

        return view("payint", ["order"=>$order,'api_return_url'=>$api_return_url]);
    }

    # 支付
    public function paydetail()
    {
        $data=$this->request->param();
        $order_no = input('order_no');
        if (empty($order_no)) return json(["status" => 'error', "errorcode" => '102',"errormsg" => '非法订单1']);

        $order = Db::name("excoin_match")->where(['order_no'=>$data['order_no']])->find();

        if (empty($order) || $order['type'] == 0||$order['status'] == 2)
            return json(["status" => 'error', "errorcode" => '102',"errormsg" => '非法订单2']);

        $userapp = Db::name("user")->where(['id'=>$order['app_uid']])->field("id,username,api_return_url")->find();
        if (empty($userapp)) return json(["status" => 'error', "errorcode" => '102',"errormsg" => '非法订单3']);

        $api_return_url = !empty($userapp['api_return_url'])?$userapp['api_return_url']:'';

        # 超时取消匹配
        $end_time = $order['end_time'];
        if (getTime($end_time,false)<time()){
            Db::startTrans();
            $sell_id = $order['sell_id'];
            $buy_id = $order['buy_id'];
            $num = $order['num'];

            $rs[] = Db::name("excoin_match")->where(['order_no'=>$order['order_no']])->update(['status'=>3]);
            $rs[] = Db::execute("UPDATE `coinpay_excoin_buy` SET `match_num`=`match_num`-{$num},`shengyu_num`=`shengyu_num`+{$num}
     WHERE id={$buy_id} and match_num >= {$num}");
            $rs[] = Db::execute("UPDATE `coinpay_excoin_sell` SET `match_num`=`match_num`-{$num},`shengyu_num`=`shengyu_num`+{$num}
     WHERE id={$sell_id} and match_num >= {$num}");

            if (in_array($order['status'],[2,4])){
                $rs[] = Db::name('excoin_sell')->where("id={$sell_id}")->update(['status'=>0]);
            }
            if (check_arr($rs)){
                Db::commit();
                Db::execute("UPDATE `coinpay_excoin_buy` SET `status`=5 WHERE id={$buy_id} AND `match_num`=`shengyu_num`");
                return json(["status" => 'error', "errorcode" => '103',"errormsg" => '非法订单']);
            }else{
                Db::rollback();
                return json(["status" => 'error', "errorcode" => '104',"errormsg" => '非法订单']);
            }
        }

        $end_time = getTime($order['end_time_s'],false);
        $yu_time_s = $end_time - time();
        # session('paytoken',null);
        session('paytoken',makeCode(40,true));
        return view("paydetail", ["yu_time_s"=>$yu_time_s,'order'=>$order,'api_return_url'=>$api_return_url]);
    }

    # 选支付方式
    public function pay_type()
    {
        $_paytoken = session('_paytoken');
        if (!$_paytoken){
            return json(["status" => 'error', "errorcode" => '101',"errormsg" => '非法订单']);
        }

        $data=$this->request->param();
        if($this->request->isGet()) {
            $type = (int)input("type");
            $order_no = input("order_no");

            $order = Db::name("excoin_match")->where(['order_no'=>$data['order_no']])->find();
            if (empty($order)||$order['price']<0)
                return json(["status" => 'error', "errormsg" => '非法订单']);

            if (!empty($order['type'])){
                if (in_array($order['type'],[1,2,3])){
                    $this->redirect(url('pay/paydetail',['order_no'=>$order['order_no']]));
                }
                return json(["status" => 'error', "errormsg" => '非法订单1']);
            }

            $match_id = $order['id'];
            $type_arr = [1,2,3];
            $liushui_no = $order['id'].makeCode(2);
            if (in_array($type,$type_arr)){
                $where = "is_match=1 and bank_type like '%{$type}%'";
                #
                $shopinfo = Db::name("exshop_shopinfo")->where(['uid'=>$order['app_uid']])->select();
                if ($shopinfo) {

                    $price = $order['price'];
                    $exshop_str = $this->check_day_match_uid(array_column($shopinfo,'shop_uid'),$price);
                    $price_round = round($price,2);
                    if (empty($exshop_str)) return json(["status" => 'error', "errormsg" => "充值金额不符合，请充值大于或小于{$price_round}"]);
                    $where .= " and uid in ({$exshop_str})";
                }
                #
                $match_sell_list = Db::name("excoin_sell")->where($where)->select();
                $match_sell = shuffle_assoc($match_sell_list);

                $log = @json_encode($match_sell)."\r\n";
                error_log($log,3,'./match_sell.log');
                _elog(@array_column($match_sell,'uid'),"./sell.log");
                
                $sell_info = [];
                $sell_status = false;
                foreach ($match_sell as $v) {
                    $num = number_format($order['price'] / $v['price_cny'], 8, '.', '');
                    if ($v['shengyu_num'] > $num && $v['min'] < $num) {
                        if ($this->check_exshop($v['uid'],$num)){ // 限额
                            $sell_info = $v;
                            $sell_status = true;
                            break;
                        }
                    }
                }

                if (empty($sell_info)) return json(["code" => 204, "result" => "支付订单异常，请联系管理员"]);
                if (!$sell_status) return json(["code" => 204, "result" => "订单金额小于最低限额"]);

                $bank = Db::name("user_bank")->where("uid = {$sell_info['uid']} and type = {$type}")->find();
                if (empty($bank)) return json(["status" => 'error', "errorcode" => '101',"errormsg" => '支付方式异常，请联系管理员']);
                $ip = $this->request->ip();

                # 打款失效时间
                $exshop_conf_arr = Db::name('exshop_config')->select();
                $exshop_conf = array_column($exshop_conf_arr,'val','name');
                if (empty($exshop_conf)||$exshop_conf['dakuan_time']< 0) return json(["code" => 204, "result" => "稍后再试"]);
                $end_time_conf = $exshop_conf['dakuan_time'] > 0 ? $exshop_conf['dakuan_time']: 86400;
                $time = time();
                $end_time = $time + $end_time_conf * 3;
                $end_time_n = $time + $end_time_conf;
                $end_time_s = getTime($end_time);
                $end_time_n = getTime($end_time_n);
                Db::startTrans();
                # 更新订单
                $rs[] = Db::name('excoin_sell')->where("id={$sell_info['id']} and shengyu_num >= {$num}")->setDec('shengyu_num',$num);
                $rs[] = Db::name('excoin_sell')->where("id={$sell_info['id']}")->setInc('match_num',$num);
                $rs[] = $buy_id= Db::name('excoin_buy')->insertGetId([
                    'uid'=>$sell_info['uid'], # 商户uid
                    'name_en'=>'usdt','match_num'=>$num, 'num'=>$num,'min'=>$sell_info['min'],
                    'shengyu_num'=>0,'price_cny'=>$sell_info['price_cny'],
                    'status'=>1,
                    'ip'=>$ip,
                ]);

                # 更新匹配
                $rs[] = Db::name('excoin_match')->where(['id'=>$match_id])->update([
                    'buy_id'=>$buy_id, 'sell_id'=>$sell_info['id'],'match_uid'=>$sell_info['uid'],
                    'name_en'=>'usdt','liushui_no'=>$liushui_no,
                    'num'=>$num,'type'=>$type,
                    'user_name'=>$bank['user_name'],'bank_name'=>$bank['bank_name'],'bank_number'=>$bank['bank_number'],'qrcode'=>$bank['qrcode'],
                    'status'=>1,'dakuan_time'=>getTime(),'end_time'=>$end_time_s,'end_time_s'=>$end_time_n,
                ]);

                # 消息提醒
                $content = "订单号：[ {$order['order_no']} ]，数量[ {$num} ]，-金额[ {$order['price']} ]";
                $rs[] = Db::name("exshop_msg")->insert(['uid'=>$sell_info['uid'],'match_id'=>$order['id'],'content'=>$content]);
                # 更新订单状态
                $sell_info_now = Db::name('excoin_sell')->where("id={$sell_info['id']}")->field('id,match_num,num,status')->find();
                if($sell_info_now['match_num'] < $sell_info_now['num'])$status = 3;
                else if($sell_info_now['match_num'] == $sell_info_now['num'])$status = 1;
                else{
                    return json(["code" => 204, "result" => "稍后再试12"]);
                }
                Db::name('excoin_sell')->where("id={$sell_info['id']}")->update(['status'=>$status]);

                if (check_arr($rs)){
                    Db::commit();
                    session('paytoken',null);
                    $this->redirect(url('pay/paydetail',['order_no'=>$order_no]));
                }else{
                    Db::rollback();
                    return json(["status" => 0, "msg" => "发布失败"]);
                }
            }
        }
        return json(["status" => 'error', "errormsg" => '非法订单']);
    }
    # 确认打款
    public function pay_dakuan()
    {
        if($this->request->isPost()) {
            $order_id = input("post.order_id");
            $_paytoken = input("post._paytoken");
            $paytoken = session('paytoken');
            if ($_paytoken!=$paytoken) return json(["status" => 'error', "errorcode" => 101,"errormsg" => '非法提交']);
            $match_order = Db::name("excoin_match")->where(['id'=>$order_id])->field("id,match_uid,order_no,num,price,order_no")->find();
            if (!$match_order)return json(["status" => 'error', "errorcode" => 101,"errormsg" => '非法提交']);
            session('paytoken',null);

            Db::startTrans();
            $rs[] = Db::name("excoin_match")->where(['id'=>$order_id,'status'=>0])->update(['status'=>1,'dakuan_time'=>getTime()]);
            # 消息
            $content = "订单号：[ {$match_order['order_no']} ]，数量[ {$match_order['num']} ]，+金额[ {$match_order['price']} ]";
            $rs[] = Db::name("exshop_msg")->insert(['uid'=>$match_order['match_uid'],'match_id'=>$match_order['id'],'content'=>$content]);

            if (check_arr($rs)){
                Db::commit();
                return json(["status" => 'ok', "msg" => '确认成功']);
            }else{
                Db::rollback();
            }
        }
        return json(["status" => 'error', "errorcode" => 102,"errormsg" => '付款失败']);
    }

    # 取消打款
    public function pay_quxc()
    {
        if($this->request->isPost()) {
            $order_id = input("post.order_id");
            $_paytoken = input("post._paytoken");
            $paytoken = session('paytoken');
            if ($_paytoken!=$paytoken) return json(["status" => 'error', "errorcode" => 101,"errormsg" => '非法提交']);
            $match_order = Db::name("excoin_match")->where(['id'=>$order_id])->field("id,sell_id,buy_id,match_uid,order_no,num,price,order_no")->find();
            if (!$match_order)return json(["status" => 'error', "errorcode" => 101,"errormsg" => '非法提交']);

            $sell_id = $match_order['sell_id'];
            $buy_id = $match_order['buy_id'];
            $num = $match_order['num'];

            $sell_order = Db::name('excoin_sell')->where("id={$sell_id}")->find();
            if(empty($sell_order))return ['status'=>"error","errorcode"=>"101","errormsg"=>"卖出订单异常"];

            $buy_order = Db::name('excoin_buy')->where("id={$buy_id}")->find();
            if(empty($buy_order))return ['status'=>"error","errorcode"=>"101","errormsg"=>"买入订单异常"];

            session('paytoken',null);

            Db::startTrans();
            $rs[] = Db::name('excoin_match')->where(['id'=>$order_id,'status'=>0])->update(['status'=>4,'callback_status'=>0]);
            $rs[] = Db::execute("UPDATE `coinpay_excoin_buy` SET `match_num`=`match_num`-{$num},`shengyu_num`=`shengyu_num`+{$num} WHERE id={$buy_id} AND `match_num`>={$num}");
            $rs[] = Db::execute("UPDATE `coinpay_excoin_sell` SET `match_num`=`match_num`-{$num},`shengyu_num`=`shengyu_num`+{$num} WHERE id={$sell_id} AND `match_num`>={$num}");

            if (check_arr($rs)){
                Db::commit();
                Db::execute("UPDATE `coinpay_excoin_buy` SET `status`=2 WHERE id={$buy_id} AND `shengyu_num`=`num`");

                $act = controller("apiexshop");
                $act->call_back_now($order_id);

                return json(["status" => 'ok', "msg" => '订单取消成功']);
            }else{
                Db::rollback();
            }
        }
        return json(["status" => 'error', "errorcode" => 102,"errormsg" => '订单取消失败']);
    }

    # 检测当日限额
    protected function check_exshop($exshop_uid,$num)
    {
        $exshop_conf = Db::name('user_api')->where("user_id = {$exshop_uid}")->field("day_max_shoukuan")->find();
        if (empty($exshop_conf)) return true;
        $day_max_shoukuan = $exshop_conf['day_max_shoukuan'];
        $stime = date("Y-m-d 00:00:00");
        $etime = date("Y-m-d 23:59:59"); #状态:0=待打款,1=待确认,2=已完成,3=>过期订单,4=已取消,5=投诉订单
        $match = Db::name('excoin_match')->where("match_uid={$exshop_uid} and status in(0,1,2,5) and time between '{$stime}' and '{$etime}'")->sum("num");
        if ($day_max_shoukuan > ($match+$num)) {
            return true;
        } else {
            return false;
        }
    }

    public function daojitime($time)
    {
        $time = strtotime($time);
        $etime = $time + 15 * 60 *100;

        if ($etime<time()){
            return false;
        }

        return $etime-$time;
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
    /*public function aabb(){
        $match_sell_list = Db::name("excoin_sell")->select();
        $match_sell = shuffle_assoc($match_sell_list);
        dump($match_sell);
    }*/



}