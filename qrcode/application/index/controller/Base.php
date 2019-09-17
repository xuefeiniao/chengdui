<?php
namespace app\index\controller;

use think\Controller;
use think\Db;

class Base extends Controller {
    public function _initialize()
    {
        $nowtime = time();
        $s_time = session('logintime');

        if (!session('id') && !session('ucode') && empty($s_time)) {
            //$this->error('请先登录系统！', 'Login/index');
            $this->redirect('Login/index');
        }

        if (($nowtime - $s_time) > 60 * 30) {
            session(null);
            $this->redirect('Login/index');
            //$this->error('登录超时，请重新登录', 'Login/index');
        }

        $admin = Db::name('admins')->where(['id' => session('id')])->field('id,username,password,status')->find();
        if (empty($admin) || session('ucode') != md5(json_encode($admin))) {
//            $this->error('请先登录系统！', 'Login/index');
            $this->redirect('Login/index');
        }
        $this->assign('admin', $admin);
    }
}