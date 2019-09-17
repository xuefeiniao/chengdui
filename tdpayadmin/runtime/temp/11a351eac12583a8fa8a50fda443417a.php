<?php /*a:1:{s:99:"E:\phpstudy\PHPTutorial\WWW\chengdui\tdpayadmin\application\admincoin\view\Permission\roleList.html";i:1553561274;}*/ ?>

<style>
	body .card .card-header .add-btn{
		position: absolute;
		right:30px;
		top:10px;
		font-size: 14px;
    line-height: 23px;
    padding: 5px 23px;
	width:auto;
	border-radius: 4px;
	height: auto;
	border:0;
	}
</style>
<section class="section">
	<div class="row">
		<div class="col-lg-12">
			<div class="card">
				<div class="card-header">
					<h4>角色管理</h4>
					<button type="submit" class="btn btn-primary mt-1 mb-0 add-btn" id="add">添加角色</button>
				</div>
				<div class="card-body">
					<div class="table-responsive">
					<table id="example" class="table table-striped table-bordered border-t0 text-nowrap w-100" >
						<thead>
							<tr>
								<th class="wd-15p">角色ID</th>
								<th class="wd-10p">角色名</th>
								<th class="wd-25p">描述</th>
								<th class="wd-25p">添加时间</th>
								<th class="wd-25p">操作</th>
							</tr>
						</thead>
						<tbody>
							<?php if(!empty($list)): if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
					                <tr>
										<td><?php echo htmlentities($vo['role_id']); ?></td>
										<td><?php echo htmlentities($vo['role_name']); ?></td>
										<td><?php echo htmlentities($vo['discription']); ?></td>
										<td><?php echo htmlentities($vo['created_time']); ?></td>
										<td><a class="btn btn-action btn-primary" onclick="editRole(<?php echo htmlentities($vo['role_id']); ?>)">编辑</a> <a class="btn btn-action btn-primary" onclick="delRole(<?php echo htmlentities($vo['role_id']); ?>)">删除</a></td>
									</tr>
				                <?php endforeach; endif; else: echo "" ;endif; else: ?>
				            	<tr>
				            		<td>没有数据</td>
				            	</tr>
			                <?php endif; ?>
						</tbody>
					</table>
				</div>
					<?php echo $list; ?>
				</div>
			</div>
		</div>
	</div>
</section>
		
<script type="text/javascript">
	
	// 编辑角色
	function editRole(id)
    {
        $('.app-content').empty();
		$.ajax({
			type: "GET",
			url: '/admincoin/Permission/roleEdit/id/'+id,
			success: function(data) {
				$('.app-content').append(data);
			}
		});
		return false;
    }
    // 删除角色
	function delRole(id)
    {
		$.ajax({
			type: "GET",
			url: '/admincoin/Permission/roleDel/id/'+id,
			success: function(e) {
				if (e.code == 200) {
						layer.msg(e.msg);
						$('.app-content').empty();
						$.ajax({
							type: "GET",
							url: '/admincoin/Permission/roleList',
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
	// 添加管理员
	$('#add').click(function()
		{
			$('.app-content').empty();
			$.ajax({
				type: "GET",
				url: '/admincoin/Permission/roleAdd',
				success: function(data) {
					$('.app-content').append(data);
				}
			});
			return false;
		});

	// 编辑管理员
    function editAdmin(id)
    {
        $('.app-content').empty();
		$.ajax({
			type: "GET",
			url: '/admincoin/Permission/adminEdit/id/'+id,
			success: function(data) {
				$('.app-content').append(data);
			}
		});
		return false;
    }

    // 删除管理员
    function delAdmin(id)
    {
        $('.app-content').empty();
		$.ajax({
			type: "GET",
			url: '/admincoin/Permission/adminDel/id/'+id,
			success: function(e) {
				if (e.code == 200) {
						layer.msg(e.msg);
						$('.app-content').empty();
						$.ajax({
							type: "GET",
							url: '/admincoin/Permission/index',
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
	/*分页*/
	$('.pagination li a').click(function()
	{
		var href = this.href;
		$('.app-content').empty();
		$.ajax({
			type: "GET",
			url: href,
			success: function(data) {
				$('.app-content').append(data);
			}
		});
		return false;
	});
</script>
		

