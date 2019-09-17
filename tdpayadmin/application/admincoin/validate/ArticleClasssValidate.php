<?php

namespace app\admincoin\validate;

use think\Validate;

class ArticleClasssValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'class_name'  => 'require',
        'sort'        => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'class_name.require'  => '分类名称必填',
        'sort.require'        => '排序必填'
    ];
}
