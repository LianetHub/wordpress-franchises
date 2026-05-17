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

(function ($) {
    if (!$) return;

    $(function () {

        function showInlineFancybox(selector) {
            if (typeof Fancybox === "undefined" || !Fancybox) return;
            Fancybox.show([{ src: selector, type: "inline" }], { dragToClose: false });
        }

        window.__showLeadFeedback = (message = "", isError = false) => {
            const $root = $("[data-lead-feedback]");
            const $card = $root.find("[data-lead-feedback-card]");
            if (!$root.length || !$card.length) return;

            $card.toggleClass("is-error", isError).toggleClass("is-success", !isError);
            $root.find("[data-lead-feedback-success-text], [data-lead-feedback-success-block]").toggle(!isError);
            const $errorText = $root.find("[data-lead-feedback-error-text]");
            $errorText.toggle(isError);
            if (isError && message) {
                $errorText.text(message);
            }

            const $mark = $root.find("[data-lead-feedback-mark]");
            if (!isError && $mark.length) {
                $mark.removeClass("animate");
                void $mark[0].offsetWidth;
                $mark.addClass("animate");
            }
            showInlineFancybox("#lead-feedback");
        };

        if (typeof Fancybox !== "undefined" && Fancybox !== null) {
            Fancybox.bind("[data-fancybox]", { dragToClose: false });
            Fancybox.bind("[data-fancybox-inline]", { dragToClose: false });
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

        function initHorizontalStripSwiper(strip, { prevBtn, nextBtn, paginationEl, slidesPerView = "auto", spaceBetween = 14, autoplay = false } = {}) {
            if (typeof Swiper === "undefined" || !strip || strip.swiper) return null;

            const config = {
                speed: 400,
                slidesPerView,
                spaceBetween,
                watchOverflow: true,
            };

            if (prevBtn && nextBtn) {
                config.navigation = { prevEl: prevBtn, nextEl: nextBtn };
            }
            if (paginationEl) {
                config.pagination = { el: paginationEl, clickable: true };
            }
            if (autoplay) {
                config.autoplay = { delay: 4000, disableOnInteraction: false, pauseOnMouseEnter: true };
            }

            return new Swiper(strip, config);
        }

        const logoStrip = document.querySelector(".logo-strip");
        if (logoStrip) {
            const logoWrap = logoStrip.closest(".logo-wrap");
            initHorizontalStripSwiper(logoStrip, {
                prevBtn: logoWrap?.querySelector(".logo-arrow.prev"),
                nextBtn: logoWrap?.querySelector(".logo-arrow.next"),
                spaceBetween: 14,
            });

            const logoModal = document.querySelector("#logo-modal");
            const isMobileLogo = () => window.matchMedia("(max-width: 900px)").matches;

            logoStrip.querySelectorAll(".logo-card").forEach((card) => {
                card.addEventListener("click", () => {
                    if (!isMobileLogo() || !logoModal) return;
                    const img = logoModal.querySelector(".logo-modal-media img");
                    const brand = logoModal.querySelector(".logo-modal-brand");
                    const title = logoModal.querySelector(".logo-modal-title");
                    const meta = logoModal.querySelector(".logo-modal-meta");
                    const link = logoModal.querySelector(".logo-modal-cta");
                    const cardImg = card.querySelector("img");
                    if (img) img.src = card.dataset.image || cardImg?.src || "";
                    if (brand) brand.textContent = card.dataset.brand || "";
                    if (title) title.textContent = card.dataset.title || "";
                    if (meta) meta.textContent = card.dataset.invest || "";
                    if (link) link.href = card.dataset.link || card.getAttribute("href") || "#";
                    showInlineFancybox("#logo-modal");
                });
            });
        }

        const reviewsStrip = document.querySelector(".reviews-strip");
        if (reviewsStrip) {
            const reviewsWrap = reviewsStrip.closest(".reviews-wrap");
            initHorizontalStripSwiper(reviewsStrip, {
                prevBtn: reviewsWrap?.querySelector(".reviews-arrow.prev"),
                nextBtn: reviewsWrap?.querySelector(".reviews-arrow.next"),
                paginationEl: document.querySelector(".reviews-dots"),
                spaceBetween: 20,
            });
        }



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

        class CustomSelect {
            static openDropdown = null;
            static eventsBound = false;
            static typeahead = '';
            static typeaheadTimer = null;

            constructor(dropdownElement, options = {}) {
                this.$dropdown = $(dropdownElement);
                this.nativeSelect = options.nativeSelect || null;
                this.$input = this.nativeSelect ? null : this.$dropdown.find('input[type="hidden"]').first();
                this.$button = this.$dropdown.find('.dropdown__button').first();
                this.$buttonText = this.$dropdown.find('.dropdown__button-text').first();
                this.$body = this.$dropdown.find('.dropdown__body').first();
                this.$list = this.$dropdown.find('.dropdown__list').first();
                this.$listItems = this.$list.find('.dropdown__list-item');
                this.activeIndex = -1;
                this.uid = this.$dropdown.attr('id') || `dropdown-${Math.random().toString(36).slice(2, 9)}`;
                this.$dropdown.attr('id', this.uid);

                this.isValid = Boolean(this.$button.length && this.$list.length);
                if (!this.isValid) return;

                this.setupA11y();
                this.initialValue = this.getValue();
                this.initialText = this.$buttonText.text().trim();
                this.init();
            }

            init() {
                this.setupEvents();
                CustomSelect.bindGlobalEvents();
                this.syncStateWithInput();
                this.$dropdown.data('customSelect', this);
            }

            static bindGlobalEvents() {
                if (CustomSelect.eventsBound) return;

                $(document).on('click.customSelectGlobal', (event) => {
                    if (CustomSelect.openDropdown && !$(event.target).closest('.dropdown').length) {
                        CustomSelect.openDropdown.closeDropdown();
                    }
                });

                $(document).on('keydown.customSelectGlobal', (event) => {
                    const open = CustomSelect.openDropdown;
                    if (!open) return;

                    if (event.key === 'Escape') {
                        event.preventDefault();
                        open.closeDropdown();
                        open.$button.trigger('focus');
                        return;
                    }

                    if (!open.$dropdown.hasClass('visible')) return;

                    const items = open.getEnabledItems();
                    if (!items.length) return;

                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        const delta = event.key === 'ArrowDown' ? 1 : -1;
                        const nextIndex = open.activeIndex < 0
                            ? (delta > 0 ? 0 : items.length - 1)
                            : Math.max(0, Math.min(items.length - 1, open.activeIndex + delta));
                        open.setActiveIndex(nextIndex);
                        return;
                    }

                    if (event.key === 'Home') {
                        event.preventDefault();
                        open.setActiveIndex(0);
                        return;
                    }

                    if (event.key === 'End') {
                        event.preventDefault();
                        open.setActiveIndex(items.length - 1);
                        return;
                    }

                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        if (open.activeIndex >= 0) {
                            open.selectOption(items.eq(open.activeIndex));
                        }
                        return;
                    }

                    if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                        open.handleTypeahead(event.key);
                    }
                });

                CustomSelect.eventsBound = true;
            }

            setupA11y() {
                const listboxId = `${this.uid}-listbox`;
                const buttonId = `${this.uid}-button`;

                this.$button.attr({
                    id: buttonId,
                    type: 'button',
                    role: 'combobox',
                    'aria-haspopup': 'listbox',
                    'aria-expanded': 'false',
                    'aria-controls': listboxId,
                });

                this.$list.attr({
                    id: listboxId,
                    role: 'listbox',
                    tabindex: '-1',
                });

                this.$body.attr('aria-hidden', 'true');

                const labelId = this.nativeSelect?.id
                    ? $(`label[for="${this.nativeSelect.id}"]`).attr('id')
                    : null;
                const ariaLabel = this.nativeSelect?.getAttribute('aria-label')
                    || this.$dropdown.data('label')
                    || null;

                if (labelId) {
                    this.$button.attr('aria-labelledby', `${labelId} ${buttonId}`);
                } else if (ariaLabel) {
                    this.$button.attr('aria-label', ariaLabel);
                }

                this.$listItems.each((index, element) => {
                    const $item = $(element);
                    if (!$item.attr('id')) {
                        $item.attr('id', `${listboxId}-opt-${index}`);
                    }
                    $item.attr({
                        role: 'option',
                        'aria-selected': 'false',
                    });
                });

                if (this.nativeSelect) {
                    this.nativeSelect.setAttribute('aria-hidden', 'true');
                    this.nativeSelect.setAttribute('tabindex', '-1');
                    this.nativeSelect.classList.add('select-native-source');
                }
            }

            setupEvents() {
                this.$button.on('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    if (this.isDisabled()) return;
                    this.toggleDropdown(!this.$dropdown.hasClass('visible'));
                });

                this.$button.on('keydown', (event) => {
                    if (this.isDisabled()) return;
                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        if (!this.$dropdown.hasClass('visible')) {
                            this.toggleDropdown(true);
                        }
                    }
                });

                this.$dropdown.on('click', '.dropdown__list-item', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const item = $(event.currentTarget);
                    if (this.isItemDisabled(item)) return;
                    this.selectOption(item);
                });

                const $form = this.nativeSelect
                    ? $(this.nativeSelect).closest('form')
                    : this.$input?.closest('form');
                $form?.on('reset', () => {
                    window.setTimeout(() => this.restoreInitialState(), 0);
                });
            }

            isDisabled() {
                return this.$dropdown.hasClass('is-disabled')
                    || this.$button.is(':disabled')
                    || Boolean(this.nativeSelect?.disabled);
            }

            isItemDisabled($item) {
                return $item.hasClass('disabled') || $item.attr('aria-disabled') === 'true';
            }

            getEnabledItems() {
                return this.$listItems.filter((_, el) => !this.isItemDisabled($(el)));
            }

            getValue() {
                if (this.nativeSelect) return String(this.nativeSelect.value ?? '');
                return String(this.$input?.val() ?? '');
            }

            setValue(value, triggerChange = true) {
                if (this.nativeSelect) {
                    this.nativeSelect.value = value;
                    if (triggerChange) {
                        $(this.nativeSelect).trigger('change');
                    }
                } else if (this.$input) {
                    this.$input.val(value);
                    if (triggerChange) {
                        this.$input.trigger('change');
                    }
                }
            }

            getItemsByValue(value) {
                return this.$listItems.filter((_, el) => String($(el).data('value')) === String(value));
            }

            toggleDropdown(isOpen) {
                if (this.isDisabled()) return;

                if (isOpen && CustomSelect.openDropdown && CustomSelect.openDropdown !== this) {
                    CustomSelect.openDropdown.closeDropdown();
                }

                const hasScroll = this.$list.length && this.$list[0].scrollHeight > this.$list[0].clientHeight;

                this.$dropdown.toggleClass('visible', isOpen);
                this.$button.attr('aria-expanded', String(isOpen));
                this.$body.attr('aria-hidden', String(!isOpen));

                if (isOpen) {
                    CustomSelect.openDropdown = this;
                    this.$dropdown.removeClass('dropdown-top');
                    this.syncActiveIndexWithSelection();
                    this.$list.toggleClass('has-scroll', Boolean(hasScroll));

                    window.requestAnimationFrame(() => {
                        const dropdownRect = this.$body[0]?.getBoundingClientRect();
                        if (dropdownRect && dropdownRect.bottom > window.innerHeight) {
                            this.$dropdown.addClass('dropdown-top');
                        }
                        this.scrollActiveItemIntoView();
                    });
                } else {
                    this.clearActiveOption();
                    if (CustomSelect.openDropdown === this) {
                        CustomSelect.openDropdown = null;
                    }
                }
            }

            closeDropdown() {
                this.toggleDropdown(false);
            }

            clearActiveOption() {
                this.activeIndex = -1;
                this.$listItems.removeClass('is-active');
                this.$button.removeAttr('aria-activedescendant');
            }

            setActiveIndex(index) {
                const items = this.getEnabledItems();
                if (!items.length) return;

                this.activeIndex = Math.max(0, Math.min(index, items.length - 1));
                items.removeClass('is-active');
                const $active = items.eq(this.activeIndex).addClass('is-active');
                this.$button.attr('aria-activedescendant', $active.attr('id') || '');
                this.scrollActiveItemIntoView();
            }

            syncActiveIndexWithSelection() {
                const items = this.getEnabledItems();
                const selectedIndex = items.index(items.filter('.selected, [aria-selected="true"]').first());
                this.setActiveIndex(selectedIndex >= 0 ? selectedIndex : 0);
            }

            scrollActiveItemIntoView() {
                const active = this.$listItems.filter('.is-active')[0];
                active?.scrollIntoView({ block: 'nearest' });
            }

            handleTypeahead(char) {
                window.clearTimeout(CustomSelect.typeaheadTimer);
                CustomSelect.typeahead += char.toLowerCase();
                CustomSelect.typeaheadTimer = window.setTimeout(() => {
                    CustomSelect.typeahead = '';
                }, 500);

                const items = this.getEnabledItems();
                const matchIndex = items.get().findIndex((el) =>
                    $(el).text().trim().toLowerCase().startsWith(CustomSelect.typeahead)
                );
                if (matchIndex >= 0) {
                    this.setActiveIndex(matchIndex);
                }
            }

            selectOption(item) {
                const value = item.data('value');
                const text = item.text().trim();

                this.$listItems.removeClass('selected is-active').attr('aria-selected', 'false');
                item.addClass('selected').attr('aria-selected', 'true');

                this.$button.addClass('selected');
                this.$buttonText.text(text);
                this.setValue(value == null ? '' : String(value));
                this.closeDropdown();
                this.$button.trigger('focus');
            }

            restoreInitialState() {
                this.setValue(this.initialValue, false);
                this.$buttonText.text(this.initialText);
                this.syncStateWithInput();
            }

            syncStateWithInput() {
                const currentValue = this.getValue();
                const currentItem = this.getItemsByValue(currentValue);
                const placeholder = this.$dropdown.data('placeholder')
                    || this.nativeSelect?.querySelector('option[value=""]')?.textContent?.trim()
                    || this.$buttonText.text().trim();

                this.$listItems.removeClass('selected is-active').attr('aria-selected', 'false');

                if (currentItem.length) {
                    currentItem.addClass('selected').attr('aria-selected', 'true');
                    this.$buttonText.text(currentItem.first().text().trim());
                    this.$button.addClass('selected');
                } else {
                    this.$buttonText.text(placeholder);
                    this.$button.toggleClass('selected', Boolean(currentValue));
                }
            }

            refreshFromNative() {
                if (!this.nativeSelect) return;

                const currentValue = this.getValue();
                this.$list.empty();

                Array.from(this.nativeSelect.options).forEach((option, index) => {
                    if (option.hidden) return;

                    const $item = $('<div>', {
                        class: 'dropdown__list-item',
                        'data-value': option.value,
                        id: `${this.$list.attr('id')}-opt-${index}`,
                        role: 'option',
                        'aria-selected': 'false',
                        text: option.textContent?.trim() || option.label || option.value,
                    });

                    if (option.disabled) {
                        $item.addClass('disabled').attr('aria-disabled', 'true');
                    }

                    this.$list.append($item);
                });

                this.$listItems = this.$list.find('.dropdown__list-item');
                this.syncStateWithInput();

                if (currentValue && !this.getItemsByValue(currentValue).length) {
                    this.setValue('');
                }
            }

            static fromNativeSelect(selectElement) {
                const select = selectElement;
                const $select = $(select);
                const existing = $select.data('customSelect');
                if (existing) return existing;

                if ($select.data('customSelectBuilt')) {
                    return $select.closest('.dropdown').data('customSelect') || null;
                }

                const uid = select.id || `select-${Math.random().toString(36).slice(2, 9)}`;
                const listboxId = `${uid}-listbox`;
                const buttonId = `${uid}-button`;
                const label = select.getAttribute('aria-label') || '';

                const $dropdown = $(`
                    <div class="dropdown" data-native-for="${uid}">
                        <button type="button" class="dropdown__button" id="${buttonId}" aria-expanded="false">
                            <span class="dropdown__button-text"></span>
                        </button>
                        <div class="dropdown__body" aria-hidden="true">
                            <div class="dropdown__list" id="${listboxId}" role="listbox"></div>
                        </div>
                    </div>
                `);

                if (label) {
                    $dropdown.attr('data-label', label);
                }

                if ($select.hasClass('dropdown--fit') || $select.hasClass('sort-select')) {
                    $dropdown.addClass('dropdown--fit');
                }

                $select.after($dropdown).data('customSelectBuilt', true);

                const instance = new CustomSelect($dropdown[0], { nativeSelect: select });
                if (!instance.isValid) {
                    $dropdown.remove();
                    $select.removeData('customSelectBuilt');
                    return null;
                }
                instance.refreshFromNative();
                $select.data('customSelect', instance);
                return instance;
            }

            static initAll(root = document) {
                const $root = $(root);
                $root.find('.dropdown').each((_, element) => {
                    if (!$(element).data('customSelect')) {
                        const instance = new CustomSelect(element);
                        if (instance.isValid) {
                            $(element).data('customSelect', instance);
                        }
                    }
                });

                $root.find('select.select, select[data-custom-select], select.filter-select-native').each((_, element) => {
                    CustomSelect.fromNativeSelect(element);
                });
            }
        }

        CustomSelect.initAll(document);
        window.CustomSelect = CustomSelect;
        window.initCustomSelects = (root) => CustomSelect.initAll(root || document);

        $(document).on("wpcf7domready", (event) => {
            CustomSelect.initAll(event.target || document);
        });

        $(document).on("wpcf7mailsent", function () {
            if (typeof Fancybox !== "undefined" && Fancybox) Fancybox.close();
            if (typeof window.__showLeadFeedback === "function") {
                window.__showLeadFeedback("", false);
            }
        });

        $(document).on("wpcf7mailfailed", function (event) {
            if (typeof Fancybox !== "undefined" && Fancybox) Fancybox.close();
            const detail = event?.detail || {};
            const message = detail.apiResponse?.message || "Не удалось отправить заявку. Попробуйте ещё раз.";
            if (typeof window.__showLeadFeedback === "function") {
                window.__showLeadFeedback(message, true);
            }
        });


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


    });

})(window.jQuery);

