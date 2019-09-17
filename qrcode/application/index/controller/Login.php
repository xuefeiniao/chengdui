<?php
namespace app\index\Controller;

use think\Controller;
use think\Db;

class Login extends Controller {
    public function index()
    {
        if (session('ucode') && session('id')) {
            //$this->error('您已经登录成功，请勿重复登录！', 'Coin/index');
            $this->redirect('Coin/index');
        }
        if (request()->isPost()) {
            $data = input('post.');
            if (!captcha_check($data['code'])) {
                $this->error('验证码错误！');
            };
            $password = md5($data['password'] . 'hujin@123');
            $admin = Db::name('admins')->where(['username' => $data['username'], 'password' => $password])->field('id,username,password,status')->find();
            # 管理状态1.正常，2.禁用
            if ($admin['status'] == 1) {
                Db::name('admins')->where(['id' => $admin['id']])->update(['updated_time' => gettime()]);
                session('id',$admin['id']);
                session('username',$admin['username']);
                session('ucode',md5(json_encode($admin)));
                session('logintime',time());
                $this->redirect('index/index');
            } else {
                $this->error('当前账号已被禁用！');
            }
        }

        return view();
    }


    public function logout(){
        session(null);
        $this->redirect('Login/index');
        # $this->success('退出成功！','Login/index');
    }

}