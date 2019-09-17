<?php /*a:7:{s:91:"E:\phpstudy\PHPTutorial\WWW\chengdui\tdpayadmin\application\admincoin\view\Index\index.html";i:1553071600;s:89:"E:\phpstudy\PHPTutorial\WWW\chengdui\tdpayadmin\application\admincoin\view\base\base.html";i:1562222294;s:91:"E:\phpstudy\PHPTutorial\WWW\chengdui\tdpayadmin\application\admincoin\view\base\header.html";i:1562832692;s:88:"E:\phpstudy\PHPTutorial\WWW\chengdui\tdpayadmin\application\admincoin\view\base\msg.html";i:1563532012;s:89:"E:\phpstudy\PHPTutorial\WWW\chengdui\tdpayadmin\application\admincoin\view\base\left.html";i:1564393288;s:90:"E:\phpstudy\PHPTutorial\WWW\chengdui\tdpayadmin\application\admincoin\view\base\right.html";i:1562312926;s:91:"E:\phpstudy\PHPTutorial\WWW\chengdui\tdpayadmin\application\admincoin\view\base\footer.html";i:1562223432;}*/ ?>
<!DOCTYPE html>
<html lang="en">
	<head>

		<meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>B-payer Payment后台管理</title>

		<!--Favicon -->
		<link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon"/>

		<!--Bootstrap.min css-->
		<link rel="stylesheet" href="/assets/plugins/bootstrap/css/bootstrap.min.css">

		<!--Icons css-->
		<link rel="stylesheet" href="/assets/css/icons.css">

		<!--Style css-->
		<link rel="stylesheet" href="/assets/css/style.css">

		<!--mCustomScrollbar css-->
		<link rel="stylesheet" href="/assets/plugins/scroll-bar/jquery.mCustomScrollbar.css">

		<!--Sidemenu css-->
		<link rel="stylesheet" href="/assets/plugins/toggle-menu/sidemenu.css">

		<!--Chartist css-->
		<link rel="stylesheet" href="/assets/plugins/chartist/chartist.css">
		<link rel="stylesheet" href="/assets/plugins/chartist/chartist-plugin-tooltip.css">

		<!--Full calendar css-->
		<link rel="stylesheet" href="/assets/plugins/fullcalendar/stylesheet1.css">
		
		<!--morris css-->
		<link rel="stylesheet" href="/assets/plugins/morris/morris.css">
		<link rel="stylesheet" href="/assets/plugins/toastr/build/toastr.css">
		<style>
            .side-menu li{
              display:block;
            }
			#adminType{
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
			
			/*分页*/
			.pagination {}
			.pagination li {display: inline-block;margin-right: -1px;padding: 5px;border: 1px solid #e2e2e2;min-width: 2em;text-align: center;}
			.pagination li.active {background: #009688;color: #fff;border: 1px solid #009688;}
			.pagination li a {display: block;text-align: center;}
		</style>
		<style>
            /*版本卡样式*/
            .navbar .form-inline .btn{
                border-radius:0.25rem;
                padding-top:2px;
                padding-bottom:2px;
                color:#fff;
                cursor: default;
                border:0;
                padding-left:5px;
                padding-right:5px;
                font-size: 12px;
            }
            .visitor .media-body{
                margin-top:0;
            }
            .send.disabled{
                background-color: #ddd;
                border-color:#ddd;
                color:#666!important;
            }
            .card .knob-chart{
                padding-top:10px;
                padding-bottom:10px;
            }
            section .row:first-child .knob-chart{
                padding-top:3px;
                padding-bottom:3px;
            }
         
            .mt-4, .my-4 {
                margin-top: 0.9em!important;
                margin-bottom: 0.6rem!important;
            }
            
              /*表格背景色*/
            .table-striped tbody tr{
                background-color: #fff!important;
            }
           table.table-bordered.dataTable th,table.table-bordered.dataTable tbody td{
                border-right-width:0;
                border-left-width:0;
                padding-right:5px;
                vertical-align: middle;
            }
           table.dataTable.no-footer{
               border-right-width:0;
               border-left-width:0;
           }
           #hangqing span{
               overflow: hidden;
               text-align: right;
               display: block;
               padding-right: 2px;
           }
            #hangqing span i{
               font-style: normal;
           }
           #hangqing span img{
               display: inline-block;
               width:20px;
               height:20px; 
               margin-top:-2px;
               margin-right:5px;
           }
          
           #hangqing tr td:first-child span{
               text-align: center;
           }
          
           span.wrap b {
                display: block;
                font-weight: 400;
                font-size: 14px;
                float: none;
            }
            span.wrap em {
                color: #9ca9b5;
                font-size: 12px;
            }
            span .rate {
                line-height: 1;
                width: 90px;
                padding: 6px 14px;
                text-align: center;
                border-radius: 2px;
                font-weight: 400;
                display: inline-block;
                background-color: rgba(156,169,181,.1);
            }
            .rate.up {
                color: #0da88b;
                background-color: rgba(13,168,139,.1);
            }
            .rate.down {
                color: #ef5656;
                background-color: rgba(239,86,86,.1);
            }
            #hangqing th{
                text-align:right;
                padding-right:25px;
            }
            .table-bordered td, .table-bordered th{
                vertical-align: middle;
            }
            
          	/*去掉tye=numbe的样式*/
            input[type=number] {
                -moz-appearance:textfield;
            }
            input[type=number]::-webkit-inner-spin-button,
            input[type=number]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            
        </style>
		<script src="/assets/js/jquery.min.js"></script>
	</head>

	<body class="app ">

		<div id="spinner"></div>

		<div id="app">
			<div class="main-wrapper" >
                <nav class="navbar navbar-expand-lg main-navbar">
                    <a class="header-brand" href="index.html">
                        <img src="/assets/img/brand/logo.png" class="header-brand-img" alt="Kharna-Admin  logo">
                    </a>
                    <form class="form-inline mr-auto">
                        <ul class="navbar-nav mr-3">
                            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i
                                            class="ion ion-navicon-round"></i></a></li>
                            <li><a href="#" data-toggle="search" class="nav-link nav-link-lg d-md-none navsearch"><i
                                            class="ion ion-search"></i></a></li>
                        </ul>
                        <div class="search-element">
                            <button type="submit" class="btn btn-primary mt-1 mb-0 add-btn" id="adminType">总后台v1.1
                            </button>
                        </div>
                    </form>
                    <ul class="navbar-nav navbar-right">
                        <li class="dropdown dropdown-list-toggle"><a href="#" data-toggle="dropdown"
                                                                     class="nav-link  nav-link-lg beep"><i
                                        class="ion-ios-bell-outline"></i></a>
                            <div class="dropdown-menu dropdown-list dropdown-menu-right">
                                <div class="dropdown-header">承兑消息提醒
                                    <div class="float-right">
                                        <a href="#">....</a>
                                    </div>
                                </div>
                                <div class="dropdown-list-content">
                                    <a class="dropdown-item" onclick="tiaomsg()" style="cursor: pointer">
                                        <i class="fa fa-comment text-primary"></i>
                                        <div class="dropdown-item-desc">
                                            <b>待处理</b>
                                            <div class="float-right"><span class="badge badge-pill badge-danger badge-sm msgnum">0</span></div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </li>
                        <!--<li class="dropdown dropdown-list-toggle">
                            <a href="#" class="nav-link nav-link-lg full-screen-link">
                                <i class="ion-arrow-expand"  id="fullscreen-button"></i>
                            </a>
                        </li>-->
                        <li class="dropdown"><a href="#" data-toggle="dropdown"
                                                class="nav-link dropdown-toggle nav-link-lg">
                                <img src="/assets/img/avatar/avatar-1.jpeg.jpg" alt="profile-user"
                                     class="rounded-circle w-32">
                                <div class="d-sm-none d-lg-inline-block"><?php echo htmlentities($username); ?></div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a href="<?php echo url('login/loginOut'); ?>" class="dropdown-item has-icon">
                                    <i class="ion-ios-redo"></i> 退出
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
<!--<audio id="audio" controls="controls" autoplay='autoplay' hidden>
    <source src="/assets/msg.mp3" type="audio/mpeg"/>