/* ===== UI: главная, галерея, общие компоненты (без manifest/DB) ===== */
(() => {
    const escapeHtml = (value) =>
        String(value)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#39;");

    const initSegmentTabs = () => {
        const segButtons = document.querySelectorAll(".seg");
        const segPanels = document.querySelectorAll(".segment-panel");
        if (!segButtons.length || !segPanels.length) return;
        segButtons.forEach((btn) => {
            btn.addEventListener("click", () => {
                const targetId = btn.getAttribute("data-target");
                segButtons.forEach((b) => {
                    b.classList.toggle("active", b === btn);
                    b.setAttribute("aria-selected", String(b === btn));
                });
                segPanels.forEach((panel) => panel.classList.toggle("active", panel.id === targetId));
            });
        });
    };

    const initCategoryBar = () => {
        const categoryWrap = document.querySelector("#category-grid-wrap");
        const categoryToggle = document.querySelector(".category-toggle");
        const categoryGrid = categoryWrap?.querySelector(".category-grid");
        if (!categoryWrap || !categoryGrid) return;

        let categoryLastScroll = null;
        const isMobile = () => window.matchMedia("(max-width: 900px)").matches;

        const getCollapsedHeight = () => {
            const items = Array.from(categoryGrid.children);
            if (!items.length) return 0;
            if (isMobile()) return categoryGrid.scrollHeight;
            const rowsTarget = 2;
            const rowTops = [];
            items.forEach((item) => {
                const top = item.offsetTop;
                if (!rowTops.find((t) => Math.abs(t - top) <= 2)) rowTops.push(top);
            });
            rowTops.sort((a, b) => a - b);
            if (rowTops.length <= rowsTarget) return categoryGrid.scrollHeight;
            const targetTop = rowTops[rowsTarget - 1];
            let maxBottom = 0;
            items.forEach((item) => {
                if (Math.abs(item.offsetTop - targetTop) <= 2) {
                    maxBottom = Math.max(maxBottom, item.offsetTop + item.offsetHeight);
                }
            });
            return maxBottom + 2;
        };

        const setWrapHeight = (height) => {
            categoryWrap.style.height = `${Math.max(0, Math.round(height))}px`;
        };

        const setCollapsedState = () => {
            categoryWrap.classList.remove("expanded");
            categoryWrap.classList.add("collapsed");
            if (categoryToggle) {
                categoryToggle.textContent = "Показать все отрасли";
                categoryToggle.setAttribute("aria-expanded", "false");
            }
            setWrapHeight(getCollapsedHeight());
            categoryWrap.style.overflow = "hidden";
        };

        const setExpandedState = () => {
            categoryWrap.classList.add("expanded");
            categoryWrap.classList.remove("collapsed");
            if (categoryToggle) {
                categoryToggle.textContent = "Скрыть отрасли";
                categoryToggle.setAttribute("aria-expanded", "true");
            }
            setWrapHeight(categoryGrid.scrollHeight);
            categoryWrap.style.overflow = "visible";
        };

        const applyMode = () => {
            const shouldShowToggle = isMobile() && categoryGrid.children.length > 6;
            if (shouldShowToggle) {
                if (categoryToggle) categoryToggle.style.display = "";
                setCollapsedState();
                setWrapHeight(getCollapsedHeight());
                if (categoryToggle && !categoryToggle.dataset.bound) {
                    categoryToggle.dataset.bound = "true";
                    categoryToggle.addEventListener("click", () => {
                        const willExpand = !categoryWrap.classList.contains("expanded");
                        if (willExpand) {
                            setWrapHeight(getCollapsedHeight());
                            categoryWrap.classList.add("expanded");
                            categoryWrap.classList.remove("collapsed");
                            requestAnimationFrame(() => setWrapHeight(categoryGrid.scrollHeight));
                        } else {
                            setWrapHeight(categoryGrid.scrollHeight);
                            categoryWrap.classList.remove("expanded");
                            categoryWrap.classList.add("collapsed");
                            requestAnimationFrame(() => setWrapHeight(getCollapsedHeight()));
                        }
                        const isExpanded = willExpand;
                        categoryToggle.textContent = isExpanded ? "Скрыть отрасли" : "Показать все отрасли";
                        categoryToggle.setAttribute("aria-expanded", String(isExpanded));
                        if (isExpanded) {
                            categoryLastScroll = window.scrollY;
                            const section = categoryWrap.closest(".category-bar");
                            if (section) {
                                const rootStyles = getComputedStyle(document.documentElement);
                                const headerHeight = parseFloat(rootStyles.getPropertyValue("--header-height")) || 0;
                                const top = section.getBoundingClientRect().top + window.scrollY - headerHeight - 12;
                                window.scrollTo({ top, behavior: "smooth" });
                            }
                        } else if (categoryLastScroll !== null) {
                            window.scrollTo({ top: categoryLastScroll, behavior: "smooth" });
                            categoryLastScroll = null;
                        }
                    });
                }
            } else {
                if (categoryToggle) categoryToggle.style.display = "none";
                setExpandedState();
                categoryWrap.style.height = "auto";
                categoryWrap.style.overflow = "visible";
            }
        };

        applyMode();
        requestAnimationFrame(applyMode);
        window.addEventListener("resize", applyMode);
    };

    const initHomeCollections = () => {
        const chips = document.querySelector("[data-collections-chips]");
        if (!chips) return;

        const activate = (key) => {
            chips.querySelectorAll("[data-collection]").forEach((btn) => {
                const active = btn.getAttribute("data-collection") === key;
                btn.classList.toggle("active", active);
                btn.setAttribute("aria-pressed", String(active));
            });
            document.querySelectorAll("[data-collection-panel]").forEach((panel) => {
                const active = panel.getAttribute("data-collection-panel") === key;
                panel.hidden = !active;
            });
        };

        const first = chips.querySelector("[data-collection]");
        if (first) activate(first.getAttribute("data-collection") || "");

        chips.addEventListener("click", (event) => {
            const btn = event.target.closest("[data-collection]");
            if (!btn || !chips.contains(btn)) return;
            activate(btn.getAttribute("data-collection") || "");
        });
    };

    const initFranchiseGallery = () => {
        if (typeof Swiper === "undefined") return;

        const galleryCard = document.querySelector(".gallery-card");
        const mainEl = galleryCard?.querySelector(".gallery-main.swiper");
        if (!mainEl || mainEl.swiper) return;

        const slideCount = mainEl.querySelectorAll(".swiper-slide").length;
        if (!slideCount) return;

        const thumbsEl = galleryCard?.querySelector(".gallery-thumbs.swiper");
        const prevMain = galleryCard?.querySelector(".gallery-nav-prev");
        const nextMain = galleryCard?.querySelector(".gallery-nav-next");
        const thumbsPrev = galleryCard?.querySelector(".thumbs-nav-prev");
        const thumbsNext = galleryCard?.querySelector(".thumbs-nav-next");
        const countEl = galleryCard?.querySelector("[data-gallery-count]");

        let thumbsSwiper = null;
        if (thumbsEl && slideCount > 1) {
            thumbsSwiper = new Swiper(thumbsEl, {
                slidesPerView: "auto",
                spaceBetween: 10,
                watchSlidesProgress: true,
                slideToClickedSlide: true,
                watchOverflow: true,
                navigation:
                    thumbsPrev && thumbsNext
                        ? {
                            prevEl: thumbsPrev,
                            nextEl: thumbsNext,
                        }
                        : undefined,
                breakpoints: {
                    0: { spaceBetween: 8 },
                    641: { spaceBetween: 10 },
                },
            });
        }

        const mainConfig = {
            speed: 400,
            watchOverflow: true,
        };

        if (thumbsSwiper) {
            mainConfig.thumbs = { swiper: thumbsSwiper };
        }
        if (slideCount > 1 && prevMain && nextMain) {
            mainConfig.navigation = { prevEl: prevMain, nextEl: nextMain };
        }
        if (slideCount > 1 && countEl) {
            mainConfig.pagination = {
                el: countEl,
                type: "fraction",
            };
        }

        new Swiper(mainEl, mainConfig);
    };

    const initMobileToc = () => {
        document.querySelectorAll(".toc-mobile").forEach((block) => {
            if (block.dataset.tocReady === "1") return;
            const title = block.querySelector(".toc-title");
            const list = block.querySelector(".toc-list");
            if (!title || !list) return;
            const toggle = document.createElement("button");
            toggle.type = "button";
            toggle.className = "toc-mobile-toggle";
            toggle.setAttribute("aria-expanded", "false");
            toggle.textContent = title.textContent?.trim() || "Содержание";
            title.replaceWith(toggle);
            list.hidden = true;
            toggle.addEventListener("click", () => {
                const isOpen = block.classList.toggle("is-open");
                toggle.setAttribute("aria-expanded", String(isOpen));
                list.hidden = !isOpen;
            });
            block.dataset.tocReady = "1";
        });
    };

    const initHomeStatsCounter = () => {
        if (!document.body.classList.contains("home")) return;
        const statNodes = Array.from(document.querySelectorAll(".about-stats .stat-value"));
        if (!statNodes.length) return;

        const reduceMotion = window.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches;
        const parseNumericText = (text) => {
            const source = String(text || "").trim();
            const match = source.match(/(\d[\d\s]*)/);
            if (!match || typeof match.index !== "number") return null;
            const target = Number(String(match[1]).replace(/\s+/g, ""));
            if (!Number.isFinite(target)) return null;
            return { target, prefix: source.slice(0, match.index), suffix: source.slice(match.index + match[1].length) };
        };

        const counters = statNodes.map((node) => ({ node, meta: parseNumericText(node.textContent) })).filter((x) => x.meta);
        if (!counters.length) return;

        const root = document.querySelector(".about-stats") || counters[0].node;
        let started = false;
        let hasScrolled = window.scrollY > 0;

        const runAll = () => {
            if (started) return;
            started = true;
            counters.forEach(({ node, meta }) => {
                if (reduceMotion) {
                    node.textContent = `${meta.prefix}${meta.target.toLocaleString("ru-RU")}${meta.suffix}`;
                    return;
                }
                const duration = 980;
                let startedAt = 0;
                const frame = (ts) => {
                    if (!startedAt) startedAt = ts;
                    const progress = Math.min(1, (ts - startedAt) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = Math.floor(meta.target * eased);
                    node.textContent = `${meta.prefix}${current.toLocaleString("ru-RU")}${meta.suffix}`;
                    if (progress < 1) requestAnimationFrame(frame);
                };
                requestAnimationFrame(frame);
            });
        };

        const maybeStart = () => {
            if (hasScrolled) runAll();
        };

        window.addEventListener("scroll", () => {
            hasScrolled = true;
            maybeStart();
        }, { passive: true, once: true });

        if ("IntersectionObserver" in window && root instanceof Element) {
            const observer = new IntersectionObserver((entries) => {
                if (entries.some((e) => e.isIntersecting && e.intersectionRatio >= 0.32)) {
                    maybeStart();
                    observer.disconnect();
                }
            }, { threshold: [0, 0.32, 1] });
            observer.observe(root);
        } else {
            maybeStart();
        }
    };


    const initSliderSections = () => {
        if (typeof Swiper === "undefined") return;
        document.querySelectorAll(".slider-section .slider.swiper").forEach((container) => {
            const section = container.closest(".slider-section");
            const prev = section?.querySelector("[data-slider-prev]");
            const next = section?.querySelector("[data-slider-next]");
            if (!container || container.swiper) return;
            new Swiper(container, {
                speed: 400,
                slidesPerView: "auto",
                spaceBetween: 12,
                watchOverflow: true,
                navigation: prev && next ? { prevEl: prev, nextEl: next } : undefined,
            });
        });
    };

    const run = () => {
        initSegmentTabs();
        initCategoryBar();
        initHomeCollections();
        initFranchiseGallery();
        initMobileToc();
        initHomeStatsCounter();
        initSliderSections();
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", run, { once: true });
    } else {
        run();
    }
})();

document.addEventListener('DOMContentLoaded', () => {
    const main = document.querySelector('main.wrap.catalog-page');
    if (!main) return;

    const filterCard = main.querySelector('.filter-card');
    const filterToggle = main.querySelector('.filter-toggle');
    if (filterToggle && filterCard) {
        filterToggle.addEventListener('click', () => {
            const collapsed = filterCard.classList.toggle('advanced-collapsed');
            filterToggle.setAttribute('aria-expanded', String(!collapsed));
        });
    }

    const investRange = main.querySelector('#invest-range');
    const investHidden = main.querySelector('#invest_max_input');
    const investLabel = main.querySelector('#invest-value');
    const profitRange = main.querySelector('#profit-range');
    const profitHidden = main.querySelector('#profit_min_input');
    const profitLabel = main.querySelector('#profit-value');

    const formatMoney = (n) =>
        new Intl.NumberFormat('ru-RU').format(Math.round(Number(n) || 0)) + ' ₽';

    const syncCatalogRange = (rangeEl) => {
        if (!rangeEl) return;
        const max = Number(rangeEl.max) || 0;
        const min = Number(rangeEl.min) || 0;
        const value = Number(rangeEl.value) || 0;
        const hiddenSel = rangeEl.getAttribute("data-range-hidden");
        const labelSel = rangeEl.getAttribute("data-range-label");
        const hidden = hiddenSel ? main.querySelector(hiddenSel) : null;
        const label = labelSel ? main.querySelector(labelSel) : null;
        if (!hidden || !label) return;

        const emptyLabel = rangeEl.getAttribute("data-range-empty-label") || "";
        const prefix = rangeEl.getAttribute("data-range-prefix") || "";
        const emptyValue = rangeEl.getAttribute("data-range-empty-value") ?? "0";
        const emptyAt = rangeEl.getAttribute("data-range-empty-at") || "max";
        const isEmpty = emptyAt === "min" ? value <= min : value >= max;

        if (isEmpty) {
            hidden.value = emptyValue;
            label.textContent = emptyLabel;
        } else {
            hidden.value = String(value);
            label.textContent = prefix + formatMoney(value);
        }
    };

    [investRange, profitRange].forEach((rangeEl) => {
        if (!rangeEl) return;
        rangeEl.addEventListener("input", () => syncCatalogRange(rangeEl));
        syncCatalogRange(rangeEl);
    });

    main.querySelectorAll(".preset-btn[data-invest]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const v = btn.getAttribute("data-invest");
            if (investRange && v) {
                investRange.value = v;
                syncCatalogRange(investRange);
            }
        });
    });

    main.querySelectorAll(".preset-btn[data-profit]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const v = btn.getAttribute("data-profit");
            if (profitRange && v) {
                profitRange.value = v;
                syncCatalogRange(profitRange);
            }
        });
    });

    const sphereSelect = main.querySelector('select[name="sphere"]');
    const categorySelect = main.querySelector('select[name="category"]');
    const syncCategoryOptions = () => {
        if (!sphereSelect || !categorySelect) return;
        const sphere = String(sphereSelect.value || '').trim();
        Array.from(categorySelect.options).forEach((opt) => {
            if (!opt.value) {
                opt.hidden = false;
                return;
            }
            const ds = String(opt.getAttribute('data-sphere') || '').trim();
            if (!sphere) {
                opt.hidden = false;
            } else {
                opt.hidden = ds !== sphere;
            }
        });
        const categoryCustom = $(categorySelect).data('customSelect');
        if (categoryCustom?.refreshFromNative) {
            categoryCustom.refreshFromNative();
        }
    };
    if (sphereSelect) {
        sphereSelect.addEventListener('change', syncCategoryOptions);
        syncCategoryOptions();
    }

    const tagsToggle = main.querySelector('.catalog-tags-toggle');
    const tagsEl = main.querySelector('.catalog-tags.segment-tabs');
    if (tagsToggle && tagsEl) {
        tagsToggle.addEventListener('click', () => {
            const open = tagsEl.classList.toggle('expanded');
            tagsToggle.setAttribute('aria-expanded', String(open));
            tagsToggle.setAttribute('data-state', open ? 'open' : '');
        });
    }

    const modal = document.getElementById('filter-modal');
    const sheetBody = modal ? modal.querySelector('.filter-sheet-body') : null;
    const openBtn = main.querySelector('[data-filter-open]');
    const filterForm = main.querySelector('#franchises-catalog-filters');
    if (modal && sheetBody && filterCard && openBtn && filterForm) {
        const setModal = (open) => {
            modal.classList.toggle('active', open);
            modal.setAttribute('aria-hidden', String(!open));
            document.body.classList.toggle('modal-open', open);
            if (open) {
                sheetBody.appendChild(filterCard);
            } else {
                filterForm.insertBefore(filterCard, filterForm.firstElementChild);
            }
        };
        openBtn.addEventListener('click', () => setModal(true));
        modal.querySelectorAll('[data-filter-close]').forEach((el) => {
            el.addEventListener('click', () => setModal(false));
        });
    }
});

