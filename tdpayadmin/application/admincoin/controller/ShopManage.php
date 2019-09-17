<?php

namespace app\admincoin\controller;

use app\admincoin\model\Coin;
use app\admincoin\model\UserLog;
use app\admincoin\model\UserM;
use think\Db;
use think\Request;

class ShopManage extends Base
{
    /**
     * 商户列表
     *
     * @return \think\Response
     */
    public function shopList(Request $request)
    {
        // 拼接查询条件
        $pid        = $request->has('pid') ? $request->pid : '';
        $shopName   = $request->has('shopName') ? $request->shopName : '';
        $username   = $request->has('username') ? $request->username : '';
        $startTime  = $request->has('startTime') ? strtotime($request->startTime) : '';
        $endTime    = $request->has('endTime') ? strtotime($request->endTime) : '';
        $data       = [];
        $pid && $data[] = ['parentid', 'like', "%$pid%"];
        $shopName && $data[] = ['nickname', 'like', "%$shopName%"];
        $username && $data[] = ['username', 'like', "%$username%"];
        if ($startTime && $endTime)
        {
            $data[] = ['createtime', 'between',"$startTime,$endTime"];
        }
        else if ($startTime) 
        {
            $data[] = ['createtime', '>',"$startTime"];
        }
        else if ($endTime)
        {
            $data[] = ['createtime', '<', "$endTime"];
        }
        $field  = 'id,parentid,nickname,username,createtime,status';
        $list   = UserM::where($data)->field($field)->order('id','desc')->paginate(10)->each(function($item, $key)
        {
            $balance        = Coin::where('user_id', $item->id)->column('coin_balance', 'coin_name');
            $balance_ex        = Coin::where('user_id', $item->id)->column('coin_balance_ex', 'coin_name');
            $item->balance  = $balance;
            $item->balance_ex  = $balance_ex;
        });

        $result     = $request->has('result','get') ? true: '';
        if ($result) 
        {
            $list   = UserM::where($data)->field('parentid,nickname,username,createtime,status')->order('id','desc')->all();
            $head = ['所属代理', '商户名称', '手机号/邮箱', '加入时间', '状态'];
            excelExport('商户列表', $head, $list->toArray());
        }
        else
        {
            $pArr = [$pid,$request->shopName,$request->startTime,$request->endTime];
            $this->assign('list',$list);
            $this->assign('count',$list->total());
            $this->assign('pArr',$pArr);
            $this->assign('num',0);
            return view();
        }
    }
    //商户列表信息
    public function shop(Request $request)
    {
        $where=[];
        $username = $request->param("username");
        $nickname = $request->param("nickname");
        $parentid = $request->param("parentid");
        $exshop_pid = $request->param("exshop_pid");
        $beigin = $request->param("beigin");
        $end = $request->param("end");
        if($username){
            $where[]=["username","=",$username];
        }else{
            $nickname && $where[]=["nickname","=",$nickname];
            $beigin && $where[]=["createtime",">=",strtotime($beigin)];
            $end && $where[]=["createtime","<=",strtotime($end)];
        }
        if ($parentid){
            $parent = Db::name("user")->where(['username'=>$parentid])->field("id")->find();
            if ($parent){
                $parent_arr = Db::name("user")->where(['parentid'=>$parent['id']])->select();
                if ($parent_arr){
                    $parent_arr_str = implode(',',array_column($parent_arr,'id'));
                    $where[]=['id','in',($parent_arr_str)];
                }
            }
        }

        if ($exshop_pid){
            $exshop = Db::name("user")->where(['username'=>$exshop_pid])->field("id")->find();
            if ($exshop){
                $exshop_arr = Db::name("user")->where(['exshop_pid'=>$exshop['id']])->select();
                if ($exshop_arr){
                    $exshop_arr_str = implode(',',array_column($exshop_arr,'id'));
                    $where[]=['id','in',($exshop_arr_str)];
                }
            }
        }


        $title = '用户';
        $tmp = ['shop'=>'商户','exshop'=>'承兑商','agency'=>'代理商'];
        $type = $request->param("type");
        if (!empty($type)){
            $where[]=["type","=",$type];
            $title = $tmp[$type];
        }
        $admin_uid_str = $this->get_yonghu_uid();
        if ($admin_uid_str){
            $where[]=['id','in',($admin_uid_str)];
        }
        $field="id,username,nickname,type,parentid,exshop_pid,exshop_code,inviter,integral,status,idnumber,createtime";
        $list=UserM::field($field)->where($where)->order('id desc')->paginate(10)->each(function($item,$key){
            $sell=0;
            if($item->parentid>0){
                $item->parent=UserM::where("id",$item->parentid)->value("username");
            }
            if(($item->exshop_pid>0) && ($item->type=='exshop')){
                $item->exshop=UserM::where("id",$item->exshop_pid)->value("username");
                $sell = Db::name("excoin_sell")->where("uid={$item->id} and status<>4")->field("id,uid")->count();
            }else{
                $item->exshop='';
            }
            $item->type_en=$this->get_user_bank($item->id);
            $item->dx_shop=$this->get_shop_info($item->id);
            $item->sell_status = $sell > 0 ? "是[{$sell}]" : '否';
        });

        $data=$list->all();
        $page=$list->render();
        $count=$list->count();
        $variate=["username"=>$username,"nickname"=>$nickname,"beigin"=>$beigin,"end"=>$end,"parentid"=>$parentid,"exshop_pid"=>$exshop_pid];
      return view("shop",["data"=>$data,"page"=>$page,"count"=>$count,"variate"=>$variate,'title'=>$title]);
    }

