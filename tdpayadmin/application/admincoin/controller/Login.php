<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\facade\Session;

class Login extends Controller
{
    public static $timeout = 300;                                                                            // 短信过期时间
    /**
     * 登陆
     *
     * @return \think\Response
     */
    public function login(Request $request)
    {
        if ($request->isPost())
        {
            $mobile = $request->param('mobile','','trim');
            $pass   = $request->param('password','','trim');
            $mes    = $request->param('code','','trim');
            // if (empty($mobile) || empty($pass) || empty($mes)) return json_error('请填写完整信息');
            if (strstr($mobile,'@'))
            {
                $preg_email = '/^[a-zA-Z0-9]+([-_.][a-zA-Z0-9]+)*@([a-zA-Z0-9]+[-.])+([a-z]{2,5})$/ims';
                if(!preg_match($preg_email,$mobile)) return json_error('邮箱格式不正确');
            }
            else
            {
                if (!preg_match('/^0?(13|14|15|17|18|19)[0-9]{9}$/', $mobile)) return json_error('手机格式错误');
            }
            $uinfo  = Db::name('admins')->where(['username' => $mobile, 'status' => 1])->find();

            if (!$uinfo) return json_error('账号不存在');
            if (md5($pass.$uinfo['salt']) != $uinfo['password']) return json_error('密码错误');
            $cinfo  = Db::name('phone_mes')->where(['phone' => $mobile])->order('created_time','desc')->find();
            $time   = static::$timeout + $cinfo['created_time'];
            // if (!$cinfo || ($time < time()) || ($cinfo['code'] != $mes)) return json_error('验证码错误或已超时');
            $data = [
                    'loginip'       => $_SERVER["REMOTE_ADDR"],
                    'updated_time'  => date('Y-m-d H:i:s')
                ];
            $lN = Db::name('admins')->where(['id' => $uinfo['id']])->update($data);
            if ($lN) 
            {
                $this->setRole($uinfo['id']);
                Session::set('user.id',$uinfo['id']);
                return json_success('登录成功');
            }
            else
            {
                return json_error('登录失败');
            }
        }
        else
        {
            return $this->fetch('login/index');
        }
        
    }

    # 刷新权限
    protected function setRole($uid){
        $user_role = Db::name("user_role")->where(['user_id' => $uid])->find();
        if (empty($user_role)) return false;
        $rolelist = Db::name("permission_role")->where(['role_id' => $user_role['role_id']])->select();
        if (empty($user_role)) return false;
        $rolelist_arr = implode(',', array_column($rolelist, 'permission_id'));
        $role_list_info = Db::name("permissions")->where("permissions_id in ({$rolelist_arr})")->select();
        if (empty($role_list_info)) return false;
        $role_list_arr = array_column($role_list_info, 'name');
        $data = [];
        foreach ($role_list_arr as $v){
            $data[]=strtolower($v);
        }
        $role_list_json = json_encode($data);
        session('user.role', $role_list_json);
    }
    /**
     * @title 获取手机验证码
     * @url /admincoin/Login/getcode
     * @method POST
     * @param name:phone type:int require:1 default:1 other: desc:手机号
     *
     */
    public function getcode(Request $request)
    {
        $phone  = $request->param('phone');
        if (strstr($phone,'@'))
        {
            return sendMail($phone);
        }
        else
        {
            return sendPhoneCode($phone);
        }
    }

    /**
     * @title 退出
     * @url /admincoin/Login/loginout
     * @method get
     * @param name:phone type:int require:1 default:1 other: desc:手机号
     *
     */
    public function loginOut(){
        Session::delete('user');
        $this->redirect('admincoin/Login/login');
        // return view('Login/login');
    }
    /**
     * 空方法
     */
    public function _empty($name){
        return $this->display("没有".$name."方法");
    }
}
