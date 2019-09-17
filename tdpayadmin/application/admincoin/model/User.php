<?php

namespace app\admincoin\model;

use think\Model;

class User extends Model
{
    protected $table = 'coinpay_admins';
    public function getCreatetimeAttr($value)
    {
    	return date('Y-m-d H:i:s',$value);
    }

    public function getStatusAtrr($value)
    {
    	$status = ['normal' => '正常', 'hidden' => '禁用'];
    	return $status[$value];
    }

}
