<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/4/10
 * Time: 15:18
 */

namespace app\mobile\controller;
use think\Db;
use think\Exception;
use zb\ZbAPI;
class Autoplan
{
    /**
     * 计划任务判断OTC订单是否超时
     */
    public function otcouttime(){
        /**
         * 获取所有已经匹配的订单
         */
        $order=Db::name("otc_user")
            ->where("status","normal")
            ->where("nowstatus",1)
            ->select();
        $outtime=Db::name("config")->where("name","outtime")->value("value") ?? 30;
        $nowtime=time();
        foreach($order as $key=>$val){
            $outtimes=$outtime*60+$val["createtime"];       //订单过期时间
            /**
             * 买入订单撤销直接更新挂单的额度
             */
            if($val["type"]==1){
                if($nowtime>=$outtimes){
                    Db::startTrans();
                    try{
                        $S1=Db::name("otc_user")->where("id",$val["id"])->setField("nowstatus",4);
                        if(!$S1) throw new Exception("撤销订单失败");
                        $S2=Db::name("otc")
                            ->where("id",$val["otc_id"])
                            ->where("nowstatus",1)
                            ->setDec("pipei",$val["number"]);
                        if(!$S2) throw new Exception("更新订单失败");
                        Db::commit();
                    } catch (\Exception $e){
                        Db::rollback();
                        echo $e->getMessage();
                    }
                }else{
                    continue;
                }
            }
            /**
             * 卖出订单撤销先退还用户冻结USDT，在更新订单的额度
             */
            if($val["type"]==2){
                if($nowtime>=$outtimes){
                    Db::startTrans();
                    try{
                        $S1=Db::name("otc_user")->where("id",$val["id"])->setField("nowstatus",4);
                        if(!$S1) throw new Exception("撤销订单失败");
                        /**
                         * 解除用户冻结USDT 返回到可用余额
                         */
                        $S2=Db::name("coin")
                            ->where("user_id",$val["user_id"])
                            ->where("coin_name","usdt")
                            ->setDec("coin_balanced",$val['number']);
                        if(!$S2) throw new Exception("解除冻结USDT失败");
                        $S3=Db::name("coin")
                            ->where("user_id",$val["user_id"])
                            ->where("coin_name","usdt")
                            ->setInc("coin_balance",$val['number']);
                        if(!$S3) throw new Exception("更新冻结USDT失败");
                        $S5=Db::name("otc")
                            ->where("id",$val["otc_id"])
                            ->where("nowstatus",1)
                            ->setDec("pipei",$val["number"]);
                        if(!$S5) throw new Exception("更新订单失败");
                        Db::commit();
                    } catch (\Exception $e){
                        Db::rollback();
                        echo $e->getMessage();
                    }
                }else{
                    continue;
                }
            }
        }
        echo "ok";
    }
    /**
     * 计划任务更新币种行情
     */
    public function updatecoin()
    {
        $coin=Db::name("change_info")->field("name")->select();
        $zb=new ZbAPI();
        foreach($coin as $val){
            $coinInfo=$zb->Hangqing($val["name"]);
            if(array_key_exists("error",$coinInfo)) continue;
            if($coinInfo['ticker']["last"]>0){
                Db::name("change_info")
                    ->where("name",$val["name"])
                    ->setField("bili",$coinInfo['ticker']["last"]);
            }
        }
        echo "更新完成";
    }
}