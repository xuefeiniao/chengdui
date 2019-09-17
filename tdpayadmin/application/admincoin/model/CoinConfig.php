<?php

namespace app\admincoin\model;

use think\Model;

class CoinConfig extends Model
{
    // 状态
	public function getStatusAttr($value)
	{
		$status = ['normal' => '正常', 'hidden' => '冻结'];
		return $status[$value];
	}

	// 修改状态
	/*public function setStatusAttr($value)
	{
		$status = ['正常' => 'normal', '冻结' => 'hidden'];
		return $status[$value];
	}*/
}
