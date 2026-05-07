"use strict";

// animation lazy loading images

document.addEventListener('DOMContentLoaded', () => {
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');

    lazyImages.forEach(img => {
        const parent = img.parentElement;

        function markAsLoaded() {
            img.classList.add('is-loaded');
            if (parent) {
                parent.classList.add('is-ready');
            }
        }

        if (img.complete) {
            markAsLoaded();
        } else {
            img.addEventListener('load', markAsLoaded);
            img.addEventListener('error', markAsLoaded);
        }
    });
});

$(function () {

    //  init Fancybox
    if (typeof Fancybox !== "undefined" && Fancybox !== null) {
        Fancybox.bind("[data-fancybox]", {
            dragToClose: false,
        });
    }

    // detect user OS
    const isMobile = {
        Android: () => /Android/i.test(navigator.userAgent),
        BlackBerry: () => /BlackBerry/i.test(navigator.userAgent),
        iOS: () => /iPhone|iPad|iPod/i.test(navigator.userAgent),
        Opera: () => /Opera Mini/i.test(navigator.userAgent),
        Windows: () => /IEMobile/i.test(navigator.userAgent),
        any: function () {
            return this.Android() || this.BlackBerry() || this.iOS() || this.Opera() || this.Windows();
        },
    };

    function getNavigator() {
        if (isMobile.any() || $(window).width() < 992) {
            $('body').removeClass('_pc').addClass('_touch');
        } else {
            $('body').removeClass('_touch').addClass('_pc');
        }
    }

    getNavigator();

    $(window).on('resize', () => {
        clearTimeout(window.resizeTimer);
        window.resizeTimer = setTimeout(() => {
            getNavigator();
        }, 100);
    });

    // event handlers
    $(document).on('click', (e) => {
        const $target = $(e.target);

    });



    // sliders
    class MobileSwiper {
        constructor(sliderName, options, condition = 991.98) {
            this.$slider = $(sliderName);
            this.options = options;
            this.init = false;
            this.swiper = null;
            this.condition = condition;

            if (this.$slider.length) {
                this.handleResize();
                $(window).on("resize", () => this.handleResize());
            }
        }

        handleResize() {
            if (window.innerWidth <= this.condition) {
                if (!this.init) {
                    this.init = true;
                    this.swiper = new Swiper(this.$slider[0], this.options);
                }
            } else if (this.init) {
                this.swiper.destroy();
                this.swiper = null;
                this.init = false;
            }
        }
    }

    if ($('.hero__slider').length) {
        const heroSlider = new Swiper('.hero__slider', {
            speed: 400,
            slidesPerView: 1,
            spaceBetween: 12,
            loop: true,
            pagination: {
                el: '.hero__slider-pagination',
                clickable: true,
            },

            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },
            breakpoints: {
                900: {
                    slidesPerView: 2,
                },
                1100: {
                    slidesPerView: 3,
                }
            }

        });
    }



    // Phone Input Mask Russia

    const $phoneInputs = $('input[type="tel"]');

    const getInputNumbersValue = (input) => {
        return input.value.replace(/\D/g, '');
    };

    const onPhoneInput = function (e) {
        let input = e.target,
            inputNumbersValue = getInputNumbersValue(input),
            selectionStart = input.selectionStart,
            formattedInputValue = "";

        if (!inputNumbersValue) {
            return input.value = "";
        }

        if (input.value.length != selectionStart) {
            if (e.originalEvent.data && /\D/g.test(e.originalEvent.data)) {
                input.value = inputNumbersValue;
            }
            return;
        }

        if (["7", "8", "9"].indexOf(inputNumbersValue[0]) > -1) {
            if (inputNumbersValue[0] == "9") inputNumbersValue = "7" + inputNumbersValue;
            let firstSymbols = (inputNumbersValue[0] == "8") ? "8" : "+7";
            formattedInputValue = firstSymbols + " ";

            if (inputNumbersValue.length > 1) {
                formattedInputValue += '(' + inputNumbersValue.substring(1, 4);
            }
            if (inputNumbersValue.length >= 5) {
                formattedInputValue += ') ' + inputNumbersValue.substring(4, 7);
            }
            if (inputNumbersValue.length >= 8) {
                formattedInputValue += '-' + inputNumbersValue.substring(7, 9);
            }
            if (inputNumbersValue.length >= 10) {
                formattedInputValue += '-' + inputNumbersValue.substring(9, 11);
            }
        } else {
            formattedInputValue = '+' + inputNumbersValue.substring(0, 16);
        }
        input.value = formattedInputValue;
    };

    const onPhoneKeyDown = function (e) {
        let inputValue = e.target.value.replace(/\D/g, '');
        if (e.keyCode == 8 && inputValue.length == 1) {
            e.target.value = "";
        }
    };

    const onPhonePaste = function (e) {
        let input = e.target,
            inputNumbersValue = getInputNumbersValue(input);
        let pasted = e.originalEvent.clipboardData || window.clipboardData;
        if (pasted) {
            let pastedText = pasted.getData('Text');
            if (/\D/g.test(pastedText)) {
                input.value = inputNumbersValue;
            }
        }
    };

    $phoneInputs
        .on('keydown', onPhoneKeyDown)
        .on('input', onPhoneInput)
        .on('paste', onPhonePaste);

    // custom select
    // class CustomSelect {
    //     static openDropdown = null;
    //     static eventsBound = false;

    //     constructor(dropdownElement) {
    //         this.$dropdown = $(dropdownElement);
    //         this.$input = this.$dropdown.find('input[type="hidden"]');
    //         this.$button = this.$dropdown.find('.dropdown__button');
    //         this.$buttonText = this.$dropdown.find('.dropdown__button-text');
    //         this.$listItems = this.$dropdown.find('.dropdown__list-item');

    //         this.initialValue = this.$input.val();
    //         this.initialText = this.$buttonText.text();

    //         this.init();
    //     }

    //     init() {
    //         this.setupEvents();
    //         this.bindGlobalEvents();
    //         this.syncStateWithInput();
    //     }

    //     bindGlobalEvents() {
    //         if (CustomSelect.eventsBound) return;

    //         $(document).on('click.customSelectGlobal', (event) => {
    //             if (CustomSelect.openDropdown && !$(event.target).closest('.dropdown').length) {
    //                 CustomSelect.openDropdown.closeDropdown();
    //             }
    //         });

    //         $(document).on('keydown.customSelectGlobal', (event) => {
    //             if (event.key === 'Escape' && CustomSelect.openDropdown) {
    //                 CustomSelect.openDropdown.closeDropdown();
    //             }
    //         });

    //         CustomSelect.eventsBound = true;
    //     }

    //     setupEvents() {
    //         this.$button.on('click', (event) => {
    //             event.preventDefault();
    //             event.stopPropagation();
    //             const isOpen = this.$dropdown.hasClass('visible');
    //             this.toggleDropdown(!isOpen);
    //         });

    //         this.$dropdown.on('click', '.dropdown__list-item', (event) => {
    //             event.preventDefault();
    //             event.stopPropagation();
    //             const item = $(event.currentTarget);

    //             if (!item.hasClass('disabled')) {
    //                 this.selectOption(item);
    //             }
    //         });

    //         this.$input.closest('form').on('reset', () => {
    //             setTimeout(() => this.restoreInitialState(), 0);
    //         });
    //     }

    //     toggleDropdown(isOpen) {
    //         if (isOpen && CustomSelect.openDropdown && CustomSelect.openDropdown !== this) {
    //             CustomSelect.openDropdown.closeDropdown();
    //         }

    //         const body = this.$dropdown.find('.dropdown__body');
    //         const list = this.$dropdown.find('.dropdown__list');
    //         const hasScroll = list.length && list[0].scrollHeight > list[0].clientHeight;

    //         this.$dropdown.toggleClass('visible', isOpen);
    //         this.$button.attr('aria-expanded', isOpen);
    //         body.attr('aria-hidden', !isOpen);

    //         if (isOpen) {
    //             CustomSelect.openDropdown = this;
    //             this.$dropdown.removeClass('dropdown-top');

    //             const dropdownRect = body[0].getBoundingClientRect();
    //             const viewportHeight = window.innerHeight;

    //             if (dropdownRect.bottom > viewportHeight) {
    //                 this.$dropdown.addClass('dropdown-top');
    //             }
    //             list.toggleClass('has-scroll', hasScroll);
    //         } else {
    //             if (CustomSelect.openDropdown === this) {
    //                 CustomSelect.openDropdown = null;
    //             }
    //         }
    //     }

    //     closeDropdown() {
    //         this.toggleDropdown(false);
    //     }

    //     selectOption(item) {
    //         const value = item.data('value');
    //         const text = item.text();

    //         this.$listItems.removeClass('selected').attr('aria-checked', 'false');
    //         item.addClass('selected').attr('aria-checked', 'true');

    //         this.$button.addClass('selected');
    //         this.$buttonText.text(text);

    //         this.$input.val(value).trigger('change');

    //         this.closeDropdown();
    //     }

    //     restoreInitialState() {
    //         this.$input.val(this.initialValue);
    //         this.$buttonText.text(this.initialText);

    //         this.$listItems.removeClass('selected').attr('aria-checked', 'false');
    //         const initialItem = this.$listItems.filter((_, el) => $(el).data('value') == this.initialValue);

    //         if (initialItem.length) {
    //             initialItem.addClass('selected').attr('aria-checked', 'true');
    //             this.$button.addClass('selected');
    //         } else {
    //             this.$button.removeClass('selected');
    //         }
    //     }

    //     syncStateWithInput() {
    //         const currentValue = this.$input.val();
    //         const currentItem = this.$listItems.filter((_, el) => $(el).data('value') == currentValue);

    //         if (currentItem.length) {
    //             this.$listItems.removeClass('selected').attr('aria-checked', 'false');
    //             currentItem.addClass('selected').attr('aria-checked', 'true');
    //             this.$buttonText.text(currentItem.text());
    //             this.$button.addClass('selected');
    //         }
    //     }
    // }

    // $('.dropdown').each((index, element) => {
    //     new CustomSelect(element);
    // });


    function getSuccessSubmitting() {
        Fancybox.close();
        Fancybox.show([{
            src: "#success-submitting",
            type: "inline"
        }]);
    }

    function getErrorSubmitting() {
        Fancybox.close();
        Fancybox.show([{
            src: "#error-submitting",
            type: "inline"
        }]);
    }

    document.addEventListener('wpcf7mailsent', function () {
        getSuccessSubmitting()
    }, false);

    document.addEventListener('wpcf7mailfailed', function () {
        getErrorSubmitting()
    }, false);


    // function initYandexMap() {
    //     const $mapContainer = $('#yandex-map');
    //     if (!$mapContainer.length) return;

    //     let myMap, myPlacemark;

    //     const syncOffset = () => {
    //         const $card = $('.baloon__card');
    //         if (!$card.length || !myPlacemark) return;

    //         const w = $card.outerWidth();
    //         const h = $card.outerHeight();
    //         const gap = window.innerWidth <= 768 ? 8 : 10;

    //         myPlacemark.options.set('balloonOffset', [
    //             -Math.round(w / 2),
    //             -Math.round(h + gap)
    //         ]);
    //     };

    //     const init = () => {
    //         const rawCoords = $mapContainer.data('coords');
    //         const centerCoords = rawCoords ? rawCoords.split(',').map(Number) : [59.957545, 30.412431];
    //         const balloonHtml = $('#map-balloon-template').html();

    //         myMap = new ymaps.Map('yandex-map', {
    //             center: centerCoords,
    //             zoom: 17,
    //             controls: ['zoomControl']
    //         });

    //         myMap.behaviors.disable('scrollZoom');

    //         const MyBalloonLayout = ymaps.templateLayoutFactory.createClass(
    //             `<div class="map-balloon-wrapper">${balloonHtml}</div>`
    //         );

    //         myPlacemark = new ymaps.Placemark(centerCoords, {}, {
    //             balloonLayout: MyBalloonLayout,
    //             balloonCloseButton: false,
    //             balloonPanelMaxMapArea: 0,
    //             hideIconOnBalloonOpen: false,
    //             balloonOffset: [0, 0]
    //         });

    //         myPlacemark.events.add('balloonopen', () => {
    //             requestAnimationFrame(() => {
    //                 requestAnimationFrame(syncOffset);
    //             });
    //             setTimeout(syncOffset, 120);
    //         });

    //         myMap.geoObjects.add(myPlacemark);
    //         myPlacemark.balloon.open();

    //         $(window).on('resize', () => {
    //             clearTimeout(window.mapResizeTimer);
    //             window.mapResizeTimer = setTimeout(() => {
    //                 if (myPlacemark && myPlacemark.balloon.isOpen()) {
    //                     syncOffset();
    //                 }
    //             }, 150);
    //         });
    //     };

    //     const loadScript = () => {
    //         if (typeof ymaps !== 'undefined') return;

    //         const script = document.createElement('script');
    //         script.src = 'https://api-maps.yandex.ru/2.1/?apikey=496cd84c-0a7a-4b7e-a9d5-bd9261e5f0a6&lang=ru_RU';
    //         script.type = 'text/javascript';
    //         script.onload = () => {
    //             ymaps.ready(init);
    //         };
    //         document.head.appendChild(script);
    //     };

    //     const observer = new IntersectionObserver((entries) => {
    //         entries.forEach(entry => {
    //             if (entry.isIntersecting) {
    //                 loadScript();
    //                 observer.unobserve(entry.target);
    //             }
    //         });
    //     }, {
    //         rootMargin: '200px'
    //     });

    //     observer.observe($mapContainer[0]);
    // }

    // initYandexMap();


})


