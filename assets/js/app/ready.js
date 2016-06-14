/**
* Executes the ready function
*
**/
$(document).ready( function() {
  $('.collapsible').collapsible();

  $('.button-collapse').sideNav({
    menuWidth: 240,
    edge: 'left',
    closeOnClick: false
  });

  $('select').material_select();

  $('.modal-trigger').leanModal({
    dismissible: true,
    opacity: 0.5,
    in_duration: 300,
    out_duration: 200,
  });
  // materialize tabs
  $('ul.tabs').tabs();

  if (!window.record.canRecord()) {
    $("#post-or, #post-record").hide();
    $("#post-upload").css('float', 'none');
    var value = readCookie('no_record_support');
    if (null !== value && '' !== value) {
      return;
    }
    $("#no-record-support").show();
    document.cookie = 'no_record_support=1';
  }
  
  // google analytics
  (function(i,s,o,g,r,a,m) {
    i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){ (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m);
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
  ga('create', 'UA-69290712-1', 'auto');
  ga('send', 'pageview');
  
  // transform the dates
  updateDates();
  window.setInterval(updateDates, 20000);

  callOnLoadFunctions();
});