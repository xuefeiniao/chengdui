<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

//生成充值提现订单号
function order_number($title,$type)
{
    while (true) {
        $str = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        if($type==1){                        //充值订单
            $status=db("corder")->where("order_number",$str)->field("id")->find();
            if(empty($status)) break;
        }else if($type==2){                  //提现订单
            $status=db("torder")->where("order_number",$str)->field("id")->find();
            if(empty($status)) break;
        }
        $str="";
    }
    return $title.$str;
}
//生成密码盐
function salt($len=6, $chars=null)
{
    if (is_null($chars)) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    }
    mt_srand(10000000*(double)microtime());
    for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
        $str .= $chars[mt_rand(0, $lc)];
    }
    return $str;
}
//生成邀请码
function inviter($len=8, $chars=null)
{
    while(true){
        if (is_null($chars)) {
            $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        $res=db("user")->where("inviter",$str)->find();
        if(empty($res) && (!empty($str))) break;
        $str="";
    }
    return $str;
}
//验证用户名是不是邮箱
function is_email($email)
{
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($email, '@') !== false && strpos($email, '.') !== false)
    {
        if (preg_match($chars, $email))
        {
            return true;
        }else{
            return false;
        }
    }else{
        return false;
    }
}
//验证IP是否合法
function is_ip($ip){
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return true;
    }else
    {
        return false;
    }
}
//记录用户登录信息
function user_log($username,$action,$ip,$desc=null){
    $data["username"]=$username;
    $data["action"]=$action;
    $data["ip"]=$ip;
    $addr=get_area($ip);
    $data["location"]=$addr;
    $data["createtime"]=time();
    $data["desc"]=$desc;
    db("user_log")->insert($data);
}
//根据IP获取登录所在地
function get_area($ip = ''){
    if($ip == '127.0.0.1'){
        $data["region"]="";
        $data["city"]="本机";
        return $data;
    }else{
        # $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
        $ip = trim(iconv("GBK", "utf-8",file_get_contents("http://whois.pconline.com.cn/ip.jsp?ip={$ip}")));
        if(empty($ip)){
            return false;
        }
        return  $ip;
    }
}
//发送短信
function sms_send($mobile,$code){
    $user=db("config")->where("name","user")->value("value");
    $key=db("config")->where("name","key")->value("value");
    $url='http://utf8.api.smschinese.cn/?Uid='.$user.'&Key='.$key.'&smsMob='.$mobile.'&smsText=您的手机验证码：'.$code;
    $ch = curl_init();
    $timeout = 5;
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $file_contents = curl_exec($ch);
    curl_close($ch);
    return $file_contents;
}
//发送邮件
function sendMail($to, $title, $content) {
    $res=db("config")->where("group","email")->select();
    $config=array();
    foreach($res as $key=>$value){
        $config[$value["name"]]=$value["value"];
    }
    require_once('../extend/PHPMailer/PHPMailerAutoload.php');
    $mail = new PHPMailer(); //实例化
    $mail->IsSMTP(); // 启用SMTP
    $mail->Host=$config['MAIL_HOST']; //smtp服务器的名称（这里以QQ邮箱为例）
    $mail->SMTPAuth = TRUE;   //启用smtp认证
    $mail->Username = $config['MAIL_USERNAME'];   //你的邮箱名
    $mail->Password = $config['MAIL_PASSWORD'] ;  //邮箱密码
    $mail->From = $config['MAIL_FROM'];           //发件人地址（也就是你的邮箱地址）
    $mail->FromName = $config['MAIL_FROMNAME'];           //发件人姓名
    $mail->AddAddress($to,"Hello Do You Love ME?");
    $mail->WordWrap = 50; //设置每行字符长度
    $mail->IsHTML(TRUE);        // 是否HTML格式邮件
    $mail->CharSet=$config['MAIL_CHARSET'];       //设置邮件编码
    $mail->Subject =$title;                 //邮件主题
    $mail->Body = $content;                 //邮件内容
    $mail->AltBody = "这是一个纯文本的HTML电子邮件客户端"; //邮件正文不支持HTML的备用显示
    return($mail->Send());
}

# 随机数
function makeCode($num = 6,$zimu=false) {
    $res = "";
    $rand = $zimu ? "0123456789abcdefghijklmnopqrstuvwxyz" : "0123456789";
    while(strlen($res)<$num) {
        $res .= $rand[rand(0, strlen($rand)-1)];
    }
    return $res;
}

# 验证数组
function check_arr($rs)
{
    foreach ($rs as $v) {
        if (!$v) {
            return false;
        }
    }

    return true;
}

//格式化时间
function getTime($time = '',$type=true)
{
    if ($type){
        $_time = empty($time) ? date("Y-m-d H:i:s") : date("Y-m-d H:i:s", $time);
    }else{
        $_time = strtotime($time);
    }
    return $_time;
}

//发送请求
function curl($url, $data = NULL, $json = false){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_TIMEOUT, 120);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    if(!empty($data)){
        if($json && is_array($data)){
            $data = json_encode($data);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        if($json){ //发送JSON数据
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length:' . strlen($data),
                ]
            );
        }
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, TRUE);
    curl_setopt($curl, CURLOPT_NOBODY, FALSE);
    $response = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    curl_close($curl);
    if($code == "200"){
        $header = trim(substr($response, 0, $headerSize));
        $body = trim(substr($response, $headerSize));
        return ["header"=>$header,"body"=>$body];
    }
    return null;
}
# 打乱一个二维数组
function shuffle_assoc($list)
{
    if (!is_array($list)) {
        return $list;
    }
    $keys = array_keys($list);
    shuffle($keys);
    $random = array();
    foreach ($keys as $key) {
        $random[$key] = $list[$key];
    }
    return $random;
}
# 检测用户名
function checkstr($username,$num=5)
{
    if (preg_match('/^[A-Za-z]{1}[A-Za-z0-9_-]{3,15}$/', $username)) {
        if (strlen($username)>=$num){
            return true;
        }
    }
    return false;
}

# 调试log
function _elog($data,$path){
    $log = @json_encode($data)."\r\n";
    return error_log($log,3,$path);
}


