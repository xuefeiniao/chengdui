<?php /*a:1:{s:91:"E:\phpstudy\PHPTutorial\WWW\chengdui\tdpayadmin\application\admincoin\view\login\index.html";i:1554812684;}*/ ?>
<!DOCTYPE html>
<html lang="en">
	<head>

		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>登录</title>
		
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
		<style>
			.send{
				color:#fff!important;
				padding:2px 5px;
				font-size:12px;
				position: absolute;
				right:5px;
				top:5px;
			}
			/*input[type='number']去掉箭头*/
			input[type='number']::-webkit-outer-spin-button,
			input[type='number']::-webkit-inner-spin-button{
				-webkit-appearance: none !important;
				margin: 0;
			}
			input[type="number"]{-moz-appearance:textfield;}
			
			/*滑块验证样式*/	
			#drag{ 
				position: relative;
				background-color: #e8e8e8;
				width: 294px;
				height: 34px;
				line-height: 34px;
				text-align: center;
			}
			#drag .handler{
				position: absolute;
				top: 0px;
				left: 0px;
				width: 40px;
				height: 34px;
				border: 1px solid #ccc;
				cursor: move;
			}
			.handler_bg{
				background: #fff url("__IMG__/right.png") no-repeat center;
			}
			.handler_ok_bg{
				background: #fff url("__IMG__/dui.png") no-repeat center;
			}
			#drag .drag_bg{
				background-color: #7ac23c;
				height: 34px;
				width: 0px;
			}
			#drag .drag_text{
				position: absolute;
				top: 0px;
				width: 300px;
				-moz-user-select: none;
				-webkit-user-select: none;
				user-select: none;
				-o-user-select:none;
				-ms-user-select:none; 
			}
	
		</style>
	</head>

	<body class="bg-primary">
		<div id="app">
			<section class="section section-2">
                <div class="container">
					<div class="row">
						<div class="single-page single-pageimage construction-bg cover-image " data-image-src="/assets/img/news/img14.jpg">
							<div class="row">
								<div class="col-lg-6">
									<div class="wrapper wrapper2">
										<form id="login" class="card-body" tabindex="500">
											<h3>登录</h3>
											<div class="mobile">
												<input type="mobile" name="mobile" id="phone">
												<label>手机号/邮箱</label>
											</div>								
											<div class="code">
												<input type="number" name="code" id="code">
												<a class="btn btn-primary send">发送验证码</a>
												<label>短信验证码</label>
											</div>	
											<div class="password">
												<input type="password" name="password" id="password">
												<label>密码</label>
											</div>
											<!-- 滑块验证 -->
											<div class="verify-wrap" id="verify-wrap"></div>			
											<div class="submit">
												<a class="btn btn-primary btn-block" onclick="login()">登录</a>
											</div>
											<p class="mb-2">
												<a href=""  class="float-left">回到首页</a>
												<a href="register.html" class="float-right">注册</a>
											</p>
											<p class="text-dark mb-0">
												<a href="forgot.html" class="text-primary ml-1">忘记密码？</a>
											</p>
										</form>										
									</div>
								</div>
								<div class="col-lg-6">
									<div class="log-wrapper text-center">
										<img src="/assets/img/brand/logo-white.png" class="mb-2 mt-4 mt-lg-0 " alt="logo">
										<p>There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure</p>
										<a class="btn btn-primary mt-3" href="#">Read More</a>
									</div>
								</div>
							</div>
						</div>	
					</div>
				</div>
			</section>
		</div>

		<!--Jquery.min js-->
		<script src="/assets/js/jquery.min.js"></script>

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
		
		<script src="/assets/js/moment.min.js"></script>

		<!--mCustomScrollbar js-->
		<script src="/assets/plugins/scroll-bar/jquery.mCustomScrollbar.concat.min.js"></script>

		<!--Sidemenu js-->
		<script src="/assets/plugins/toggle-menu/sidemenu.js"></script>

		<!--Scripts js-->
		<script src="/assets/js/scripts.js"></script>
		<!--drag js-->
		<script src="/assets/js/jq-slideVerify.js"></script>
		<script src="/assets/layer/layer.js"></script>
		<script>
			//获取验证码
			$('.send').on('click', function(){
				timeClock(".send");
				var phone 	 = $('#phone').val();
				var sendData = {
					type:1,
					phone:phone,
				};
				
				$.ajax({
					url:http+'admincoin/login/getcode',
					type:'post',
					data:sendData,
					success:function(data){	
						if(data.code == 200){
							layer.msg(data.msg);
						}else{
							layer.msg(data.msg);
							/*layer.msg(data.msg,{icon:2,shift:5});
							wserrorMsg(data['errorcode']);*/
						}
					},
					error:function(data){
						console.log(data);
					}
				})
			                 	   
			});
			
			//手机验证码倒计时函数
			function timeClock(cls) {					
				var _this = $(cls);
				if(_this.hasClass('disabled')) {						
					return false;
				} else {
					_this.addClass('disabled');
					var i = 59;
					var int = setInterval(clock, 1000);
		
					function clock() {
						_this.text("重新发送"+"(" + i + ")");						
						i--;
						if(i < 0) {
							_this.removeClass('disabled');
							i = 59;
							_this.text("获取验证码");
							clearInterval(int);
						}
					}
					return false;
				}
			}
			
			//滑块验证
			var SlideVerifyPlug = window.slideVerifyPlug;
			var slideVerify = new SlideVerifyPlug('#verify-wrap',{
				wrapWidth:'100%',//设置 容器的宽度 ,不设置的话，会设置成100%，需要自己在外层包层div,设置宽度，这是为了适应方便点；
	            initText:'拖动滑块验证',  //设置  初始的 显示文字
	            sucessText:'验证通过',//设置 验证通过 显示的文字
			});
					
			

			function login(){
				var phone 		= $('#phone').val();
				var password 	= $('#password').val();
				var code 		= $('#code').val();
				if(phone == ""){
		    		layer.msg('请输入手机号/邮箱');
		    		return
	    		}
	    		if(code == ""){
		    		layer.msg('请输入验证码');
		    		return
		    	}
		    	if(password == ""){
		    		layer.msg('请输入密码');
		    		return
		    	}
		    	if(!slideVerify.slideFinishState){
		    		layer.msg('请拖动滑块验证');
		    		return
		    	}
				$.ajax({
		    		type:"post",
		    		url:http+'admincoin/login/login',
		    		data:{
		    			mobile:phone,
		    			password:password,
		    			code:code
		    		},
		    		success:function(data){	
						if (data.code == 200) {
								layer.msg(data.msg, {time:500},function (index) {
			                    layer.close(index);
			                    window.location.href='/admincoin/Index/index';
			                });
						}
						else
						{	
                            slideVerify.resetVerify()
							layer.msg(data.msg);
						}
					},
					error:function(data){
						console.log(data);
					}
				})
		    }		
		</script>

	</body>
</html>