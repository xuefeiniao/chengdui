<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Request;
use think\Db;
use app\admincoin\model\Config;
use app\admincoin\model\CoinConfig;

class SystemSet extends Base
{
    /**
     * 参数设置
     *
     * @return \think\Response
     */
    public function systemSet()
    {
        $list   = Config::where("name!='otctime' AND name!='outtime'")->all();
        $this->assign('list',$list);
        return view('canshu1');
    }

    /**
     * 参数修改
     *
     * @return \think\Response
     */
    public function systemEdit(Request $request)
    {
        $data = $request->param('');
        Db::startTrans();
        try {
            foreach ($data as $key => $value) 
            {
                Config::where('name',$key)->update(['value' => $value]);
            }
            Db::commit();
            return json_success('更新成功');
        } catch (Exception $e) {
            Db::rollback();
            return json_error('更新失败');
        }
        
    }

    /**
     * 币种配置.
     *
     * @return \think\Response
     */
    public function currencySet(Request $request)
    {

        if ($request->isPost())
        {
            $name   = $request->param('coin-name');
            $data   = $request->param('');
            $con    = CoinConfig::where('name',$name)->find();
            foreach ($data as $key => $value)
            {
                $con->$key = $value;
            }
            $list = $con->save();
            if ($list) return json_success(['cInfo' => $list]); else return json_error('更新失败');
        }
        else
        {
            $where  = $request->has('name') ? $request->name: '';
            if ($where) 
            {
                $where  = [['name','=',$where],];
                $data = CoinConfig::where($where)->find();
                if ($data) return json_success($data); else return json_error($data);
            }
            # $name   = CoinConfig::column('name');
            # $list   = CoinConfig::find();
            $name = ['usdt'];
            $list   = CoinConfig::where('name','usdt')->find();
            $this->assign('list',$list);
            $this->assign('name',$name);
            return view('bizhong_set');
        }
    }

    /**
     * 数据库备份
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function sqlBak(Request $request)
    {
        return view('beifen');
    }

    # 交易设置
    public function setexshop()
    {
        $list   = Db::name('exshop_config')->all();
        $this->assign('list',$list);

        return view('setexshop');
    }
    # 修改交易设置
    public function setexshopac(Request $request)
    {
        $data = $request->param('');
        Db::startTrans();
        try {
            foreach ($data as $key => $value)
            {
                Db::name('exshop_config')->where('name',$key)->update(['val' => $value]);
            }
            Db::commit();
            return json_success('更新成功');
        } catch (Exception $e) {
            Db::rollback();
            return json_error('更新失败');
        }
    }

    public function set_uifh()
    {
        $data = Db::name("t1_log")->order('id desc')->field("time")->find();
        return view('set_uifh',['data'=>$data]);
    }
}
