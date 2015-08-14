$(document).ready(function() {

  // add a number to add your own speed to the slideshow
  $('#slideshow').slideShow();
  
});

// Can copy this to use somewhere else as its own plugin
(function($) {
  $.fn.slideShow = function(options) {
    var settings = $.extend({
      time: 1500,
    }, options);

    var finalTime = settings.time * 2; // Sets how long
    var selector = this; // Save "this" as variable for multiple use
    var timer;
    selector.css('cursor', 'pointer').children('img:not(:first)').hide();
    
    var startSlideshow =  setInterval(function() {
      timer = true
      selector.children('img:first')
        .fadeOut(settings.time, function() {
          $(this).next()
          .fadeIn(settings.time)
          .delay(settings.time)
          .end()
          .appendTo(selector)
        });
    }, finalTime);

    selector.click(function() {
      if (timer) {
        selector.children('img:first').stop().fadeIn(200);
        clearInterval(startSlideshow);
        timer = false;
      } else {
        console.log('2 ' + settings.time);
        selector.slideShow({time: settings.time});
        timer = true;
      }
    });
  }
}(jQuery));