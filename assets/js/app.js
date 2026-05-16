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

    window.__showLeadFeedback = (text, isError = false) => {
        const node = document.querySelector("[data-lead-feedback]");
        const textNode = node?.querySelector("[data-lead-feedback-text]");
        const card = node?.querySelector(".lead-feedback-card");
        const mark = node?.querySelector("[data-lead-feedback-mark]");
        if (!node || !textNode || !card) return;

        const successHtml = "<strong>Заявка отправлена</strong><span>В ближайшее время менеджер свяжется с вами.</span>";
        if (!isError && /заявка отправлена/i.test(String(text || ""))) {
            textNode.innerHTML = successHtml;
        } else {
            textNode.textContent = text;
        }
        card.classList.toggle("is-error", isError);
        card.classList.toggle("is-success", !isError);
        if (mark) {
            mark.classList.remove("animate");
            if (!isError) {
                void mark.offsetWidth;
                mark.classList.add("animate");
            }
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

    function wrapChildrenAsSwiperSlides(container) {
        if (!container || container.classList.contains("swiper-initialized")) return null;
        if (container.querySelector(":scope > .swiper-wrapper")) return container;

        const wrapper = document.createElement("div");
        wrapper.className = "swiper-wrapper";
        Array.from(container.children).forEach((child) => {
            child.classList.add("swiper-slide");
            wrapper.appendChild(child);
        });
        container.classList.add("swiper");
        container.appendChild(wrapper);
        return container;
    }

    function initHorizontalStripSwiper(strip, { prevBtn, nextBtn, paginationEl, slidesPerView = "auto", spaceBetween = 14, autoplay = false } = {}) {
        if (typeof Swiper === "undefined" || !strip || strip.swiper) return null;

        wrapChildrenAsSwiperSlides(strip);

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
        showInlineFancybox("#success-submitting");
    }

    function getErrorSubmitting() {
        Fancybox.close();
        showInlineFancybox("#error-submitting");
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
        const grid = document.querySelector("[data-collections-grid]");
        const source = document.querySelector("[data-cards-source]");
        const openBtn = document.querySelector("[data-collections-open]");
        if (!chips || !grid || !source) return;

        const defaultCollections = [
            "Новые франшизы",
            "Для начинающих",
            "Быстрая окупаемость",
            "Без роялти",
            "Без паушального взноса",
            "Премиум",
        ];
        const sourceCards = Array.from(source.querySelectorAll(".popular-card"));
        const shopUrl = openBtn?.getAttribute("href") || "/";

        chips.innerHTML = defaultCollections
            .map((name) => `<button type="button" class="collection-tile" data-collection="${escapeHtml(name)}">${escapeHtml(name)}</button>`)
            .join("");

        const cardHasTag = (card, tagName) => {
            const tags = String(card.dataset.tags || "")
                .split(/[|,]/)
                .map((t) => t.trim())
                .filter(Boolean);
            if (tagName === "Проверено") return String(card.dataset.verified || "").trim() === "true";
            return tags.includes(tagName);
        };

        const renderCards = (collectionName) => {
            let cards = sourceCards.slice();
            if (collectionName === "Проверено") {
                cards = cards.filter((c) => String(c.dataset.verified || "").trim() === "true");
            } else if (collectionName === "Популярные франшизы" || collectionName === "Все франшизы") {
                cards.sort((a, b) => Number(b.dataset.popularity || 0) - Number(a.dataset.popularity || 0));
            } else if (collectionName === "Новые франшизы") {
                cards.sort((a, b) => Number(b.dataset.date || 0) - Number(a.dataset.date || 0));
            } else {
                cards = cards.filter((c) => cardHasTag(c, collectionName));
            }
            grid.innerHTML = "";
            cards.slice(0, 10).forEach((card) => grid.appendChild(card.cloneNode(true)));
        };

        const setActive = (name) => {
            chips.querySelectorAll("[data-collection]").forEach((node) => {
                node.classList.toggle("active", node.getAttribute("data-collection") === name);
            });
            if (openBtn) openBtn.href = shopUrl;
        };

        chips.querySelectorAll("[data-collection]").forEach((node) => {
            node.addEventListener("click", () => {
                const name = node.getAttribute("data-collection") || "";
                renderCards(name);
                setActive(name);
            });
        });

        const first = defaultCollections[0] || "Новые франшизы";
        renderCards(first);
        setActive(first);
    };

    const initFranchiseGallery = () => {
        const mainImage = document.querySelector("[data-gallery-main]");
        const galleryThumbs = document.querySelector(".gallery-thumbs");
        const countLabel = document.querySelector("[data-gallery-count]");
        const prevBtn = document.querySelector(".gallery-nav-prev");
        const nextBtn = document.querySelector(".gallery-nav-next");
        const thumbsScroller = document.querySelector(".gallery-thumbs");
        const thumbsPrev = document.querySelector(".thumbs-nav-prev");
        const thumbsNext = document.querySelector(".thumbs-nav-next");
        const galleryMain = document.querySelector(".gallery-main");

        if (!mainImage || !galleryThumbs || !countLabel) return;

        const thumbs = Array.from(galleryThumbs.querySelectorAll("[data-gallery-thumb]"));
        if (!thumbs.length) return;

        let currentIndex = 0;
        const getSrc = (index) => thumbs[index]?.dataset.full || thumbs[index]?.querySelector("img")?.src || mainImage.src;

        const updateCount = () => {
            countLabel.textContent = `${currentIndex + 1} / ${thumbs.length}`;
        };

        const showImage = (index, options = {}) => {
            const { skipScroll = false } = options;
            const total = thumbs.length;
            if (!total) return;
            const safeIndex = (index + total) % total;
            thumbs.forEach((btn, i) => {
                const active = i === safeIndex;
                btn.classList.toggle("is-active", active);
                btn.setAttribute("aria-selected", String(active));
            });
            currentIndex = safeIndex;
            updateCount();
            mainImage.style.opacity = "0.45";
            mainImage.src = getSrc(safeIndex);
            if (!skipScroll) {
                thumbs[safeIndex]?.scrollIntoView({ behavior: "smooth", inline: "nearest", block: "nearest" });
            }
        };

        mainImage.addEventListener("load", () => {
            mainImage.style.opacity = "1";
        });

        thumbs.forEach((thumb, index) => {
            thumb.addEventListener("click", (e) => {
                e.preventDefault();
                showImage(index);
            });
        });

        prevBtn?.addEventListener("click", () => showImage(currentIndex - 1));
        nextBtn?.addEventListener("click", () => showImage(currentIndex + 1));

        const swipeTarget = galleryMain instanceof HTMLElement ? galleryMain : mainImage.parentElement;
        if (swipeTarget instanceof HTMLElement && swipeTarget.dataset.gallerySwipeBound !== "1") {
            let touchStartX = null;
            let touchStartY = null;
            const reset = () => {
                touchStartX = null;
                touchStartY = null;
            };
            swipeTarget.addEventListener(
                "touchstart",
                (e) => {
                    const touch = e.changedTouches?.[0];
                    if (!touch) return;
                    touchStartX = touch.clientX;
                    touchStartY = touch.clientY;
                },
                { passive: true }
            );
            swipeTarget.addEventListener("touchend", (e) => {
                const touch = e.changedTouches?.[0];
                if (!touch || touchStartX === null || touchStartY === null) {
                    reset();
                    return;
                }
                const deltaX = touch.clientX - touchStartX;
                const deltaY = touch.clientY - touchStartY;
                reset();
                if (Math.abs(deltaX) < 36 || Math.abs(deltaY) > Math.abs(deltaX) * 0.8) return;
                showImage(deltaX < 0 ? currentIndex + 1 : currentIndex - 1);
            }, { passive: true });
            swipeTarget.dataset.gallerySwipeBound = "1";
        }

        if (thumbsScroller && thumbsPrev && thumbsNext) {
            const updateThumbButtons = () => {
                const maxScroll = thumbsScroller.scrollWidth - thumbsScroller.clientWidth;
                thumbsPrev.disabled = thumbsScroller.scrollLeft <= 4;
                thumbsNext.disabled = thumbsScroller.scrollLeft >= maxScroll - 4;
            };
            const scrollThumbs = (dir) => {
                const first = thumbsScroller.querySelector(".gallery-thumb");
                const gap = first ? parseFloat(getComputedStyle(thumbsScroller).gap || "0") || 0 : 0;
                const step = first ? first.getBoundingClientRect().width + gap : thumbsScroller.clientWidth * 0.8;
                thumbsScroller.scrollBy({ left: dir * step, behavior: "smooth" });
            };
            thumbsPrev.addEventListener("click", () => scrollThumbs(-1));
            thumbsNext.addEventListener("click", () => scrollThumbs(1));
            thumbsScroller.addEventListener("scroll", updateThumbButtons, { passive: true });
            updateThumbButtons();
        }

        showImage(0, { skipScroll: true });
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

    const initSelectionPopup = () => {
        const triggerPattern = /(получить подбор|связаться с франчайзером)/i;
        const popup = document.querySelector("#selection-popup");
        const form = popup?.querySelector("[data-selection-form]");
        const nameInput = popup?.querySelector("[data-selection-name]");
        const phoneInput = popup?.querySelector("[data-selection-phone]");
        const consentInput = popup?.querySelector("[data-selection-consent]");
        if (!popup || !form || !nameInput || !phoneInput || !consentInput) return;

        const triggers = Array.from(
            document.querySelectorAll('a.btn.btn-primary[href], button.btn.btn-primary, [data-selection-open], [data-franchise-contact]')
        ).filter((node) => {
            if (!(node instanceof HTMLElement)) return false;
            if (node.matches("[data-selection-open], [data-franchise-contact]")) return true;
            if (node.closest(".side-contact")) return true;
            const label = `${node.textContent || ""} ${node.getAttribute("aria-label") || ""}`.trim();
            return triggerPattern.test(label);
        });

        const openPopup = () => {
            if (typeof Fancybox !== "undefined" && Fancybox) {
                Fancybox.show([{ src: "#selection-popup", type: "inline" }], { dragToClose: false });
            }
        };

        triggers.forEach((trigger) => {
            if (trigger.dataset.selectionPopupBound === "1") return;
            trigger.addEventListener("click", (e) => {
                e.preventDefault();
                openPopup();
            });
            trigger.dataset.selectionPopupBound = "1";
        });

        if (form.dataset.selectionSubmitBound === "1") return;
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            if (!String(nameInput.value || "").trim()) {
                nameInput.setCustomValidity("Заполните поле");
                nameInput.reportValidity();
                return;
            }
            const digits = String(phoneInput.value || "").replace(/\D/g, "").replace(/^[78]/, "").slice(0, 10);
            if (digits.length < 10) {
                phoneInput.setCustomValidity("Введите номер телефона полностью.");
                phoneInput.reportValidity();
                return;
            }
            if (!consentInput.checked) {
                consentInput.setCustomValidity("Подтвердите согласие с политикой конфиденциальности.");
                consentInput.reportValidity();
                return;
            }
            if (typeof Fancybox !== "undefined" && Fancybox) Fancybox.close();
            form.reset();
            if (typeof window.__showLeadFeedback === "function") {
                window.__showLeadFeedback("Заявка отправлена. В ближайшее время менеджер свяжется с вами.");
            }
        });
        form.dataset.selectionSubmitBound = "1";
    };

    const initPlainLeadForms = () => {
        const leadForms = Array.from(
            document.querySelectorAll("form.form-grid, form.ask-form-grid, form.final-form")
        ).filter((form) => form instanceof HTMLFormElement && !form.closest(".wpcf7"));

        leadForms.forEach((form) => {
            if (form.dataset.leadFormFeedbackBound === "1") return;
            form.addEventListener("submit", (e) => {
                if (e.defaultPrevented) return;
                const consent = form.querySelector('input[type="checkbox"][name*="consent"]');
                if (consent instanceof HTMLInputElement && !consent.checked) {
                    e.preventDefault();
                    consent.reportValidity();
                    if (typeof window.__showLeadFeedback === "function") {
                        window.__showLeadFeedback("Подтвердите согласие с политикой конфиденциальности.", true);
                    }
                    return;
                }
                if (!form.getAttribute("action")) {
                    e.preventDefault();
                    if (typeof window.__showLeadFeedback === "function") {
                        window.__showLeadFeedback("Заявка отправлена. В ближайшее время менеджер свяжется с вами.");
                    }
                    form.reset();
                }
            });
            form.dataset.leadFormFeedbackBound = "1";
        });
    };

    const initSliderSections = () => {
        if (typeof Swiper === "undefined") return;
        document.querySelectorAll(".slider-section").forEach((section) => {
            const container = section.querySelector(".slider");
            const prev = section.querySelector("[data-slider-prev]");
            const next = section.querySelector("[data-slider-next]");
            if (!container || container.swiper) return;
            if (!container.querySelector(":scope > .swiper-wrapper")) {
                const wrapper = document.createElement("div");
                wrapper.className = "swiper-wrapper";
                Array.from(container.children).forEach((child) => {
                    child.classList.add("swiper-slide");
                    wrapper.appendChild(child);
                });
                container.classList.add("swiper");
                container.appendChild(wrapper);
            }
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
        initSelectionPopup();
        initPlainLeadForms();
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

    if (investRange && investHidden && investLabel) {
        const syncInvest = () => {
            const max = Number(investRange.max) || 3000000;
            const v = Number(investRange.value) || 0;
            if (v >= max) {
                investHidden.value = '0';
                investLabel.textContent = 'Любые вложения';
            } else {
                investHidden.value = String(v);
                investLabel.textContent = 'до ' + formatMoney(v);
            }
        };
        investRange.addEventListener('input', syncInvest);
        syncInvest();
    }

    if (profitRange && profitHidden && profitLabel) {
        const syncProfit = () => {
            const v = Number(profitRange.value) || 0;
            if (v <= 0) {
                profitHidden.value = '0';
                profitLabel.textContent = 'Любая прибыль';
            } else {
                profitHidden.value = String(v);
                profitLabel.textContent = 'от ' + formatMoney(v);
            }
        };
        profitRange.addEventListener('input', syncProfit);
        syncProfit();
    }

    main.querySelectorAll('.preset-btn[data-invest]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const v = btn.getAttribute('data-invest');
            if (investRange && investHidden && investLabel && v) {
                investRange.value = v;
                investHidden.value = v;
                investLabel.textContent = 'до ' + formatMoney(v);
            }
        });
    });

    main.querySelectorAll('.preset-btn[data-profit]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const v = btn.getAttribute('data-profit');
            if (profitRange && profitHidden && profitLabel && v) {
                profitRange.value = v;
                profitHidden.value = v;
                profitLabel.textContent = 'от ' + formatMoney(v);
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
(() => {
    const header = document.querySelector('.site-header');
    if (!header) return;

    const setHeaderState = () => {
        header.classList.toggle('scrolled', window.scrollY > 10 || header.classList.contains('dropdown-open'));
    };
    window.setHeaderState = setHeaderState;
    setHeaderState();
    window.addEventListener('scroll', setHeaderState, { passive: true });

    const toggle = document.querySelector('.menu-toggle');
    const menu = document.querySelector('#mobile-menu');
    if (toggle && menu) {
        const setMenuOpen = (isOpen) => {
            menu.classList.toggle('open', isOpen);
            menu.setAttribute('aria-hidden', String(!isOpen));
            toggle.setAttribute('aria-expanded', String(isOpen));
            document.body.classList.toggle('modal-open', isOpen);
        };

        toggle.addEventListener('click', () => {
            setMenuOpen(!menu.classList.contains('open'));
        });

        menu.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;
            if (target === menu) {
                setMenuOpen(false);
                return;
            }
            if (target.hasAttribute('data-mobile-close') || target.closest('[data-mobile-close]')) {
                setMenuOpen(false);
                return;
            }
            if (target.tagName === 'A') {
                setMenuOpen(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && menu.classList.contains('open')) setMenuOpen(false);
        });

        const accTriggers = Array.from(menu.querySelectorAll('[data-mobile-acc-trigger]'));
        const accBlocks = Array.from(menu.querySelectorAll('[data-mobile-acc]'));
        const setAccOpen = (key, open) => {
            const block = menu.querySelector(`[data-mobile-acc="${key}"]`);
            const trigger = menu.querySelector(`[data-mobile-acc-trigger="${key}"]`);
            if (!block || !trigger) return;
            block.classList.toggle('open', open);
            trigger.setAttribute('aria-expanded', String(open));
        };

        accTriggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const key = trigger.getAttribute('data-mobile-acc-trigger');
                if (!key) return;
                const isOpen = menu.querySelector(`[data-mobile-acc="${key}"]`)?.classList.contains('open');
                accBlocks.forEach((block) => {
                    const otherKey = block.getAttribute('data-mobile-acc');
                    if (otherKey && otherKey !== key) setAccOpen(otherKey, false);
                });
                setAccOpen(key, !isOpen);
            });
        });
    }

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

        let backdrop = document.querySelector('[data-categories-backdrop]');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'catalog-dropdown-backdrop';
            backdrop.setAttribute('data-categories-backdrop', '');
            backdrop.setAttribute('aria-hidden', 'true');
            document.body.appendChild(backdrop);
        }

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
            backdrop.classList.toggle('open', opened);
            backdrop.setAttribute('aria-hidden', String(!opened));
            if (categoriesTrigger) {
                categoriesTrigger.setAttribute('aria-expanded', String(isCategoriesOpen));
            }
            if (collectionsTrigger) {
                collectionsTrigger.setAttribute('aria-expanded', String(isCollectionsOpen));
            }
            setHeaderState();
        };

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

        backdrop.addEventListener('click', () => {
            categoriesDropdown?.classList.remove('is-open', 'open');
            collectionsDropdown?.classList.remove('is-open', 'open');
            syncOpenState();
        });

        if (collectionsDropdown && collectionsTrigger && collectionsPanel) {
            let collectionsCloseTimer = null;
            const clearCollectionsTimer = () => {
                if (collectionsCloseTimer) {
                    window.clearTimeout(collectionsCloseTimer);
                    collectionsCloseTimer = null;
                }
            };
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

            collectionsTrigger.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                clearCollectionsTimer();
                setCollectionsOpen(!collectionsDropdown.classList.contains('is-open'));
            });

            document.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Node)) return;
                if (!collectionsDropdown.contains(target)) setCollectionsOpen(false);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                if (collectionsDropdown.classList.contains('is-open')) {
                    setCollectionsOpen(false);
                }
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
})();

