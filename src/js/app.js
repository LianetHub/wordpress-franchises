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

            $root.toggleClass("is-error", isError).toggleClass("is-success", !isError);

            $root.find("[data-lead-feedback-success-text]").toggle(!isError);
            const $errorText = $root.find("[data-lead-feedback-error-text]");
            $errorText.toggle(isError);
            $root.find("[data-lead-feedback-success-close]").toggle(!isError);
            $root.find("[data-lead-feedback-error-close]").toggle(isError);
            if (isError && message) {
                $root.find("[data-lead-feedback-error-message]").text(message);
            }

            $root.find("[data-lead-feedback-success-block]").prop("hidden", isError);
            $root.find("[data-lead-feedback-error-block]").prop("hidden", !isError);

            const $mark = isError
                ? $root.find("[data-lead-feedback-error-block]")
                : $root.find("[data-lead-feedback-success-block]");

            if ($mark.length) {
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

        function initHorizontalStripSwiper(strip, { prevBtn, nextBtn, paginationEl, slidesPerView = "auto", spaceBetween = 14, autoplay = false, breakpoints = null } = {}) {
            if (typeof Swiper === "undefined" || !strip || strip.swiper) return null;

            const slideCount = strip.querySelectorAll(".swiper-slide").length;
            if (!slideCount) return null;

            const config = {
                speed: 400,
                slidesPerView,
                spaceBetween,
                watchOverflow: true,
            };

            if (breakpoints) {
                config.breakpoints = breakpoints;
            }
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
                paginationEl: reviewsWrap?.closest(".reviews-section")?.querySelector(".reviews-dots"),
                spaceBetween: 20,
                watchOverflow: true,
                slidesPerView: 1,
                breakpoints: {
                    601: { slidesPerView: 2, spaceBetween: 16 },
                    901: { slidesPerView: 3, spaceBetween: 20 },
                },
            });
        }



        // sliders


        if ($('.hero__slider').length) {
            new Swiper('.hero__slider', {
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


        const initYandexMap = () => {
            const mapContainer = document.getElementById('map');
            if (!mapContainer) return;

            let myMap;
            let placemark;
            let resizeTimer;
            const iconPath = mapContainer.dataset.icon || '';

            const getIconParams = () => {
                const width = window.innerWidth;
                let size = [104, 116];

                if (width <= 767) {
                    size = [78, 88];
                } else if (width <= 1024) {
                    size = [67, 75];
                }

                return {
                    size,
                    offset: [-(size[0] / 2), -size[1]]
                };
            };

            const applyPlacemarkIcon = () => {
                if (!placemark || !iconPath) return;

                const { size, offset } = getIconParams();
                placemark.options.set('iconImageSize', size);
                placemark.options.set('iconImageOffset', offset);
            };

            const init = () => {
                const rawCoords = mapContainer.dataset.coords;
                if (!rawCoords) return;

                const coords = rawCoords
                    .split(',')
                    .map((item) => parseFloat(item.trim()))
                    .filter((value) => Number.isFinite(value));

                if (coords.length < 2) return;

                const zoom = parseInt(mapContainer.dataset.zoom, 10) || 16;
                const iconParams = getIconParams();

                myMap = new ymaps.Map(mapContainer, {
                    center: coords,
                    zoom,
                    controls: ['zoomControl']
                });

                myMap.behaviors.disable('scrollZoom');

                const placemarkOptions = {};

                if (iconPath) {
                    Object.assign(placemarkOptions, {
                        iconLayout: 'default#image',
                        iconImageHref: iconPath,
                        iconImageSize: iconParams.size,
                        iconImageOffset: iconParams.offset
                    });
                }

                placemark = new ymaps.Placemark(coords, {}, placemarkOptions);
                myMap.geoObjects.add(placemark);
                mapContainer.classList.add('is-loaded');

                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(applyPlacemarkIcon, 150);
                });
            };

            const loadScript = () => {
                if (typeof ymaps !== 'undefined') {
                    ymaps.ready(init);
                    return;
                }

                const apiKey = mapContainer.dataset.apikey || '';
                const script = document.createElement('script');
                script.src = apiKey
                    ? `https://api-maps.yandex.ru/2.1/?apikey=${encodeURIComponent(apiKey)}&lang=ru_RU`
                    : 'https://api-maps.yandex.ru/2.1/?lang=ru_RU';
                script.type = 'text/javascript';
                script.async = true;
                script.onload = () => {
                    ymaps.ready(init);
                };
                document.head.appendChild(script);
            };

            if (!('IntersectionObserver' in window)) {
                loadScript();
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        loadScript();
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '200px'
            });

            observer.observe(mapContainer);
        };

        initYandexMap();


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

        const openBtn = document.querySelector("[data-collections-open]");

        const activate = (key) => {
            let activeUrl = "";
            chips.querySelectorAll("[data-collection]").forEach((btn) => {
                const active = btn.getAttribute("data-collection") === key;
                btn.classList.toggle("active", active);
                btn.setAttribute("aria-pressed", String(active));
                if (active) {
                    activeUrl = btn.getAttribute("data-collection-url") || "";
                }
            });
            document.querySelectorAll("[data-collection-panel]").forEach((panel) => {
                const active = panel.getAttribute("data-collection-panel") === key;
                panel.hidden = !active;
            });
            if (openBtn && activeUrl) {
                openBtn.href = activeUrl;
            }
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
                slidesPerView: 3,
                spaceBetween: 8,
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
                    641: {
                        spaceBetween: 10,
                        slidesPerView: 5,
                    },
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

    const filterForm = document.getElementById('franchises-catalog-filters');
    const filterRoot = filterForm || main;

    const filterCard = filterRoot.querySelector('.filter-card');
    const filterToggle = filterRoot.querySelector('.filter-toggle');
    const filterAdvanced = filterRoot.querySelector('.filter-advanced');
    const filterToggleLabel = filterToggle?.querySelector('.filter-toggle__text');

    const setFilterToggleLabel = (collapsed) => {
        if (!filterToggle || !filterToggleLabel) return;
        const labelShow = filterToggle.getAttribute('data-label-show') || 'Показать дополнительные фильтры';
        const labelHide = filterToggle.getAttribute('data-label-hide') || 'Скрыть дополнительные фильтры';
        filterToggleLabel.textContent = collapsed ? labelShow : labelHide;
        filterToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    };

    if (filterToggle && filterCard && filterAdvanced && window.jQuery) {
        const $advanced = window.jQuery(filterAdvanced);
        const isCollapsed = () => filterCard.classList.contains('advanced-collapsed');

        setFilterToggleLabel(isCollapsed());

        filterToggle.addEventListener('click', () => {
            if (isCollapsed()) {
                filterCard.classList.remove('advanced-collapsed');
                $advanced.stop(true, true).slideDown(280, () => {
                    setFilterToggleLabel(false);
                });
            } else {
                $advanced.stop(true, true).slideUp(280, () => {
                    filterCard.classList.add('advanced-collapsed');
                    setFilterToggleLabel(true);
                });
            }
        });
    }

    const investRange = filterRoot.querySelector('#invest-range');
    const profitRange = filterRoot.querySelector('#profit-range');

    const formatMoney = (n) =>
        new Intl.NumberFormat('ru-RU').format(Math.round(Number(n) || 0)) + ' ₽';

    const syncCatalogRange = (rangeEl) => {
        if (!rangeEl) return;
        const max = Number(rangeEl.max) || 0;
        const min = Number(rangeEl.min) || 0;
        const value = Number(rangeEl.value) || 0;
        const hiddenSel = rangeEl.getAttribute("data-range-hidden");
        const labelSel = rangeEl.getAttribute("data-range-label");
        const hidden = hiddenSel ? document.querySelector(hiddenSel) : null;
        const label = labelSel ? document.querySelector(labelSel) : null;
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

    filterRoot.querySelectorAll(".preset-btn[data-invest]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const v = btn.getAttribute("data-invest");
            if (investRange && v) {
                investRange.value = v;
                syncCatalogRange(investRange);
            }
        });
    });

    filterRoot.querySelectorAll(".preset-btn[data-profit]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const v = btn.getAttribute("data-profit");
            if (profitRange && v) {
                profitRange.value = v;
                syncCatalogRange(profitRange);
            }
        });
    });

    const sphereSelect = filterForm?.querySelector('select[name="sphere"]');
    const categorySelect = filterForm?.querySelector('select[name="category"]');

    const refreshNativeSelect = (selectEl) => {
        if (!selectEl) return;
        const custom = $(selectEl).data('customSelect');
        if (custom?.refreshFromNative) {
            custom.refreshFromNative();
        }
    };

    const syncCategoryOptions = () => {
        if (!sphereSelect || !categorySelect) return;
        const sphere = String(sphereSelect.value || '').trim();
        Array.from(categorySelect.options).forEach((opt) => {
            if (!opt.value) {
                opt.hidden = false;
                return;
            }
            const ds = String(opt.getAttribute('data-sphere') || '').trim();
            opt.hidden = Boolean(sphere) && ds !== sphere;
        });
        refreshNativeSelect(categorySelect);
    };

    const syncSphereFromCategory = () => {
        if (!sphereSelect || !categorySelect) return;
        const categoryOpt = categorySelect.selectedOptions?.[0];
        if (!categoryOpt?.value) return;
        const sphereName = String(categoryOpt.getAttribute('data-sphere') || '').trim();
        if (!sphereName) return;
        const match = Array.from(sphereSelect.options).find((opt) => opt.value === sphereName);
        if (!match) return;
        sphereSelect.value = sphereName;
        refreshNativeSelect(sphereSelect);
    };

    const updateCatalogFormAction = () => {
        if (!filterForm) return;
        const shopUrl = String(filterForm.getAttribute('data-shop-url') || '').trim();
        const categoryOpt = categorySelect?.selectedOptions?.[0];
        const sphereOpt = sphereSelect?.selectedOptions?.[0];
        const categoryUrl = categoryOpt ? String(categoryOpt.getAttribute('data-url') || '').trim() : '';
        const sphereUrl = sphereOpt ? String(sphereOpt.getAttribute('data-url') || '').trim() : '';
        if (categoryUrl) {
            filterForm.action = categoryUrl;
        } else if (sphereUrl) {
            filterForm.action = sphereUrl;
        } else if (shopUrl) {
            filterForm.action = shopUrl;
        }
    };

    const onSphereChange = () => {
        const sphere = String(sphereSelect?.value || '').trim();
        const categoryOpt = categorySelect?.selectedOptions?.[0];
        if (categoryOpt?.value) {
            const ds = String(categoryOpt.getAttribute('data-sphere') || '').trim();
            if (sphere && ds !== sphere) {
                categorySelect.value = '';
            }
        }
        syncCategoryOptions();
        updateCatalogFormAction();
    };

    if (sphereSelect) {
        $(sphereSelect).on('change', onSphereChange);
        onSphereChange();
    }
    if (categorySelect) {
        $(categorySelect).on('change', () => {
            syncSphereFromCategory();
            syncCategoryOptions();
            updateCatalogFormAction();
        });
    }
    updateCatalogFormAction();

    const tagsToggle = main.querySelector('.catalog-tags-toggle');
    const tagsEl = main.querySelector('.catalog-tags.segment-tabs');
    if (tagsToggle && tagsEl) {
        tagsToggle.addEventListener('click', () => {
            const open = tagsEl.classList.toggle('expanded');
            tagsToggle.setAttribute('aria-expanded', String(open));
            tagsToggle.setAttribute('data-state', open ? 'open' : '');
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

    const setupMobileSearchBarAutoHide = () => {
        const mobileSearchBar = document.querySelector('.mobile-search-bar');
        if (!mobileSearchBar) return;

        const mobileMq = window.matchMedia('(max-width: 600px)');
        const scrollTopThreshold = 16;
        const directionDeltaMin = 4;
        const wheelDeltaMin = 8;

        const getScrollY = () =>
            window.pageYOffset ||
            document.documentElement.scrollTop ||
            document.body.scrollTop ||
            0;

        let lastScrollY = getScrollY();
        let scrollTicking = false;
        let touchLastY = null;

        const isActive = () => mobileMq.matches && !$menu.hasClass('open');

        const setMobileSearchHidden = (hidden) => {
            mobileSearchBar.classList.toggle('is-hidden', hidden);
            mobileSearchBar.setAttribute('aria-hidden', hidden ? 'true' : 'false');
        };

        const applyDirection = (scrollingDown) => {
            if (!isActive()) return;

            if (getScrollY() <= scrollTopThreshold) {
                setMobileSearchHidden(false);
                return;
            }

            setMobileSearchHidden(scrollingDown);
        };

        const syncFromScrollPosition = () => {
            if (!isActive()) return;

            const y = getScrollY();

            if (y <= scrollTopThreshold) {
                setMobileSearchHidden(false);
                lastScrollY = y;
                return;
            }

            const delta = y - lastScrollY;
            lastScrollY = y;

            if (Math.abs(delta) < directionDeltaMin) return;

            applyDirection(delta > 0);
        };

        const scheduleScrollSync = () => {
            if (scrollTicking) return;
            scrollTicking = true;
            requestAnimationFrame(() => {
                syncFromScrollPosition();
                scrollTicking = false;
            });
        };

        const onWheel = (event) => {
            if (!isActive()) return;

            const { deltaY } = event;
            if (Math.abs(deltaY) < wheelDeltaMin) return;

            applyDirection(deltaY > 0);
            lastScrollY = getScrollY();
        };

        const onTouchStart = (event) => {
            if (!isActive()) return;
            touchLastY = event.touches[0]?.clientY ?? null;
        };

        const onTouchMove = (event) => {
            if (!isActive() || touchLastY === null) return;

            const y = event.touches[0]?.clientY;
            if (y == null) return;

            const delta = touchLastY - y;
            touchLastY = y;

            if (Math.abs(delta) < directionDeltaMin) return;

            applyDirection(delta > 0);
        };

        const onTouchEnd = () => {
            touchLastY = null;
            lastScrollY = getScrollY();
        };

        window.addEventListener('wheel', onWheel, { passive: true });
        window.addEventListener('scroll', scheduleScrollSync, { passive: true });
        document.addEventListener('scroll', scheduleScrollSync, { passive: true });
        document.addEventListener('touchstart', onTouchStart, { passive: true });
        document.addEventListener('touchmove', onTouchMove, { passive: true });
        document.addEventListener('touchend', onTouchEnd, { passive: true });
        document.addEventListener('touchcancel', onTouchEnd, { passive: true });

        mobileMq.addEventListener('change', () => {
            if (!mobileMq.matches) {
                setMobileSearchHidden(false);
            }
            lastScrollY = getScrollY();
            touchLastY = null;
        });
    };

    setupMobileSearchBarAutoHide();

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

        const getDropdownGap = () => {
            const raw = getComputedStyle(document.documentElement)
                .getPropertyValue('--header-dropdown-gap')
                .trim();
            const gap = parseFloat(raw);
            return Number.isFinite(gap) ? gap : 12;
        };

        const syncPanelTop = () => {
            if (!(categoriesPanel instanceof HTMLElement)) return;
            const headerInner = header.querySelector('.header-inner');
            const anchor = headerInner instanceof HTMLElement ? headerInner : header;
            const headerBottom = Math.round(anchor.getBoundingClientRect().bottom);
            const panelTop = Math.max(0, headerBottom + getDropdownGap());
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

    const setupFiboSearchHeader = () => {
        if (typeof franchisesFiboSearch === 'undefined') {
            return;
        }

        const shopUrl = franchisesFiboSearch.shopUrl || '';
        const submitLabel = franchisesFiboSearch.i18n?.submit || 'Найти';
        const placeholder = franchisesFiboSearch.i18n?.placeholder || 'Поиск по франшизам';
        const searchIconHtml =
            '<svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path></svg>';

        const syncSearchIcon = (form) => {
            const wrapp = form.querySelector('.dgwt-wcas-sf-wrapp');
            if (!wrapp || wrapp.querySelector('.search-icon')) {
                return;
            }
            const input = wrapp.querySelector('.dgwt-wcas-search-input, input[type="search"]');
            if (input) {
                input.insertAdjacentHTML('beforebegin', searchIconHtml);
            }
        };

        const syncSearchForm = (form) => {
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (shopUrl) {
                form.setAttribute('action', shopUrl);
            }

            syncSearchIcon(form);

            const input = form.querySelector('.dgwt-wcas-search-input, input[type="search"]');
            if (input) {
                input.setAttribute('name', 'q');
                if (!input.getAttribute('placeholder')) {
                    input.setAttribute('placeholder', placeholder);
                }
            }

            const submit = form.querySelector('.dgwt-wcas-search-submit, button[type="submit"]');
            if (submit && submitLabel) {
                submit.setAttribute('aria-label', submitLabel);
                if (!submit.textContent.trim()) {
                    submit.textContent = submitLabel;
                }
            }
        };

        const syncAllHeaderSearchForms = () => {
            document
                .querySelectorAll('[data-header-search] .dgwt-wcas-search-form, .header-search--fibosearch .dgwt-wcas-search-form')
                .forEach(syncSearchForm);
        };

        const actions = header.querySelector('.header-actions');
        const desktopMq = window.matchMedia('(min-width: 901px)');

        const setSearchExpanded = (expanded) => {
            if (!desktopMq.matches) {
                header.classList.remove('search-expanded');
                return;
            }
            header.classList.toggle('search-expanded', expanded);
        };

        const isHeaderSearchField = (node) =>
            node instanceof Element &&
            !!node.closest('.header-search-wrap, .header-search--fibosearch') &&
            node.matches('.dgwt-wcas-search-input, input[type="search"]');

        if (actions) {
            actions.addEventListener(
                'focusin',
                (event) => {
                    if (isHeaderSearchField(event.target)) {
                        setSearchExpanded(true);
                    }
                },
                true
            );

            actions.addEventListener(
                'focusout',
                () => {
                    window.setTimeout(() => {
                        const focused = actions.querySelector('.dgwt-wcas-search-input:focus, input[type="search"]:focus');
                        if (!focused) {
                            setSearchExpanded(false);
                        }
                    }, 0);
                },
                true
            );
        }

        document.addEventListener('fibosearch/open', () => setSearchExpanded(true));
        document.addEventListener('fibosearch/close', () => {
            window.setTimeout(() => {
                const focused = actions?.querySelector('.dgwt-wcas-search-input:focus, input[type="search"]:focus');
                if (!focused) {
                    setSearchExpanded(false);
                }
            }, 0);
        });

        desktopMq.addEventListener('change', () => {
            if (!desktopMq.matches) {
                setSearchExpanded(false);
            }
        });

        syncAllHeaderSearchForms();
        $(document).on('fibosearch/open fibosearch/show-suggestions fibosearch/show-pre-suggestions', syncAllHeaderSearchForms);

        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(syncAllHeaderSearchForms);
            document.querySelectorAll('[data-header-search]').forEach((root) => {
                observer.observe(root, { childList: true, subtree: true });
            });
        }
    };

    const initHeaderMenu = () => {
        setupCategoriesDropdown();
        setupHeaderDropdowns();
        setupFiboSearchHeader();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderMenu, { once: true });
    } else {
        initHeaderMenu();
    }
})(window.jQuery);

