(function($) {
	"use strict";
	
	$(document).ready(function() {
		
		/*-----------------------------------------------------------------------------------*/
		/*  Set height of header
		/*-----------------------------------------------------------------------------------*/ 
			function setHeight() {
				var windowHeight = $(window).innerHeight();
				$('.site-header, .site-brand-main').css('height', windowHeight);
			};
			setHeight();
		
		/*-----------------------------------------------------------------------------------*/
		/*  Home icon in main menu
		/*-----------------------------------------------------------------------------------*/ 
			if($('body').hasClass('rtl')) {
				$('.main-navigation .menu-item-home > a').append('<i class="fa fa-home spaceLeft"></i>');
			} else {
				$('.main-navigation .menu-item-home > a').prepend('<i class="fa fa-home spaceRight"></i>');
			}
			
		/*-----------------------------------------------------------------------------------*/
		/*  If Comment Metadata exist or Edit Comments Link exist
		/*-----------------------------------------------------------------------------------*/ 
			if ( $( '.comment-metadata' ).length ) {
				$('.comment-metadata').addClass('smallPart');
			}
			if ( $( '.reply' ).length ) {
				$('.reply').addClass('smallPart');
			}
		
		/*-----------------------------------------------------------------------------------*/
		/*  Manage Sidebar
		/*-----------------------------------------------------------------------------------*/ 
			$('.openSidebar').click(function() {
				$('.widget-area, #page.site, .openSidebar, .openSearch').toggleClass('yesOpen');
			});
		
		/*-----------------------------------------------------------------------------------*/
		/*  Search button
		/*-----------------------------------------------------------------------------------*/ 
			$('.openSearch').click(function() {
				$('#search-full').fadeIn(300);
				if ( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
				} else {
					$("#search-full #search-field").focus();
				}
			});

			$('.closeSearch').click(function() {
				$('#search-full').fadeOut(300);
			});
			
		/*-----------------------------------------------------------------------------------*/
		/*  Change Color Sidebar & Search Button
		/*-----------------------------------------------------------------------------------*/ 
			var $filter = $('.site-header');
			$(window).scroll(function () {
				if ($(window).scrollTop() > $filter.outerHeight() - 50 ) {
					$('.openSidebar, .openSearch').addClass("sidebarColor");
				} else if ($(window).scrollTop() < $filter.outerHeight() + 50 ) {
					$('.openSidebar, .openSearch').removeClass("sidebarColor");
				}
			});
		
		/*-----------------------------------------------------------------------------------*/
		/*  Detect Mobile Browser
		/*-----------------------------------------------------------------------------------*/ 
		if ( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
		} else {
			
			$(window).resize(function() {
				setHeight();
			});
			
			/*-----------------------------------------------------------------------------------*/
			/*  Scroll To Top
			/*-----------------------------------------------------------------------------------*/ 
				$(window).scroll(function(){
					if ($(this).scrollTop() > 700) {
						$('#toTop').fadeIn(300);
					} 
					else {
						$('#toTop').fadeOut(300);
					}
				}); 
				$('#toTop').click(function(){
					$("html, body").animate({ scrollTop: 0 }, 1000);
					return false;
				});
			
		}
		
	});
	
})(jQuery);