</audio>-->
<script src="/assets/plugins/toastr/build/toastr.min.js"></script>
<script>
    var playSound = function () {
        var borswer = window.navigator.userAgent.toLowerCase();
        if ( borswer.indexOf( "ie" ) >= 0 )
        {
            //IE内核浏览器
            var strEmbed = '<embed name="embedPlay" src="/assets/msg.mp3" autostart="true" hidden="true" loop="false"/>';
            if ( $( "body" ).find( "embed" ).length <= 0 ) $( "body" ).append( strEmbed );
            var embed = document.embedPlay; //浏览器不支持 audion，则使用 embed 播放 embed.volume = 100;
        } else {
            //非IE内核浏览器
            var strAudio = "<audio id='audioPlay' src='/assets/msg.mp3' hidden='true'>";
            if($("#audioPlay").length<=0){
                $( "body" ).append( strAudio );
            }
            var audio = document.getElementById( "audioPlay" );
            //浏览器支持 audio
            audio.play();
        }
    }
</script>
<script>
    $(function(){
        require();
//        $("#audio").removeAttr("autoplay");
    });

    function require() {
        var url = "<?php echo url('admincoin/exshop/get_exshop_msg'); ?>";
        $.get(url, null, function (data) {
            if(data.status=='ok'){
                $(".msgnum").html(data.data);
                if(data.data!=0){
                    playSound();
                    //$("#audio").attr("autoplay", 'autoplay');
                }
            }else{
                //layer.msg(data.msg);
            }
        });
        setTimeout('require()', 10000);

    }
    function tiaomsg()
    {
        $('.app-content').empty();
        $.ajax({
            type: "GET",
            url: "<?php echo url('admincoin/exshop/msglist',['status'=>0]); ?>",
            success: function(data) {
                $('.app-content').append(data);
            }
        });
        return false;
    }
</script>







