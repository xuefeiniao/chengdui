<?php

namespace app\admincoin\model;

use think\Model;

class CoinLog extends Model
{
    // 时间
    public function getCreatetimeAttr($value)
    {
    	return date('Y-m-d H:i:s',$value);
    } 
}
