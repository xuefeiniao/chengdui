<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/20
 * Time: 13:50
 */

namespace app\index\controller;
use think\App;
use think\Db;
use think\Controller;


class Paycommon extends Controller
{
    function __construct(App $app = null)
    {
        parent::__construct($app);
    }

    # 验证签名
    protected function auth_info(array $arr, $sign)
    {
        if (empty($arr) || !is_array($arr) || empty($sign)) return false;
        unset($arr['sign']);
        ksort($arr);
        $where['shopid'] = $arr['shopid'];
        $shop_info = Db::name('user')->where($where)->field('id,shopid,apikey,apipassword')->find();
        $arr_json_md5_pwd = md5(json_encode($arr,JSON_UNESCAPED_SLASHES)) . $shop_info['apipassword'];
        $_sign = strtoupper(substr(md5($arr_json_md5_pwd), 0, 18));
        if ($sign != $_sign) return false;

        return $shop_info;
    }

    /**
     * @param $user_id 商户ID
     * @param $name 接口名称
     * @param $content 访问内容
     * @param int $status 状态1=成功，2=失败
     * @return bool
     */
    protected function api_log($user_id, $name, array $content, $status = 1)
    {
        $ip = $this->request->ip();
        $content = json_encode($content);
        $metod = $this->request->controller() . '//' . $this->request->action();
        Db::name('api_log')->insert(['user_id' => $user_id, 'name' => $name, 'ip' => $ip, 'content' => $content, 'createtime' => time(), 'status' => $status,'desc'=>$metod]);

        return true;
    }

    function _empty(){
        //header("Location:/bbs/thinkphp/404.html");
        echo "_empty";
    }
}