<aside class="app-sidebar">
    <div class="app-sidebar__user">
        <div class="dropdown">
            <a class="nav-link pl-2 pr-2 leading-none d-flex" data-toggle="dropdown" href="#">
                <img alt="image" src="/assets/img/avatar/avatar-1.jpeg.jpg" class=" avatar-md rounded-circle">
                <span class="ml-2 d-lg-block">
                    <span class="text-white app-sidebar__user-name mt-5"><?php echo htmlentities($username); ?></span><br>
                    <span class="text-muted app-sidebar__user-name text-sm"> <?php echo htmlentities($phone); ?></span>
                </span>
            </a>
        </div>
    </div>
    <ul class="side-menu">
        <li class="slide">
            <a class="side-menu__item noChild" href="<?php echo url('admincoin/Index/index1'); ?>"><i class="side-menu__icon fa fa-desktop"></i><span class="side-menu__label">管理首页</span></a>
        </li>
        <!-- <li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-flask"></i><span class="side-menu__label">商户设置</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <li><a href="canshu.html" class="slide-item"> 参数设置</a></li>
                <li><a href="userMessage.html" class="slide-item"> 商户信息</a></li>
                <li><a href="changePassword.html" class="slide-item"> 修改密码</a></li>
            </ul>
        </li> -->
        <li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-table"></i><span class="side-menu__label">用户基本管理</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <li><a href="<?php echo url('admincoin/ShopManage/shop'); ?>" class="slide-item">用户管理</a></li>
                <li><a href="<?php echo url('admincoin/ShopManage/shopList'); ?>" class="slide-item">资产变更</a></li>
                <li><a href="<?php echo url('admincoin/ShopManage/shopCheckRecord'); ?>" class="slide-item">登录记录</a></li>
            </ul>
        </li>

        <li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-flask"></i><span class="side-menu__label">商户管理</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <li><a href="<?php echo url('admincoin/ShopManage/shop',['type'=>'shop']); ?>" class="slide-item">商户列表</a></li>
                <li><a href="<?php echo url('admincoin/Shop/coinlog'); ?>" class="slide-item">商户收款记录</a></li>
                <!--<li><a href="<?php echo url('admincoin/OrderManage/rechargeOrder'); ?>" class="slide-item"> 充值订单</a></li>
                <li><a href="<?php echo url('admincoin/OrderManage/withdrawOrder'); ?>" class="slide-item"> 提现订单</a></li>-->
                <!--<li><a href="<?php echo url('admincoin/ShopManage/shop'); ?>" class="slide-item">商户出售订单列表</a></li>-->
                <!--<li><a href="<?php echo url('admincoin/ShopManage/shopChange'); ?>" class="slide-item">变更记录</a></li>-->
            </ul>
        </li>

        <li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-paw"></i><span class="side-menu__label">承兑商管理</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <li><a href="<?php echo url('admincoin/ShopManage/shop',['type'=>'exshop']); ?>" class="slide-item">承兑商列表</a></li>
                <li><a href="<?php echo url('exshop/coinlog'); ?>" class="slide-item">承兑商奖励</a></li>
                <li><a href="<?php echo url('exshop/sell_list'); ?>" class="slide-item">出售广告</a></li>
                <li><a href="<?php echo url('exshop/sell_listac'); ?>" class="slide-item">订单列表</a></li>
                <li><a href="<?php echo url('exshop/msglist'); ?>" class="slide-item">消息提醒</a></li>
                <li><a href="<?php echo url('exshop/tousu'); ?>" class="slide-item">投诉订单</a></li>
                <!--<li><a href="<?php echo url('exshop/buy_list'); ?>" class="slide-item">交易员求购广告</a></li>
                <li><a href="<?php echo url('exshop/buy_listac'); ?>" class="slide-item">交易员求购订单</a></li>-->
            </ul>
        </li>

        <li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-paw"></i><span class="side-menu__label">代理商管理</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <li><a href="<?php echo url('admincoin/ShopManage/shop',['type'=>'agency']); ?>" class="slide-item">代理商列表</a></li>
                <li><a href="<?php echo url('admincoin/AgencyManage/index'); ?>" class="slide-item">代理列表</a></li>
                <li><a href="<?php echo url('admincoin/AgencyManage/shareLog'); ?>" class="slide-item">奖励记录</a></li>
                <!--<li><a href="<?php echo url('admincoin/AgencyManage/withdraw'); ?>" class="slide-item">提币记录</a></li>-->
            </ul>
        </li>

        <li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-tasks"></i><span class="side-menu__label">订单管理</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <li><a href="<?php echo url('admincoin/OrderManage/rechargeOrder'); ?>" class="slide-item"> 充值订单</a></li>
                <li><a href="<?php echo url('admincoin/OrderManage/withdrawOrder'); ?>" class="slide-item"> 提币订单</a></li>
                <li><a href="<?php echo url('admincoin/OrderManage/tixmcny_list'); ?>" class="slide-item"> 提现订单</a></li>
            </ul>
        </li>
        <!-- <li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-paw"></i><span class="side-menu__label">钱包管理</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <li><a href="recharge.html" class="slide-item">在线充币</a></li>
                <li><a href="withdraw.html" class="slide-item">在线提币</a></li>
                <li><a href="change.html" class="slide-item">币种兑换</a></li>
            </ul>
        </li> -->
        <li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-table"></i><span class="side-menu__label">财务管理</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <!--<li><a href="<?php echo url('admincoin/FinanceManage/onlineRecharge'); ?>" class="slide-item">在线充值</a></li>-->
                <!--<li><a href="<?php echo url('admincoin/FinanceManage/fDetail'); ?>" class="slide-item">财务明细</a></li>-->
                <li><a href="<?php echo url('admincoin/FinanceManage/integralDetail'); ?>" class="slide-item">资金明细</a></li>
                <li><a href="<?php echo url('admincoin/exshop/shop_list'); ?>" class="slide-item">订单明细</a></li>
                <!--								<li><a href="<?php echo url('FinanceManage/integral'); ?>" class="slide-item">积分明细</a></li>-->
                <li><a href="<?php echo url('FinanceManage/changedetail'); ?>" class="slide-item">兑换记录</a></li>
                <li><a href="<?php echo url('FinanceManage/clwulog'); ?>" class="slide-item">财务报表</a></li>
            </ul>
        </li>
        <li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-table"></i><span class="side-menu__label">系统设置</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <li><a href="<?php echo url('admincoin/ShopManage/shopCheckRecord'); ?>" class="slide-item">登录记录</a></li>
                <li><a href="<?php echo url('admincoin/SystemSet/systemSet'); ?>" class="slide-item">参数设置</a></li>
                <li><a href="<?php echo url('admincoin/SystemSet/currencySet'); ?>" class="slide-item">币种配置</a></li>
                <li><a href="<?php echo url('admincoin/SystemSet/setexshop'); ?>" class="slide-item">交易设置</a></li>
                <li><a href="<?php echo url('admincoin/SystemSet/set_uifh'); ?>" class="slide-item">系统维护</a></li>
            </ul>
        </li>
        <!--<li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-table"></i><span class="side-menu__label">地址管理</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <li><a href="<?php echo url('admincoin/AddressManage/index'); ?>" class="slide-item">地址列表</a></li>
                <li><a href="<?php echo url('admincoin/AddressManage/set'); ?>" class="slide-item">地址发行</a></li>
                <li><a href="<?php echo url('admincoin/AddressManage/record'); ?>" class="slide-item">分配记录</a></li>
            </ul>
        </li>-->
        <li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-bar-chart"></i><span class="side-menu__label">信息管理</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <li><a href="<?php echo url('admincoin/MessageManage/information'); ?>" class="slide-item">文章列表</a></li>
                <li><a href="<?php echo url('admincoin/MessageManage/class'); ?>" class="slide-item">分类管理</a></li>
            </ul>
        </li>

        <!--<li class="slide">
              <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-cloud"></i><span class="side-menu__label">OTC管理</span><i class="angle fa fa-angle-right"></i></a>
              <ul class="slide-menu">
                  <li><a href="<?php echo url('Otcmanage/userotcorder'); ?>" class="slide-item">订单管理</a></li>
                  <li><a href="<?php echo url('Otcmanage/otcorder'); ?>" class="slide-item">系统订单</a></li>
                  <li><a href="<?php echo url('Otcmanage/order'); ?>" class="slide-item">系统挂单</a></li>
                  <li><a href="<?php echo url('Otcmanage/tousu'); ?>" class="slide-item">投诉订单</a></li>
              </ul>
          </li>-->

        <li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-pie-chart"></i><span class="side-menu__label">权限管理</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <li><a href="<?php echo url('admincoin/Permission/index'); ?>" class="slide-item">管理员管理</a></li>
                <li><a href="<?php echo url('admincoin/Permission/roleList'); ?>" class="slide-item">角色管理</a></li>
                <li><a href="<?php echo url('admincoin/Permission/ffpz'); ?>" class="slide-item">用户管理权限分配</a></li>
