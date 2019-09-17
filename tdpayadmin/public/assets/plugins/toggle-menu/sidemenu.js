(function () {
	"use strict";

	var slideMenu = $('.side-menu');
	$('.app').addClass('sidebar-mini');
	// $('.app').addClass('sidenav-toggled');
  
  //	判断是否为手机端
	if ((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))) {
       $('.app').addClass('sidenav-toggled');
        $(".slide-item").click(function(){
       	 $('.app').addClass('sidenav-toggled');
       })
       $(".noChild").click(function(){
       	 $('.app').addClass('sidenav-toggled');
       })
    }
	
	// Toggle Sidebar
	$(document).on("click", "[data-toggle='sidebar']", function(event) {
		event.preventDefault();
		$('.app').toggleClass('sidenav-toggled');
	});
	 
	if ( $(window).width() > 739) {     
		$('.app-sidebar').on("mouseover", function(event) {
			event.preventDefault();
			$('.app').removeClass('sidenav-toggled');
		});
	}
	
	// Activate sidebar slide toggle
	$(document).on("click", "[data-toggle='slide']", function(event) {
		event.preventDefault();
		if(!$(this).parent().hasClass('is-expanded')) {
			slideMenu.find("[data-toggle='slide']").parent().removeClass('is-expanded');
		}
		$(this).parent().toggleClass('is-expanded');
	});

	// Set initial active toggle
	$("[data-toggle='slide.'].is-expanded").parent().toggleClass('is-expanded');

	//Activate bootstrip tooltips
	$("[data-toggle='tooltip']").tooltip();

})();
