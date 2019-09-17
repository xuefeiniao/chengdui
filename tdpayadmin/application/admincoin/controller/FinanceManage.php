<?php

namespace app\admincoin\controller;

use PHPExcel_IOFactory;
use PHPExcel_Style;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Style_Fill;
use think\Db;
use think\Request;

class FinanceManage extends Base
{
    /**
     * 在线充值列表
     *
     * @return \think\Response
     */
    public function onlineRecharge()
    {
        return view('recharge');
    }

    /**
     * 财务明细.
     *
     * @return \think\Response
     */
    public function fDetail()
    {
        return view('finance_detail');
    }

    /**
     * 财务明细
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function integralDetail(Request $request)
    {
        $username=$request->param("username");
        $type=$request->param("type","","intval");
        $coinname=$request->param("coinname","","strtolower");
        if($username){
            $id = Db::name("user")->where("username", $username)->value("id");
            $id && $where[] = ["user_id", "=", $id];
        }
        $type && $type!="all" && $where[]=["type","=",$type];
        $coinname && $coinname!="all" && $where[]=["coin_name","=",$coinname];

        $where[]=["id",">",0];
        $list=Db::name("coin_log")->where($where)->order("id DESC")->paginate(10);
        $data=$list->all();
        $page=$list->render();
        $count=$list->total();
        foreach($data as $key=>$val){
            $data[$key]["username"]=Db::name("user")->where("id",$val["user_id"])->value("username");
            $data[$key]["createtime"]=date("Y-m-d H:i:s",$val["createtime"]);
        }

        $znum = array_sum(array_column($data,'coin_money'));

        $variate=["username"=>$username,"type"=>$type,"coinname"=>$coinname];
        return view('integral_detail',["page"=>$page,"count"=>$count,"data"=>$data,"znum" => $znum,"variate"=>$variate]);
    }
    /**
     * 积分明细
     */
    public function integral(Request $request)
    {
        $where=[];
        $username=$request->param("username");
        $type=$request->param("type","","intval");
        $beigin=$request->param("beigin");
        $end=$request->param("end");
        if(!empty($username)){
            $id=Db::name("user")->where("username",$username)->value("id");
            $id && $where[]=["user_id","=",$id];
        }
        ($type && $type!=5) && $where[]=["type","=",$type];
        $beigin && $where[]=["createtime",">=",strtotime($beigin)];
        $end && $where[]=["createtime","<=",strtotime($end)];
        $list=Db::name("integral_log")
            ->where($where)
            ->order("id DESC")
            ->paginate(10)
            ->each(function($item,$key){
               $item["username"]=Db::name("user")->where("id",$item["user_id"])->value("username");
               return $item;
            });
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        $variate=["type"=>$type,"beigin"=>$beigin,"end"=>$end,"username"=>$username];
        return $this->fetch("integral", [
            "page"=>$page,
            "data"=>$data,
            "count"=>$count,
            "variate"=>$variate,
            "num"=>1
        ]);
    }
    /**
     * 币种转换记录
     */
    public function changedetail(Request $request)
    {
        $where=[];
        $coin_name=$request->param("coin_name","","strtolower");
        $to_coinname=$request->param("to_coinname","","strtolower");
        $coin_name && $coin_name!="all" && $where[]=["coin_name","=",$coin_name];
        $to_coinname && $to_coinname!="all" && $where[]=["to_coinname","=",$to_coinname];
        $list=db("change_log")
            ->where($where)
            ->order("id DESC")
            ->paginate(10);
        $page=$list->render();
        $data=$list->all();
        $count=$list->total();
        $variate=["coin_name"=>$coin_name,"to_coinname"=>$to_coinname];
        return $this->fetch("changedetail",[
            "page"=>$page,
            "data"=>$data,
            "count"=>$count,
            "variate"=>$variate
        ]);
    }
    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
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