<!--                <li><a href="<?php echo url('admincoin/Permission/permissionList'); ?>" class="slide-item">权限管理</a></li>-->
            </ul>
        </li>

        <!--<li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="#"><i class="side-menu__icon fa fa-pie-chart"></i><span class="side-menu__label">API管理</span><i class="angle fa fa-angle-right"></i></a>
            <ul class="slide-menu">
                <li><a href="<?php echo url('ApiManage/apirecord'); ?>" class="slide-item">API记录</a></li>
                <li><a href="<?php echo url('ApiManage/apiblack'); ?>" class="slide-item">API白名单</a></li>
            </ul>
        </li>-->

        <li class="slide">
            <a class="side-menu__item" href="<?php echo url('admincoin/Login/loginout'); ?>"><i class="side-menu__icon icon icon-power"></i><span class="side-menu__label">退出登录</span></i></a>
        </li>
    </ul>
</aside>

<!-- 右侧内容 -->
				<div class="app-content">
					<section class="section">
						<div class="row">
						    <div class="col-xl-3 col-lg-6 col-sm-6 col-md-12">
								<div class="card">
									<div class="card-body knob-chart">
										<div class="row mb-0">
											<div class="col-4">
												<div class="card-icon d-flex justify-content-center">
													  <img src="/assets/img/total.png" alt="cny">
												</div>
											</div>
											<div class="col-8">
												<div class="dash3 text-center">
													<small class="text-muted mt-0">总资产(行情)</small>
													<h2 class="text-dark mb-0" id="tAsset"></h2>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="col-xl-3 col-lg-6 col-sm-6 col-md-12">
								<div class="card">
									<div class="card-body knob-chart">
										<div class="row mb-0">
											<div class="col-4">
											<div class="card-icon d-flex justify-content-center">
											   <img src="/assets/img/total.png" alt="cny">
											</div>
											</div>
											<div class="col-8">
												<div class="dash3 text-center">
													<small class="text-muted mt-0">总资产(平台)</small>
													<h2 class="text-dark mb-0">￥<?php echo htmlentities(round($indexArr['cny_money'],2)); ?></h2>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
                            <div class="col-xl-3 col-lg-6 col-sm-6 col-md-12">
                                <div class="card">
                                    <div class="card-body knob-chart">
                                        <div class="row mb-0">
                                            <div class="col-4">
                                                <div class="card-icon d-flex justify-content-center">
                                                    <img src="/assets/img/usdt.png" alt="BTC">
                                                </div>
                                            </div>
                                            <div class="col-8">
                                                <div class="dash3 text-center">
                                                    <small class="text-muted mt-0">USDT</small>
                                                    <h2 class="text-dark mb-0" id="usdt">$<?php echo htmlentities($indexArr['usdt']); ?></h2>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6 col-sm-6 col-md-12">
                                <div class="card">
                                    <div class="card-body knob-chart">
                                        <div class="row mb-0">
                                            <div class="col-4">
                                                <div class="card-icon d-flex justify-content-center">
                                                    <img src="/assets/img/usdt.png" alt="cny">
                                                </div>
                                            </div>
                                            <div class="col-8">
                                                <div class="dash3 text-center">
                                                    <small class="text-muted mt-0">总收益</small>
                                                    <h2 class="text-dark mb-0">$<?php echo htmlentities(round($indexArr['shouyi'],2)); ?></h2>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
						</div>
						<div class="row">
							<div class="col-lg-6 col-xl-3 col-md-6 col-12">
								<div class="card">
									<div class="card-body knob-chart">
										<div class="text-center">
											<span class="text-muted">总收入</span>
											<h4 class="mt-1">$<?php echo htmlentities(round($indexArr['shopnum'],2)); ?></h4>
											<div class="card-progressbar mb-0 mt-4">
												<div class="progress h-6">
												  <div class="progress-bar bg-success w-80" role="progressbar"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-lg-6 col-xl-3 col-md-6 col-12">
								<div class="card">
									<div class="card-body knob-chart">
										<div class="text-center">
											<span class="text-muted">总提现</span>
											<h4 class="mt-1">$<?php echo htmlentities(round($indexArr['tixian'],2)); ?></h4>
											<div class="card-progressbar mb-0 mt-4">
												<div class="progress h-6" style="height: 6px;">
												  <div class="progress-bar bg-primary w-60" role="progressbar"></div>
												  <input type="hidden" name="ethW" id="ethW" value="">
												  <input type="hidden" name="btcW" id="btcW" value="">
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-lg-6 col-xl-3 col-md-6 col-12">
								<div class="card">
									<div class="card-body knob-chart">
										<div class="text-center">
											<span class="text-muted">今日收入</span>
											<h4 class="mt-1" id="tROrder">$<?php echo htmlentities(round($indexArr['sum_day'],2)); ?></h4>
											<div class="card-progressbar mb-0 mt-4">
												<div class="progress h-6">
												  <div class="progress-bar bg-orange w-70" role="progressbar"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-lg-6 col-xl-3 col-md-6 col-12">
								<div class="card">
									<div class="card-body knob-chart">
										<div class=" text-center">
											<span class="text-muted">今日提现</span>
											<h4 class="mt-1">$<?php echo htmlentities(round($indexArr['tixian_day'],2)); ?></h4>
											<div class="card-progressbar mt-4 mb-0">
												<div class="progress h-6">
												  <div class="progress-bar bg-cyan w-60" role="progressbar"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-12 col-sm-12">
								<div class="card">
									<div class="card-header">
										<h4>火币行情</h4>
									</div>
            						<div class="card-body" style="padding-top: 20px;">
                                        <div class="table-responsive">
            								<table id="hangqing" class="table table-striped table-bordered border-t0 text-nowrap w-100" >
            									<thead>
            										<tr>
            											<th class="wd-15p text-center">交易对</th>
            											<th class="wd-15p">最新价</th>
            											<th class="wd-20p">涨幅</th>
            											<th class="wd-15p">最高价</th>
            											<th class="wd-10p">最低价</th>
            											<th class="wd-25p">24H量</th>
            											<th class="wd-25p">24H成交额</th>
            										</tr>
            									</thead>
            									<tbody>
            										<?php foreach($market as $key=>$vo): ?> 
	            										<tr>
	            											<td>
	            											    <span>
	            											        <img src="/assets/img/<?php echo htmlentities(strtolower($vo)); ?>Small.png" alt="">
	            											        <i><?php echo htmlentities(strtolower($vo)); ?>/USDT</i>
	            											    </span>
	            											</td>
	            											<td>
	            											    <span class="wrap price">
	            											        <b class="close" id="<?php echo htmlentities($vo); ?>Price">4012.06</b>
	            											        <em class="estimated" id="<?php echo htmlentities($vo); ?>ToQc">≈ ¥27282.00</em>
	            											     </span>
	            											</td>
	            											<td>
	            											    <span>
	            											        <b class="rate up" id="<?php echo htmlentities($vo); ?>Increase">+0.45%</b>
	            											    </span>
	            											</td>
	            											<td>
	            											    <span class="wrap high">
	            											        <b class="close" id="<?php echo htmlentities($vo); ?>High">4020.00</b>
	            											        <em class="estimated" id="<?php echo htmlentities($vo); ?>HighToQc">≈ ¥27336.00</em>
	            											    </span>
	            											</td>
	            											<td>
	            											    <span class="wrap low">
	            											        <b class="close" id='<?php echo htmlentities($vo); ?>Low'>3980.00</b>
	            											        <em class="estimated" id="<?php echo htmlentities($vo); ?>LowToQc">≈ ¥27064.00</em>
	            											    </span>
	            											</td>
	            											<td>
	            											    <span class="amount" id="<?php echo htmlentities($vo); ?>Vol">16,860</span>
	            											</td>
	            											<td>
	            											    <span class="vol" id="<?php echo htmlentities($vo); ?>daySum">¥ 457,818,187</span>
	            											</td>
	            										</tr>
            										<?php endforeach; ?>
            									</tbody>
            								</table>
            							</div>
            						</div>
            					</div>
            				</div>
                        </div>
    
						<div class="row">
							<div class="col-lg-8 col-md-12 col-12 col-sm-12">
								<div class="card">
									<div class="card-header">
										<h4>订单统计</h4>
									</div>
									<div class="card-body">
										<div  id="line-chart" class="overflow-hidden"></div>
									</div>
								</div>
							</div>
							<div class="col-lg-4 col-md-12 col-12 col-sm-12">
								<div class="card">
									<div class="card-header">
										<h4>币种统计</h4>
									</div>
									<div class="card-body">
										<div  id="sales-chart" class="overflow-hidden"></div>
									</div>
								</div>
							</div>
						</div>
					
						<div class="row">
							<div class="col-lg-5 col-xl-4 col-sm-12">
								<div class="card">
									<div class="card-header">
										<h4>系统公告</h4>
									</div>
									<div class="card-body">
										<ul class="visitor list-unstyled list-unstyled-border">
											<?php foreach($article as $val): ?>
											<li class="media">
												<img class="mr-3" width="50" src="/assets/img/news/img01.jpg" alt="avatar">
												<div class="media-body">
													<div class="float-right"><small><?php echo htmlentities(date('Y-m-d H:i:s',!is_numeric($val['createtime'])? strtotime($val['createtime']) : $val['createtime'])); ?></small></div>
													<div class="media-title"><?php echo htmlentities($val['title']); ?></div>
												</div>
											</li>
											<?php endforeach; ?>
											<!-- <li class="media">
												<img class="mr-3" width="50" src="/assets/img/news/img01.jpg" alt="avatar">
												<div class="media-body">
													<div class="float-right"><small>15-9-2018</small></div>
													<div class="media-title">Keith Rutherford</div>
													<small>sed do eiusmod tempor incididunt ut labore</small>
												</div>
											</li>
											<li class="media">
                    							<img class="mr-3" width="50" src="/assets/img/news/img01.jpg" alt="avatar">
                    							<div class="media-body">
                    								<div class="float-right"><small>17-9-2018</small></div>
                    								<div class="media-title">Elizabeth Parsons</div>
                    								<small>sed do eiusmod tempor incididunt ut labore</small>
                    							</div>
                    						</li>
											<li class="media">
												<img class="mr-3" width="50" src="/assets/img/news/img01.jpg" alt="avatar">
												<div class="media-body">
													<div class="float-right"><small>17-9-2018</small></div>
													<div class="media-title">Elizabeth Parsons</div>
													<small>sed do eiusmod tempor incididunt ut labore</small>
												</div>
											</li>
											<li class="media border-b0 mb-0 pb-0">
												<img class="mr-3" width="50" src="/assets/img/news/img01.jpg" alt="avatar">
												<div class="media-body">
													<div class="float-right"><small>22-9-2018</small></div>
													<div class="media-title">Nicola Lambert</div>
													<small>sed do eiusmod tempor incididunt ut labore</small>
												</div>
											</li> -->
										
										</ul>
									</div>
								</div>
							</div>
							<div class="col-lg-7 col-xl-8 col-sm-12">								
								<div class="card">
									<div class="card-header">
										<h4>访问区域</h4>
									</div>
									<div class="card-body" style="padding: 14px 30px;">
										<div id="barchart1" class="overflow-hidden h-365"></div>
									</div>
								</div>
							</div>
					
						</div>
					
					</section>
				</div>
				<!-- 右侧内容   end-->
