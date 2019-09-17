<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Db;
use think\Request;
use app\admincoin\model\Admins;
use app\admincoin\model\Role;
use app\admincoin\model\Permissions;
use app\admincoin\vilidate\PermissionVilidate;
use app\admincoin\model\UserRole;
use app\admincoin\model\PermissionRole;

class Permission extends Base
{
    public $permission;
    public $role;
    public $admins;
    public function initialize()
    {
        $this->permission   = new Permissions();
        $this->role         = new Role();
        $this->admins       = new Admins();
    }

    /**
     * 显示管理员列表
     *
     * @return \think\Response
     */
    public function index()
    {

        $list = Admins::order('id','desc')->alias('a')->join('coinpay_user_role ur','a.id = ur.user_id')->join('coinpay_role r','ur.role_id = r.role_id')->field('a.*,r.role_name')->paginate(10);
        $page = $list->render();
        $this->assign('list',$list);
        $this->assign('page',$page);
        return view('Permission/index');
    }

    /**
     * 添加管理员
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function adminAdd(Request $request)
    {
        if ($request->isPost())
        {
            return $this->admins->insertA($request);
        }
        else
        {
            $list = Role::all();
            $this->assign('list',$list);
            return view('Permission/adminAdd');
        }
    }

    /**
     * 编辑管理员
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function adminEdit(Request $request)
    {
        if ($request->isPost())
        {
            return $this->admins->updateA($request);
        }
        else
        {
            $info = Admins::get($request->id);
            $role = UserRole::get($info->id);
            $list = Role::all();
            $this->assign('info',$info);
            $this->assign('list',$list);
            $this->assign('role',$role);
            return view('Permission/adminEdit');
        }
    }

    /**
     * 删除管理员
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function adminDel(Request $request)
    {
        $id = $request->param('id');
        return $this->admins->delA($id);
    }


    /**
     * 显示角色列表.
     *
     * @return \think\Response
     */
    public function roleList()
    {
        $list = Role::order('role_id','desc')->paginate(10);
        $this->assign('list',$list);
        return view('Permission/roleList');
    }

    /**
     * 添加角色.
     *
     * @return \think\Response
     */
    public function roleAdd(Request $request)
    {
        if ($request->isPost())
        {
            return $this->role->insertR($request);
        }
        else
        {
            $list = $this->permission->getlist();
            $this->assign('permissions',$list);
            return view('Permission/roleAdd');
        }
    }

    /**
     * 编辑角色.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function roleEdit(Request $request)
    {
        if ($request->isPost())
        {
            $params         = $request->param();
            return $this->role->updateR($params);
        }
        else
        {
            $id     = $request->param('id');
            $rinfo  = Role::get($id);
            $list   = $this->permission->getlist();
            $rP     = PermissionRole::where('role_id',$id)->column('permission_id');
            $this->assign('rinfo', $rinfo);
            $this->assign('rp', $rP);
            $this->assign('permissions', $list);
            return view('Permission/roleEdit');
        }
    }

    /**
     * 删除角色
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function roleDel(Request $request)
    {
        $id = $request->param('id');
        return $this->role->delR($id);
    }


    /**
     * 显示权限列表.
     *
     * @return \think\Response
     */
    public function permissionList()
    {
        $list = Permissions::order('permissions_id','desc')->paginate(20);
        $this->assign('list',$list);
        return view('Permission/permissionList');
    }

