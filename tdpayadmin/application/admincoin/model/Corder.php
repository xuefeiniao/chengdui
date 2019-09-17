<?php

namespace app\admincoin\model;

use think\Model;

class Corder extends Model
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
}
