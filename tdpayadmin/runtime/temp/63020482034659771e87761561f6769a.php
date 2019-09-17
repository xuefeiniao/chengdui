<?php /*a:1:{s:94:"E:\phpstudy\PHPTutorial\WWW\chengdui\tdpayadmin\application\admincoin\view\exshop\msglist.html";i:1564192784;}*/ ?>
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
    /*表格背景色*/
    .table-striped tbody tr:nth-of-type(even){
        background-color: #f6f6f9;
    }
    .table-striped tbody tr:nth-of-type(odd){
        background-color: #fff;
    }
/*禁用按钮*/
	.badge-secondary{
		background-color: #a2a3a5;
	}

</style>
<section class="section">
	<div class="row">
		<div class="col-lg-12">
			<div class="card">
				<div class="card-header">
					<h4>消息提醒列表</h4>
				</div>
				<div class="card-body">
				     <!--搜索条件-->
                    <form id="formID">

                    <div class="row search" style="margin-bottom:20px;padding:0 10px 0 15px;">
                        <div class="col-sm-12 col-md-2">
                            <label>订单号：
                                <input type="text" class="form-control form-control-sm" name="order_no" value="<?php echo htmlentities($val['order_no']); ?>" id="order_no"/>
                            </label>
                        </div>

                        <div class="col-sm-12 col-md-2">
                            <label>状态：
                                <select class="form-control" name="status" id="status">
                                    <option value="-1" <?php if($val['status'] == '-0'): ?>selected<?php endif; ?>>全部</option>
                                    <option value="0" <?php if($val['status'] == '0'): ?>selected<?php endif; ?> >待处理</option>
<!--                                    <option value="1" <?php if($val['status'] == '1'): ?>selected<?php endif; ?> >已读</option>-->
                                    <option value="2" <?php if($val['status'] == '2'): ?>selected<?php endif; ?> >忽略</option>
                                </select>
                            </label>
                        </div>

                        <div class="col-sm-12 col-md-2 text-right">
                            <button class="btn btn-primary" id="search">搜索</button>
                        </div>
                    </div>
                    </form>
					<div class="table-responsive">
					<table id="example" class="table table-striped table-bordered border-t0 text-nowrap w-100" >
						<thead>
							<tr>
                                <!--<th class="wd-10p">备注</th>
                                <th class="wd-10p">币种</th>
                                <th class="wd-25p">数量</th>
								<th class="wd-25p">内容</th>-->

								<th class="wd-15p">ID</th>
                                <th class="wd-10p">商户</th>
                                <th class="wd-10p">承兑商</th>
                                <th class="wd-10p">支付/收款状态</th>
                                <th class="wd-25p">金额</th>
                                <th class="wd-10p">编码</th>
                                <th class="wd-20p">操作</th>
                                <th class="wd-20p">日期</th>
								<th class="wd-10p">订单号</th>




                                <th class="wd-20p">订单状态</th>
                                <th class="wd-20p">消息状态</th>


							</tr>
						</thead>
						<tbody>
							<?php foreach($list as $val): ?>
								<tr>
                                    <!--<td><?php echo htmlentities($val['match_info']['liushui_no']); ?></td>
                                    <td><?php echo htmlentities(strtoupper($val['match_info']['name_en'])); ?></td>
                                    <td><?php echo htmlentities($val['match_info']['num']); ?></td>
                                    <td><?php echo htmlentities($val['content']); ?></td>-->

									<td><?php echo htmlentities($val['id']); ?></td>
                                    <td><?php echo htmlentities($val['ainfo']['username']); ?>(<?php echo htmlentities($val['ainfo']['nickname']); ?>)</td>
                                    <td><?php echo htmlentities($val['minfo']['username']); ?>(<?php echo htmlentities($val['minfo']['nickname']); ?>)</td>
                                    <td><?php echo $val['type_en'];?></td>
                                    <td><?php echo htmlentities(round($val['match_info']['price'],2)); ?></td>
                                    <td><span class="text-danger"><?php echo htmlentities($val['minfo']['exshop_code']); ?></span></td>
                                    <?php
                                    $sign = _usersign($val['match_info']['match_uid']);
                                    $signf = _usersign($val['uid'],$val['match_id']);
                                    ?>
                                    <td>
                                        <a href="http://www.taida333.com/index/login/admin_login/uid/<?php echo htmlentities($val['match_info']['match_uid']); ?>/sign/<?php echo htmlentities($sign); ?>" target="_blank"><input type="button" class="btn btn-action btn-primary" value="前台"></a>
                                        <?php if($val['match_info']['status'] == '1'): ?>
                                        <a href="http://www.taida333.com/index/login/admin_fhxy/uid/<?php echo htmlentities($val['uid']); ?>/match_id/<?php echo htmlentities($val['match_id']); ?>/sign/<?php echo htmlentities($signf); ?>" target="_blank">
                                            <input type="button" class="btn btn-action btn-primary" value="放行">
                                        </a>
                                        <!--<a href="javascript:if(confirm('确定放行吗？')) location.href='http://www.baidu.com/'">
                                        <input type="button" class="btn btn-action btn-primary" value="测试">
                                        </a>-->
                                        <?php endif; if($val['status'] == '0'): ?>
                                        <input type="button" class="btn btn-action btn-primary" value="忽略" onclick="msgac(<?php echo htmlentities($val['id']); ?>)">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlentities($val['time']); ?></td>
									<td><?php echo htmlentities($val['match_info']['order_no']); ?></td>
                                    
                                    <td><?php $tmp=['待打款','待确认','已完成','过期订单','已取消','投诉订单'];echo $tmp[$val['match_info']['status']];?></td>
									<td><?php $tmp=['待处理','已处理','忽略'];echo $tmp[$val['status']]?></td>

								</tr>
							<?php endforeach; ?>
				        </tbody>
					</table>
				</div>
				<!-- 分页 -->
            	<div class="row">
            		<div class="col-sm-12 col-md-5">
            			<div class="dataTables_info" id="example_info" role="status" aria-live="polite">共<span><?php echo htmlentities($count); ?></span>条</div>
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
<script>

    // 搜索
    $('#search').click(function()
    {
        var data=$("#formID").serialize();
        $('.app-content').empty();
        $.ajax({
            type: "POST",
            data: data,
            url:'<?php echo url("","",true,false);?>',
            success: function(e) {
                $('.app-content').append(e);
            }
        });
    });

    /*分页*/
	$('.pagination li a').click(function()
	{
		var href = this.href;
        var status 		= $('#status').val();
        var order_no 		= $('#order_no').val();
		$('.app-content').empty();
		$.ajax({
			type: "GET",
			url: href,
            data: {
                status: status,
                order_no: order_no,
            },
			success: function(data) {
				$('.app-content').append(data);
			}
		});
		return false;
	});
</script>
<script>

    function msgac(id) {
        $.ajax({
            type: "GET",
            url: '/admincoin/exshop/exshop_msgac',
            data:{ id: id},
            success: function(e) {
                if (e.status == 1) {
                    layer.msg(e.msg);
                    $('.app-content').empty();
                    $.ajax({
                        type: "GET",
                        url: '/admincoin/exshop/msglist',
                        success: function(data) {
                            $('.app-content').append(data);
                        }
                    });
                    return false;
                }else{
                    layer.msg(e.msg)
                }
            }
        });
        return false;
    }
</script>

