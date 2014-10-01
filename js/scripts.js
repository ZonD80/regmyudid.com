/*****************************************************************************
	CONTACT FORM - you can change your notification message here
*****************************************************************************/
   $(document).ready(function(){	
			$("#ajax-contact-form").submit(function() {
				var str = $(this).serialize();		
				$.ajax({
					type: "POST",
					url: "contact_form/contact_process.php",
					data: str,
					success: function(msg) {
						// Message Sent - Show the 'Thank You' message and hide the form
						if(msg == 'OK') {
							result = '<div class="notification_ok">Your message has been sent. Thank you!</div>';
							$("#fields").hide();
						} else {
							result = msg;
						}
						$('#note').html(result);
					}
				});
				return false;
			});															
		});
/*****************************************************************************
	CSS3 ANIMATIONS
*****************************************************************************/
	jQuery('.jumbotron').appear(function() {
		$('.jumbotron').each(function(){
			$(this).addClass("fadeIn");
		});
	});
	jQuery('.hi-icon').appear(function() {
		$('.hi-icon').each(function(){
			$(this).addClass("fadeIn");
		});
	});
	jQuery('.grid').appear(function() {
		$('.grid').each(function(){
			$(this).addClass("slideRight");
		});
	});
	jQuery('.grida').appear(function() {
		$('.grida').each(function(){
			$(this).addClass("fadeIn");
		});
	});
	jQuery('#myCarousel').appear(function() {
		$('#myCarousel').each(function(){
			$(this).addClass("fadeIn");
		});
	});
	
	jQuery('.carousel2').appear(function() {
		$('.carousel2').each(function(){
			$(this).addClass("slideUp");
		});
	});
	jQuery('.pricing').appear(function() {
		$('.pricing').each(function(){
			$(this).addClass("slideRight");
		});
	});
	jQuery('.soon').appear(function() {
		$('.soon').each(function(){
			$(this).addClass("bounce");
		});
	});
	jQuery('#bar-1, #bar-2, #bar-3, #bar-4').appear(function() {
		$('#bar-1, #bar-2, #bar-3, #bar-4').each(function(){
			$(this).addClass("slideUp");
		});
	});
/*****************************************************************************
	ADD YOUR COUNTER NUMBERS HERE
*****************************************************************************/	
	jQuery('#counter-1').appear(function() {
		$('#counter-1').countTo({
			from: 0,
			to: 2009,
			speed: 5000,
			refreshInterval: 50,
			onComplete: function(value) { 
			//console.debug(this); 
			}
			});
		});
	jQuery('#counter-2').appear(function() {
		$('#counter-2').countTo({
			from: 0,
			to: 605372,
			speed: 30000,
			refreshInterval: 50,
			onComplete: function(value) { 
			//console.debug(this); 
			}
			});
		});
	jQuery('#counter-3').appear(function() {
		 $('#counter-3').countTo({
			from: 0,
			to: 4,
			speed: 500,
			refreshInterval: 50,
			onComplete: function(value) { 
			//console.debug(this); 
			}
			});
		});

// carousel quotes speed, tooltip, nav collapde, modal box
jQuery('.carousel2').carousel({ interval: 4000})
$('[data-toggle="tooltip"]').tooltip({ 'placement': 'top' })
jQuery('.navbar .nav > li > a').click(function(){
jQuery('.navbar .in').removeClass('in').addClass('collapse').css('height', '0');
$('.modal').bigmodal('hide');
});

