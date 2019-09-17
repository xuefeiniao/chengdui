<?php /*a:1:{s:95:"E:\phpstudy\PHPTutorial\WWW\chengdui\tdpayadmin\application\admincoin\view\Permission\ffpz.html";i:1562757908;}*/ ?>
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
        <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h4>管理员用户权限分配（不设置就全部用户权限，设置之后就定向管理用户）</h4>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" id="form">
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="username" id="typeName">管理员账号（用户名）</label>
                            <div class="col-md-9">
                                <input type="text"  name="val" class="form-control" id="val" value="">
                                <p class="text-danger">指定管理员账号用户名</p>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="type" id="type">指定用户类别</label>
                            <div class="col-md-9">
                                <select class="form-control" name="type">
                                    <option value="2">承兑商</option>
                                    <option value="1">商户</option>
                                </select>
                                <p class="text-danger">指定管理员管理哪种类别的用户</p>
                            </div>
                        </div>
                        <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12 text-right">
                            <button type="button" class="btn btn-outline-primary  mt-1 mb-0" id="cancelBtn">取消</button>
                            <button type="button" class="btn btn-primary mt-1 mb-0" id="sub">确定</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class=" mb-0">
                    <div class="clearfix card-body p-3 border-bottom">
                        <div class="pull-left">
                            <h5 class="mb-0">特定管理员列表</h5>
                        </div>

                    </div>
                    <!-- end row -->

                    <div class="card-body pt-4">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table border table-bordered text-nowrap">
                                        <thead>
                                        <tr>
                                            <th class="border-0 text-uppercase  font-weight-bold">ID</th>
                                            <th class="border-0 text-uppercase  font-weight-bold">账号</th>
                                            <th class="border-0 text-uppercase font-weight-bold"> 名称</th>
                                            <th class="border-0 text-uppercase font-weight-bold"> 指定用户类别</th>
                                            <th class="border-0 text-uppercase  font-weight-bold">操作</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach($data as $vo): ?>
                                        <tr>
                                            <td><?php echo htmlentities($vo['id']); ?></td>
                                            <td><?php echo htmlentities($vo['username']); ?></td>
                                            <td><?php echo htmlentities($vo['nickname']); ?></td>
                                            <td><?php $tmp = [1=>'商户',2=>'承兑商'];echo $tmp[$vo['type']];?></td>
                                            <td>
                                                <a class="btn btn-action btn-primary" onclick="ffpz_ac(<?php echo htmlentities($vo['id']); ?>)"">设置</a>
                                                <a class="btn btn-action btn-primary" onclick="ffpz_delac(<?php echo htmlentities($vo['id']); ?>)"">删除</a>
                                            </td>
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
            </div>
        </div>
    </div>
</section>
<script>
    /**
     * 分页数据处理
     */
    $(".pagination a").click(function(){
        var href=this.href;
        var data=$("#formID").serialize();
        $('.app-content').empty();
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
    $("#sub").click(function(){
        var form =new FormData($( "#form")[0] );
        $.ajax({
            type:"POST",
            dataType:"json",
            url:"<?php echo url('admincoin/permission/ffpz'); ?>",
            data:form,
            cache:false,
            contentType: false,
            processData: false,
            success:function(data){
                if(data.status==1){
                    $(".app-content").empty();
                    $.get("<?php echo url('admincoin/permission/ffpz'); ?>",function(data){
                        $(".app-content").append(data);
                    });
                    layer.msg(data.msg);
                }else{
                    layer.msg(data.msg);
                }
            },
            error:function(error){}
        });
    })

    function ffpz_ac(id) {
        $.ajax({
            type: "GET",
            async: false,
            cache: false,
            url:'/admincoin/permission/ffpz_ac?id='+id,
            success: function (data) {
                $(".app-content").empty();
                $('.app-content').append(data);
            }
        });
    }


    function ffpz_delac(id)
    {
        $.ajax({
            type:"POST",
            dataType:"json",
            data: { id: id},
            url:"<?php echo url('admincoin/permission/ffpz_delac'); ?>",
            success: function(e) {
                if(e.status==1){
                    $(".app-content").empty();
                    $.get("<?php echo url('admincoin/permission/ffpz'); ?>",function(data){
                        $(".app-content").append(data);
                    });
                    layer.msg(e.msg);
                }else{
                    layer.msg(e.msg);
                }
            }
        });
        return false;
    }
</script>