<script>
	// var ws_url = 'wss://api.huobi.pro/ws';
	var ws_url = 'wss://api.zb.cn/websocket';
	var Room = {
		socket: null,
		uid: 0,
		users: [],
		pingInterval: 0,
		init: function() {
			// 创建一个Socket实例
			this.socket = new WebSocket(ws_url);
			// 打开Socket 
			this.socket.onopen = function(event) {
				<?php foreach($market as $val): ?>
					Room.send({
						'event':'addChannel',
						'channel':'<?php echo htmlentities(strtolower($val)); ?>usdt_ticker',
					});
				<?php endforeach; foreach($market as $val): ?>
					Room.send({
						'event':'addChannel',
						'channel':'<?php echo htmlentities(strtolower($val)); ?>qc_ticker',
					});
				<?php endforeach; ?>
				// 监听消息
				this.socket.onmessage = function(event) {
					var data = $.parseJSON(event.data);
					// console.log('接收:', data);
					switch(data.channel) {
						case 'btcusdt_ticker':
							this.btcResult(data.ticker);
							break;
						case 'ethusdt_ticker':
							this.ethResult(data.ticker);
							break;
						case 'eosusdt_ticker':
							this.eosResult(data.ticker);
							break;
						case 'xrpusdt_ticker':
							this.xrpResult(data.ticker);
							break;
						case 'btcqc_ticker':
							this.btcqc_ticker(data.ticker);
							break;
						case 'ethqc_ticker':
							this.ethqc_ticker(data.ticker);
							break;
						case 'eosqc_ticker':
							this.eosqc_ticker(data.ticker);
							break;
						case 'xrpqc_ticker':
							this.xrpqc_ticker(data.ticker);
							break;
					}
				}.bind(this);

				// 监听Socket的关闭
				this.socket.onclose = function(event) {
					if(this.pingInterval) clearInterval(this.pingInterval);
					$('.alert').show();
					console.log('Client notified socket has closed', event);
				}.bind(this);
			}.bind(this);

		},
		send: function(e) {
			console.info('发送:', e);
			this.socket.send(JSON.stringify(e));
		},
		btcResult: function(e) {
			// BTC总数
			$('#btc').text('$'+(e.last * <?php echo htmlentities($indexArr['btc']); ?>).toFixed(2));
			var btcN 	= Number($('#btc').text().split("$").join(""));
			var ethN 	= Number($('#eth').text().split("$").join(""));
			var usdtN 	= Number($('#usdt').text().split("$").join(""));
            var usdt_cny = <?php echo htmlentities($usdt_cny); ?>;
			$('#tAsset').text('￥'+((btcN + ethN + usdtN)*usdt_cny).toFixed(2));
			// btc总提现
			<?php if(array_key_exists('BTC',$indexArr['withdraw'])): ?>
				var btcW = (e.last * <?php echo htmlentities($indexArr['withdraw']['BTC']['tp_sum']); ?>).toFixed(2);
				$('#btcW').val(btcW);
			<?php else: ?>
				var btcW = 0;
				$('#btcW').val(btcW);
			<?php endif; ?>

			var ethW = $('#ethW').val();
			$('#wOrder').text('$'+(Number(btcW) + Number(ethW)).toFixed(2));
			// btc今日提现
			var btcTw;


			<?php if(count($indexArr['Twithdraw'])): ?>
				btcTw = (e.last * <?php echo htmlentities($indexArr['Twithdraw']['BTC']['tp_sum']); ?>).toFixed(2);
			<?php else: ?>
				btcTw = 0;
			<?php endif; ?>
			$('#btcTW').val(btcTw);
			var ethTw = $('#ethTW').val();
			$('#tWOrder').text('$'+(Number(btcTw) + Number(ethTw)).toFixed(2));
			// 行情
			$('#BTCPrice').text(e.last);
			$('#BTCHigh').text(e.high);
			$('#BTCLow').text(e.low);
			$('#BTCVol').text(e.vol);
		},
		ethResult: function(e) {
			// ETH总数
			$('#eth').text('$'+(e.last * <?php echo htmlentities($indexArr['eth']); ?>).toFixed(2));
			var btcN 	= Number($('#btc').text().split("$").join(""));
			var ethN 	= Number($('#eth').text().split("$").join(""));
			var usdtN 	= Number($('#usdt').text().split("$").join(""));
            var usdt_cny = <?php echo htmlentities($usdt_cny); ?>;
			$('#tAsset').text('￥'+((btcN + ethN + usdtN)*usdt_cny).toFixed(2));
			// eth总提现
			<?php if(array_key_exists('ETH',$indexArr['withdraw'])): ?>
				var ethW = (e.last * <?php echo htmlentities($indexArr['withdraw']['ETH']['tp_sum']); ?>).toFixed(2);
				$('#ethW').val(ethW);
			<?php else: ?>
				var ethW = 0;
				$('#ethW').val(ethW);
			<?php endif; ?>
			
			var btcW = $('#btcW').val();
			$('#wOrder').text('$'+(Number(btcW) + Number(ethW)).toFixed(2));
			// eth今日提现
			var ethTw;
			<?php if(count($indexArr['Twithdraw'])): ?>
				ethTw = (e.last * <?php echo htmlentities($indexArr['Twithdraw']['BTC']['tp_sum']); ?>).toFixed(2);
			<?php else: ?>
				ethTw = 0;
			<?php endif; ?>
			
			$('#ethTW').val(ethTw);
			var btcTw = $('#btcTW').val();
			$('#tWOrder').text('$'+(Number(btcTw) + Number(ethTw)).toFixed(2));
			// 行情
			$('#ETHPrice').text(e.last);
			$('#ETHHigh').text(e.high);
			$('#ETHLow').text(e.low);
			$('#ETHVol').text(e.vol);
		},
		eosResult: function(e) {
			// 行情
			$('#EOSPrice').text(e.last);
			$('#EOSHigh').text(e.high);
			$('#EOSLow').text(e.low);
			$('#EOSVol').text(e.vol);
		},
		xrpResult: function(e) {
			// 行情
			$('#XRPPrice').text(e.last);
			$('#XRPHigh').text(e.high);
			$('#XRPLow').text(e.low);
			$('#XRPVol').text(e.vol);
		},
		btcqc_ticker: function(e) {
			// 行情
			$('#BTCToQc').text('≈ ¥'+e.last);
			$('#BTCHighToQc').text('≈ ¥'+e.high);
			$('#BTCLowToQc').text('≈ ¥'+e.low);
			$('#BTCdaySum').text('￥ '+(e.last * e.vol).toFixed(2));
		},
		ethqc_ticker: function(e) {
			// 行情
			$('#ETHToQc').text('≈ ¥'+e.last);
			$('#ETHHighToQc').text('≈ ¥'+e.high);
			$('#ETHLowToQc').text('≈ ¥'+e.low);
			$('#ETHdaySum').text('￥ '+(e.last * e.vol).toFixed(2));
		},
		eosqc_ticker: function(e) {
			// 行情
			$('#EOSToQc').text('≈ ¥'+e.last);
			$('#EOSHighToQc').text('≈ ¥'+e.high);
			$('#EOSLowToQc').text('≈ ¥'+e.low);
			$('#EOSdaySum').text('￥ '+(e.last * e.vol).toFixed(2));
		},
		xrpqc_ticker: function(e) {
			// 行情
			$('#XRPToQc').text('≈ ¥'+e.last);
			$('#XRPHighToQc').text('≈ ¥'+e.high);
			$('#XRPLowToQc').text('≈ ¥'+e.low);
			$('#XRPdaySum').text('￥ '+(e.last * e.vol).toFixed(2));
		},
	};
	Room.init();
