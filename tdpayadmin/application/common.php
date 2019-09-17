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

use mailer\PHPMailer;
use mailer\SMTP;
use auth\Auth;
use think\Config;

// 应用公共文件
/**
     * 失败
     * @return [type] [description]
     */
    function json_error($msg='失败')
    {
        return json(['code'=>400, 'msg'=>$msg]);
    }

    /**
     * 成功
     * @return [type] [description]
     */
    function json_success($data=[], $msg='成功')
    {
        if (is_string($data)) {
            $msg  = $data;
            $data = [];
        }
        return json(['code'=>200, 'data'=>$data, 'msg'=>$msg]);
    }
    /**
     * 请求短息接口
     * @param int phone
     * @return [type] [description]
     */
    function sendApi($phone,$code)
    {
    	$phone 		= trim($phone);
    	$content 	= '验证码为'.$code.',有效时间5分钟！【B-payer Payment】';
    	$fp 		= Fopen('http://utf8.api.smschinese.cn/?Uid=okzhangbo&Key=b2a5be5a48a2a074d8861&smsMob=' . $phone . '&smsText=' . $content,"r");
    	$ret 		= fgetss($fp,255);
    	$str 		= urldecode($ret);
    	fclose($fp);
    	return $str;
    }

    /**
     * 发送手机验证码
     * @param int phone
     * @return [type] [description]
     */
    function sendPhoneCode($phone)
    {
    	$exTime = 60;																					// 过期时间
    	if(!$phone) return json_error('手机号不能为空');
    	if(!preg_match("/^1[3456789]\d{9}$/", $phone)) return json_error('请输入有效手机号');
    	$lastGet = db('phone_mes')->where('phone',$phone)->order('mes_id desc')->find();
    	if ($lastGet) 
    	{
    		# 判断重复请求
    		if(($lastGet['created_time']+$exTime)-time() > 0) return json_error($exTime.'秒内只能请求一次');
    	}
    	$code = mt_rand(111111,999999);
    	$codeArr['phone'] 			= $phone; 
    	$codeArr['code'] 			= $code; 
    	$codeArr['created_time'] 	= time(); 
    	$cN = db('phone_mes')->insert($codeArr);
    	if(!$cN) return json_error('验证码发送失败'); 
    	sendApi($phone,$code);
    	return json_success('验证码发送成功');
    }


/*
 * 应用公共函数文件，函数不能定义为public类型，
 * 如果我们要使用我们定义的公共函数，直接在我们想用的地方直接调用函数即可。
 * */
// 公共发送邮件函数
function sendEmail($desc_content, $toemail,  $desc_url=''){
        $mail = new PHPMailer();
        $mail->isSMTP();                                // 使用SMTP服务
        $mail->CharSet      = "utf8";                   // 编码格式为utf8，不设置编码的话，中文会出现乱码
        $mail->Host         = "smtp.163.com";           // 发送方的SMTP服务器地址
        $mail->SMTPAuth     = true;                     // 是否使用身份验证
        $mail->Username     = "yxuefeiniao@163.com";    // 发送方的163邮箱用户名，就是你申请163的SMTP服务使用的163邮箱</span><span style="color:#333333;">
        $mail->Password     = "yang123";                   // 发送方的邮箱密码，注意用163邮箱这里填写的是“客户端授权密码”而不是邮箱的登录密码！</span><span style="color:#333333;">
        $mail->SMTPSecure   = "ssl";                    // 使用ssl协议方式</span><span style="color:#333333;">
        $mail->Port         = 465;                      // 163邮箱的ssl协议方式端口号是465/994
        $mail->setFrom("yxuefeiniao@163.com","B-payer Payment");         // 设置发件人信息，如邮件格式说明中的发件人，这里会显示为Mailer(xxxx@163.com），Mailer是当做名字显示
        $mail->addAddress($toemail,'博客回复消息');      // 设置收件人信息，如邮件格式说明中的收件人，这里会显示为Liang(yyyy@163.com)
        $mail->addReplyTo("yxuefeiniao@163.com","Reply");       // 设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址
        //$mail->addCC("xxx@163.com");                  // 设置邮件抄送人，可以只写地址，上述的设置也可以只写地址(这个人也能收到邮件)
        //$mail->addBCC("xxx@163.com");                 // 设置秘密抄送人(这个人也能收到邮件)
        //$mail->addAttachment("bug0.jpg");             // 添加附件
        $mail->Subject      = "B-payer Payment";// 邮件标题
        // 您本次身份认证的验证码为511967请尽快完成认证!【ALITOKEN】
        
        $mail->Body         = "您本次身份认证的验证码为: ".$desc_content." 请尽快完成认证".$desc_url.'【B-payer Payment】';// 邮件正文
        //$mail->AltBody = "This is the plain text纯文本";// 这个是设置纯文本方式显示的正文内容，如果不支持Html方式，就会用到这个，基本无用
      
        if(!$mail->send()){// 发送邮件
            /*return json_success('123');
            echo 22;*/
            return $mail->ErrorInfo;
        // echo "Message could not be sent.";
        // echo "Mailer Error: ".$mail->ErrorInfo;// 输出错误信息
        }else{
            return 1;
        }
}

