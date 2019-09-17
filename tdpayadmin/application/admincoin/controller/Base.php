<?php 
namespace app\admincoin\controller;

use app\admincoin\model\Admins;
use auth\Auth;
use think\Controller;
use think\Db;
use think\facade\Request;


class Base extends Controller
{
    protected $user;
	public function __empty()
    {
        $this->redirect('admincoin/Login/index');
    }

    public function __construct(Request $request)
    {
        parent::__construct();
        if (empty(session('user.id'))) 
        {
            $this->redirect('Login/login');
        }
        $uid            = session('user.id');
        $uinfo          = Admins::get($uid);
        $this->user     = $uinfo;
        $this->assign('username',$this->user['nickname']);
        $this->assign('phone',$this->user['username']);
        $res = $this->auth();
        //dump($res);
        if ($res == false) {
            $this->assign('errorcode',101);
            exit('权限不足');
        }
    }

    # 检测权限
    protected function auth()
    {
        $uid = session('user.id');
        $control = strtolower(request()->controller());
        $action = strtolower(request()->action());
        $val = $control . '.' . $action;
        if ($val=='index.index'||$val=='index.index1'){
            return true;
        }
       // dump($val);
        $role_json = session("user.role");
        if (empty($role_json)) return false;
        $role_arr = json_decode($role_json, true);
        if (in_array($val, $role_arr)) {
            return true;
        }
        return false;
    }
    # 获取定向管理用户
    protected function get_yonghu_uid(){
        $admin_uid = session('user.id');
        $admin = Db::name("admins_userinfo")->where(['admin_uid'=>$admin_uid])->order("user_uid")->select();
        if (empty($admin))return false;
        $admin_str = implode(',',array_column($admin,'user_uid'));
        return $admin_str;
    }

    function auth2($uid)
    {
        $uid = session('user.id');
        $control = strtolower(request()->controller());
        $action = strtolower(request()->action());
        $val = $control . '.' . $action;
        $role_json = session("user.role");
        if (empty($role_json)) return false;
        $role_arr = json_decode($role_json, true);
        if (in_array($val, $role_arr)) {
            return true;
        }
        return false;
    }


    # 权限检测
    function authCheck($control,$action,$uid)
    {
        //首页所有人都有权限
        if($control=='index')
        {
            return true;
        }
        $role_id    = Db::name('permission_role')->where(['permission_id' =>$uid])->value('role_id');
        $role       = RoleModel::where('id', $role_id)->find();
        if ($role['rule'] == '*') {
            //所有权限
            return true;
        }
        $node = [];
        if ($role['rule']) {
            if (strpos($role['rule'], ',') !== false) {
                $node = explode(',', $role['rule']);
            }else{
                $node[] = $role['rule'];
            }
        } else {
            //没有任何权限
            return false;
        }
        $pass = 0;
        foreach ($node as $v) {
            $node_info = NodeModel::where('id', $v)->find();
            if ($node_info['control_name'] == $control && $node_info['action_name'] == $action) {
                $pass = 1;
                break;
            }
        }
        if($pass==1){
            return true;
        }else{
            return false;
        }
    }

    //后台权限验证
    function authCheck___bak($rule,$uid,$relation='or',$type=1, $mode='url'){
        //超级管理员跳过验证
        $auth = new Auth();
        //获取当前uid所在的角色组id
        $groups = $auth->getGroups($uid);
        //这里偷懒了,因为我设置的是一个用户对应一个角色组,所以直接取值.如果是对应多个角色组的话,需另外处理
        if(in_array($groups[0]['group_id'], config('administrator'))){
            return true;
        }else{
            return $auth->check($rule,$uid,$relation='or',$type=1, $mode='url')?true:false;
        }
    }


    public static function exportExcel($expTitle, $expCellName, $expTableData, $topData) 
    {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $xlsTitle;//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        $topNum  = count($topData);

        $objPHPExcel = new \PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle);

        for ($i = 0; $i < count($topData); $i++) {
            for ($j = 0; $j < count($topData[$i]); $j++) {
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$j] . ($i + 2), $topData[$i][$j]);
            }
        }

        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . ($topNum + 2), $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                if ($expCellName[$j][0] == 'account_type') {
                    if ($expTableData[$i][$expCellName[$j][0]] == 0) {
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $topNum + 3), '餐饮');
                    } else {
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $topNum + 3), '果蔬');
                    }
                } else {
                    $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $topNum + 3), $expTableData[$i][$expCellName[$j][0]]);
                }
            }
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}