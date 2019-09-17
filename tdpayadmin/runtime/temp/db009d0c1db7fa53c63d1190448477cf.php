<?php /*a:1:{s:98:"E:\phpstudy\PHPTutorial\WWW\chengdui\tdpayadmin\application\admincoin\view\exshop\sell_listac.html";i:1564411866;}*/ ?>
<style>
    .search .form-control{
        display:inline-block;
        width:171px;
    }
    .search select.form-control{
        height:31px!important;
        line-height: 31px!important;
        padding:0 15px!important;
    }
    .search>div{
        padding:0;
    }
    .search label{
        margin-right:8px;
    }
</style>
<style>
    /*分页样式*/
    .pagination{text-align:center;margin-top:20px;margin-bottom: 20px;}
    .pagination li{margin:0px 10px; border:1px solid #e6e6e6;padding: 3px 8px;display: inline-block;}
    .pagination .active{background-color: #46A3FF;color: #fff;}
    .pagination .disabled{color:#aaa;}
</style>
<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4>出售订单列表</h4>
                </div>
                <div class="card-body">
                    <!--搜索条件-->
                    <form id="formID">
                        <div class="row search" style="margin-bottom:20px;padding:0 10px 0 15px;">
                            <div class="col-sm-12 col-md-3">
                                <label>订单号：
                                    <input type="text" class="form-control form-control-sm" name="order_no" value="<?php echo htmlentities($variate['order_no']); ?>" />
                                </label>
                                <label>备注号：
                                    <input type="text" class="form-control form-control-sm" name="liushui_no" value="<?php echo htmlentities($variate['liushui_no']); ?>" />
                                </label>
                                <label>订单状态：
                                    <select class="form-control" name="status">
                                        <option value="-1" <?php if($variate["status"]=="-1"): ?>selected<?php endif; ?>>全部状态</option>
                                        <option value="0" <?php if($variate["status"]=="0"): ?>selected<?php endif; ?>>待打款</option>
                                        <option value="1" <?php if($variate["status"]=="1"): ?>selected<?php endif; ?>>待确认</option>
                                        <option value="2" <?php if($variate["status"]=="2"): ?>selected<?php endif; ?>>已完成</option>
                                        <option value="3" <?php if($variate["status"]=="3"): ?>selected<?php endif; ?>>过期订单</option>
                                        <option value="4" <?php if($variate["status"]=="4"): ?>selected<?php endif; ?>>已取消</option>
                                        <option value="5" <?php if($variate["status"]=="5"): ?>selected<?php endif; ?>>投诉订单</option>
                                    </select>
                                </label>
                            </div>


                            <div class="col-sm-12 col-md-4">
                                <label>开始日期：
                                    <input type="text" class="form-control form-control-sm" name="beigin" value="<?php echo htmlentities($variate['beigin']); ?>" id="startDate"/>
                                </label>
                                <label>结束日期：
                                    <input type="text" class="form-control form-control-sm" name="end" value="<?php echo htmlentities($variate['end']); ?>" id="endDate"/>
                                </label>
                            </div>

                            <div class="col-sm-12 col-md-4 text-right">
                                <input type="button" class="btn btn-primary bill" value="搜索">
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table id="example" class="table table-striped table-bordered border-t0 text-nowrap w-100" >
                            <thead>
                            <tr>
                                <th class="wd-15p">订单编号</th>
                                <th class="wd-15p">承兑用户名</th>
                                <th class="wd-15p">承兑昵称</th>
                                <th class="wd-15p">编号</th>
                                <th class="wd-25p">交易金额</th>
                                <th class="wd-25p">手续费金额</th>
                                <th class="wd-25p">实际到账金额</th>
                                <th class="wd-25p">交易数量</th>
                                <th class="wd-25p">手续费数量</th>
                                <th class="wd-25p">实际到账数量</th>
                                <th class="wd-25p">交易价格</th>
                                <th class="wd-25p">支付方式</th>
                                <th class="wd-25p">订单时间</th>
                                <th class="wd-25p">回调地址</th>
                                <th class="wd-25p">回调状态</th>
                                <th class="wd-25p">交易状态</th>
                                <th class="wd-25p">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($data as $key=>$vo): ?>
                            <tr>
                                <td><?php echo htmlentities($vo['order_no']); ?></td>
                                <td><?php echo htmlentities($vo['user']['username']); ?></td>
                                <td><?php echo htmlentities($vo['user']['nickname']); ?></td>
                                <td> <span class="text-danger"><?php echo htmlentities($vo['user']['exshop_code']); ?></span></td>
                                <td><?php echo htmlentities(round($vo['price'],2)); ?></td>
                                <td><?php echo htmlentities(round($vo['shop_fee_cny'],2)); ?></td>
                                <td><?php echo htmlentities(round($vo['true_num_cny'],2)); ?></td>
                                <td><?php echo htmlentities(round($vo['num'],2)); ?></td>
                                <td><?php if (!empty($vo['shop_fee']) && $vo['shop_fee'] > 0) echo round($vo['shop_fee'],2); ?></td>
                                <td><?php echo htmlentities(round($vo['true_num'],2)); ?></td>
                                <td><?php echo number_format($vo['price']/$vo['num'],2,'.','');?></td>
                                <td><?php echo $vo['type'];?></td>
                                <td><?php echo htmlentities($vo['time']); ?></td>
                                <td><?php echo htmlentities($vo['callback_addr']); ?></td>
                                <?php if ($vo['callback_addr']){?>
                                    <td><?php $tmp=["<span class='text-danger'>失败</span>","<span class='text-muted'>成功</span>"];echo $tmp[$vo['callback_status']];?></td>
                                <?php }else{
                                    echo "<td></td>";
                                }?>
                                <td><span <?php if ($vo['status']==5){echo 'class="text-danger"';} ?>><?php $tmp=['待打款','待确认','已完成','过期订单','已取消','投诉中'];echo $tmp[$vo['status']]?></span></td>
                                <td><?php if(($vo['type']=="2") and ($vo['callback_status']=="0") and ($vo['callback_addr']!="")): ?>
                                    <div class="badge badge-success" onclick="callbackac(<?php echo htmlentities($vo['id']); ?>)" style="cursor:pointer;
">重发回调</div>
                                    <?php endif; if(($vo['status']=="0") and ($vo['type']!="")): ?>
                                    <a href="http://www.taida333.com/index/pay/paydetail/order_no/<?php echo htmlentities($vo['order_no']); ?>" target="_blank"><div class="badge badge-success" style="cursor:pointer;
">查看</div></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- 分页 -->
                    <div class="row">
                        <div class="col-sm-12 col-md-5">
                            <div class="dataTables_info" id="example_info" role="status" aria-live="polite">
                                共<span><?php echo htmlentities($count); ?></span>条
                                实际总数量 [<span class="text-danger"> <?php echo htmlentities(round($true_num,2)); ?> </span>]
                                实际总金额 [<span class="text-danger"> <?php echo htmlentities(round($true_num_cny,2)); ?> </span>]
                                总手续费数量 [<span class="text-danger"> <?php echo htmlentities(round($shop_fee,2)); ?> </span>]
                                总手续费金额 [<span class="text-danger"> <?php echo htmlentities(round($shop_fee_cny,2)); ?> </span>]
                                交易金额 [<span class="text-danger"> <?php echo htmlentities(round($price_zj,2)); ?> </span>]
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-7">
                            <div class="dataTables_paginate paging_simple_numbers text-right" >
                                <ul class="pagination" style="justify-content: flex-end;">
                                    <?php echo $page; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Grid Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="exampleModal2" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="example-Modal2">请输入申诉理由，并确认</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <textarea class="form-control" rows="5" id="inputtousu"></textarea>
                    </div>
                    <div id="tusuids">

                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="tousu">提交</button>
                <button type="button" class="btn btn-success" data-dismiss="modal">关闭</button>
            </div>

        </div>
    </div>
</div>
<script>
    /**
     * 引用日期插件
     */
    laydate.render({
        elem: '#startDate' //指定元素
    });
    laydate.render({
        elem: '#endDate' //指定元素
    });
    $(".btn-action1").click(function(){
        var id=$(this).attr("data-id");
        //type=1 代表是充值订单
        $.post("<?php echo url('index/getcorder'); ?>",{id:id,type:1}, function(data){
            console.log(data);
            $("#order_number").html(data.data.order_number);
            $("#address_id").html(data.data.address_id);
            $("#coin_money").html(data.data.coin_money);
            $("#coin_from").html(data.data.coin_from);
            $("#txid").html(data.data.txid);
            $("#coin_affirm").html(data.data.coin_affirm);
        });
    });
    /** 过滤数据 */
    $(".bill").click(function(){
        var data=$("#formID").serialize();
        $('.app-content').empty();
        $.ajax({
            type: "POST",
            data:data,
            url:"<?php echo url('exshop/sell_listac'); ?>",
            success: function(e) {
                $('.app-content').append(e);
            }
        });
    });
    /**
     * 分页数据
     */
    $(".pagination a").click(function(){
        var href=this.href;
        var data=$("#formID").serialize();
        $(".app-content").empty();
        $.ajax({
            type: "GET",
            data:data,
            url:href,
            success: function(e) {
                $('.app-content').append(e);
            }
        });
        return false;
    });
</script>

<script>
    //提交表单数据
    $("#fangxing").click(function(){
        var id=$(this).attr("data-id");
        $.ajax({
            type:"POST",
            dataType:"json",
            url:"<?php echo url('exshop/sell_dakuanac'); ?>",
            data:{
                match_id:id,
            },
            success:function(data){
                if(data.status==1){
                    layer.msg(data.msg);
                    tiao();
                }else{
                    layer.msg(data.msg);
                }
            },
            error:function(error){}
        });
    });

    //	跳页面
    function tiao() {
        var href = "<?php echo url('exshop/sell_dakuanlist'); ?>";
        $('.app-content').empty();
        $.ajax({
            type: "GET",
            async : false,
            cache : false,
            url:httpHead+href,
            success: function(data) {
                $('.app-content').append(data);
            }
        });
        return false;
    }

    //提交表单数据
    $("#tousuinfo").click(function(){
        var id=$(this).attr("data-id");
        var data = '<input name="tusuid" id="tousuid" type="hidden" value="' + id +'">';
        $('#tusuids').append(data);
    });

    $("#tousu").click(function(){
        var id=$("#tousuid").val();
        var inputtousu=$("#inputtousu").val();
        $.ajax({
            type:"POST",
            dataType:"json",
            url:"<?php echo url('exshop/tousuac'); ?>",
            data:{
                match_id:id,
                inputtousu:inputtousu,
            },
            success:function(data){
                if(data.status==1){
                    layer.msg(data.msg);
                    tiao();
                }else{
                    layer.msg(data.msg);
                }
            },
            error:function(error){}
        });


    });
</script>
<script>
    //提交表单数据
    function  callbackac(id) {
        console.log(id);
        $.ajax({
            type:"post",
            dataType:"json",
            url:"<?php echo url('exshop/callbackac'); ?>",
            data:{
                id:id,
            },
            success:function(data){
                if(data.status==1){
                    layer.msg(data.msg);
                }else{
                    layer.msg(data.msg);
                }
            },
            error:function(error){}
        });
    }
</script>