/**
     * 邮件发送
     */
    function sendMail($email,$url='')
    {
        $code       = mt_rand(111111,999999);
        $preg_email = '/^[a-zA-Z0-9]+([-_.][a-zA-Z0-9]+)*@([a-zA-Z0-9]+[-.])+([a-z]{2,5})$/ims';
        if(!preg_match($preg_email,$email)) return json_error('邮箱格式不正确');
        $res        = sendEmail($code, $email, $url);
        if ($res) 
        {
            $codeArr['phone']           = $email; 
            $codeArr['code']            = $code; 
            $codeArr['created_time']    = time(); 
            $cN = db('phone_mes')->insert($codeArr);  
            return json_success('邮件发送成功');
        }
        else
        {
            return json_error('邮件发送失败');
        }
    }

    /**
     * 生成随机密码盐
     */
    function getSalt()
    {
        $code   = ''; 
        $arr    = array_rand($array = array_merge(range(0, 9), range('A', 'Z'), range('a', 'z')), 6);
        foreach ($arr as $value) $code .= $array[$value];
        return $code;
    }

    /**
    * 时间
    */
    function getTime()
    {
        $arr = [];
        $z = date('Y-m');
        $a = date('Y-m', strtotime('-12 months'));
        $begin = new DateTime($a);
        $end = new DateTime($z);
        $end = $end->modify('+1 month');
        $interval = new DateInterval('P1M');
        $daterange = new DatePeriod($begin, $interval ,$end);
        foreach($daterange as $date){ 
          $arr[] = $date->format("Y-m");
        }
        return $arr;
    }

    /**
     * excel表格导出
     * @param string $fileName 文件名称
     * @param array $headArr 表头名称
     * @param array $data 要导出的数据
     * @author static7  
     */
    function excelExport($fileName = '', $headArr = [], $data = []) 
    {
        $fileName .= "_" . date("Y_m_d", Request::instance()->time()) . ".xls";
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties();
        $key = ord("A"); // 设置表头
        foreach ($headArr as $v) 
        {
            $colum = chr($key);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);
            $key += 1;
        }
        $column = 2;
        $objActSheet = $objPHPExcel->getActiveSheet();
        foreach ($data as $key => $rows) 
        { // 行写入
            $span = ord("A");
            foreach ($rows as $keyName => $value) 
            { // 列写入
                $objActSheet->setCellValue(chr($span) . $column, $value);
                $span++;
            }
            $column++;
        }
        $fileName = iconv("utf-8", "gb2312", $fileName); // 重命名表
        // dump($fileName);die;
        $objPHPExcel->setActiveSheetIndex(0); // 设置活动单指数到第一个表,所以Excel打开这是第一个表
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$fileName");
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output'); // 文件通过浏览器下载
        exit();
    }

    // 获取地理位置
    function getCity($ip = '')
    {
        if($ip == ''){
            $url = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json";
            ini_set('user_agent','Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
            $ip=json_decode(file_get_contents($url),true);
            $data = $ip;
        }else{
            $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
            ini_set('user_agent','Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
            $ip=json_decode(file_get_contents($url));   
            if((string)$ip->code=='1'){
               return false;
            }
            $data = (array)$ip->data;
        }
        
        return $data;   
    }

	/* 获取btc Api接口内容 */
    function getBtcApi($url)
    {
        $result    = file_get_contents($url);
        return json_decode($result,true)['data'];
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

# 格式化时间
function _getTime($time = '',$type=true)
{
    if ($type){
        $_time = empty($time) ? date("Y-m-d H:i:s") : date("Y-m-d H:i:s", $time);
    }else{
        $_time = strtotime($time);
    }
    return $_time;
}

# 超管登录用户前台验签 md5(md5($vo['id'].'vifu123'.date("Y/m/d/h/i")));
function _usersign($uid,$match_id='')
{
    if (empty($match_id)){
        return md5(md5($uid.'vifu123'.date("Y/m/d/h")));
    }else{
        return md5(md5($match_id.$uid.'vifu123'.date("Y/m/d/h")));
    }
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
/*
*处理Excel导出
*@param $data array 数据
*@param $column array 字段
*@param $title string 标题
*@param $filename string 文件名
*/
function excelOutput($data, $column, $title, $filename)
{
    $str = "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\"\r\nxmlns:x=\"urn:schemas-microsoft-com:office:excel\"\r\nxmlns=\"http://www.w3.org/TR/REC-html40\">\r\n<head>\r\n<meta http-equiv=Content-Type content=\"text/html; charset=utf-8\">\r\n</head>\r\n<body>";
    $str .= "<table border=1><head style='font-weight:bold;font-size:20px;text-align:center;color:#7e8c8d;'>" . $title . "</head>";
    $str .= "<tr>";
    foreach ($column as $v) {
        $str .= "<th>{$v}</th>";
    }
    $str .= "</tr>";
    foreach ($data as $key => $rt) {
        $str .= "<tr>";
        foreach ($rt as $k => $v) {
            $str .= "<td  style='width:100px;'>{$v}</td>";
        }
        $str .= "</tr>\n";
    }
    $str .= "</table></body></html>";
    header("Content-Type: application/vnd.ms-excel; name='excel'");
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=" . $filename . ".xls");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    exit($str);
}