/* ===== header menu (разметка в PHP) ===== */
(function ($) {
    if (!$) return;

    const header = document.querySelector('.site-header');
    if (!header) return;

    const setHeaderState = () => {
        header.classList.toggle('scrolled', window.scrollY > 10 || header.classList.contains('dropdown-open'));
    };
    window.setHeaderState = setHeaderState;
    setHeaderState();
    $(window).on('scroll', setHeaderState);

    const $menu = $('#mobile-menu');
    const setMenuOpen = (isOpen) => {
        if (!$menu.length) return;
        $menu.toggleClass('open', isOpen).attr('aria-hidden', String(!isOpen));
        $('.menu-toggle').attr('aria-expanded', String(isOpen));
        $('body').toggleClass('modal-open', isOpen);
    };

    $(document)
        .on('click', '.menu-toggle', function (event) {
            event.preventDefault();
            setMenuOpen(!$menu.hasClass('open'));
        })
        .on('click', '#mobile-menu', function (event) {
            const $target = $(event.target);
            if ($target.is('#mobile-menu') || $target.closest('[data-mobile-close]').length || $target.is('a')) {
                setMenuOpen(false);
            }
        })
        .on('click', '[data-mobile-acc-trigger]', function (event) {
            event.preventDefault();
            const key = $(this).attr('data-mobile-acc-trigger');
            if (!key || !$menu.length) return;
            const $block = $menu.find(`[data-mobile-acc="${key}"]`);
            const willOpen = !$block.hasClass('open');
            $menu.find('[data-mobile-acc]').each(function () {
                const otherKey = $(this).attr('data-mobile-acc');
                if (otherKey && otherKey !== key) {
                    $(this).removeClass('open');
                    $menu.find(`[data-mobile-acc-trigger="${otherKey}"]`).attr('aria-expanded', 'false');
                }
            });
            $block.toggleClass('open', willOpen);
            $(this).attr('aria-expanded', String(willOpen));
        })
        .on('click', '[data-categories-backdrop]', function () {
            $('[data-categories-dropdown], [data-collections-dropdown]').removeClass('is-open open');
            if (typeof window.syncHeaderDropdownState === 'function') {
                window.syncHeaderDropdownState();
            }
        })
        .on('click', '[data-collections-trigger]', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const $dropdown = $('[data-collections-dropdown]');
            if (!$dropdown.length) return;
            window.clearCollectionsDropdownTimer?.();
            const willOpen = !$dropdown.hasClass('is-open');
            if (willOpen) {
                $('[data-categories-dropdown]').removeClass('is-open open');
            }
            $dropdown.toggleClass('is-open', willOpen);
            $(this).attr('aria-expanded', String(willOpen));
            if (typeof window.syncHeaderDropdownState === 'function') {
                window.syncHeaderDropdownState();
            }
            if (!willOpen) {
                $(this).blur();
            }
        })
        .on('click', function (event) {
            const $collections = $('[data-collections-dropdown]');
            if (!$collections.length || !$collections.hasClass('is-open')) return;
            if ($(event.target).closest('[data-collections-dropdown]').length) return;
            $collections.removeClass('is-open');
            $('[data-collections-trigger]').attr('aria-expanded', 'false');
            if (typeof window.syncHeaderDropdownState === 'function') {
                window.syncHeaderDropdownState();
            }
        });

    $(document).on('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if ($menu.hasClass('open')) setMenuOpen(false);
        const $collections = $('[data-collections-dropdown]');
        if ($collections.hasClass('is-open')) {
            $collections.removeClass('is-open');
            $('[data-collections-trigger]').attr('aria-expanded', 'false').blur();
            if (typeof window.syncHeaderDropdownState === 'function') {
                window.syncHeaderDropdownState();
            }
        }
        const $categoriesTrigger = $('[data-categories-trigger]');
        if ($categoriesTrigger.attr('aria-expanded') === 'true') {
            $('[data-categories-dropdown]').removeClass('is-open');
            $categoriesTrigger.attr('aria-expanded', 'false').blur();
            if (typeof window.syncHeaderDropdownState === 'function') {
                window.syncHeaderDropdownState();
            }
        }
    });

    const setupCategoriesDropdown = () => {
        const dropdown = document.querySelector('[data-categories-dropdown]');
        if (!dropdown) return;

        const trigger = dropdown.querySelector('[data-categories-trigger]');
        const panel = dropdown.querySelector('[data-categories-panel]');
        const list = dropdown.querySelector('[data-categories-list]');
        const panelsWrap = dropdown.querySelector('[data-categories-panels]');
        if (!trigger || !panel || !list || !panelsWrap) return;

        const panels = Array.from(panelsWrap.querySelectorAll('[data-categories-panel]'));
        const items = Array.from(list.querySelectorAll('.categories-item'));
        if (!panels.length || !items.length) return;

        const setActive = (index) => {
            const i = Math.max(0, Math.min(index, panels.length - 1));
            items.forEach((btn, idx) => btn.classList.toggle('active', idx === i));
            panels.forEach((panelEl, idx) => {
                const active = idx === i;
                panelEl.classList.toggle('is-active', active);
                if (active) {
                    panelEl.removeAttribute('hidden');
                } else {
                    panelEl.setAttribute('hidden', '');
                }
            });
        };

        list.addEventListener('mouseover', (event) => {
            const btn = event.target.closest('.categories-item');
            if (!btn) return;
            const idx = Number(btn.dataset.index);
            if (!Number.isNaN(idx)) setActive(idx);
        });

        list.addEventListener('focusin', (event) => {
            const btn = event.target.closest('.categories-item');
            if (!btn) return;
            const idx = Number(btn.dataset.index);
            if (!Number.isNaN(idx)) setActive(idx);
        });

        const setDropdownOpen = (isOpen) => {
            dropdown.classList.toggle('is-open', isOpen);
            trigger.setAttribute('aria-expanded', String(isOpen));
            header.classList.toggle('dropdown-open', isOpen);
            setHeaderState();
            if (!isOpen) trigger.blur();
        };

        let dropdownCloseTimer = null;
        const clearDropdownTimer = () => {
            if (dropdownCloseTimer) {
                window.clearTimeout(dropdownCloseTimer);
                dropdownCloseTimer = null;
            }
        };
        const openDropdown = () => {
            clearDropdownTimer();
            document.querySelector('[data-collections-dropdown]')?.classList.remove('is-open', 'open');
            setDropdownOpen(true);
        };
        const closeDropdownSoon = () => {
            clearDropdownTimer();
            dropdownCloseTimer = window.setTimeout(() => setDropdownOpen(false), 140);
        };

        dropdown.addEventListener('mouseenter', openDropdown);
        dropdown.addEventListener('mouseleave', closeDropdownSoon);
        panel.addEventListener('mouseenter', openDropdown);
        panel.addEventListener('mouseleave', closeDropdownSoon);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && trigger.getAttribute('aria-expanded') === 'true') {
                setDropdownOpen(false);
                trigger.blur();
            }
        });

        setActive(0);
    };

    const setupHeaderDropdowns = () => {
        const categoriesDropdown = document.querySelector('[data-categories-dropdown]');
        const collectionsDropdown = document.querySelector('[data-collections-dropdown]');
        const categoriesTrigger = document.querySelector('[data-categories-trigger]');
        const collectionsTrigger = document.querySelector('[data-collections-trigger]');
        const categoriesPanel = document.querySelector('[data-categories-panel]');
        const collectionsPanel = document.querySelector('[data-collections-panel]');

        const backdrop = document.querySelector('[data-categories-backdrop]');

        const isDropdownOpen = (el) => !!el && (el.classList.contains('is-open') || el.classList.contains('open'));

        const syncPanelTop = () => {
            if (!(categoriesPanel instanceof HTMLElement)) return;
            const headerInner = header.querySelector('.header-inner');
            const anchor = headerInner instanceof HTMLElement ? headerInner : header;
            const headerBottom = Math.round(anchor.getBoundingClientRect().bottom);
            const panelTop = Math.max(0, headerBottom - 1);
            categoriesPanel.style.setProperty('top', `${panelTop}px`, 'important');
            if (collectionsPanel instanceof HTMLElement) {
                collectionsPanel.style.setProperty('top', `${panelTop}px`, 'important');
            }
        };

        const syncOpenState = () => {
            syncPanelTop();
            const isCategoriesOpen = isDropdownOpen(categoriesDropdown);
            const isCollectionsOpen = isDropdownOpen(collectionsDropdown);
            const opened = isCategoriesOpen || isCollectionsOpen;
            header.classList.toggle('dropdown-open', opened);
            if (backdrop) {
                backdrop.classList.toggle('open', opened);
                backdrop.setAttribute('aria-hidden', String(!opened));
            }
            if (categoriesTrigger) {
                categoriesTrigger.setAttribute('aria-expanded', String(isCategoriesOpen));
            }
            if (collectionsTrigger) {
                collectionsTrigger.setAttribute('aria-expanded', String(isCollectionsOpen));
            }
            setHeaderState();
        };

        window.syncHeaderDropdownState = syncOpenState;
        syncOpenState();
        const observer = new MutationObserver(syncOpenState);
        if (categoriesDropdown) {
            observer.observe(categoriesDropdown, { attributes: true, attributeFilter: ['class'] });
        }
        if (collectionsDropdown) {
            observer.observe(collectionsDropdown, { attributes: true, attributeFilter: ['class'] });
        }
        window.addEventListener('resize', syncPanelTop);
        window.addEventListener('scroll', syncPanelTop, { passive: true });

        if (collectionsDropdown && collectionsTrigger && collectionsPanel) {
            let collectionsCloseTimer = null;
            const clearCollectionsTimer = () => {
                if (collectionsCloseTimer) {
                    window.clearTimeout(collectionsCloseTimer);
                    collectionsCloseTimer = null;
                }
            };
            window.clearCollectionsDropdownTimer = clearCollectionsTimer;
            const setCollectionsOpen = (isOpen) => {
                if (isOpen) {
                    categoriesDropdown?.classList.remove('is-open', 'open');
                }
                collectionsDropdown.classList.toggle('is-open', isOpen);
                collectionsTrigger.setAttribute('aria-expanded', String(isOpen));
                syncOpenState();
                if (!isOpen) collectionsTrigger.blur();
            };
            const openCollections = () => {
                clearCollectionsTimer();
                setCollectionsOpen(true);
            };
            const closeCollectionsSoon = () => {
                clearCollectionsTimer();
                collectionsCloseTimer = window.setTimeout(() => setCollectionsOpen(false), 140);
            };

            collectionsDropdown.addEventListener('mouseenter', openCollections);
            collectionsDropdown.addEventListener('mouseleave', closeCollectionsSoon);
            collectionsPanel.addEventListener('mouseenter', openCollections);
            collectionsPanel.addEventListener('mouseleave', closeCollectionsSoon);
            collectionsDropdown.addEventListener('focusin', openCollections);
            collectionsDropdown.addEventListener('focusout', (event) => {
                const next = event.relatedTarget;
                if (next instanceof Node && collectionsDropdown.contains(next)) return;
                closeCollectionsSoon();
            });

            const closeCollectionsIfOpen = () => {
                if (isDropdownOpen(collectionsDropdown)) {
                    setCollectionsOpen(false);
                }
            };
            categoriesDropdown?.addEventListener('mouseenter', closeCollectionsIfOpen);
            categoriesDropdown?.addEventListener('focusin', closeCollectionsIfOpen);
        }
    };

    const initHeaderMenu = () => {
        setupCategoriesDropdown();
        setupHeaderDropdowns();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderMenu, { once: true });
    } else {
        initHeaderMenu();
    }
})(window.jQuery);

