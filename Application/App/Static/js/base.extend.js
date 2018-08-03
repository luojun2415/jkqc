/*****************************************************
页面扩展插件：5、感受亚朵
******************************************************/
(function ($) {
    "use strict";
    jQuery.fn.experience = function () {
        var PLAYER_TIME = 3500;
        window.onerror = function () {
            return true
        };
        var Slider = function () {
            var DURATION = 500,
            count = $('#slider li').length,
            current = 0, animating = false, timer;
            function slideTo(idx) {
                if (animating || idx == current) {
                    return;
                }
                if (idx < 0) {
                    idx = count - 1;
                } else if (idx >= count) {
                    idx = 0;
                }
                var up = (idx > current) || (idx == current - count + 1);
                var $lis = $('#slider li');
                var $current = $lis.eq(idx);
                var $prev = $('#slider li.current');
                var offset = $current.height();
                animating = true;

                $current.show().css({
                    top: up ? offset : -offset
                }).animate({
                    top: 0
                }, DURATION, function () {
                    $(this).addClass('current');
                    $('#nav li').removeClass('current').eq(idx).addClass('current');
                    animating = false;
                });
                $prev.animate({
                    top: up ? -offset : offset
                }, DURATION, function () {
                    $(this).removeClass('current').hide();
                });
                var $arrow = $('.arrow');
                if (idx == count - 1) {
                    $arrow.hide();
                } else {
                    $arrow.show();
                }
                current = idx;
                child_animating(idx);
            }
            return {
                slideTo: slideTo,
                slidePrev: function () {
                    if (current == 0) return;
                    slideTo(current - 1);
                },
                slideNext: function () {
                    slideTo(current + 1);
                }
            }
        }();
        //bind event
        $(document).on('mousewheel', function (e) {
            if (e.originalEvent.wheelDelta > 0) {
                Slider.slidePrev();
            } else {
                Slider.slideNext();
            }
        });
        $('.arrow').on('click', function () {
            Slider.slideNext();
        });
        $('#nav a').on('mousedown', function () {
            Slider.slideTo($(this).parent().index());
        });
        setInterval(function () {
            $('.arrow').animate({
                opacity: 1
            }, 800, function () {
                $('.arrow').css({ opacity: 0.4 });
            });
        }, 800);
        $(".current_info div").css({
            opacity: 0
        });
        $(".cr_bg").css({
            opacity: 0.6
        });
        $(".current_info").find("span").css({
            opacity: 0
        });
        function child_animating(_eq) {
            if (_eq == 0) {
                if (!$(".cr_bg").eq(_eq).hasClass("exec")) {
                    $(".current_info .cri_01").eq(0).css({ opacity: 0, "margin-top": "-100px" });
                    $(".current_info .cri_02").css({ opacity: 0, "margin-left": "-100px" });
                    $(".current_info .cri_03").css({ opacity: 0, "height": "0px" });
                    $(".current_info .cri_01").eq(0).animate({ opacity: 1, "margin-top": "0px" }, 800, function () {
                        $(".current_info .cri_02").animate({ opacity: 1, "margin-left": "0px" }, 800, function () {
                            $(".current_info .cri_03").animate({ opacity: 1, "height": "100px" }, 1200, function () {
                                $(".cr_bg").eq(0).animate({ opacity: 0.5 }).addClass("exec");
                            });
                        });
                    });
                }
            } else {
                $(".current_info").eq(_eq).find("div").animate({
                    opacity: 1
                }, 800);
                if (!$(".cr_bg").eq(_eq).hasClass("exec")) {
                    var _spans = $(".current_info").eq(_eq).find("div").find("span");
                    _spans.eq(0).animate({
                        opacity: 1
                    }, 800, function () {
                        1 < _spans.length ? _spans.eq(1).animate({ opacity: 1 }, 700, function () {
                            2 < _spans.length ? _spans.eq(2).animate({ opacity: 1 }, 700, function () {
                                3 < _spans.length ? _spans.eq(3).animate({ opacity: 1 }, 700, function () {
                                    4 < _spans.length ? _spans.eq(4).animate({ opacity: 1 }, 700, function () {
                                        5 < _spans.length ? _spans.eq(5).animate({ opacity: 1 }, 700, function () {
                                            6 < _spans.length ? _spans.eq(6).animate({ opacity: 1 }, 700, function () { }) : $(".cr_bg").eq(_eq).animate({ opacity: 0 }).addClass("exec");
                                        }) : $(".cr_bg").eq(_eq).animate({ opacity: 0.5 }).addClass("exec");
                                    }) : $(".cr_bg").eq(_eq).animate({ opacity: 0.5 }).addClass("exec");
                                }) : $(".cr_bg").eq(_eq).animate({ opacity: 0.5 }).addClass("exec");
                            }) : $(".cr_bg").eq(_eq).animate({ opacity: 0.5 }).addClass("exec");
                        }) : $(".cr_bg").eq(_eq).animate({ opacity: 0.5 }).addClass("exec");
                    });
                }
            }
        }

        child_animating(0);
        $("#cri_href_01").bind("click", function () {
            Slider.slideTo(5);
        });
        $("#cri_href_02").bind("click", function () {
            window.location.href = "/Experience/Products";
        });
    }
})(jQuery);

$(document).ready(function () {
    /********************** 感受亚朵 ***********************/
    $(".experience").experience();
    /********************** 感受亚朵 ***********************/
});