<?php

namespace app\admincoin\model;

use app\admincoin\vilidate\RoleVilidate;
use think\Db;
use think\Model;

class Role extends Model
{
    protected $pk = 'role_id';
    protected $validate;
	public function initialize()
	{
		$this->validate 	= new RoleVilidate;
	}

    // 添加
    public static function insertR($data)
    {
    	$data = $data->param();
        $roleV = new RoleVilidate;
        $role  = new Role;
        $PermissionsRole  = new PermissionRole;
    	if (!$roleV->check($data)) return json_error($roleV->getError());
        $rdata = [
            'role_name'     => $data['username'],
            'discription'   => $data['discription'],
            'created_time'  => date('Y-m-d H:i:s')
        ];
        
        Db::startTrans();
        try {
            $rN = $role->save($rdata);
            foreach ($data['permissions'] as $key => $value) 
            {
                $pr[] = ['permission_id' => $value, 'role_id' => $role->role_id];
            }
            $prN = $PermissionsRole->saveAll($pr);

            if ($rN && $prN) 
            {
                Db::commit();
                return json_success('角色添加成功');
            }
        } catch (Exception $e) {
            Db::rollback();
            return json_error('角色添加失败');
        }
    }

    // 更新
    public static function updateR($data)
    {
        $roleV              = new RoleVilidate;
        $role               = new Role;
        $PermissionsRole    = new PermissionRole;
        if (!$roleV->check($data)) return json_error($roleV->getError());
        $rdata = [
            'role_name'     => $data['username'],
            'discription'   => $data['discription'],
            'updated_time'  => date('Y-m-d H:i:s')
        ];

        $rN = $role->save($rdata,['role_id' => $data['role_id']]);
        if (!empty($data['permissions'])){
            foreach ($data['permissions'] as $key => $value)
            {
                $pr[] = ['permission_id' => $value, 'role_id' => $data['role_id']];
            }
        }
        $dN     = PermissionRole::where('role_id',$data['role_id'])->delete();
        if (!empty($pr)) {
            $prN    = $PermissionsRole->saveAll($pr);
        }

        if ($rN || $prN) return json_success('更新成功'); else return json_error('更新失败');
    }

    // 删除
    public function delR($id)
    {
        Db::startTrans();
        try {
            $rN     = static::where('role_id',$id)->delete();
            $hasP   = PermissionRole::where('role_id',$id)->find();
            if ($hasP)
            {
                $prN    = PermissionRole::where('role_id',$id)->delete();
            }
            else
            {
                $prN = true;
            }
            
            if ($rN && $prN)
            {
                Db::commit();
                return json_success('删除成功');
            }  
            else 
            {
                Db::rollback();
                return json_error('删除失败');
            }
        } catch (Exception $e) {
            Db::rollback();
            return json_error('删除失败');
        }
        
        
    }
}
