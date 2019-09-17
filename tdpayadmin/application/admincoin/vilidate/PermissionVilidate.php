<?php

namespace app\admincoin\vilidate;

use think\Validate;

class PermissionVilidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'name'          => 'require',
        'display'       => 'require',
        'discription'   => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'name.require'          => '节点必填',
        'display.require'       => '节点名必填',
        'discription.require'   => '节点描述必填',
    ];
}
