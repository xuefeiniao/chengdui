<?php

namespace app\admincoin\model;

use think\Model;

class UserLog extends Model
{
    public function getCreatetimeAttr($value)
    {
    	return date('Y-m-d H:i:s',$value);
    }

}
