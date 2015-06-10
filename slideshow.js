

(function($) {
  $.fn.slideShow = function(options) {
    var settings = $.extend({
      time: 2000,
    }, options);

    var finalTime = settings.time * 2;
    var selector = this.selector;
    var timer;
    $(selector).css('cursor', 'pointer');
    console.log('1 ' + settings.time);
    
    var startSlideshow =  setInterval(function() {
      timer = true
      $(selector).children('img:first')
        .fadeOut(settings.time, function() {
          $(this).next()
          .fadeIn(settings.time)
          .delay(settings.time)
          .end()
          .appendTo(selector)
        });
    }, finalTime);

    $(selector).click(function() {
      if (timer) {
        clearInterval(startSlideshow);
        timer = false;
      } else {
        console.log('2 ' + settings.time);
        $(selector).slideShow({time: settings.time});
        timer = true;
      }
    });
  }
}(jQuery));