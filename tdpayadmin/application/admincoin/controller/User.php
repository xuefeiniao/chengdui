<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Request;
use think\Db;
class User extends Base
{
    public function userlogin(Request $request)
    {
        if ($this->request->isGet()){
            $uid = (int)input('uid');
            $sign  = md5(md5($uid.'vifu123'.date("Y/m/d")));
            $data = ['uid'=>$uid,'sign'=>$sign];
            $loginurl = "http://www.taida333.com/index/login/admin_login";
            $res = $this->curl($loginurl,$data);
            $result = json_decode($res['body'],true);
            if($result['status']=='ok'){
                $url = "http://www.taida333.com/index/index/index.html";
                header("Location: {$url}");
                return json(['status'=>1,'msg'=>$url]);
            }
            return json(['status'=>0,'msg'=>'稍后再试']);
        }
    }


    //发送请求
    protected function curl($url, $data = NULL, $json = false){
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
}
