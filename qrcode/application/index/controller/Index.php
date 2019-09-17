<?php
namespace app\index\controller;

use think\Db;

class Index extends Base{
    public function index()
    {
        $user = Db::name("user")->count();

        $bank = Db::name("user_bank")->group('uid')->count();
        $data = ['usercount'=>$user,'bankcount'=>$bank];
        return view('index',$data);
    }

    public function test()
    {
        $conn = ftp_connect('118.244.206.131'); //建立基础连接
        $login_result = ftp_login($conn, 'smei', 'ffffff'); //使用用户名和口令登录
        ftp_pasv($conn, true);  //打开被动模式
        if ($conn){
            if ($login_result){
                $filelist = ftp_nlist($conn, '/');
                $path = '/Users/shimei/Downloads/a123';
                if (!file_exists($path)){
                    mkdir ($path,0777,true);
                }
                $rs = @ftp_mkdir($conn,'/test/a123'); # 创建目录
                //$re = ftp_get($conn, $path.'/a.txt', '/test/tt.sql', FTP_BINARY); # 下载
                //$re = ftp_nb_put($conn, '/sites/ab.txt','/Users/shimei/Downloads/abcdefg.txt', FTP_BINARY); # 上传
                //$re = ftp_delete($conn, '/sites/t.txt');
                dump($rs);

                ftp_close($conn); //关闭连接
            }else{
                exit('用户登陆失败');
            }
        }else{
            exit('连接失败');
        }

    }

}
