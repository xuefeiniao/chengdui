<?php
use think\Db;
use think\Request;

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
//创建随机验证码
function makeCode($num = 6,$zimu=false) {
    $res = "";
    $rand = $zimu ? "0123456789abcdefghijklmnopqrstuvwxyz" : "0123456789";
    while(strlen($res)<$num) {
        $res .= $rand[rand(0, strlen($rand)-1)];
    }
    return $res;
}

# 异常入库
function exception_log($info)
{
    # INSERT INTO `exception_log` (`msg`,`ip`,`time`) VALUES (:msg,'".$_SERVER['REMOTE_ADDR']."','".date("Y-m-d H:i:s")."')";
    $metod = request()->module() . '.' . request()->controller() . '.' . request()->action();
    $msg = "[{$metod}]:" . $info;
    $ip = Request::instance()->ip();
    $rs = Db::name('exception_log')->insert(['msg' => $msg, 'ip' => $ip, 'time' => gettime()]);
    if ($rs) return false;

    return true;
}


# 拉密钥的signq签名
function unlock_sign(array $data)
{
    return substr(md5($data['salt'] . $data['batch'] . $data['time'] . 'hujin@123'), 0, 18);
}


# 加密字符串
function md5_to_sha1($salt)
{
    $salt_a = md5($salt);
    $salt_b = sha1(md5($salt . $salt_a . 'FIWORK36COIN'));
    $_salt_a = substr($salt_a, 0, 20);
    $_salt_b = substr($salt_b, 0, 20);
    $new_str = $_salt_a . '@*' . $_salt_b;

    return $new_str;
}
function gettime()
{
    return date('Y-m-d H:i:s',time());
}
# 应用数据库
function client_db(){
    $db = config('app.db');
    $db_host=$db['db_host'];
    $db_database=$db['db_database'];
    $db_username=$db['db_username'];
    $db_password=$db['db_password'];
    $app_db = Db::connect("mysql://{$db_username}:{$db_password}@{$db_host}/{$db_database}#utf8");
    return $app_db;
}

function fftp_comm($ftp_server_host, $ftp_user_name, $ftp_user_pass)
{
    $ftp_server_host = "118.244.206.131";
    $ftp_user_name = "smei";
    $ftp_user_pass = "ffffff";

//建立基础连接
    $ftp_connect = ftp_connect($ftp_server_host);

    if ($ftp_connect) {
        //使用用户名和口令登录
        $login_result = ftp_login($ftp_connect, $ftp_user_name, $ftp_user_pass);

        if ($login_result) {
            $flag = ftp_pasv($ftp_connect, true);  //打开被动模式
            //var_dump($flag);

            $pwd = ftp_pwd($ftp_connect);  //当前ftp的目录
            //var_dump($pwd);

//      遍历目录(非递归)
            $remote_path = "/";
            $file_arr = ftp_nlist($ftp_connect, $remote_path);
            dump($file_arr);

//      上传文件(需要先开启 被动模式)(如果已经存在 无法上传)
            $remote_file = "a.txt";
            $local_file = "d:/ftp_up_test.txt";
            # $flag = ftp_put($ftp_connect, $remote_file, $local_file, FTP_BINARY);
            dump($flag);

//      下载文件(需要先开启 被动模式)
            $remote_file = "a.txt";
            $local_file = "d:/ftp_down_test.txt";
            # $flag = ftp_get($ftp_connect, $local_file, $remote_file, FTP_BINARY);
            var_dump($flag);

//      删除文件
            $remote_file = "a.php";
            # $flag = ftp_delete($ftp_connect, $remote_file);
            var_dump($flag);

//      修改文件权限(此文件需要 登陆账号有权限去进行 chmod)
            $remote_file = "a.txt";
            # $flag = ftp_chmod($ftp_connect, 0777, $remote_file);
            var_dump($flag);

        } else {
            echo "用户登陆失败";
        }

        //关闭连接
        ftp_close($ftp_connect);
    } else {
        echo "连接ftp服务器失败";
    }
}