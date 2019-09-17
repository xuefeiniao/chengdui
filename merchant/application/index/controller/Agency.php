<?php
namespace app\index\controller;
use think\Db;
use think\Request;

require_once('../application/common.php');
class Agency extends Indexcommon
{

    public function buy_list(Request $request)
    {
        $uid = session("id");
        $user_info = Db::name('user')->where(['id'=>$uid,'type'=>'agency'])->field('id')->find();
        if ($user_info){
            $where='status !=4';
        }else{
            $where=0;
        }
        $list = Db::name("excoin_buy")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();

        foreach ($data as $key => $val) {

        }
        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval")
        ];

        return view("buy_list", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate]);
    }

    public function match_list(Request $request)
    {
        $uid = session("id");
        $user_info = Db::name('user')->where(['id'=>$uid,'type'=>'agency'])->field('id')->find();
        if ($user_info){
            $where='status !=4';
        }else{
            $where=1;
        }
        $list = Db::name("excoin_buy")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();

        foreach ($data as $key => $val) {

        }
        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval")
        ];

        return view("match_list", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate]);
    }

    public function invitlist(Request $request)
    {
        $uid = session("id");
       /* $user_info = Db::name('user')->where(['id'=>$uid,'type'=>'agency'])->field('id')->find();
        if ($user_info){
            $where="type!='agency' and parentid={$uid}";
        }else{
            $where=1;
        }*/
       $type = input('type');
        if (!empty($type)){
            $where = "type='{$type}' and parentid={$uid}";
        }else{
            $where = "parentid={$uid}";
        }
        $list = Db::name("user")->where($where)->order("id DESC")->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();

        foreach ($data as $key => $val) {

        }
        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval")
        ];

        return view("invitlist", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate]);
    }

    public function jiang(Request $request)
    {
        $uid = session("id");
        $list = Db::name("coin_log")->where("user_id={$uid}")->order("id DESC")->paginate(10);
        $page = $list->render();
        $data = $list->all();
        $count = $list->total();
        foreach ($data as $key => $val) {
            $data[$key]['createtime'] = getTime($val['createtime']);
        }
        $variate = [
            "username"  => $request->param("username", "", "trim"),
            "type"      => $request->param("type", "", "intval"),
            "nowstatus" => $request->param("nowstatus", "", "intval")
        ];
        return view("jiang", ["page" => $page, "data" => $data, "count" => $count, "variate" => $variate]);
    }

    public function yeji()
    {
        $uid = session("id");
        $stime = input('stime');
        $etime = input('etime');

        $username = input('username');

        if ($username)
            $where[] = ['username','=',$username];

        $where[] = ['parentid','=',$uid];

        $exshop = Db::name("user")->where($where)->field("id,username,nickname")->paginate(10);
        $page = $exshop->render();
        $exshop_data = $exshop->all();
        $count = $exshop->total();
        $pingtai_price = $this->get_exshop_conf('usdt_price');
        foreach ($exshop_data as $k=>$v){
            $map = "match_uid ={$v['id']} and status=2";
            if ($stime && $etime) {
                $map .= " and time between '{$stime}' and '{$etime}'";
            }
            $exshop_c_yeji = Db::name("excoin_match")->where($map)->sum('num');
            $exshop_c_yeji_num = !empty($exshop_c_yeji)? $exshop_c_yeji:0;

            $pyeji = $this->get_c_yeji($v['id'],$stime,$etime);
            $pyeji_num = !empty($pyeji)? $pyeji:0;

            $num = $exshop_c_yeji_num + $pyeji_num;
            $cny_num = $num * $pingtai_price;
            
            $exshop_data[$k]['num'] = $num;
            $exshop_data[$k]['num_cny'] = $cny_num;
        }
        $variate = [
            "username"  => $username,
            "stime"  => $stime,
            "etime"  => $etime,
        ];
        return view("yeji", ["page" => $page, "data" => $exshop_data, "count" => $count, "variate" => $variate]);
    }

    # 个人业绩计算
    protected function get_c_yeji($uid_c,$stime='',$etime='')
    {
        $exshop_c = Db::name("user")->where("exshop_pid={$uid_c}")->field("id,username,nickname")->select();
        if (!$exshop_c) return false;


        $data = [];
        foreach ($exshop_c as $k=>$v){
            $where = "match_uid ={$v['id']} and status=2";
            if ($stime && $etime) {
                $where .= " and time between '{$stime}' and '{$etime}'";
            }
            $match_list = Db::name("excoin_match")->where($where)->field('num')->select();
            $data[] = array_sum(array_column($match_list,'num'));
        }


        return array_sum($data);
    }
}