</script>

<script type="">
	var data 	= <?php echo $indexArr1; ?>;
	var data1 	= <?php echo $currencyOrder; ?>;
	// var data 	= <?php echo htmlentities(htmlspecialchars_decode($indexArr1)); ?>;
</script>
<script src="/assets/js/dashboard3.js"></script>
				<!-- 右侧内容   end-->

				<footer class="main-footer">
					<div class="text-center">
						Copyright &copy;Kharna-Admin 2018. Design By：<a href="http://www.mycodes.net/" target="_blank">B-payer Payment</a>
					</div>
				</footer>

			</div>
		</div>
		<!--Jquery.min js-->

		<!--popper js-->
		<script src="/assets/js/popper.js"></script>

		<!--Tooltip js-->
		<script src="/assets/js/tooltip.js"></script>

		<!--Bootstrap.min js-->
		<script src="/assets/plugins/bootstrap/js/bootstrap.min.js"></script>

		<!--Jquery.nicescroll.min js-->
		<script src="/assets/plugins/nicescroll/jquery.nicescroll.min.js"></script>

		<!--Scroll-up-bar.min js-->
		<script src="/assets/plugins/scroll-up-bar/dist/scroll-up-bar.min.js"></script>

		<!--Sidemenu js-->
		<script src="/assets/plugins/toggle-menu/sidemenu.js"></script>

		<!--mCustomScrollbar js-->
		<script src="/assets/plugins/scroll-bar/jquery.mCustomScrollbar.concat.min.js"></script>

		<!-- ECharts -->
		<script src="/assets/plugins/echarts/dist/echarts.js"></script>

		<!--Min Calendar -->
		<script src="/assets/plugins/fullcalendar/calendar.min.js"></script>
		<script src="/assets/plugins/fullcalendar/custom_calendar.js"></script>

		<!--Morris js-->
		<script src="/assets/plugins/morris/morris.min.js"></script>
		<script src="/assets/plugins/morris/raphael.min.js"></script>	

		<!--Scripts js-->
		<script src="/assets/js/scripts.js"></script>

		<!--Dashboard js-->
		<script src="/assets/js/apexcharts.js"></script>
		<script src="/assets/layer/layer.js"></script>
		<script src="/assets/plugins/toastr/build/toastr.min.js"></script>
		<script src="/assets/plugins/laydate/laydate.js"></script>
		<script type="text/javascript">
			// 点击子菜单切换
			var srcLi = $(".slide-menu").find('li');
			srcLi.on('click', function(e) {
                var href = $(this).find("a").attr('href');
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
			
			// 点击首页			
			$(".noChild").on('click', function(e) {
				var href = $(this).attr('href');
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
		
	</body>
</html>