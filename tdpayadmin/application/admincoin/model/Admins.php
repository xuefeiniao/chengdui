<?php

namespace app\admincoin\model;

use think\Model;
use think\Db;
use app\admincoin\vilidate\AdminsVilidate;
use app\admincoin\model\Role;

class Admins extends Model
{
	protected $validate;
	public function initialize()
	{
		$this->validate 	= new AdminsVilidate();
	}
	// 添加
    public function insertA($data)
    {
    	$data = $data->param();
    	if (!$this->validate->check($data)) return json_error($this->validate->getError());
        $data['created_time'] = date("Y-m-d H:i:s"); 
        $salt                 = getSalt();
        $data['password']     = md5($data['password'].$salt); 
    	$data['salt']         = $salt; 
    	Db::startTrans();
    	try {
    		$aO = $this->create($data);
    		$uR = Db::name('user_role')->insert(['user_id' => $aO->id, 'role_id' => $data['role']]);
    		if($aO->id && $uR) {Db::commit();return json_success('管理员添加成功');} else {Db::rollback();return json_error('管理员添加失败');}
    	} catch (Exception $e) {
    		Db::rollback();
    		return json_error('管理员添加失败');
    	}
    	
    }

    // 更新
    public function updateA($data)
    {
        $data = $data->param();
        if (!$this->validate->check($data)) return json_error($this->validate->getError());
        $data['updated_time'] = date("Y-m-d H:i:s");
        $admin = Db::name("admins")->where(['id'=>$data['id']])->find();
        //$salt                 = getSalt();
        $salt                 = $admin['salt'];
        if ($data['password']){
            $data['password']     = md5($data['password'].$salt);
        }else{
            unset($data['password']);
        }
        $data['salt']         = $salt;
        Db::startTrans();
        try {
            $aO     = $this->save($data,['id' => $data['id']]);
            $uRI    = Db::name('user_role')->get($data['id']);
            if ($uRI['role_id'] != $data['role']) 
            {
                $urN = Db::name('user_role')->where(['user_id' => $data['id']])->update(['role_id' => $data['role']]);
            }
            else
            {
                $urN = true;
            }
            if($aO && $urN) {Db::commit();return json_success('管理员编辑成功');} else {Db::rollback();return json_error('管理员编辑失败');}
        } catch (Exception $e) {
            Db::rollback();
            return json_error('管理员添加失败');
        }
    }

    // 删除
    public function delA($id)
    {
        $n = static::destroy($id);
        if ($n) return json_success('管理员删除成功'); else return josn_error('管理员删除失败');
    }
}
