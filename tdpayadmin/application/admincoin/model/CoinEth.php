<?php

namespace app\admincoin\model;

use think\Model;

class CoinEth extends Model
{
    // 时间
    public function getCreatetimeAttr($value)
    {
    	return date('Y-m-d H:i:s',$value);
    }

    // 状态
    public function getStatusAttr($value)
    {
    	$status = ['normal' => '正常', 'hidden' => '禁用'];
    	return $status[$value];
    }
}
