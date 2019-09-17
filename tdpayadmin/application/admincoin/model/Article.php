<?php

namespace app\admincoin\model;

use think\Model;

class Article extends Model
{
    // protected $pk 	= 'class_id';
    public function getTypeAttr($value)
    {
    	$type 	= ['news' => '新闻资讯', 'notice' => '通知公告', 'fuwu' => '服务协议', 'privacy' => '隐私条款'];
    	return $type[$value];
    }

    public function getCreatetimeAttr($value)
    {
    	return date('Y-m-d H:i:s', $value);
    }

    public function getUpdatetimeAttr($value)
    {
    	return date('Y-m-d H:i:s', $value);
    }
}
