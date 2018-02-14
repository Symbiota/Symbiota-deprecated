/* 
 * Custom Responsive Superfish settings
 */
jQuery(document).ready(function(jQuery){
	var breakpoint = 70; //make 700 to remove on responsive
    var sf = jQuery('.main-navigation');
	console.log(sf);
    if(jQuery(document).width() >= breakpoint){
        sf.superfish({
            delay: 200,
            speed: 'fast'
        });
    }
	
//on small windows remove superfish.
    jQuery(window).resize(function(){
        if(jQuery(document).width() >= breakpoint & !sf.hasClass('sf-js-enabled')){
            sf.superfish({
                delay: 200,
                speed: 'fast'
            });
			console.log("active");
        } else if(jQuery(document).width() < breakpoint) {
            sf.superfish('destroy');
			console.log("inactive");
        }
    });
	
});
  jQuery.fn.extend({
    highlight: function(search, insensitive, hls_class){
      var regex = new RegExp("(<[^>]*>)|(\\b"+ search.replace(/([-.*+?^jQuery{}()|[\]\/\\])/g,"\\jQuery1") +")", insensitive ? "ig" : "g");
      return this.html(this.html().replace(regex, function(a, b, c){
        return (a.charAt(0) == "<") ? a : "<strong class=\""+ hls_class +"\">" + c + "</strong>";
      }));
    }
  });
  jQuery(document).ready(function(jQuery){
    if(typeof(hls_query) != 'undefined'){
      jQuery("#main").highlight(hls_query, 1, "hls");
    }
  });
  
  jQuery("#to-top").click(function() {
    jQuery('html, body').animate(
    { scrollTop: jQuery("#main").offset().top}, // what we are animating
    {
        duration: 1000, // how fast we are animating
        easing: 'easeOutQuint', // the type of easing
        complete: function() { // the callback
            //alert('done');
        }
	}
	);
});



//to top
jQuery(document).ready(function(){
	jQuery('#to-top').click(function() {
	    jQuery('body,html').animate({scrollTop:0},800);
	});	
});

jQuery(window).scroll(function() {
	if(jQuery(this).scrollTop() != 0) {//add to top linds
		jQuery('#to-top').fadeIn();	
	} else {
		jQuery('#to-top').fadeOut();
	}
});

//sticky header
jQuery(document).ready(function(){
	 //console.log(jQuery(window).scrollTop());
	 //console.log(jQuery('.main-navigation').offset().top);
    if ( jQuery(window).scrollTop() >= jQuery('.main-navigation').offset().top  && $(window).width() > 700 ) {
        // top nav has reached the top
		jQuery(".main-navigation").addClass("at-top");
    }
});

jQuery(window).scroll(function() {
	//fix top nav to top when scrolled down past that point
    if ( jQuery(window).scrollTop() >= jQuery('.main-navigation').offset().top  && $(window).width() > 6700 ) {
        // top nav has reached the top
		jQuery(".main-navigation").addClass("at-top");
    }
    if ( jQuery(window).scrollTop() <= jQuery('.content-wrapper').offset().top ) {
        // top nav has reached the top
		jQuery(".main-navigation").removeClass("at-top");
    }
	
});
jQuery(window).resize(function() {
	
});
// as of 1.4.2 the mobile safari reports wrong values on offset()
// http://dev.jquery.com/ticket/6446
// remove once it's fixed
if ( /webkit.*mobile/i.test(navigator.userAgent)) {
  (function($) {
      $.fn.offsetOld = $.fn.offset;
      $.fn.offset = function() {
        var result = this.offsetOld();
        result.top -= window.scrollY;
        result.left -= window.scrollX;
        return result;
      };
  })(jQuery);
}
