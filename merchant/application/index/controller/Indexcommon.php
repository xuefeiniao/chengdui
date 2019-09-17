<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/20
 * Time: 13:50
 */

namespace app\index\controller;
use think\App;
use think\Controller;
use think\Db;



class Indexcommon extends Controller
{
    //验证是否登录
    protected $id;
    function __construct(App $app = null)
    {
        parent::__construct($app);
        $username=session("?username");
        $id=session("?id");
        if(empty($username) || empty($id)){
           $this->redirect("Login/index");
        }else{
            $user=db("user")->where(["id"=>session('id')])->field("username,type")->find();
            if($user["username"]!=session('username')){
               $this->redirect('Login/index');
            }
            # 状态:shop=商户，personage=个人，agency=代理，exshop=承兑商
            $usertype = '个人';
            if ($user['type']=='shop'){
                $usertype = '商户';
            }elseif ($user['type']=='agency'){
                $usertype = '代理';
            }elseif ($user['type']=='exshop'){
                $usertype = '承兑商';
            }
            $this->assign("usertype",$usertype);

        }
        $this->id=session("id");
        $coin=db("coin_config")
            ->where("status","normal")
            ->field("name")
            ->select();
        $this->assign("coinall",$coin);


    }


    // 创建地址
    function createAddr(array $coin_arr, $accountOrPassword)
    {
        if (!isset($coin_arr['cate'])) return false;
        switch (strtolower($coin_arr['cate'])) {
            case "btc":
                $client = new \Coin\Btc($coin_arr["username"], $coin_arr["password"], $coin_arr["host"], $coin_arr["port"]);
                if (empty($client->getblockchaininfo())) return false;
                $addr = $client->getnewaddress($accountOrPassword);
                break;
            case "eth":
                $client = new \Coin\Eth($coin_arr["host"], $coin_arr["port"]);
                if (empty($client->web3_clientVersion())) return false;
                $addr = $client->personal_newAccount($accountOrPassword);
                break;
            case "other":
                $addr = makeCode($accountOrPassword, true);
                break;
            default:
                return false;
        }

        return $addr;
    }

    # 获取奖金手续费比例
    protected function get_conf_fee($uid)
    {
        if (empty($uid)) return false;
        $user_conf_arr = Db::name('user_api')->where(['user_id'=>$uid])->field("user_id,shop_fee,shop_reward,exshop_reward,daili_reward,day_tixm_fee")->find();
        $conf_list= Db::name('exshop_config')->select();
        $conf_list_arr = array_column($conf_list,'val','name');
        $data['shop_fee'] = !empty($user_conf_arr['shop_fee']) ? $user_conf_arr['shop_fee'] : $conf_list_arr['shop_fee'];
        $data['shop_reward'] = !empty($user_conf_arr['shop_reward']) ? $user_conf_arr['shop_reward'] : 0;
        $data['exshop_reward'] = !empty($user_conf_arr['exshop_reward']) ? $user_conf_arr['exshop_reward'] : $conf_list_arr['exshop_reward'];
        $data['daili_reward'] = !empty($user_conf_arr['daili_reward']) ? $user_conf_arr['daili_reward'] : $conf_list_arr['daili_reward'];
        $data['day_tixm_fee'] = !empty($user_conf_arr['day_tixm_fee']) ? $user_conf_arr['day_tixm_fee'] : $conf_list_arr['day_tixm_fee'];
        $data['dakuan_time'] = $conf_list_arr['dakuan_time'];
        return $data;
    }

    # 承兑系统配置
    protected function get_exshop_conf($val)
    {
        $config = Db::name("exshop_config")->where(['name'=>$val])->find();
        if ($val=='usdt_price'){
            return !empty($config['val'])?$config['val']:6.5;
        }
        return $config['val'];
    }
}