    # 承兑商获取商户名
    protected function get_shop_info($uid)
    {
        $dx_shop=Db::name("exshop_shopinfo")->where(["shop_uid"=>$uid])->field('uid')->order('uid')->select();
        $dx_shop_arr = array_column($dx_shop,'uid');
        $data = '';
        if ($dx_shop_arr){
            $dx_shop_uid = implode(',',$dx_shop_arr);
            $user = Db::name("user")->where("id in({$dx_shop_uid})")->field('username,nickname')->select();
            $data = '';
            if (is_array($user)){
                foreach ($user as $v){
                    $data.=$v['username']."({$v['nickname']}),";
                }
            }
        }
        return substr($data,0,-1);
    }
    # 获取支付类型展示图片
    public function get_user_bank($uid)
    {
        $tmp = ['1'=>'<img src="/assets/img/pay/bank.png">','2'=>'<img src="/assets/img/pay/alipay.png">','3'=>'<img src="/assets/img/pay/wechatpay.png">'];
        $bank_list = Db::name('user_bank')->where(['uid'=>$uid])->field('type')->order("type")->select();
        if (!$bank_list) return false;
        $bank_list_arr = array_column($bank_list,'type');
        $arr = array_replace($bank_list_arr,$tmp);
        if (!$arr) return false;
        $str = '';
        foreach ($bank_list_arr as $v){
            $str .= $tmp[$v];
        }
        return $str;
    }

    /**
     * 修改用户余额
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function editWallet(Request $request)
    {
        $ip = $this->request->ip();
        if ($request->type == 1) 
        {
            $n    = Coin::where([['user_id', '=', $request->id], ['coin_name', '=', $request->name]])->setInc('coin_balance',$request->num);
            $log    = Db::name("coin_log")->insert([
                'user_id'=>$request->id,'coin_name'=>$request->name,
                'coin_money'=>$request->num,'type'=>15,'action'=>'后台充值','ip'=>$ip,'createtime'=>time()]);
        }
        else if ($request->type == 2)
        {
            $n    = Coin::where([['user_id', '=', $request->id], ['coin_name', '=', $request->name]])->setDec('coin_balance',$request->num);
            $log    = Db::name("coin_log")->insert([
                'user_id'=>$request->id,'coin_name'=>$request->name,
                'coin_money'=>$request->num,'type'=>16,'action'=>'后台扣除','ip'=>$ip,'createtime'=>time()]);
        }
        if ($n&&$log) return json_success('修改成功'); else return json_error('修改失败');
    }

    /**
     * 商户详情
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function shopDetail(Request $request)
    {
        $id=$request->param("id","","intval");
        $info=UserM::where("id",$id)->field("salt",true)->find();
        if($info["parentid"]>0){
            $info["parent"]=UserM::where("id",$info["parentid"])->value("username");
        }else{
            $info["parent"]="无";
        }
        if($info["exshop_pid"]>0){
            $info["exshop_pid"]=UserM::where("id",$info["exshop_pid"])->value("username");
        }else{
            $info["exshop_pid"]="无";
        }

        if($info["shop_pid"]>0){
            $info["shop_pid"]=UserM::where("id",$info["shop_pid"])->value("username");
        }else{
            $info["shop_pid"]="无";
        }
        if($info["type"]=="shop"){
            $info["type_cn"]="商户";
        }else if($info["type"]=="exshop"){
            $info["type_cn"]="承兑商";
        }else if($info["type"]=="agency"){
            $info["type_cn"]="代理";
        }

        $paylist = Db::name('user_bank')->where(['uid'=>$id])->select();
        $payinfo = '';
        if ($paylist){
            $payinfo=array_column($paylist,null,'type');
        }
       # halt($payinfo);
        return view("shopDetail",["data"=>$info,'payinfo'=>$payinfo]);
    }
    /**
     * 商户信息修改
     */
    public function shopinfoup(Request $request)
    {
        $data=$request->param();
        $user = Db::name('user')->where(['id'=>$data['id']])->field("id,salt")->find();
        if (empty($user))return json(["status"=>0,"msg"=>"账号异常"]);
        if (!empty($data['parentid'])){
            $pid = Db::name('user')->where(['username'=>$data['parentid'],'type'=>'agency'])->value('id');
            if ($pid){
                $data['parentid']=$pid;
            }else{
                unset($data['parentid']);
            }
        }
        if (!empty($data['exshop_pid'])){
            $pid = Db::name('user')->where(['username'=>$data['exshop_pid'],'type'=>'exshop'])->value('id');
            if ($pid){
                $data['exshop_pid']=$pid;
            }else{
                unset($data['exshop_pid']);
            }
        }

        if (!empty($data['shop_pid'])){
            $pid = Db::name('user')->where(['username'=>$data['shop_pid'],'type'=>'agency'])->value('id');
            if ($pid){
                $data['shop_pid']=$pid;
            }else{
                unset($data['shop_pid']);
            }
        }

        if (!empty($data['password'])){
            $data['password'] = md5($data["password"].md5($user["salt"]));
        }else{
            unset($data['password']);
        }
        if (!empty($data['paypassword'])){
            $data['paypassword'] = md5($data['paypassword'] . $user["salt"]);
        }else{
            unset($data['paypassword']);
        }
        $field="parentid,exshop_pid,shop_pid,idnumber,nickname,password,paypassword,exshop_code";
        $status=UserM::field($field)->update($data);
        if($status!==false){
            return json(["status"=>1,"msg"=>"修改成功"]);
        }else{
            return json(["status"=>0,"msg"=>"修改失败"]);
        }
    }
    /**
     * 参数详情
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function shopVariate(Request $request)
    {
        $id=$request->param("id","","intval");
        $info=Db::name("user_api")
            ->where("user_id",$id)
            ->find();
        $user = Db::name('user')->where(['id'=>$id])->field('id,type')->find();
        return view("shopVariate",["data"=>$info,"user"=>$user]);
    }
    /**
     * 参数修改
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function shopVariateup(Request $request)
    {
        $data=$request->param();
        /*$data["usdttime"]=strtotime($data["usdttime"]);
        $data["btctime"]=strtotime($data["btctime"]);
        $data["ethtime"]=strtotime($data["ethtime"]);*/

