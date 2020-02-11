(function($, Drupal, drupalSettings) {
    "use strict";
    function initmatchHeight(context) {
      $(function() {
        $('.equal-match').matchHeight({
            target: $('.equal-match.match'),
        });
        $('.equal').matchHeight({
            byRow: true,
        });
        $('.equal-group').each(function(){
            $(this).find('.equal').matchHeight();
        });
        $('.equal-row article').matchHeight({
            byRow: false,
        });
        $('.equal-group').each(function(){
            $(this).find('.equal-match').matchHeight({
                target: $('.equal-match.match'),
            });
        });
      });
    }
    function initExternalLinks(context) {
        $('a[href*="//"]:not([href*="' + document.location.hostname + '"])', context).attr("target", "_blank").addClass("external");
    }
    function initBootstrapSelect(context) {
        $(".selectpicker", context).selectpicker({});
    }
    function initSlick(context) {
        $('.single').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: true,
            fade: true,
            asNavFor: '.thumbs',
            //adaptiveHeight:true
        });
        $('.thumbs').slick({
            slidesToShow: 5,
            slidesToScroll: 1,
            asNavFor: '.single',
            dots: true,
            centerMode: true,
            focusOnSelect: true
        });
    }
    function initCopy(context) {
        var clipboard = new ClipboardJS('.copy-link');
        clipboard.on('success', function(e) {
            $('.copy-link').addClass("success");
            e.clearSelection();
        });

        clipboard.on('error', function(e) {
            $('.copy-link').addClass("error");
        });
    }
    function initSmoothScroll(context) {
        $('a[href*="#"]:not([href="#"])', context).click(function() {
            if (location.pathname.replace(/^\//, "") === this.pathname.replace(/^\//, "") && location.hostname === this.hostname) {
                var target = $(this.hash);
                target = target.length ? target : $("[name=" + this.hash.slice(1) + "]");
                if (target.length) {
                    $("html, body").animate({
                        scrollTop: target.offset().top
                    }, 1e3);
                    return false;
                }
            }
        });
    }
    function initScrollClass(context) {
      $(window).scroll(function() {    
        var scroll = $(window).scrollTop();

        if (scroll >= 50) {
            $('body').addClass("scroll");
        } else {
            $('body').removeClass("scroll");
        }
      });
    }
    Drupal.behaviors.staffordshire = {
        _isInvokedByDocumentReady: true,
        attach: function(context) {
            if (this._isInvokedByDocumentReady) {  
                $(".nav-toggle").click(function() {
                    $("body").toggleClass("nav-open");
                    $("body").removeClass("search-open");
                });                      
                $('.accordions h4').click(function() {
                  var height = $(this).next('div').prop('scrollHeight');
                  console.log(height);
                  $(this).toggleClass('open');
                  $(this).siblings().removeClass('open');
                  $(this).siblings('div').css('max-height', 0);
                  if ($(this).hasClass('open')) {    
                    $(this).next('div').css('max-height', height);
                  }
                  else {
                     $(this).next('div').css('max-height', 0); 
                  }
                });
                this._isInvokedByDocumentReady = false;
            }
            initCopy(context);
            initScrollClass(context);
            initExternalLinks(context);
            initmatchHeight(context);
            initBootstrapSelect(context);
            initSmoothScroll(context);
            initSlick(context);
            $(".search-toggle").click(function() {
                $("body").toggleClass("search-open");
                $("body").removeClass("nav-open");
            });
            $(".filter-toggle").click(function() {
                $("body").toggleClass("filter-open");
            });    
        }
    };
})(jQuery, Drupal, drupalSettings);;
