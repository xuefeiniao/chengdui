<?php

namespace app\admincoin\vilidate;

use think\Validate;

class RoleVilidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'username'      => 'require',
        'discription'   => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'username.require'      => '角色名称必填',
        'discription.require'   => '角色描述必填',
    ];
}
