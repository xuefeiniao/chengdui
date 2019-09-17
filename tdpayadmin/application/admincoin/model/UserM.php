<?php

namespace app\admincoin\model;

use think\Model;

class UserM extends Model
{
    protected $table = 'coinpay_user';
    // 状态
    public function getStatusAttr($value)
    {
    	$status = ['normal' => '正常', 'hidden' => '禁用'];
    	return $status[$value];
    }
    // 时间
    public function getCreatetimeAttr($value)
    {
    	return date("Y-m-d H:i:s",$value);
    }
}
