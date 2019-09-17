<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Request;
use app\admincoin\model\CoinLog;
use app\admincoin\model\UserM;
use app\admincoin\model\UserShop;
use app\admincoin\model\Torder;

class AgencyManage extends Base
{
    /**
     * 代理列表
     *
     * @return \think\Response
     */
    public function index(Request $request)
    {
        // 拼接查询条件
        $id         = $request->has('id','post') ? $request->id : '';
        $startTime  = $request->has('startDate','post') ? strtotime($request->startDate) : '';
        $endTime    = $request->has('endDate','post') ? strtotime($request->endDate) : '';
        $nickname   = $request->has('nickname','post') ? $request->nickname : '';
        $status     = $request->has('status','post') ? $request->status : '';
        $wson       = $request->has('son','post') ? $request->son : '';
        $data       = [];
        $id && $data[]          = ['id', '=', "$id"];
        $nickname && $data[]    = ['nickname', 'like', "%$nickname%"];
        $status && $data[]      = ['status', '=', "$status"];
        // $son && $data[]         = ['a.son', 'like', "%$son%"];
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
        /*$list   = UserM::where($data)->order('id','desc')->paginate(5)->each(function($item,$key) use ($wson)
        {
            $son    = UserM::where('parentid',$item->id)->count('id');
            if ($son < $wson) 
            {
                return;
            }
            else
            {
                $item->son = $son;
                return $item;
            }
           
        });*/
        $data[] = ['type', '=', "agency"];
        $list   = UserM::where($data)->order('id','desc')->paginate(10)->each(function($item, $key)
            {
                $item->sons = UserM::where('parentid', $item->id)->count('id');
            });
        $count=$list->total();
        $this->assign('request',$request);
        $this->assign('list',$list);
        $this->assign('count',$count);
        return view('daili_list');
    }

    /**
     * 代理分成.
     *
     * @return \think\Response
     */
    public function shareLog()
    {
        $list   = CoinLog::where('type in(4,8)')->order('id','desc')->alias('a')->paginate(10)->each(function($item,$key)
            {
              $item->username=UserM::where("id",$item->user_id)->value("username");
            });
        $this->assign('list',$list);
        $this->assign('count',$list->total());
        return view('daili_fencheng');
    }

    /**
     * 代理提币
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function withdraw()
    {
        $list   = Torder::where('user_type',1)->paginate(10);
        $this->assign('num',0);
        $this->assign('list',$list);
        return view('daili_tibi');
    }

    /**
     * 删除分润记录
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delSL($id)
    {
        $n  = CoinLog::destroy($id);
        if ($n) return json_success('删除成功'); else return json_error('删除失败');
    }

    /**
     * 删除提币记录
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delWL($id)
    {
        $n  = Torder::destroy($id);
        if ($n) return json_success('删除成功'); else return json_error('删除失败');
    }

}