    /**
     * 添加权限节点
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function permissionAdd(Request $request)
    {
        if ($request->isPost())
        {
            $params         = $request->param('','','trim');
            return $this->permission->insertP($params);
        }
        else
        {
            $menu   = Permissions::where('parent',0)->select();
            $this->assign('menu',$menu);
            return view('Permission/permissionAdd');
        }
    }

    /**
     * 编辑节点.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function permissionEdit(Request $request)
    {
        if ($request->isPost())
        {
            $params         = $request->param('','','trim');
            return $this->permission->updateP($params);
        }
        else
        {
            $id     = $request->param('id');
            $info   = Permissions::get($id);
            $this->assign('info',$info);
            return view('Permission/permissionEdit');
        }
    }

    /**
     * 删除节点
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function permissionDel(Request $request)
    {
        $id = $request->param('id');
        return $this->permission->delP($id);
    }

    # 用户权限分配
    public function ffpz(Request $request)
    {
        if ($request->isPost()){
            $val = input("post.val");
            $type = input("post.type");

            if (empty($val)) return json(["status" => 0, "msg" => '管理员账号必填']);
            $res = Db::name("admins")->where(['username'=>$val])->update(['type'=>$type]);
            if ($res){
                return json(["status" => 1, "msg" => '修改成功']);
            }else{
                return json(["status" => 0, "msg" => '修改失败']);
            }
        }

        $adminlist = Db::name("admins")->where("type in (1,2)")->paginate(10);
        $data = $adminlist->all();
        $page = $adminlist->render();
        $count = $adminlist->total();
        $this->assign('data',$data);
        $this->assign('page',$page);
        $this->assign('count',$count);
        return view('Permission/ffpz');
    }

    # 删除管理员定向权限
    public function ffpz_delac(){
        $admin_uid = input('post.id/d');
        $res = Db::name('admins')->where(['id'=>$admin_uid])->update(['type'=>0]);
        if ($res){
            Db::name('admins_userinfo')->where(['admin_uid'=>$admin_uid])->delete();
            return json(["status" => 1, "msg" => '删除成功']);
        }else{
            return json(["status" => 0, "msg" => '删除失败']);
        }
    }

    # 添加管理员特定用户权限
    public function ffpz_ac(Request $request)
    {
        if($this->request->isPost()) {
            $list = trim(input('post.list'));
            $admin_uid = input('admin_uid/d');
            $type = input('type');
            $str = $this->get_user_uid($list);

            $map[] = ['type','=',$type];
            $map[] = ['username','in',[$str]];
            if (!empty($str)){
                $user = Db::name('user')->where($map)->field("id")->select();
                if (!$user) return json(["status" => 0, "msg" => "添加失败"]);
                $uid_arr = array_column($user,'id');
            }else{
                $uid_arr = [];
            }
            $rs = [];

            foreach ($uid_arr as $v)
            {
                $res = Db::name('admins_userinfo')->where(['admin_uid'=>$admin_uid,'user_uid'=>$v])->find();
                if (empty($res)){
                    $rs[] = Db::name("admins_userinfo")->insert(['admin_uid'=>$admin_uid,'user_uid'=>$v]);
                }
            }

            if (check_arr($rs)){
                return json(["status" => 1, "msg" => '添加成功','url'=>"/admincoin/permission/ffpz_ac/id/{$admin_uid}"]);
            }else{
                return json(["status" => 0, "msg" => '添加失败']);
            }
        }

        $admin_uid = input('id/d');
        $admin_info = Db::name("admins")->where(['id'=>$admin_uid])->find();
        $shopinfo = Db::name("admins_userinfo")->where(['admin_uid'=>$admin_uid])->select();

        $shop_arr = implode(',',array_column($shopinfo,'user_uid'));

        $list = Db::name('user')->where("id",'in',$shop_arr)->field("id,username,nickname,updatetime")->paginate(10);
        $data = $list->all();
        $page = $list->render();
        $count = $list->total();
        $path = implode(',',array_column($data,'username'));
        $tmp = [1=>'商户',2=>'承兑商'];$tmp_en = [1=>'shop',2=>'exshop'];
        return view("Permission/ffpz_ac",["page"=>$page,"count"=>$count,"data"=>$data,'admin_uid'=>$admin_uid,'type'=>$admin_info['type'],'username'=>$admin_info['username'],'tmp'=>$tmp,'tmp_en'=>$tmp_en]);
    }

    # 删除管理员特定用户权限
    public function ffpz_user_dell()
    {
        $user_uid = input('post.user_uid/d');
        $admin_uid = input('admin_uid/d');
        $res = Db::name('admins_userinfo')->where(['user_uid'=>$user_uid,'admin_uid'=>$admin_uid])->delete();

        if ($res){
            return json(["status" => 1, "msg" => '删除成功']);
        }else{
            return json(["status" => 0, "msg" => '删除失败']);
        }
    }


    #
    protected function get_user_uid($str)
    {
        $arr = explode(',',str_replace('，',',',$str));
        $return_arr = [];
        if (!empty($arr)){
            foreach ($arr as $v){
                if (!empty($v)){
                    $return_arr[] =trim($v);
                }
            }
        }
        return implode(',',array_filter($return_arr));
    }

}
