<?php

namespace app\admincoin\vilidate;

use think\Validate;

class AdminsVilidate extends Validate
{
   /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */ 
    protected $rule = [
        'username'  => 'require|mobile',
        'nickname'  => 'require',
//        'password'  => 'require',
        'role'      => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */ 
    protected $message = [
        'username.require'  => '手机号/邮箱必填',
        'username.mobile'   => '手机号格式不正确',
        'nickname.require'  => '名称必填',
//        'password.require'  => '登陆密码必填',
        'role.require'      => '角色必填',
    ];
}