        $uid = $data['user_id'];
        $uapi = Db::name("user_api")->where(['user_id' => $uid])->find();
        $status = '';
        if ($uapi) {
            unset($data['user_id']);
            $status = Db::name("user_api")->where(['user_id' => $uid])->update($data);
        } else {
            $data['user_id'] = $uid;
            $status = Db::name("user_api")->insert($data);
        }

        if ($status !== false) {
            return json(["status" => 1, "msg" => "修改成功"]);
        } else {
            return json(["status" => 0, "msg" => "修改失败"]);
        }
    }
    /**
     * 变更记录
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function shopChange(Request $request)
    {
        return view();
    }
    /**
     * 商户登录记录
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function shopCheckRecord(Request $request)
    {
        // 拼接查询条件
        $id             = $request->has('id','post') ? $request->id : '';
        $nickname       = $request->has('nickname','post') ? $request->nickname : '';
        $ip             = $request->has('ip','post') ? $request->ip : '';
        $ipAddress      = $request->has('ipAddress','post') ? $request->ipAddress : '';
        $startDate      = $request->has('startDate','post') ? strtotime($request->startDate) : '';
        $endDate        = $request->has('endDate','post') ? strtotime($request->endDate) : '';
        $data           = [];
        $id && $data[]          = ['l.id', 'like', "%$id%"];
        $nickname && $data[]    = ['l.username', 'like', "%$nickname%"];
        $ip && $data[]          = ['l.ip', 'like', "%$ip%"];
        // $ipAddress && $data[] = ['o.ipAddress', 'like', "%$ipAddress%"];             // 登陆地区
        if ($startDate && $endDate) 
        {
            $data[] = ['l.createtime', 'between',"$startDate,$endDate"];
        }
        else if ($startDate) 
        {
            $data[] = ['l.createtime', '>',"$startDate"];
        }
        else if ($endDate)
        {
            $data[] = ['l.createtime', '<', "$endDate"];
        }

        $list   = UserLog::where('l.type',1)->alias('l')->join('user u','u.username=l.username')->order('id','desc')->field('u.parentid,l.*')->where($data)->paginate(10);
        // 获取地理位置
        /*foreach ($list as $key => &$value) 
        {
            $address         = getCity($value['ip']);
            $value['adr']    = $address['country'].' '.$address['region'].' '.$address['city'];
        }*/
        $this->assign('search',$request);
        $this->assign('list',$list);
        $this->assign('count',$list->total());
        return view();
    }
    # 冻结
    public function dongjie($id)
    {
        $uid = input('id'); # 状态:normal=正常,hidden=冻结
        $status = input('status');
        $res = Db::name("user")->where(['id'=>$uid])->update(['status'=>$status]);
        if ($res){
            return json(["status" => 1, "msg" => "修改成功"]);
        }else{
            return json(["status" => 0, "msg" => "修改失败"]);
        }
    }
    /**
     * 删除登陆记录.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function logDel($id)
    {
        $n  = UserLog::destroy($id);
        if ($n) return json_success('记录删除成功'); else return josn_error('记录删除失败');
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
