<?php

namespace app\admincoin\model;

use think\Model;

class Torder extends Model
{
    // 时间
    public function getCreatetimeAttr($value)
    {
    	return date('Y-m-d H:i:s',$value);
    }

    public function getEndtimeAttr($value)
    {
        if ($value==0){
            return "0000-00-00 00:00:00";
        }else{
            return date('Y-m-d H:i:s',$value);
        }
    }

//    // 状态
//    public function getStatusAttr($value)
//    {
//    	$status 	= ['error' => '提现失败', 'already' => '已处理', 'success' => '提现成功', 'unusul' => '订单异常', 'pass' => '审核通过', 'check' => '待审核', 'refuse' => '已拒绝'];
//    	return $status[$value];
//    }
}
