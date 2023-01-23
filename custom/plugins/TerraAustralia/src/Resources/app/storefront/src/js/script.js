/* eslint-disable */

$(document).ready(function () {
    if ($('.js-banner-slider').length) {
        $('.js-banner-slider').slick({
            dots: true,
            infinite: true,
            arrows: false,
            equalizeHeight: true,
        })
    }
    if ($('.js-brands-slider').length) {
        $('.js-brands-slider').slick({
            dots: true,
            infinite: true,
            arrows: false,
            slidesToShow: 5,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 2000,
            responsive: [
                {
                    breakpoint: 1023,
                    settings: {
                        slidesToShow: 4
                    }
                },
                {
                    breakpoint: 599,
                    settings: {
                        slidesToShow: 3
                    }
                }
            ]
        })
    }
    if ($('.js-category-slider').length) {
        $('.js-category-slider').slick({
            dots: true,
            infinite: true,
            arrows: false,
            slidesToShow: 4,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 991,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 599,
                    settings: {
                        slidesToShow: 1.5,
                        infinite: false
                    }
                }
            ]
        })
    }
    if ($('.js-listing-category-slider').length) {
        $('.js-listing-category-slider').slick({
            dots: true,
            infinite: true,
            arrows: false,
            slidesToShow: 3,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 599,
                    settings: {
                        slidesToShow: 1.5,
                        infinite: false
                    }
                },
                {
                    breakpoint: 450,
                    settings: {
                        slidesToShow: 1.2,
                        infinite: false
                    }
                }
            ]
        })
    }

    if ($('.js-products-slider').length) {
        $('.js-products-slider').each(function() {
            var self = $(this);
            self.slick({
                dots: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                prevArrow: self.closest('.tr-products-slider-wrap').find('.tr-slider-prev'),
                nextArrow: self.closest('.tr-products-slider-wrap').find('.tr-slider-next'),
                responsive: [
                    {
                        breakpoint: 1200,
                        settings: {
                            slidesToShow: 3,
                        }
                    },
                    {
                        breakpoint: 992,
                        settings: {
                            slidesToShow: 2,
                        }
                    },
                    {
                        breakpoint: 460,
                        settings: {
                            slidesToShow: 1,
                        }
                    }
                ]
            });
        });
    }

    if ($('.js-products-slider-row').length) {
        $('.js-products-slider-row').each(function() {
            var self = $(this);
            self.slick({
                dots: true,
                slidesToShow: 2,
                slidesToScroll: 1,
                prevArrow: self.closest('.tr-products-slider-wrap').find('.tr-slider-prev'),
                nextArrow: self.closest('.tr-products-slider-wrap').find('.tr-slider-next'),
                responsive: [
                    {
                        breakpoint: 1200,
                        settings: {
                            slidesToShow: 3,
                        }
                    },
                    {
                        breakpoint: 992,
                        settings: {
                            slidesToShow: 2,
                        }
                    },
                    {
                        breakpoint: 460,
                        settings: {
                            slidesToShow: 1,
                        }
                    }
                ]
            });
        });
    }

    if ($('.navigation-flyout-col').length) {
        $('.navigation-flyout').each(function(i,e) {
            $(e).find('.navigation-flyout-col').first().addClass('active');
        });
        if (/iphone|ipad|ipod|android|blackberry|mini|windows\sce|palm/i.test(navigator.userAgent.toLowerCase())) {
            $(document).on('click', '.navigation-flyout-link', function (e) {
                e.preventDefault();
                $(this).closest('.navigation-flyout').each(function(i,e) {
                    $(e).find('.navigation-flyout-col').removeClass('active');
                });
                $(this).closest('.navigation-flyout-col').addClass('active');
            });
        } else {
            $(document).on('mouseenter', '.navigation-flyout-col', function () {
                $('.navigation-flyout-col').removeClass('active');
                $(this).addClass('active');
            });
            $(document).on('mouseleave', '.navigation-flyout-col', function () {
                $(this).removeClass('active');
                $('.navigation-flyout').each(function(i,e) {
                    $(e).find('.navigation-flyout-col').first().addClass('active');
                });
            })
        }
    }

    if ($('.js-tr-minus').length) {
        $(document).on('click', '.js-tr-minus', function () {
            var $input = $(this).closest('.tr-quantity-product').find('.js-tr-counter-input');
            var count = +$input.val() - 1;

            if (count >= $input.attr('min')) {
                $input.val(count);
                $(this).removeAttr("disabled");
            } else {
                $(this).attr('disabled', 'disabled');
            }

            $input.change();
            return false;
        });
    }

    if ($('.js-tr-plus').length) {
        $(document).on('click', '.js-tr-plus', function () {
            var $input = $(this).closest('.tr-quantity-product').find('.js-tr-counter-input');
            var count = +$input.val() + 1;
            $('.js-tr-minus').removeAttr("disabled");
            $input.val(count);
            $input.change();
            return false;
        });
    }

    if ($('.js-tr-counter-input').length) {
        $('body').on('change', ".js-tr-counter-input", function () {
            var $in = $(this);
            var min = $in.attr('min');
            var max = $in.attr('max');
            var step = 1;


            if ($in) {
                var val = parseInt($in.val(), 10);

                if (val > max) {
                    $in.val(max);
                } else
                if (val < min || $in.val().length === 0) {
                    $in.val(min);
                }

                if ((val - min) % step != 0 && val > min) {
                    $in.val(val - (val - min) % step);
                }
            }
        });
    }

    if ($('.js-show-more-info').length) {

        function checkHeight() {
            var elementText = $('.tr-cms-element-content-text');
            var height = elementText.height();

            if (height < 215) {
                $(elementText).closest('.cms-element-text').find('.js-show-more-info').css('display', 'none');
            } else {
                $(elementText).closest('.cms-element-text').find('.js-show-more-info').css('display', 'inline-block');
            }
        }
        checkHeight();

        $(document).on('click', '.js-show-more-info', function () {
            var heightBlock = $(this).closest('.cms-element-text');

            if (heightBlock.hasClass('show-all-text')) {
                heightBlock.removeClass('show-all-text');
            } else {
                heightBlock.addClass('show-all-text');
            }
        });

    }
});
