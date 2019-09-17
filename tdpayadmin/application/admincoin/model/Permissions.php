<?php

namespace app\admincoin\model;

use think\Model;
use app\admincoin\vilidate\PermissionVilidate;

class Permissions extends Model
{
	protected $pk = 'permissions_id';
	protected $validate;
	public function initialize()
	{
		$this->validate 	= new PermissionVilidate();
	}

	// 添加
    public function insertP($data)
    {
    	if (!$this->validate->check($data)) return json_error($this->validate->getError());
    	$data['created_time'] = date("Y-m-d H:i:s"); 
    	if($this->save($data)) return json_success('添加成功'); else return json_error('添加失败');
    }

    // 更新
    public function updateP($data)
    {
    	if (!$this->validate->check($data)) return json_error($this->validate->getError());
    	$data['updated_time'] = date("Y-m-d H:i:s"); 
    	if($this->save($data,['permissions_id' => $data['id']])) return json_success('修改成功'); else return json_error('修改失败');
    }

    // 删除
    public function delP($id)
    {
        $hasParent = static::where('parent',$id)->find();
        if ($hasParent) return json_error('该节点有子节点，不允许直接删除');
    	$n = static::destroy($id);
    	if ($n) return json_success('删除成功'); else return josn_error('删除失败');
    }

    // 获取节点列表
    public function getList()
    {
        $plist = static::where('parent',0)->order('sort','asc')->select()->toArray();
        $slist = static::where('parent','<>',0)->order('sort','asc')->select()->toArray();
        foreach ($plist as $key => &$value) 
        {
            foreach($slist as $k => $v)
            {
                if ($value['permissions_id'] == $v['parent']) $value['son'][] = $v;
            }
        }
        return $plist;
    }
}
