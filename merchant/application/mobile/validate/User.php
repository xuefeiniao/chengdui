<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/19
 * Time: 8:56
 */

namespace app\mobile\validate;
use think\Validate;

class User extends Validate
{

    protected $rule=[
        'nickname'   => 'require|chsAlpha',
        'verify'     => 'require|number|length:6',
        'password'   => 'require|length:6,20',
        'repassword' => 'require|confirm:password',
        'industry'   => 'chsAlphaNum',
        'project'    => 'chsAlphaNum',
        'paypassword'=> 'require|number|length:6',
    ];
    protected $message=[
        "username.require"             =>"用户名必须",
        "username.regex"               =>"手机号码格式不正确",
        "username.email"               =>"邮箱格式不正确",
        "username.unique"              =>"用户名已存在",
        "nickname.require"             =>"商家姓名必须",
        "nickname.chsAlpha"            =>"商家姓名必须是字母和汉字",
        "verify.require"               =>"验证码必须",
        "verify.number"                =>"验证码必须是数字",
        "verify.length"                =>"验证码必须是6位",
        "password.require"             =>"登录密码必须",
        "password.length"              =>"登录密码格式错误",
        'repassword'                   =>'两次输入密码不一致',
        'industry.chsAlphaNum'         =>'行业错误',
        'project.chsAlphaNum'          =>'项目名称错误',
        'paypassword.require'          =>'支付密码必须',
        'paypassword.number'           =>'支付密码6位数字',
        'paypassword.length'           =>'支付密码6位数字',
    ];
    protected $scene=[
        //商户手机注册
        'smobilesign'=>[
            'username'=>"require|regex:/^1[34578]\d{9}$/",
            'nickname',
            'verify',
            'password',
            'repassword',
            'industry',
            'project',
            'paypassword'
        ],
        //商户邮箱注册
        'semailsign'=>[
            'username'=>"require|email",
            'nickname',
            'verify',
            'password',
            'repassword',
            'industry',
            'project',
            'paypassword'
        ],
        //代理手机注册
        'amobilesign'=>[
            'username'=>"require|regex:/^1[34578]\d{9}$/",
            'nickname',
            'verify',
            'password',
            'repassword',
            'paypassword'
        ],
        //代理邮箱注册
        'aemailsign'=>[
            'username'=>"require|email|unique:user",
            'nickname',
            'verify',
            'password',
            'repassword',
            'paypassword'
        ],
        //登录验证
        'login'=>[
            'username'=>"require",
            'password',
            'verify'
        ],
        //修改密码验证
        'reset'=>[
            'password',
            'repassword',
            'verify'
        ],
        //修改支付密码验证
        'uppay'=>[
            'paypassword',
            'verify'
        ],
    ];

    /*
     * 自定义验证真实姓名只能是汉字
     */
    protected  function is_name($value){
        $len = preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$value);
        if($len){
            return true;
        }
        return "真实姓名格式错误";
    }

    /*
     * 自定义验证代理是否存在
     */
    protected function is_exist($value){
        $res=db("user_agency")->where("inviter",$value)->value("user_id");
        if(empty($res)){
            return "代理不存在";
        }else{
            return true;
        }
    }
}