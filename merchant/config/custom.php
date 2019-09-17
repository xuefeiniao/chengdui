<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/20
 * Time: 10:48
 */

return [
    //邮箱配置
    'email'=>[
        'MAIL_HOST' =>'smtp.exmail.qq.com',     //smtp服务器的名称
        'MAIL_SMTPAUTH' =>TRUE,                 //启用smtp认证
        'MAIL_USERNAME' =>'noreply@mbmb.com',   //你的邮箱名
        'MAIL_FROM' =>'noreply@mbmb.com',       //发件人地址
        'MAIL_FROMNAME'=>'MBMB',                //发件人姓名
        'MAIL_PASSWORD' =>'Bt13142514@',        //邮箱密码
        'MAIL_CHARSET' =>'utf-8',               //设置邮件编码
        'MAIL_ISHTML' =>TRUE,                   // 是否HTML格式邮件
        'title' =>"B-payer Payment",                    //邮箱发送标题
        'content'=>"您的邮箱验证码是："          //邮箱发送内容

    ],

];