/*****************************************************************************
	GOOGLE MAP - ADD YOUR ADDRESS HERE
******************************************************************************/	
$(window).load(function() {
	$(".google-maps").gmap3({
    marker:{     
address:"23, Mornington Crescent, London",  options:{icon: "img/marker.png"}},
    map:{
      options:{
styles: [ {
stylers: [
{ "visibility": "on" }, { "saturation": -70 }, { "gamma": 1 }]
}],
        zoom: 14,
		scrollwheel: false,
		mapTypeControl: false,
		streetViewControl: false,
		scalControl: false,
		draggable: false}
		}
	});	
});	
/*****************************************************************************
	SLIDER REVOLUTION
******************************************************************************/
$(document).ready(function() {
	if ($.fn.cssOriginal!=undefined)
	$.fn.css = $.fn.cssOriginal;
	$('.fullwidthbanner').revolution(
		{
			delay:9000,
			startwidth:1170,
			startheight:610,
			onHoverStop:"on",	
			navigationType:"none",		
			soloArrowLeftHOffset:0,
			soloArrowLeftVOffset:0,
			soloArrowRightHOffset:0,
			soloArrowRightVOffset:0,
			touchenabled:"on",			
			fullWidth:"on",
			shadow:0					
		});
		
//scrollers
	jQuery('.nav').localScroll(6000);
	jQuery('.scroll').localScroll(6000);
	jQuery('#top').localScroll(6000);

//parallax
	jQuery('.well').parallax("50%", 0.1);
	jQuery('#big_button').parallax("50%", 0.1);
	jQuery('#Section-5 .well').parallax("50%", 0.1);
	
//scrollbar
	jQuery("body").niceScroll({cursorcolor:"#777", cursorborder:"0px", cursorwidth :"8px", zindex:"9999" });
  });

//skill bars
	setTimeout(function(){
	$('.progress .bar').each(function() {
            var me = $(this);
            var perc = me.attr("data-percentage");
			 var current_perc = 0;
			var progress = setInterval(function() {
                if (current_perc>=perc) {
                    clearInterval(progress);
                } else {
                    current_perc +=1;
                    me.css('width', (current_perc)+'%');
                }
				me.text((current_perc)+'%');
			}, 20);
		});
	},300);
	$('.bar-percentage[data-percentage]').each(function () {
  var progress = $(this);
  var percentage = Math.ceil($(this).attr('data-percentage'));
  $({countNum: 0}).animate({countNum: percentage}, {
    duration: 9000,
    easing:'linear',
    step: function() {
      // What todo on every count
      var pct = Math.floor(this.countNum) + '%';
      progress.text(pct) && progress.siblings().children().css('width',pct); }
		});
	});	
	
//ticker
(function(a){a.fn.airport=function(g,n){var b=a.extend({transition_speed:1000,loop:true,fill_space:false,colors:null},n),m=a(this),j=["a","b","c","d","e","f","g"," ","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","-","1","2","3","4","5","6","7","8","9","0"],h,c,d=0,i=g.length,f=g.length;function e(p,o){return p+new Array(o-p.length+1).join(" ")}m.empty();while(i--){if(g[i].length>d){d=g[i].length}}while(f--){g[f]=e(g[f],d)}h=d;while(h--){var k=document.createElement("span");k.className="c"+h;m.prepend(k)}if(b.colors){c=b.colors.replace(/\s+/g,"").split(",")}function l(x,w,v,u){var q=m.find(".c"+x),r=g[v]?g[v].substring(u,u+1):null,p,s,o=g[v]?a.trim(g[v]).length:null,t=g[v-1]?a.trim(g[v-1]).length:a.trim(g[0]).length;if(v>=g.length){if(!b.loop){clearTimeout(p);return}p=setTimeout(function(){l(0,0,0,0)},10)}else{if(u>=d){p=setTimeout(function(){if(b.colors){s=c[~~(Math.random()*c.length)];m.css("color",s.substring(0,1)==="#"?s:"#"+s)}l(0,0,v+1,0)},b.transition_speed)}else{q.html((j[w]===" ")?"&nbsp;":j[w]);p=setTimeout(function(){if(w>j.length){l(x+1,0,v,u+1)}else{if(j[w]!==r.toLowerCase()){l(x,w+1,v,u)}else{q.html((r===" "&&b.fill_space)?"&nbsp;":r);if(o<t){if(x>o){for(x;x<t;x++){m.find(".c"+x).html("")}u=d}}l(x+1,0,v,u+1)}}},10)}}}l(0,0,0,0)}})(jQuery);

if( navigator.userAgent.match(/Android/i) || 
	navigator.userAgent.match(/webOS/i) ||
	navigator.userAgent.match(/iPhone/i) || 
	navigator.userAgent.match(/iPad/i)|| 
	navigator.userAgent.match(/iPod/i) || 
	navigator.userAgent.match(/BlackBerry/i)){
			$('.parallax').addClass('mobile');
		}
	

