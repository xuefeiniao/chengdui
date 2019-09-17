<?php
namespace app\index\controller;

use think\Controller;
use think\Db;

class Crontab extends Controller {


    # 计划任务获取用户
    public function get_yonghu()
    {
        $user = Db::name("user")->select();
        $user_arr = array_column($user,'uid');
        $user_arr_json = json_encode($user_arr);
        $url = config("web.shop_url").'/index/qrcode/user';
        $res = curl($url,['user_list'=>$user_arr_json]);
        $sql = "INSERT INTO `user`(`uid`, `username`, `nickname`,`mobile`, `type`) VALUES";
        $type_en = ['shop'=>1,'exshop'=>2,'agency'=>3];
        $status = false;
        if ($res && isset($res['body'])) {
            $response = json_decode($res['body'], true);
            if ($response['status'] == 'ok') {
               foreach ($response['data'] as $k=>$v){
                   if (!in_array($v['id'],$user_arr)){
                       $type = $type_en[$v['type']];
                       $sql.="({$v['id']},'{$v['username']}','{$v['nickname']}','{$v['mobile']}',{$type}),";
                       $status = true;
                   }
               }
                if ($status){
                    $exec = Db::execute(substr($sql,0,-1));
                    if ($exec){
                        return json(['status'=>'ok','data'=>$exec]);
                    }
                }
            }
        }
        return json(['status'=>'error']);
    }

    # 计划任务 实时更新失败，需要重复通知
    public function set_user_bank()
    {
        $user_bank = Db::name('user_bank')->where("status=0")->field("ip,time",true)->select();
        $data = [];
        if (!$user_bank) exit('暂无数据');
        foreach ($user_bank as $bank){
            $res = $this->set_curl_user($bank['uid'],$bank['type']);
            if ($res)
                $data[] = $bank['uid'];
        }
        exit(json_encode($data));
    }


    public function chech_user_payimg()
    {
        $user_bank = Db::name('user_bank')->where("type in(2,3)")->field("ip,time",true)->select();
        $data = [];
        if (!$user_bank) exit('暂无数据');
        $count = count($user_bank);
        $img_arr = [];
        $url = config("web.shop_url") . '/index/qrcode/get_file';
        $sign = md5(date("Y-m-d H") . 'bpayer');
        $res = curl($url, ['sign'=>$sign]);
        if ($res && isset($res['body'])) {
            $response = json_decode($res['body'], true);
            if ($response['status'] == 'ok') {
                $img_arr = $response['data'];
            }
        }
        if (empty($img_arr)) exit('fail');
        # if (empty($img_arr) || $count != count($img_arr)) exit('fail');

        $false = [];
        $false_sql = "INSERT INTO `jygk_log` (`uid`,`bank_id`,`type`,`qrcode`,`qrcode_bk`) VALUE";
        foreach ($user_bank as $k=>$v)
        {
            $qrcode =  pathinfo($v['qrcode']);
            if (!empty($img_arr[$qrcode['filename']])){
                $check_img = $img_arr[$qrcode['filename']];
                $check_res = curl($check_img);
                $host_qrcode = input('server.REQUEST_SCHEME') . '://' . input('server.SERVER_NAME').$v['qrcode'];
                if ($check_res && isset($check_res['body'])) {
                    $qrcode_name_md5 = md5($check_res['body']);
                    if ($qrcode['filename'] != $qrcode_name_md5) {
                        $false[]= $v['id'];
                        $false_sql .="({$v['uid']},{$v['id']},{$v['type']},'{$host_qrcode}','{$check_img}'),";
                    }
                }
            }else{
                $false[]= $v['id'];
                $false_sql .="({$v['uid']},{$v['id']},{$v['type']},'{$host_qrcode}',''),";
            }
        }
        if ($false){
            $fail_num = count($false);
            $rs[] = Db::name("check_log")->insert(['num' => $count - $fail_num, 'fail_num' => $fail_num, 'status' => 2]);
            $rs[] = @Db::execute(substr($false_sql,0,-1));
        }else{
            $rs[] = Db::name("check_log")->insert(['num'=>$count]);
        }
        halt($rs);
    }

    protected function check_exshop_img($qrcode)
    {
        # $qrcode = "/uploads/pay/033ebbc18ee05c075181f9ec73529208.jpg";
        $qrcode_name = pathinfo($qrcode)['filename'];
        $host = input('server.REQUEST_SCHEME') . '://' . input('server.SERVER_NAME');
        $url = $host . $qrcode;
        $res = curl($url);
        if ($res && isset($res['body'])) {
            $qrcode_name_md5 = md5($res['body']);
            if ($qrcode_name == $qrcode_name_md5) {
                return true;
            }
        }
        return false;
    }


    # 同步传输图片
    protected function set_curl_user($uid, $type)
    {
        $user_bank = Db::name("user_bank")->where(['uid' => $uid, 'type' => $type])->field("ip,time",true)->find();
        $url = config("web.shop_url") . '/index/qrcode/set_pay_img';
        $user_bank['sign'] = md5(date("Y-m-d H") . 'bpayer');
        $res = curl($url, $user_bank);
       # halt($res);
        if ($res && isset($res['body'])) {
            $response = json_decode($res['body'], true);
            if ($response['status'] == 'ok' && $response['msg'] == 2) {
                Db::name("user_bank")->where(['uid' => $uid])->update(['status' => $response['msg']]);

                return true;
            }
        }

        return false;
    }

}