    public function exportchangeinfo()
    {

        $where=[];
        $data=Db::name("change_log")->where($where)->select();
        $excel = new \PHPExcel();
        $excel->getProperties()
            ->setCreator("FastAdmin")
            ->setLastModifiedBy("FastAdmin")
            ->setTitle("标题")
            ->setSubject("Subject");
        $excel->getDefaultStyle()->getFont()->setName('Microsoft Yahei');
        $excel->getDefaultStyle()->getFont()->setSize(12);

        $this->sharedStyle = new PHPExcel_Style();
        $this->sharedStyle->applyFromArray(
            array(
                'fill'      => array(
                    'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => '000000')
                ),
                'font'      => array(
                    'color' => array('rgb' => "000000"),
                ),
                'alignment' => array(
                    'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'indent'     => 1
                ),
                'borders'   => array(
                    'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                )
            ));
        $myrow = 1;
        $worksheet = $excel->setActiveSheetIndex(0);
        $worksheet->setTitle('标题');
        $worksheet->setCellValue("A".$myrow, "序号")
            ->setCellValue("B".$myrow, "用户")
            ->setCellValue("C".$myrow, "币种")
            ->setCellValue("D".$myrow, "兑换币种")
            ->setCellValue("E".$myrow, "兑换数量")
            ->setCellValue("F".$myrow, "兑换比例")
            ->setCellValue("G".$myrow, "手续费")
            ->setCellValue("H".$myrow, "到账数量")
            ->setCellValue("I".$myrow, "时间");
        $mynum=1;
        $myrow=$myrow+1;
        foreach($data as $value){
            $username=Db::name("user")->where("id",$value["user_id"])->value("username");
            $worksheet->setCellValue('A' . $myrow, $mynum)
                ->setCellValue('B' . $myrow, $username)
                ->setCellValue('C' . $myrow, strtoupper($value['coin_name']))
                ->setCellValue('D' . $myrow, strtoupper($value['to_coinname']))
                ->setCellValue('E' . $myrow, $value['coin_number'])
                ->setCellValue('F' . $myrow, $value["coin_ratio"])
                ->setCellValue('G' . $myrow, $value["coin_fee"])
                ->setCellValue('H' . $myrow, $value["coin_aumount"])
                ->setCellValue('I' . $myrow, date("Y-m-d H:i:s",$value["createtime"]));
            //     $worksheet->getActiveSheet()->getRowDimension('' . $myrow)->setRowHeight(20);/*设置行高 不能批量的设置 这种感觉 if（has（蛋）！=0）{疼();}*/
            $myrow++;
            $mynum++;
            ob_flush();
            flush();
        }
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('G')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $excel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $excel->createSheet();
        // Redirect output to a client’s web browser (Excel2007)
        $title = "兑换明细-".date("Y-m-d H:i");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $title . '.xls"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $objWriter->save('php://output');
    }

    # 财务报表
    public function clwulog(Request $request)
    {

        $username = $request->param("username");
        $beigin = $request->param("beigin");
        $end = $request->param("end");

        $beigin && $where[]=["time",">=",$beigin];
        $end && $where[]=["time","<=",$end];

        $where[]=['match_uid','<>',0];

        if ($username){
            $user = Db::name('user')->where(['username'=>$username,'type'=>'shop'])->field('id')->find();
            if ($user){
                $where[]=["app_uid","=",$user['id']];
            }
        }

        $match = Db::name("excoin_match")->where($where)->select();
        $data  = [];
        $data_arr  = [];

        if ($match){
            foreach ($match as $k=>$v){
                $data[$v['match_uid']][] = $v;
            }
            foreach ($data as $kk=>$vv){
                $exshop = Db::name('user')->where("id={$kk}")->field('username,nickname,exshop_code')->find();
                $data_arr[$kk]['exshop_uname']=$exshop['username'];
                $data_arr[$kk]['exshop_nname']=$exshop['nickname'];
                $data_arr[$kk]['exshop_code']=$exshop['exshop_code'];
                $data_arr[$kk]['type_en']=$this->get_user_bank($kk);


                $price = 0;
                $num = 0;
                $true_num = 0;
                $shop_fee = 0;
                $true = 0;
                $fail = 0;
                $matchid  = [];
                foreach ($vv as $vs){
                    if ($vs['status'] == 2) {
                        $price += $vs['price'];
                        $num += $vs['num'];
                        $true_num += $vs['true_num'];
                        $shop_fee += $vs['shop_fee'];
//                        $true[] = $vs['status'];
                        $true += 1;
                    }else{
//                        $fail[] = $vs['status'];
                        $fail += 1;
                    }
                    $matchid[] = $vs['id'];
                }
                $data_arr[$kk]['price'] = $price;
                $data_arr[$kk]['num'] = $num * 7;
                $data_arr[$kk]['true_num'] = $true_num * 7;
                $data_arr[$kk]['shop_fee'] = $shop_fee * 7;
                $data_arr[$kk]['true'] = $true;
                $data_arr[$kk]['fail'] = $fail;
                $data_arr[$kk]['time'] = $this->get_mtime($matchid);
            }
        }
        $zprice = array_sum(array_column($data_arr,'price'));
        $znum = array_sum(array_column($data_arr,'num'));
        $ztrue_num = array_sum(array_column($data_arr,'true_num'));
        $zshop_fee = array_sum(array_column($data_arr,'shop_fee'));
        $ztrue = array_sum(array_column($data_arr,'true'));
        $zfail = array_sum(array_column($data_arr,'fail'));
        $tkji=['zprice'=>$zprice,'znum'=>$znum,'ztrue_num'=>$ztrue_num,'zshop_fee'=>$zshop_fee,'ztrue'=>$ztrue,'zfail'=>$zfail];
        $variate = ["beigin" => $beigin, "end" => $end,'username'=>$username];
        return $this->fetch("clwulog", ["data" => $data_arr,"variate" =>$variate,'tkji'=>$tkji]);
    }

    # 匹配时间段
    protected function get_mtime(array $matchid)
    {
        if (!$matchid)return false;
        $matchid = implode(',',$matchid);
        $match_start = Db::name('excoin_match')->where('id','in',$matchid)->order("time")->field('time')->find();
        $match_end = Db::name('excoin_match')->where('id','in',$matchid)->order("time desc")->field('time')->find();
        return ['start'=>$match_start['time'],'end'=>$match_end['time']];
    }

    # 查询商户id
    public function get_shop_clwulog($app_uid){
        $match = Db::name("excoin_match")->where("match_uid<>0 and app_uid={$app_uid}")->select();
        $data  = [];
        $data_arr  = [];
        if ($match){
            foreach ($match as $k=>$v){
                $data[$v['match_uid']][] = $v;
            }
            foreach ($data as $kk=>$vv){
                $exshop = Db::name('user')->where("id={$kk}")->field('username,exshop_code')->find();
                $data_arr[$kk]['exshop_uname']=$exshop['username'];
                $data_arr[$kk]['exshop_code']=$exshop['exshop_code'];
                $data_arr[$kk]['type_en']=$this->get_user_bank($kk);

                $price = [];
                $num = [];
                $true_num = [];
                $shop_fee = [];
                $true = [];
                $fail = [];
                foreach ($vv as $vs){
                    if ($vs['status'] == 2) {
                        $price[]=$vs['price'];
                        $num[]=$vs['num'];
                        $true_num[]=$vs['true_num'];
                        $shop_fee[]=$vs['shop_fee'];
                        $true[] = $vs['status'];
                    }else{
                        $fail[] = $vs['status'];
                    }
                }
                $data_arr[$kk]['price'] = round(array_sum($price),2);
                $data_arr[$kk]['num'] = round(array_sum($num),2);
                $data_arr[$kk]['true_num'] = round(array_sum($true_num),2);
                $data_arr[$kk]['shop_fee'] = round(array_sum($shop_fee),2);
                $data_arr[$kk]['true'] = array_sum($true);
                $data_arr[$kk]['fail'] = array_sum($fail);
            }
        }
        return $data_arr;
    }
    # 获取支付类型展示图片
    protected function get_user_bank($uid)
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
}