/* ===== Motion: reveal / intro ===== */
(() => {
    const setupModernAnimations = () => {
        const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches;
        if (reducedMotion) return;

        const path = (window.location.pathname.split('/').pop() || '').toLowerCase();
        const isHomePage =
            document.body.classList.contains('home') ||
            path === '' ||
            path === 'index' ||
            path === 'index.html';
        const isCatalogPage =
            !!document.querySelector('main.wrap.catalog-page') ||
            path === 'catalog' ||
            path === 'catalog.html';
        const isFranchisePage =
            document.body.classList.contains('woocommerce-single-franchise') ||
            document.body.classList.contains('single-product') ||
            /^franchise(?:-[a-z0-9-]+)?\.html$/i.test(path);

        if (!document.getElementById('shared-motion-style')) {
            const style = document.createElement('style');
            style.id = 'shared-motion-style';
            style.textContent = `
        @media (prefers-reduced-motion: no-preference) {
          .fx-intro,
          .fx-reveal {
            opacity: 0;
            transform: translate3d(0, var(--fx-shift, 30px), 0);
            will-change: transform, opacity;
            backface-visibility: hidden;
            transition:
              opacity var(--fx-opacity-duration, .42s) cubic-bezier(.22,.61,.36,1),
              transform var(--fx-transform-duration, .58s) cubic-bezier(.22,.61,.36,1);
            transition-delay: var(--fx-delay, 0ms);
          }

          .fx-intro {
            --fx-shift: 20px;
            --fx-opacity-duration: .4s;
            --fx-transform-duration: .52s;
          }

          .fx-reveal {
            --fx-shift: 34px;
          }

          .fx-intro.is-visible,
          .fx-reveal.is-visible {
            opacity: 1;
            transform: translate3d(0, 0, 0);
          }
        }
      `;
            document.head.appendChild(style);
        }

        const unique = (list) => Array.from(new Set(list.filter(Boolean)));
        const introTargets = unique([
            document.querySelector('.header-inner'),
            !isFranchisePage ? document.querySelector('.breadcrumbs') : null,
            !isFranchisePage ? document.querySelector('.page-title') : null,
            document.querySelector('.hero-title'),
            document.querySelector('.hero-sub'),
            document.querySelector('.catalog-hero'),
            !isFranchisePage ? document.querySelector('.franchise-head') : null,
        ]);

        introTargets.forEach((el, index) => {
            el.classList.add('fx-intro');
            el.style.setProperty('--fx-delay', `${index * 55}ms`);
        });

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                introTargets.forEach((el) => el.classList.add('is-visible'));
            });
        });

        const revealSelector = [
            '.wrap > section',
            '.catalog-layout',
            '.catalog-toolbar',
            '.pagination',
            '.pagination .page-btn',
            '.pagination .page-ellipsis',
            '.sidebar-block',
            '.catalog-cards .popular-card',
            '.popular-grid .popular-card',
            '.preview-grid .preview-card',
            '.info-grid .info-item',
            '.franchise-layout > .main-column',
            '.franchise-layout > .side-column',
            '.card',
            '.help-card',
            '.faq-item',
            '.collection-block',
            '.popular-section',
        ].join(', ');

        const prepared = new WeakSet();
        let sequence = 0;

        const observer = 'IntersectionObserver' in window
            ? new IntersectionObserver((entries, obs) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                });
            }, { threshold: 0.14, rootMargin: '0px 0px -8% 0px' })
            : null;

        const prepareEl = (el) => {
            if (!(el instanceof HTMLElement)) return;
            if (prepared.has(el)) return;
            if (el.closest('.about-stats')) return;
            if (el.closest('.mobile-menu') || el.closest('.nav-dropdown-panel')) return;
            if (
                isCatalogPage &&
                (el.closest('.catalog-cards') || el.closest('.pagination') || el.closest('.catalog-toolbar'))
            ) return;
            if (
                isFranchisePage &&
                (
                    el.matches('.page-head, .info-row, .cta-card, .content-section, .faq-section, .ask-box, .toc-desktop, .toc-mobile, .side-meta, .key-list') ||
                    el.closest('.page-head, .info-row, .cta-card, .content-section, .faq-section, .ask-box, .toc-desktop, .toc-mobile, .side-meta, .key-list')
                )
            ) return;
            if (
                isHomePage &&
                (el.matches('.popular-section .popular-card, [data-collections-section] .popular-card') ||
                    el.closest('.popular-section .popular-card, [data-collections-section] .popular-card'))
            ) return;
            if (window.getComputedStyle(el).display === 'none') return;
            prepared.add(el);

            el.classList.add('fx-reveal');
            if (!el.style.getPropertyValue('--fx-delay')) {
                const delay = Math.min(sequence, 10) * 50;
                el.style.setProperty('--fx-delay', `${delay}ms`);
            }
            sequence += 1;

            const rect = el.getBoundingClientRect();
            const mostlyVisible = rect.top < window.innerHeight * 1.02;
            if (mostlyVisible || !observer) {
                requestAnimationFrame(() => el.classList.add('is-visible'));
                return;
            }
            observer.observe(el);
        };

        const registerTargets = (root = document) => {
            if (root instanceof HTMLElement && root.matches(revealSelector)) {
                prepareEl(root);
            }
            root.querySelectorAll?.(revealSelector).forEach(prepareEl);
        };

        registerTargets(document);
        window.setTimeout(() => registerTargets(document), 320);
        window.setTimeout(() => registerTargets(document), 1100);
        window.addEventListener('load', () => registerTargets(document), { once: true });

        const wrap = document.querySelector('.wrap') || document.body;
        if (wrap instanceof HTMLElement && 'MutationObserver' in window) {
            const mo = new MutationObserver((mutations) => {
                mutations.forEach((m) => {
                    m.addedNodes.forEach((node) => {
                        if (node instanceof HTMLElement) registerTargets(node);
                    });
                });
            });
            mo.observe(wrap, { childList: true, subtree: true });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupModernAnimations, { once: true });
    } else {
        setupModernAnimations();
    }
})();

