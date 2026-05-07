/* ===== card-hydration.js ===== */
(() => {
    const CACHE = new Map();
    const hydrated = new WeakSet();

    const toNumber = (value) => {
        const normalized = String(value || '').replace(/\s+/g, '').replace(/[^\d.-]/g, '');
        const parsed = Number(normalized);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const formatMoney = (value) => String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

    const normalizeCardUrl = (raw) => {
        const value = String(raw || '').trim();
        if (!value) return '';
        try {
            return new URL(value, window.location.href).toString();
        } catch {
            return '';
        }
    };

    const extractMetaFromDoc = (doc, sourceUrl) => {
        const body = doc.body;
        const title =
            String(body?.dataset?.cardTitle || '').trim() ||
            String(doc.querySelector('.page-title')?.textContent || '').trim();
        const desc =
            String(body?.dataset?.cardDesc || '').trim() ||
            String(doc.querySelector('.page-subtitle')?.textContent || '').trim();
        const image =
            String(doc.querySelector('.gallery-thumbs .gallery-thumb img')?.getAttribute('src') || '').trim() ||
            String(doc.querySelector('[data-gallery-main]')?.getAttribute('src') || '').trim() ||
            String(body?.dataset?.cardImage || '').trim();
        const invest =
            toNumber(body?.dataset?.cardInvest) ||
            toNumber(Array.from(doc.querySelectorAll('.key-item')).find((item) =>
                /инвестиции/i.test(String(item.querySelector('span')?.textContent || ''))
            )?.querySelector('strong')?.textContent);
        const profit =
            toNumber(body?.dataset?.cardProfit) ||
            toNumber(Array.from(doc.querySelectorAll('.key-item')).find((item) =>
                /месячная прибыль/i.test(String(item.querySelector('span')?.textContent || ''))
            )?.querySelector('strong')?.textContent);
        const payback =
            toNumber(body?.dataset?.cardPayback) ||
            toNumber(Array.from(doc.querySelectorAll('.key-item')).find((item) =>
                /окупаемость/i.test(String(item.querySelector('span')?.textContent || ''))
            )?.querySelector('strong')?.textContent);
        const verified =
            String(body?.dataset?.cardVerified || '').trim() === 'true' ||
            !!doc.querySelector('.badge');

        return { sourceUrl, title, desc, image, invest, profit, payback, verified };
    };

    const fetchCardMeta = async (rawUrl) => {
        const url = normalizeCardUrl(rawUrl);
        if (!url) return null;
        if (CACHE.has(url)) return CACHE.get(url);

        const pending = fetch(url, { credentials: 'same-origin' })
            .then((response) => (response.ok ? response.text() : Promise.reject(new Error(`HTTP ${response.status}`))))
            .then((html) => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                return extractMetaFromDoc(doc, url);
            })
            .catch(() => null);

        CACHE.set(url, pending);
        return pending;
    };

    const syncCard = (card, meta) => {
        if (!meta) return;
        const brandNode = card.querySelector('.popular-brand');
        const descNode = card.querySelector('.popular-desc');
        const imageNode = card.querySelector('.popular-media img');
        const valueNode = card.querySelector('.popular-meta .meta-value');
        const mediaNode = card.querySelector('.popular-media');
        const existingBadge = mediaNode?.querySelector('.popular-badge') || null;

        if (meta.title && brandNode) brandNode.textContent = meta.title;
        if (meta.desc && descNode) descNode.textContent = meta.desc;
        if (meta.image && imageNode) imageNode.src = meta.image;
        if (meta.invest > 0 && valueNode) valueNode.textContent = `${formatMoney(meta.invest)} ₽`;

        if (mediaNode) {
            if (meta.verified && !existingBadge) {
                const badge = document.createElement('span');
                badge.className = 'popular-badge';
                badge.textContent = 'Проверено';
                mediaNode.appendChild(badge);
            } else if (!meta.verified && existingBadge) {
                existingBadge.remove();
            }
        }

        if (meta.title) card.dataset.name = meta.title;
        if (meta.desc) card.dataset.desc = meta.desc;
        if (meta.image) card.dataset.image = meta.image;
        if (meta.invest > 0) card.dataset.invest = String(meta.invest);
        if (meta.profit > 0) card.dataset.profit = String(meta.profit);
        if (meta.payback > 0) card.dataset.payback = String(meta.payback);
        card.dataset.verified = meta.verified ? 'true' : 'false';
        hydrated.add(card);
    };

    const hydrateCards = async (scope = document) => {
        const cards = Array.from(scope.querySelectorAll('.popular-card[href], .popular-card[data-franchise-url]'));
        if (!cards.length) return;
        const now = Date.now();
        cards.forEach((card, index) => {
            if (!card.dataset.order || card.dataset.order === '0') card.dataset.order = String(index);
            if (!card.dataset.date || card.dataset.date === '0') card.dataset.date = String(now - index * 86400000);
        });
        await Promise.all(cards.map(async (card) => {
            if (hydrated.has(card)) return;
            const rawUrl = card.dataset.franchiseUrl || card.getAttribute('href') || '';
            if (!/franchise/i.test(rawUrl)) return;
            const meta = await fetchCardMeta(rawUrl);
            syncCard(card, meta);
        }));
    };

    const scheduleHydrate = (() => {
        let rafId = 0;
        return () => {
            if (rafId) return;
            rafId = requestAnimationFrame(() => {
                rafId = 0;
                hydrateCards(document);
            });
        };
    })();

    const initHydrationObserver = () => {
        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (!(mutation.target instanceof HTMLElement)) continue;
                if (mutation.addedNodes.length) {
                    scheduleHydrate();
                    break;
                }
            }
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
        scheduleHydrate();
    };

    window.__hydrateCardsFromFranchisePages = hydrateCards;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHydrationObserver, { once: true });
    } else {
        initHydrationObserver();
    }
})();

/* ===== manifest-loader.js ===== */
(() => {
    if (window.__loadFranchiseManifest) return;

    let manifestPromise = null;
    const pageMetaCache = new Map();

    const toNumber = (value) => {
        const normalized = String(value || '').replace(/\s+/g, '').replace(/[^\d.-]/g, '');
        const parsed = Number(normalized);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const toAbsUrl = (raw, base = window.location.href) => {
        const value = String(raw || '').trim();
        if (!value) return '';
        try {
            return new URL(value, base).toString();
        } catch {
            return '';
        }
    };

    const toRelUrl = (raw, base = window.location.href) => {
        const abs = toAbsUrl(raw, base);
        if (!abs) return '';
        try {
            const parsed = new URL(abs);
            const filename = (parsed.pathname.split('/').pop() || '').trim();
            return filename ? `${filename}${parsed.search}` : '';
        } catch {
            return '';
        }
    };

    const normalizeFranchiseHref = (raw, base = window.location.href) => {
        const rel = toRelUrl(raw, base);
        if (!rel) return '';
        const [file] = rel.split('?');
        if (!/^franchise-[a-z0-9-]+\.html$/i.test(file)) return '';
        return file;
    };

    const unique = (values) => Array.from(new Set(values.filter(Boolean)));

    const collectFranchiseUrlsFromDoc = (doc, base) => {
        const urls = [];
        doc.querySelectorAll('a[href], [data-franchise-url]').forEach((node) => {
            const raw = node.getAttribute('href') || node.getAttribute('data-franchise-url') || '';
            const file = normalizeFranchiseHref(raw, base);
            if (file) urls.push(file);
        });
        const bodyUrl = normalizeFranchiseHref(doc.body?.dataset?.cardUrl || '', base);
        if (bodyUrl) urls.push(bodyUrl);
        return urls;
    };

    const fetchDoc = async (url) => {
        const abs = toAbsUrl(url);
        if (!abs) return null;
        const response = await fetch(abs, { credentials: 'same-origin' });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const html = await response.text();
        return new DOMParser().parseFromString(html, 'text/html');
    };

    const collectFranchiseUrls = async () => {
        const local = collectFranchiseUrlsFromDoc(document, window.location.href);
        const currentFile = (window.location.pathname.split('/').pop() || '').trim();
        if (/^franchise-[a-z0-9-]+\.html$/i.test(currentFile)) local.push(currentFile);

        const pagesToScan = ['catalog.html', 'index.html'];
        const external = await Promise.all(
            pagesToScan.map(async (page) => {
                try {
                    const doc = await fetchDoc(page);
                    if (!doc) return [];
                    return collectFranchiseUrlsFromDoc(doc, page);
                } catch {
                    return [];
                }
            })
        );

        return unique(local.concat(...external));
    };

    const parseTags = (raw) => {
        if (Array.isArray(raw)) {
            return unique(
                raw
                    .map((item) => String(item || '').trim())
                    .flatMap((item) => item.split(/[|,]/))
                    .map((item) => item.trim())
                    .filter(Boolean)
            );
        }
        return unique(
            String(raw || '')
                .split(/[|,]/)
                .map((item) => item.trim())
                .filter(Boolean)
        );
    };

    const parseMetaFromDoc = (doc, url) => {
        const body = doc.body;
        const slug = String(body?.dataset?.cardSlug || url.replace(/^franchise-/, '').replace(/\.html$/i, '')).trim();
        const title =
            String(body?.dataset?.cardTitle || '').trim() ||
            String(doc.querySelector('.page-title')?.textContent || '').trim() ||
            slug;
        const desc =
            String(body?.dataset?.cardDesc || '').trim() ||
            String(doc.querySelector('.page-subtitle')?.textContent || '').trim();
        const sphere = String(body?.dataset?.cardSphere || '').trim();
        const category = String(body?.dataset?.cardCategory || '').trim();
        const tags = parseTags(body?.dataset?.cardTags || '');
        const thumbs = Array.from(doc.querySelectorAll('.gallery-thumbs .gallery-thumb img'))
            .map((img) => String(img.getAttribute('src') || '').trim())
            .filter(Boolean);
        const fallbackImage =
            String(doc.querySelector('[data-gallery-main]')?.getAttribute('src') || '').trim() ||
            String(body?.dataset?.cardImage || '').trim();
        const images = unique([...thumbs, ...(fallbackImage ? [fallbackImage] : [])]);
        const invest =
            toNumber(body?.dataset?.cardInvest) ||
            toNumber(Array.from(doc.querySelectorAll('.key-item')).find((item) =>
                /инвестиции/i.test(String(item.querySelector('span')?.textContent || ''))
            )?.querySelector('strong')?.textContent);
        const profit =
            toNumber(body?.dataset?.cardProfit) ||
            toNumber(Array.from(doc.querySelectorAll('.key-item')).find((item) =>
                /месячная прибыль/i.test(String(item.querySelector('span')?.textContent || ''))
            )?.querySelector('strong')?.textContent);
        const payback =
            toNumber(body?.dataset?.cardPayback) ||
            toNumber(Array.from(doc.querySelectorAll('.key-item')).find((item) =>
                /окупаемость/i.test(String(item.querySelector('span')?.textContent || ''))
            )?.querySelector('strong')?.textContent);
        const verified =
            String(body?.dataset?.cardVerified || '').trim() === 'true' ||
            !!doc.querySelector('.badge');
        const popularity = toNumber(body?.dataset?.cardPopularity);

        const meta = {
            id: slug,
            slug,
            name: title,
            brand: title,
            desc,
            image: images[0] || '',
            images,
            invest,
            profit,
            payback,
            verified,
            popularity,
            tags,
            sphere,
            category
        };

        return {
            slug,
            url,
            sphere,
            category,
            meta
        };
    };

    const fetchFranchiseMeta = async (url) => {
        if (pageMetaCache.has(url)) return pageMetaCache.get(url);
        const pending = fetchDoc(url)
            .then((doc) => (doc ? parseMetaFromDoc(doc, url) : null))
            .catch(() => null);
        pageMetaCache.set(url, pending);
        return pending;
    };

    const buildManifest = (items) => {
        const bySphere = new Map();
        const collections = new Set(['Все франшизы', 'Популярные франшизы', 'Новые франшизы']);

        items.forEach((item) => {
            if (!item) return;
            const sphereName = String(item.sphere || item.meta?.sphere || 'Без отрасли').trim();
            const categoryName = String(item.category || item.meta?.category || 'Без категории').trim();
            if (!bySphere.has(sphereName)) bySphere.set(sphereName, new Map());
            const byCategory = bySphere.get(sphereName);
            if (!byCategory.has(categoryName)) byCategory.set(categoryName, []);
            byCategory.get(categoryName).push(item);
            parseTags(item.meta?.tags || []).forEach((tag) => {
                if (tag && tag !== 'Проверено') collections.add(tag);
            });
        });

        const spheres = Array.from(bySphere.entries()).map(([sphereName, categoriesMap]) => ({
            name: sphereName,
            categories: Array.from(categoriesMap.entries()).map(([categoryName, franchises]) => ({
                name: categoryName,
                franchises
            }))
        }));

        return {
            spheres,
            collections: Array.from(collections).map((name) => ({ name })),
            franchises: items
        };
    };

    window.__loadFranchiseManifest = () => {
        if (manifestPromise) return manifestPromise;
        manifestPromise = collectFranchiseUrls()
            .then((urls) => Promise.all(urls.map((url) => fetchFranchiseMeta(url))))
            .then((items) => buildManifest(items.filter(Boolean)))
            .catch(() => ({ spheres: [], collections: [], franchises: [] }));
        return manifestPromise;
    };
})();

/* ===== index.js ===== */
(() => {
    const path = (window.location.pathname.split('/').pop() || 'index.html').toLowerCase();
    const isIndexPage = path === '' || path === 'index' || path === 'index.html';
    if (!isIndexPage) return;
    const manifestPromise = window.__loadFranchiseManifest
        ? window.__loadFranchiseManifest()
        : Promise.resolve({ spheres: [], collections: [] });

    const normalizeManifest = (manifest) => {
        const sphereMap = new Map();
        (manifest?.spheres || []).forEach((sphere) => {
            const sphereName = String(sphere?.name || '').trim();
            if (!sphereName) return;
            if (!sphereMap.has(sphereName)) sphereMap.set(sphereName, new Map());
            const categoryMap = sphereMap.get(sphereName);
            (sphere?.categories || []).forEach((category) => {
                const categoryName = String(category?.name || '').trim();
                if (!categoryName) return;
                if (!categoryMap.has(categoryName)) {
                    categoryMap.set(categoryName, { name: categoryName, franchises: [] });
                }
                const targetCategory = categoryMap.get(categoryName);
                const franchiseMap = targetCategory._franchiseMap || (targetCategory._franchiseMap = new Map());
                (category?.franchises || []).forEach((franchise) => {
                    const meta = franchise?.meta || {};
                    const franchiseKey = String(meta.id || franchise?.slug || franchise?.url || '').trim();
                    if (!franchiseKey || franchiseMap.has(franchiseKey)) return;
                    franchiseMap.set(franchiseKey, true);
                    targetCategory.franchises.push({
                        ...franchise,
                        meta: { ...meta }
                    });
                });
            });
        });
        return {
            ...manifest,
            spheres: Array.from(sphereMap.entries()).map(([sphereName, categoryMap]) => ({
                name: sphereName,
                categories: Array.from(categoryMap.values()).map(({ _franchiseMap, ...category }) => category)
            }))
        };
    };

    const catalogPageMap = {
        'Торговля': {
            'Аптеки': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%90%D0%BF%D1%82%D0%B5%D0%BA%D0%B8',
            'Электроника': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%AD%D0%BB%D0%B5%D0%BA%D1%82%D1%80%D0%BE%D0%BD%D0%B8%D0%BA%D0%B0'
        },
        'Еда': {
            'Пекарни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9F%D0%B5%D0%BA%D0%B0%D1%80%D0%BD%D0%B8',
            'Кофейни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9A%D0%BE%D1%84%D0%B5%D0%B9%D0%BD%D0%B8'
        },
        'Авто': {
            'Автосервисы': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D1%81%D0%B5%D1%80%D0%B2%D0%B8%D1%81%D1%8B%20%D0%B8%20%D0%A1%D0%A2%D0%9E',
            'Автомойки': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D0%BC%D0%BE%D0%B9%D0%BA%D0%B8'
        },
        'Обучение': {
            'Языковые школы': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%AF%D0%B7%D1%8B%D0%BA%D0%BE%D0%B2%D1%8B%D0%B5%20%D1%88%D0%BA%D0%BE%D0%BB%D1%8B',
            'Детские центры': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%94%D0%B5%D1%82%D1%81%D0%BA%D0%B8%D0%B5%20%D1%86%D0%B5%D0%BD%D1%82%D1%80%D1%8B'
        },
        'Красота и здоровье': {
            'Стоматологии': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%A1%D1%82%D0%BE%D0%BC%D0%B0%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D0%B8',
            'Косметология': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%9A%D0%BE%D1%81%D0%BC%D0%B5%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D1%8F'
        }
    };
    const getCategoryPage = (sphereName, categoryName) =>
        catalogPageMap?.[sphereName]?.[categoryName] || `catalog.html?sphere=${encodeURIComponent(sphereName)}&category=${encodeURIComponent(categoryName)}`;
    const getSphereLandingPage = (sphere) => {
        const sphereName = String(sphere?.name || '').trim();
        const firstCategory = Array.isArray(sphere?.categories) ? sphere.categories.find((item) => String(item?.name || '').trim()) : null;
        const categoryName = String(firstCategory?.name || '').trim();
        return sphereName && categoryName
            ? getCategoryPage(sphereName, categoryName)
            : (sphereName ? `catalog.html?sphere=${encodeURIComponent(sphereName)}` : 'catalog.html');
    };

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

        const mobileMenuList = menu.querySelector('.mobile-menu-list');
        if (mobileMenuList instanceof HTMLElement && !mobileMenuList.querySelector('[data-mobile-acc="collections"]')) {
            const collectionsAcc = document.createElement('div');
            collectionsAcc.className = 'mobile-acc';
            collectionsAcc.setAttribute('data-mobile-acc', 'collections');
            collectionsAcc.innerHTML = `
            <button class="mobile-acc-trigger" type="button" aria-expanded="false" aria-controls="mobile-collections" data-mobile-acc-trigger="collections">
              Подборки <span class="mobile-chev" aria-hidden="true"></span>
            </button>
            <div class="mobile-acc-content" id="mobile-collections">
              <div class="mobile-category-grid" data-mobile-collections-grid></div>
            </div>
          `;
            const contactsLink = mobileMenuList.querySelector('a.mobile-menu-link[href*="contacts"]');
            if (contactsLink) mobileMenuList.insertBefore(collectionsAcc, contactsLink);
            else mobileMenuList.appendChild(collectionsAcc);
        }

        const accTriggers = Array.from(menu.querySelectorAll('[data-mobile-acc-trigger]'));
        const accBlocks = Array.from(menu.querySelectorAll('[data-mobile-acc]'));
        const setAccOpen = (key, open) => {
            const block = menu.querySelector(`[data-mobile-acc=\"${key}\"]`);
            const trigger = menu.querySelector(`[data-mobile-acc-trigger=\"${key}\"]`);
            if (!block || !trigger) return;
            block.classList.toggle('open', open);
            trigger.setAttribute('aria-expanded', String(open));
        };

        accTriggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const key = trigger.getAttribute('data-mobile-acc-trigger');
                if (!key) return;
                const isOpen = menu.querySelector(`[data-mobile-acc=\"${key}\"]`)?.classList.contains('open');
                accBlocks.forEach((block) => {
                    const otherKey = block.getAttribute('data-mobile-acc');
                    if (otherKey && otherKey !== key) setAccOpen(otherKey, false);
                });
                setAccOpen(key, !isOpen);
            });
        });

        const categoriesGrid = menu.querySelector('[data-mobile-categories-grid]');
        const categoriesAcc = menu.querySelector('[data-mobile-acc="categories"]');
        const categoryPages = {
            'Торговля': {
                'Аптеки': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%90%D0%BF%D1%82%D0%B5%D0%BA%D0%B8',
                'Электроника': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%AD%D0%BB%D0%B5%D0%BA%D1%82%D1%80%D0%BE%D0%BD%D0%B8%D0%BA%D0%B0'
            },
            'Еда': {
                'Пекарни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9F%D0%B5%D0%BA%D0%B0%D1%80%D0%BD%D0%B8',
                'Кофейни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9A%D0%BE%D1%84%D0%B5%D0%B9%D0%BD%D0%B8'
            },
            'Авто': {
                'Автосервисы': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D1%81%D0%B5%D1%80%D0%B2%D0%B8%D1%81%D1%8B%20%D0%B8%20%D0%A1%D0%A2%D0%9E',
                'Автомойки': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D0%BC%D0%BE%D0%B9%D0%BA%D0%B8'
            },
            'Обучение': {
                'Языковые школы': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%AF%D0%B7%D1%8B%D0%BA%D0%BE%D0%B2%D1%8B%D0%B5%20%D1%88%D0%BA%D0%BE%D0%BB%D1%8B',
                'Детские центры': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%94%D0%B5%D1%82%D1%81%D0%BA%D0%B8%D0%B5%20%D1%86%D0%B5%D0%BD%D1%82%D1%80%D1%8B'
            },
            'Красота и здоровье': {
                'Стоматологии': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%A1%D1%82%D0%BE%D0%BC%D0%B0%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D0%B8',
                'Косметология': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%9A%D0%BE%D1%81%D0%BC%D0%B5%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D1%8F'
            }
        };
        const getCategoryPage = (sphereName, categoryName) =>
            categoryPages?.[sphereName]?.[categoryName] || `catalog.html?sphere=${encodeURIComponent(sphereName)}&category=${encodeURIComponent(categoryName)}`;
        const getSphereLandingPage = (sphere) => {
            const sphereName = String(sphere?.name || '').trim();
            const firstCategory = Array.isArray(sphere?.categories) ? sphere.categories.find((item) => String(item?.name || '').trim()) : null;
            const categoryName = String(firstCategory?.name || '').trim();
            return (sphereName && categoryName && getCategoryPage(sphereName, categoryName)) || (sphereName ? `catalog.html?sphere=${encodeURIComponent(sphereName)}` : 'catalog.html');
        };

        if (categoriesGrid) {
            manifestPromise.then((manifest) => {
                const spheres = normalizeManifest(manifest).spheres || [];
                if (!spheres.length) {
                    if (categoriesAcc instanceof HTMLElement) categoriesAcc.style.display = 'none';
                    return;
                }
                categoriesGrid.innerHTML = spheres
                    .map((sphere) => {
                        const name = String(sphere?.name || '').trim();
                        if (!name) return '';
                        const href = getSphereLandingPage(sphere);
                        return `<a class="chip" href="${href}"><span class="icon" aria-hidden="true">${getSphereIcon(name)}</span><span class="chip-text">${name}</span></a>`;
                    })
                    .join('');
            });
        }

        const collectionsGrid = menu.querySelector('[data-mobile-collections-grid]');
        const collectionsAcc = menu.querySelector('[data-mobile-acc=\"collections\"]');
        if (collectionsGrid) {
            manifestPromise.then((manifest) => {
                const rawCollections = (Array.isArray(manifest.collections) && manifest.collections.length)
                    ? manifest.collections
                    : defaultCollections;
                const collections = rawCollections.filter((item) => String(item?.name || '').trim() !== 'Проверено');
                if (!collections.length) {
                    if (collectionsAcc instanceof HTMLElement) collectionsAcc.style.display = 'none';
                    collectionsGrid.innerHTML = '';
                    return;
                }
                if (collectionsAcc instanceof HTMLElement) collectionsAcc.style.display = '';
                const currentTag = String(new URLSearchParams(window.location.search).get('tag') || '').trim().toLowerCase();
                collectionsGrid.innerHTML = collections
                    .map((col) => {
                        const name = String(col?.name || '').trim();
                        if (!name) return '';
                        const href = `catalog.html?tag=${encodeURIComponent(name)}`;
                        const isActive = currentTag && name.toLowerCase() === currentTag ? ' is-active' : '';
                        return `<a class=\"chip mobile-collections-chip${isActive}\" href=\"${href}\"><span class=\"chip-text\">${name}</span></a>`;
                    })
                    .join('');
            });
        }
    }

    const header = document.querySelector('.site-header');
    if (header) {
        const setHeaderState = () => {
            header.classList.toggle('scrolled', window.scrollY > 10 || header.classList.contains('dropdown-open'));
        };
        window.setHeaderState = setHeaderState;
        window.setHeaderState = setHeaderState;
        setHeaderState();
        window.addEventListener('scroll', setHeaderState, { passive: true });
    }

    const headerSearchForms = document.querySelectorAll('.header-search');
    if (headerSearchForms.length) {
        headerSearchForms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const input = form.querySelector('input[type="search"], input[name="q"]');
                const q = (input?.value || '').trim();
                const url = new URL('catalog.html', window.location.href);
                if (q) url.searchParams.set('q', q);
                window.location.href = url.toString();
            });
        });
    }

    const setupCategoriesDropdown = (manifest) => {
        const dropdown = document.querySelector('[data-categories-dropdown]');
        if (!dropdown) return;
        const trigger = dropdown.querySelector('[data-categories-trigger]');
        const panel = dropdown.querySelector('[data-categories-panel]');
        const list = dropdown.querySelector('[data-categories-list]');
        const titleEl = dropdown.querySelector('[data-categories-title]');
        const subgridEl = dropdown.querySelector('[data-categories-subgrid]');
        if (!trigger || !panel || !list || !titleEl || !subgridEl) return;

        const spheres = Array.isArray(normalizeManifest(manifest)?.spheres) ? normalizeManifest(manifest).spheres : [];
        if (!spheres.length) {
            dropdown.remove();
            return;
        }

        const escapeHtml = (value) =>
            String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');

        list.innerHTML = spheres
            .map(
                (sphere, index) =>
                    `<button class="categories-item${index === 0 ? ' active' : ''}" type="button" data-index="${index}"><span class="icon" aria-hidden="true"></span><span>${escapeHtml(sphere?.name || '')}</span></button>`
            )
            .join('');

        const renderSubcats = (sphereIndex) => {
            const sphere = spheres[Math.max(0, Math.min(sphereIndex, spheres.length - 1))];
            const sphereName = String(sphere?.name || '').trim();
            const items = Array.isArray(sphere?.categories) ? sphere.categories : [];
            titleEl.textContent = sphereName || 'Категории';
            subgridEl.innerHTML = items.length
                ? items
                    .map((item) => {
                        const categoryName = String(item?.name || '').trim();
                        if (!categoryName) return '';
                        const href = getCategoryPage(sphereName, categoryName);
                        return `<a href="${href}">${escapeHtml(categoryName)}</a>`;
                    })
                    .join('')
                : `<a href="catalog.html?sphere=${encodeURIComponent(sphereName)}">Смотреть все франшизы в отрасли</a>`;
        };

        const setActive = (index) => {
            const i = Math.max(0, Math.min(index, spheres.length - 1));
            const btns = Array.from(list.querySelectorAll('.categories-item'));
            btns.forEach((btn, idx) => btn.classList.toggle('active', idx === i));
            renderSubcats(i);
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

        const setExpanded = (isOpen) => {
            trigger.setAttribute('aria-expanded', String(isOpen));
            header?.classList.toggle('dropdown-open', isOpen);
            dropdown.classList.toggle('is-open', isOpen);
            window.setHeaderState?.();
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
            setExpanded(true);
        };
        const closeDropdownSoon = () => {
            clearDropdownTimer();
            dropdownCloseTimer = window.setTimeout(() => setExpanded(false), 140);
        };
        dropdown.addEventListener('mouseenter', openDropdown);
        dropdown.addEventListener('mouseleave', closeDropdownSoon);
        panel.addEventListener('mouseenter', openDropdown);
        panel.addEventListener('mouseleave', closeDropdownSoon);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && trigger.getAttribute('aria-expanded') === 'true') {
                setExpanded(false);
                trigger.blur();
            }
        });

        setActive(0);
    };

    manifestPromise.then((manifest) => setupCategoriesDropdown(normalizeManifest(manifest)));

    const segButtons = document.querySelectorAll('.seg');
    const segPanels = document.querySelectorAll('.segment-panel');
    if (segButtons.length && segPanels.length) {
        segButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                segButtons.forEach((b) => b.classList.toggle('active', b === btn));
                segButtons.forEach((b) => b.setAttribute('aria-selected', String(b === btn)));
                segPanels.forEach((panel) => panel.classList.toggle('active', panel.id === targetId));
            });
        });
    }

    const imagePool = [
        'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&fit=crop&w=1200&q=70',
        'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1200&q=70',
        'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1200&q=70',
        'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=70',
        'https://images.unsplash.com/photo-1606811971618-4486d14f3f99?auto=format&fit=crop&w=1200&q=70'
    ];

    const franchisePrimaryImages = {
        'sovetskaya-apteka': 'https://picsum.photos/seed/sovetskaya-apteka-card/1600/900',
        'apteka-zdorovo': 'https://picsum.photos/seed/apteka-zdorovo-card/1600/900',
        'apteka-gorod': 'https://picsum.photos/seed/apteka-gorod-card/1600/900',
        'techno-shop': 'https://picsum.photos/seed/techno-shop-card/1600/900',
        'fit-service': 'https://picsum.photos/seed/fit-service-card/1600/900',
        'carwash-24': 'https://picsum.photos/seed/carwash-24-card/1600/900',
        'tyre-service-pro': 'https://picsum.photos/seed/tyre-service-pro-card/1600/900',
        'detailing-lab': 'https://picsum.photos/seed/detailing-lab-card/1600/900',
        'avtomoyka-city': 'https://picsum.photos/seed/avtomoyka-city-card/1600/900',
        'nastoyashaya-pekarna': 'https://picsum.photos/seed/nastoyashaya-pekarna-card/1600/900',
        'pekarnya-dom': 'https://picsum.photos/seed/pekarnya-dom-card/1600/900',
        'kafe-kruzhka': 'https://picsum.photos/seed/kafe-kruzhka-card/1600/900',
        'coffeeway': 'https://picsum.photos/seed/coffeeway-card/1600/900',
        'lingua-club': 'https://picsum.photos/seed/lingua-club-card/1600/900',
        'english-room': 'https://picsum.photos/seed/english-room-card/1600/900',
        'it-school': 'https://picsum.photos/seed/it-school-card/1600/900',
        'kids-club': 'https://picsum.photos/seed/kids-club-card/1600/900',
        'semeynaya-stomatologiya': 'https://picsum.photos/seed/semeynaya-stomatologiya-card/1600/900',
        'dental-plus': 'https://picsum.photos/seed/dental-plus-card/1600/900',
        'cosmo-studio': 'https://picsum.photos/seed/cosmo-studio-card/1600/900',
    };
    const defaultCollections = [
        { name: 'Популярные франшизы' },
        { name: 'Новые франшизы' },
        { name: 'Для начинающих' },
        { name: 'Быстрая окупаемость' },
        { name: 'Без роялти' },
        { name: 'Без паушального взноса' },
        { name: 'Премиум' }
    ];
    const franchiseUrlMap = {
        'sovetskaya-apteka': 'franchise-sovetskaya-apteka.html',
        'apteka-zdorovo': 'franchise-apteka-zdorovo.html',
        'apteka-gorod': 'franchise-apteka-gorod.html',
        'techno-shop': 'franchise-techno-shop.html',
        'fit-service': 'franchise-fit-service.html',
        'carwash-24': 'franchise-carwash-24.html',
        'tyre-service-pro': 'franchise-tyre-service-pro.html',
        'detailing-lab': 'franchise-detailing-lab.html',
        'avtomoyka-city': 'franchise-avtomoyka-city.html',
        'nastoyashaya-pekarna': 'franchise-nastoyashaya-pekarna.html',
        'pekarnya-dom': 'franchise-pekarnya-dom.html',
        'kafe-kruzhka': 'franchise-kafe-kruzhka.html',
        'coffeeway': 'franchise-coffeeway.html',
        'lingua-club': 'franchise-lingua-club.html',
        'english-room': 'franchise-english-room.html',
        'it-school': 'franchise-it-school.html',
        'kids-club': 'franchise-kids-club.html',
        'semeynaya-stomatologiya': 'franchise-semeynaya-stomatologiya.html',
        'dental-plus': 'franchise-dental-plus.html',
        'cosmo-studio': 'franchise-cosmo-studio.html'
    };
    const resolveFranchiseUrl = (item, fallbackValue = 'franchise.html') => {
        const directUrl = String(item?.url || '').trim();
        if (directUrl && !directUrl.includes('?')) return directUrl;
        const id = String(item?.meta?.id || item?.slug || '').trim();
        if (id && franchiseUrlMap[id]) return franchiseUrlMap[id];
        if (id) return `franchise-${id}.html`;
        return fallbackValue;
    };
    const sphereIcons = {
        'Торговля': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M2 4h10l-1 7H3z"/><path d="M4 4a3 3 0 0 1 6 0" fill="none"/></svg>',
        'Еда': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M3 5h8"/><path d="M4 5l1 6h4l1-6"/><path d="M5 5V3h4v2" fill="none"/></svg>',
        'Авто': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M3 8l1-3h6l1 3"/><path d="M3 8h8v3H3z"/><circle cx="5" cy="11" r="0.8"/><circle cx="9" cy="11" r="0.8"/></svg>',
        'Обучение': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M2.5 5.5L7 3l4.5 2.5L7 8z"/><path d="M4 6v2.2C4 9.2 5.8 10 7 10s3-.8 3-1.8V6" fill="none"/></svg>',
        'Красота и здоровье': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2l1.2 2.8L11 6l-2.8 1.2L7 10 5.8 7.2 3 6l2.8-1.2z"/></svg>'
    };
    const getSphereIcon = (name) => sphereIcons[name] || '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M3 10l4-8 4 8z"/><path d="M5 7h4" fill="none"/></svg>';

    const renderHomeSpheres = (manifest) => {
        const section = document.querySelector('#catalog');
        const grid = document.querySelector('[data-spheres-grid]');
        if (!section || !grid) return;
        const normalized = normalizeManifest(manifest);
        const spheres = Array.isArray(normalized?.spheres) ? normalized.spheres : [];
        if (!spheres.length) return;
        const escapeHtml = (value) =>
            String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');

        grid.innerHTML = spheres
            .map((sphere) => {
                const name = String(sphere?.name || '').trim();
                if (!name) return '';
                const href = `catalog.html?sphere=${encodeURIComponent(name)}`;
                return `<a class="chip" href="${href}"><span class="icon" aria-hidden="true">${getSphereIcon(name)}</span><span class="chip-text">${escapeHtml(name)}</span></a>`;
            })
            .join('');
        section.hidden = false;
    };

    const initCategoryBar = () => {
        const categoryWrap = document.querySelector('#category-grid-wrap');
        const categoryToggle = document.querySelector('.category-toggle');
        const categoryGrid = categoryWrap ? categoryWrap.querySelector('.category-grid') : null;
        if (!categoryWrap || !categoryGrid) return;
        let categoryLastScroll = null;
        const isMobile = () => window.matchMedia('(max-width: 900px)').matches;
        const getCollapsedHeight = () => {
            const items = Array.from(categoryGrid.children);
            if (!items.length) return 0;
            if (isMobile()) return categoryGrid.scrollHeight;
            const rowsTarget = 2;
            const rowTops = [];
            items.forEach((item) => {
                const top = item.offsetTop;
                const found = rowTops.find((t) => Math.abs(t - top) <= 2);
                if (found === undefined) rowTops.push(top);
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
            categoryWrap.classList.remove('expanded');
            categoryWrap.classList.add('collapsed');
            if (categoryToggle) {
                categoryToggle.textContent = 'Показать все отрасли';
                categoryToggle.setAttribute('aria-expanded', 'false');
            }
            setWrapHeight(getCollapsedHeight());
            categoryWrap.style.overflow = 'hidden';
        };
        const setExpandedState = () => {
            categoryWrap.classList.add('expanded');
            categoryWrap.classList.remove('collapsed');
            if (categoryToggle) {
                categoryToggle.textContent = 'Скрыть отрасли';
                categoryToggle.setAttribute('aria-expanded', 'true');
            }
            setWrapHeight(categoryGrid.scrollHeight);
            categoryWrap.style.overflow = 'visible';
        };
        const updateHeights = () => {
            if (categoryWrap.classList.contains('expanded')) setWrapHeight(categoryGrid.scrollHeight);
            else setWrapHeight(getCollapsedHeight());
        };
        const applyMode = () => {
            const itemsCount = categoryGrid.children.length;
            const shouldShowToggle = isMobile() && itemsCount > 6;
            if (shouldShowToggle) {
                if (categoryToggle) categoryToggle.style.display = '';
                setCollapsedState();
                updateHeights();
                if (categoryToggle && !categoryToggle.dataset.bound) {
                    categoryToggle.dataset.bound = 'true';
                    categoryToggle.addEventListener('click', () => {
                        const willExpand = !categoryWrap.classList.contains('expanded');
                        if (willExpand) {
                            setWrapHeight(getCollapsedHeight());
                            categoryWrap.classList.add('expanded');
                            categoryWrap.classList.remove('collapsed');
                            requestAnimationFrame(() => setWrapHeight(categoryGrid.scrollHeight));
                        } else {
                            setWrapHeight(categoryGrid.scrollHeight);
                            categoryWrap.classList.remove('expanded');
                            categoryWrap.classList.add('collapsed');
                            requestAnimationFrame(() => setWrapHeight(getCollapsedHeight()));
                        }

                        const isExpanded = willExpand;
                        categoryToggle.textContent = isExpanded ? 'Скрыть отрасли' : 'Показать все отрасли';
                        categoryToggle.setAttribute('aria-expanded', String(isExpanded));
                        if (isExpanded) {
                            categoryLastScroll = window.scrollY;
                            const section = categoryWrap.closest('.category-bar');
                            if (section) {
                                const rootStyles = getComputedStyle(document.documentElement);
                                const headerHeight = parseFloat(rootStyles.getPropertyValue('--header-height')) || 0;
                                const offset = headerHeight + 12;
                                const top = section.getBoundingClientRect().top + window.scrollY - offset;
                                window.scrollTo({ top, behavior: 'smooth' });
                            }
                        } else if (categoryLastScroll !== null) {
                            window.scrollTo({ top: categoryLastScroll, behavior: 'smooth' });
                            categoryLastScroll = null;
                        }
                    });
                }
            } else {
                if (categoryToggle) categoryToggle.style.display = 'none';
                setExpandedState();
                categoryWrap.style.height = 'auto';
                categoryWrap.style.overflow = 'visible';
            }
        };
        applyMode();
        window.requestAnimationFrame(() => applyMode());
        window.addEventListener('resize', applyMode);
    };

    manifestPromise.then((manifest) => {
        const normalized = normalizeManifest(manifest);
        const spheres = Array.isArray(normalized.spheres) ? normalized.spheres : [];
        const collections = Array.isArray(normalized.collections) && normalized.collections.length ? normalized.collections : defaultCollections;
        const escapeHtml = (value) =>
            String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        const formatMoney = (value) => String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        const normalizeCardMeta = (meta) => {
            const franchiseId = String(meta?.id || meta?.slug || '').trim();
            const preferred = franchisePrimaryImages[franchiseId] || '';
            const fromList = Array.isArray(meta?.images)
                ? meta.images.map((value) => String(value || '').trim()).filter(Boolean)
                : [];
            const single = String(meta?.image || '').trim();
            const images = Array.from(new Set([...(preferred ? [preferred] : []), ...(fromList.length ? fromList : []), ...(single ? [single] : [])]));
            return {
                ...(meta || {}),
                image: images[0] || single,
                images
            };
        };
        const resolveCardImage = (meta) => {
            const images = Array.isArray(meta?.images)
                ? meta.images.map((value) => String(value || '').trim()).filter(Boolean)
                : [];
            const firstImage = String(meta?.image || '').trim();
            if (images.length) return images[0];
            if (firstImage) return firstImage;
            return '';
        };
        const getCollectionTags = (meta) => {
            const tags = new Set(
                (Array.isArray(meta?.tags) ? meta.tags : typeof meta?.tags === 'string' ? meta.tags.split('|') : [])
                    .map((tag) => String(tag || '').trim())
                    .filter(Boolean)
            );
            const id = String(meta?.id || '').trim();
            if (['lingua-club', 'nastoyashaya-pekarna', 'pekarnya-dom', 'english-room'].includes(id)) tags.add('Для начинающих');
            if (['lingua-club', 'nastoyashaya-pekarna', 'apteka-zdorovo', 'coffeeway'].includes(id)) tags.add('Быстрая окупаемость');
            if (['coffeeway', 'kids-club', 'techno-shop'].includes(id)) tags.add('Без роялти');
            if (['english-room', 'it-school', 'kids-club'].includes(id)) tags.add('Без паушального взноса');
            if (['fit-service', 'carwash-24', 'dental-plus', 'tyre-service-pro'].includes(id)) tags.add('Премиум');
            return Array.from(tags);
        };

        renderHomeSpheres(normalized);

        const allFranchises = [];
        spheres.forEach((sphere) => {
            const sphereName = String(sphere?.name || '').trim();
            (sphere?.categories || []).forEach((category) => {
                const categoryName = String(category?.name || '').trim();
                (category?.franchises || []).forEach((franchise) => {
                    allFranchises.push({
                        sphere: sphereName,
                        category: categoryName,
                        url: String(franchise?.url || '').trim(),
                        meta: normalizeCardMeta(franchise?.meta || {})
                    });
                });
            });
        });

        const popularSection = document.querySelector('[data-popular-section]');
        const popularGrid = document.querySelector('[data-popular-grid]');
        if (popularSection instanceof HTMLElement) popularSection.hidden = false;
        if (popularGrid && window.__hydrateCardsFromFranchisePages) {
            window.__hydrateCardsFromFranchisePages(popularGrid);
        }

        const collectionsLink = document.querySelector('[data-nav-collections]');
        const collectionsSection = document.querySelector('[data-collections-section]');
        const collectionsChips = document.querySelector('[data-collections-chips]');
        const collectionsGrid = document.querySelector('[data-collections-grid]');
        const collectionsOpenButton = document.querySelector('[data-collections-open]');
        if (collectionsLink instanceof HTMLElement) collectionsLink.style.display = '';
        if (collectionsSection instanceof HTMLElement) collectionsSection.hidden = false;
        if (collectionsChips && collectionsGrid) {
            const cardsSource = document.querySelector('[data-cards-source]');
            const sourceCards = cardsSource
                ? Array.from(cardsSource.querySelectorAll('.popular-card'))
                : [];
            const excludedHomeCollections = new Set(['Популярные франшизы']);
            const mergedHomeCollectionMap = new Map();
            [...defaultCollections, ...collections].forEach((col) => {
                const name = String(col?.name || '').trim();
                if (!name || excludedHomeCollections.has(name)) return;
                if (!mergedHomeCollectionMap.has(name)) mergedHomeCollectionMap.set(name, { name });
            });
            const homeCollections = Array.from(mergedHomeCollectionMap.values());
            const cardHasCollectionTag = (card, tagName) => {
                const safeTag = String(tagName || '').trim();
                if (!safeTag) return false;
                if (safeTag === 'Все франшизы') return true;
                if (safeTag === 'Проверено') return String(card.dataset.verified || '').trim() === 'true';
                const cardMetaLike = {
                    id: String(card.dataset.franchiseId || card.dataset.id || '').trim(),
                    tags: String(card.dataset.tags || '').trim()
                };
                const tags = new Set(getCollectionTags(cardMetaLike).map((tag) => String(tag || '').trim()).filter(Boolean));
                return tags.has(safeTag);
            };
            const fallbackHomeCollection =
                homeCollections.find((col) => String(col?.name || '').trim() === 'Новые франшизы') ||
                homeCollections[0] ||
                { name: 'Новые франшизы' };
            const renderCollectionCards = (collectionName) => {
                const name = String(collectionName || '').trim();
                if (sourceCards.length) {
                    let cards = sourceCards.slice();
                    if (name === 'Проверено') {
                        cards = cards.filter((card) => String(card.dataset.verified || '').trim() === 'true');
                    } else if (name === 'Популярные франшизы' || name === 'Все франшизы') {
                        cards = cards.sort((a, b) => Number(b.dataset.popularity || 0) - Number(a.dataset.popularity || 0));
                    } else if (name === 'Новые франшизы') {
                        cards = cards.sort((a, b) => Number(b.dataset.date || 0) - Number(a.dataset.date || 0));
                    } else {
                        cards = cards.filter((card) => cardHasCollectionTag(card, name));
                    }
                    collectionsGrid.innerHTML = '';
                    cards.slice(0, 10).forEach((card) => collectionsGrid.appendChild(card.cloneNode(true)));
                    if (window.__hydrateCardsFromFranchisePages) window.__hydrateCardsFromFranchisePages(collectionsGrid);
                    return;
                }
                collectionsGrid.innerHTML = '';
            };

            collectionsChips.innerHTML = homeCollections.map((col) => {
                const name = String(col?.name || '').trim();
                if (!name) return '';
                const href = `catalog.html?tag=${encodeURIComponent(name)}`;
                return `<a class="collection-tile" href="${href}" data-collection="${escapeHtml(name)}">${escapeHtml(name)}</a>`;
            }).join('');

            const setActiveCollection = (collectionName) => {
                const safeName = String(collectionName || '').trim();
                collectionsChips.querySelectorAll('[data-collection]').forEach((node) => {
                    if (!(node instanceof HTMLElement)) return;
                    node.classList.toggle('active', String(node.getAttribute('data-collection') || '').trim() === safeName);
                });
            };
            const applyCollectionSelection = (collectionName) => {
                const safeName = String(collectionName || '').trim();
                if (!safeName) return;
                renderCollectionCards(safeName);
                setActiveCollection(safeName);
                if (collectionsOpenButton) {
                    collectionsOpenButton.href = `catalog.html?tag=${encodeURIComponent(safeName)}`;
                }
            };

            collectionsChips.querySelectorAll('[data-collection]').forEach((node) => {
                if (!(node instanceof HTMLElement) || node.dataset.collectionDesktopBound === '1') return;
                node.addEventListener('click', (event) => {
                    if ('metaKey' in event && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) return;
                    event.preventDefault();
                    applyCollectionSelection(node.getAttribute('data-collection') || '');
                });
                node.dataset.collectionDesktopBound = '1';
            });

            const firstTile = collectionsChips.querySelector('[data-collection]');
            const defaultHomeCollectionName = String(firstTile?.getAttribute('data-collection') || fallbackHomeCollection?.name || 'Новые франшизы').trim() || 'Новые франшизы';
            applyCollectionSelection(defaultHomeCollectionName);
        }

        initCategoryBar();
        setupCategoriesDropdown(normalized);
    });




    const logoStrip = document.querySelector('.logo-strip');
    if (logoStrip) {
        const logoNames = [
            'NOVA', 'LUMO', 'ZEN', 'ORION', 'VISTA', 'AURA',
            'NORD', 'PRIME', 'URBAN', 'PULSE', 'VIA', 'POINT'
        ];
        const logoInvest = [
            'Инвестиции от 420 000 ₽',
            'Инвестиции от 560 000 ₽',
            'Инвестиции от 310 000 ₽',
            'Инвестиции от 690 000 ₽',
            'Инвестиции от 480 000 ₽',
            'Инвестиции от 750 000 ₽'
        ];
        const logoColors = [
            { bg: '111827', fg: 'ffffff' },
            { bg: '1f2937', fg: 'f9fafb' },
            { bg: '0f172a', fg: 'e2e8f0' },
            { bg: '1e3a8a', fg: 'ffffff' },
            { bg: '0f766e', fg: 'f0fdfa' },
            { bg: '7c2d12', fg: 'fff7ed' },
            { bg: '6b21a8', fg: 'faf5ff' },
            { bg: '334155', fg: 'f8fafc' }
        ];
        const getLogoSrc = (name, index) => {
            const palette = logoColors[index % logoColors.length];
            const bg = palette.bg;
            const fg = palette.fg;
            return `https://placehold.co/140x140/${bg}/${fg}?text=${encodeURIComponent(name)}`;
        };
        const logoCards = logoStrip.querySelectorAll('.logo-card');
        logoCards.forEach((card, index) => {
            const name = logoNames[index % logoNames.length];
            const img = document.createElement('img');
            img.className = 'logo-mark';
            img.loading = 'lazy';
            img.alt = name;
            img.src = getLogoSrc(name, index);
            const details = document.createElement('div');
            details.className = 'logo-details';
            const detailsMedia = document.createElement('div');
            detailsMedia.className = 'logo-details-media';
            const detailsImg = document.createElement('img');
            detailsImg.loading = 'lazy';
            detailsImg.alt = '';
            detailsImg.src = imagePool[(index + 1) % imagePool.length];
            detailsMedia.appendChild(detailsImg);
            const detailsBody = document.createElement('div');
            detailsBody.className = 'logo-details-body';
            const titleText = index === 0 ? 'Советская аптечная сеть' : logoNames[index % logoNames.length];
            const investText = logoInvest[index % logoInvest.length];
            detailsBody.innerHTML = `
          <div class="logo-title">${titleText}</div>
          <div class="logo-meta">${investText}</div>
        `;
            details.appendChild(detailsMedia);
            details.appendChild(detailsBody);
            card.textContent = '';
            card.appendChild(img);
            card.appendChild(details);
            card.dataset.brand = name;
            card.dataset.title = titleText;
            card.dataset.invest = investText;
            card.dataset.image = detailsImg.src;
            card.dataset.link = '#';
        });

        const prevBtn = document.querySelector('.logo-arrow.prev');
        const nextBtn = document.querySelector('.logo-arrow.next');

        const getGap = () => {
            const styles = getComputedStyle(logoStrip);
            const gap = parseFloat(styles.columnGap || styles.gap || '14');
            return Number.isNaN(gap) ? 14 : gap;
        };
        const getStep = () => {
            const card = logoStrip.querySelector('.logo-card');
            const cardWidth = card ? card.getBoundingClientRect().width : 0;
            return cardWidth + getGap();
        };
        const scrollByStep = (dir = 1) => {
            logoStrip.scrollBy({ left: dir * getStep(), behavior: 'smooth' });
        };

        if (prevBtn) prevBtn.addEventListener('click', () => scrollByStep(-1));
        if (nextBtn) nextBtn.addEventListener('click', () => scrollByStep(1));

        const modal = document.querySelector('#logo-modal');
        if (modal) {
            const modalImg = modal.querySelector('.logo-modal-media img');
            const modalBrand = modal.querySelector('.logo-modal-brand');
            const modalTitle = modal.querySelector('.logo-modal-title');
            const modalMeta = modal.querySelector('.logo-modal-meta');
            const modalLink = modal.querySelector('.logo-modal-cta');
            const closeModal = () => {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            };
            const openModal = (card) => {
                if (!card) return;
                if (modalImg) modalImg.src = card.dataset.image || '';
                if (modalBrand) modalBrand.textContent = card.dataset.brand || '';
                if (modalTitle) modalTitle.textContent = card.dataset.title || '';
                if (modalMeta) modalMeta.textContent = card.dataset.invest || '';
                if (modalLink) modalLink.href = card.dataset.link || '#';
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
            };
            const isMobile = () => window.matchMedia('(max-width: 900px)').matches;
            logoCards.forEach((card) => {
                card.addEventListener('click', () => {
                    if (!isMobile()) return;
                    openModal(card);
                });
            });
            modal.addEventListener('click', (event) => {
                const target = event.target;
                if (target && (target.matches('[data-close]') || target.closest('.logo-modal-close'))) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeModal();
            });
        }
    }

    const reviewsStrip = document.querySelector('.reviews-strip');
    if (reviewsStrip) {
        const prevBtn = document.querySelector('.reviews-arrow.prev');
        const nextBtn = document.querySelector('.reviews-arrow.next');
        const getGap = () => {
            const styles = getComputedStyle(reviewsStrip);
            const gap = parseFloat(styles.columnGap || styles.gap || '20');
            return Number.isNaN(gap) ? 20 : gap;
        };
        const getStep = () => {
            const card = reviewsStrip.querySelector('.review-card');
            const cardWidth = card ? card.getBoundingClientRect().width : 0;
            return cardWidth + getGap();
        };
        const scrollByStep = (dir = 1) => {
            reviewsStrip.scrollBy({ left: dir * getStep(), behavior: 'smooth' });
        };
        if (prevBtn) prevBtn.addEventListener('click', () => scrollByStep(-1));
        if (nextBtn) nextBtn.addEventListener('click', () => scrollByStep(1));

        const dotsWrap = document.querySelector('.reviews-dots');
        if (dotsWrap) {
            const cards = Array.from(reviewsStrip.querySelectorAll('.review-card'));
            if (cards.length) {
                dotsWrap.innerHTML = cards
                    .map((_, index) => `<button class="reviews-dot${index === 0 ? ' active' : ''}" type="button" aria-label="Отзыв ${index + 1}" aria-current="${index === 0 ? 'true' : 'false'}"></button>`)
                    .join('');

                const dots = Array.from(dotsWrap.querySelectorAll('.reviews-dot'));
                const setActive = (activeIndex) => {
                    dots.forEach((dot, idx) => {
                        const isActive = idx === activeIndex;
                        dot.classList.toggle('active', isActive);
                        dot.setAttribute('aria-current', isActive ? 'true' : 'false');
                    });
                };

                dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        const card = cards[index];
                        if (!card) return;
                        card.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
                        setActive(index);
                    });
                });

                const mq = window.matchMedia('(max-width: 900px)');
                let observer = null;
                const setupObserver = () => {
                    if (observer) observer.disconnect();
                    observer = null;
                    if (!mq.matches) return;
                    observer = new IntersectionObserver(
                        (entries) => {
                            let best = null;
                            entries.forEach((entry) => {
                                if (!entry.isIntersecting) return;
                                if (!best || entry.intersectionRatio > best.intersectionRatio) best = entry;
                            });
                            if (!best) return;
                            const idx = cards.indexOf(best.target);
                            if (idx >= 0) setActive(idx);
                        },
                        { root: reviewsStrip, threshold: [0.55, 0.7, 0.85] }
                    );
                    cards.forEach((card) => observer.observe(card));
                };

                setupObserver();
                if (mq.addEventListener) mq.addEventListener('change', setupObserver);
                else mq.addListener(setupObserver);
            }
        }
    }
})();

/* ===== catalog.js ===== */
(() => {
    const path = (window.location.pathname.split('/').pop() || '').toLowerCase();
    const isCatalogPage = path === 'catalog' || path === 'catalog.html';
    if (!isCatalogPage) return;
    const manifestPromise = window.__loadFranchiseManifest
        ? window.__loadFranchiseManifest()
        : Promise.resolve({ spheres: [], collections: [] });

    const normalizeManifest = (manifest) => {
        const sphereMap = new Map();
        (manifest?.spheres || []).forEach((sphere) => {
            const sphereName = String(sphere?.name || '').trim();
            if (!sphereName) return;
            if (!sphereMap.has(sphereName)) sphereMap.set(sphereName, new Map());
            const categoryMap = sphereMap.get(sphereName);
            (sphere?.categories || []).forEach((category) => {
                const categoryName = String(category?.name || '').trim();
                if (!categoryName) return;
                if (!categoryMap.has(categoryName)) {
                    categoryMap.set(categoryName, { name: categoryName, franchises: [] });
                }
                const targetCategory = categoryMap.get(categoryName);
                const franchiseMap = targetCategory._franchiseMap || (targetCategory._franchiseMap = new Map());
                (category?.franchises || []).forEach((franchise) => {
                    const meta = franchise?.meta || {};
                    const franchiseKey = String(meta.id || franchise?.slug || franchise?.url || '').trim();
                    if (!franchiseKey || franchiseMap.has(franchiseKey)) return;
                    franchiseMap.set(franchiseKey, true);
                    targetCategory.franchises.push({
                        ...franchise,
                        meta: { ...meta }
                    });
                });
            });
        });
        return {
            ...manifest,
            spheres: Array.from(sphereMap.entries()).map(([sphereName, categoryMap]) => ({
                name: sphereName,
                categories: Array.from(categoryMap.values()).map(({ _franchiseMap, ...category }) => category)
            }))
        };
    };

    const catalogPageMap = {
        'Торговля': {
            'Аптеки': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%90%D0%BF%D1%82%D0%B5%D0%BA%D0%B8',
            'Электроника': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%AD%D0%BB%D0%B5%D0%BA%D1%82%D1%80%D0%BE%D0%BD%D0%B8%D0%BA%D0%B0'
        },
        'Еда': {
            'Пекарни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9F%D0%B5%D0%BA%D0%B0%D1%80%D0%BD%D0%B8',
            'Кофейни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9A%D0%BE%D1%84%D0%B5%D0%B9%D0%BD%D0%B8'
        },
        'Авто': {
            'Автосервисы': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D1%81%D0%B5%D1%80%D0%B2%D0%B8%D1%81%D1%8B%20%D0%B8%20%D0%A1%D0%A2%D0%9E',
            'Автомойки': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D0%BC%D0%BE%D0%B9%D0%BA%D0%B8'
        },
        'Обучение': {
            'Языковые школы': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%AF%D0%B7%D1%8B%D0%BA%D0%BE%D0%B2%D1%8B%D0%B5%20%D1%88%D0%BA%D0%BE%D0%BB%D1%8B',
            'Детские центры': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%94%D0%B5%D1%82%D1%81%D0%BA%D0%B8%D0%B5%20%D1%86%D0%B5%D0%BD%D1%82%D1%80%D1%8B'
        },
        'Красота и здоровье': {
            'Стоматологии': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%A1%D1%82%D0%BE%D0%BC%D0%B0%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D0%B8',
            'Косметология': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%9A%D0%BE%D1%81%D0%BC%D0%B5%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D1%8F'
        }
    };
    const getCategoryPage = (sphereName, categoryName) =>
        catalogPageMap?.[sphereName]?.[categoryName] || 'catalog.html';
    const getSphereLandingPage = (sphere) => {
        const sphereName = String(sphere?.name || '').trim();
        const firstCategory = Array.isArray(sphere?.categories) ? sphere.categories.find((item) => String(item?.name || '').trim()) : null;
        const categoryName = String(firstCategory?.name || '').trim();
        return sphereName && categoryName
            ? getCategoryPage(sphereName, categoryName)
            : 'catalog.html';
    };

    const resolveCatalogPage = (sphereName, categoryName) => {
        const sphere = String(sphereName || '').trim();
        const category = String(categoryName || '').trim();
        if (sphere && category && sphere !== 'Все сферы' && category !== 'Все категории') {
            return getCategoryPage(sphere, category);
        }
        if (sphere && sphere !== 'Все сферы') {
            const sphereCategories = catalogPageMap?.[sphere] || {};
            const firstCategory = Object.keys(sphereCategories).find(Boolean);
            if (firstCategory) return getCategoryPage(sphere, firstCategory);
            return 'catalog.html';
        }
        if (category && category !== 'Все категории') {
            for (const [sphereKey, categories] of Object.entries(catalogPageMap)) {
                if (categories?.[category]) return categories[category];
            }
            return 'catalog.html';
        }
        return 'catalog.html';
    };

    const resolveSphereLandingPageByName = (sphereName) => {
        const sphere = String(sphereName || '').trim();
        if (!sphere || sphere === 'Все сферы') return 'catalog.html';
        const categories = catalogPageMap?.[sphere] || {};
        const firstCategory = Object.keys(categories).find(Boolean);
        if (firstCategory) return categories[firstCategory];
        return 'catalog.html';
    };

    const resolveCategoryLandingPageByName = (categoryName) => {
        const category = String(categoryName || '').trim();
        if (!category || category === 'Все категории') return 'catalog.html';
        for (const categories of Object.values(catalogPageMap)) {
            if (categories?.[category]) return categories[category];
        }
        return 'catalog.html';
    };

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

        const mobileMenuList = menu.querySelector('.mobile-menu-list');
        if (mobileMenuList instanceof HTMLElement && !mobileMenuList.querySelector('[data-mobile-acc="collections"]')) {
            const collectionsAcc = document.createElement('div');
            collectionsAcc.className = 'mobile-acc';
            collectionsAcc.setAttribute('data-mobile-acc', 'collections');
            collectionsAcc.innerHTML = `
            <button class="mobile-acc-trigger" type="button" aria-expanded="false" aria-controls="mobile-collections" data-mobile-acc-trigger="collections">
              Подборки <span class="mobile-chev" aria-hidden="true"></span>
            </button>
            <div class="mobile-acc-content" id="mobile-collections">
              <div class="mobile-category-grid" data-mobile-collections-grid></div>
            </div>
          `;
            const contactsLink = mobileMenuList.querySelector('a.mobile-menu-link[href*="contacts"]');
            if (contactsLink) mobileMenuList.insertBefore(collectionsAcc, contactsLink);
            else mobileMenuList.appendChild(collectionsAcc);
        }

        const accTriggers = Array.from(menu.querySelectorAll('[data-mobile-acc-trigger]'));
        const accBlocks = Array.from(menu.querySelectorAll('[data-mobile-acc]'));
        const setAccOpen = (key, open) => {
            const block = menu.querySelector(`[data-mobile-acc=\"${key}\"]`);
            const trigger = menu.querySelector(`[data-mobile-acc-trigger=\"${key}\"]`);
            if (!block || !trigger) return;
            block.classList.toggle('open', open);
            trigger.setAttribute('aria-expanded', String(open));
        };

        accTriggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const key = trigger.getAttribute('data-mobile-acc-trigger');
                if (!key) return;
                const isOpen = menu.querySelector(`[data-mobile-acc=\"${key}\"]`)?.classList.contains('open');
                accBlocks.forEach((block) => {
                    const otherKey = block.getAttribute('data-mobile-acc');
                    if (otherKey && otherKey !== key) setAccOpen(otherKey, false);
                });
                setAccOpen(key, !isOpen);
            });
        });

        const categoriesGrid = menu.querySelector('[data-mobile-categories-grid]');
        const categoriesAcc = menu.querySelector('[data-mobile-acc="categories"]');
        const categoryPages = {
            'Торговля': {
                'Аптеки': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%90%D0%BF%D1%82%D0%B5%D0%BA%D0%B8',
                'Электроника': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%AD%D0%BB%D0%B5%D0%BA%D1%82%D1%80%D0%BE%D0%BD%D0%B8%D0%BA%D0%B0'
            },
            'Еда': {
                'Пекарни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9F%D0%B5%D0%BA%D0%B0%D1%80%D0%BD%D0%B8',
                'Кофейни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9A%D0%BE%D1%84%D0%B5%D0%B9%D0%BD%D0%B8'
            },
            'Авто': {
                'Автосервисы': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D1%81%D0%B5%D1%80%D0%B2%D0%B8%D1%81%D1%8B%20%D0%B8%20%D0%A1%D0%A2%D0%9E',
                'Автомойки': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D0%BC%D0%BE%D0%B9%D0%BA%D0%B8'
            },
            'Обучение': {
                'Языковые школы': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%AF%D0%B7%D1%8B%D0%BA%D0%BE%D0%B2%D1%8B%D0%B5%20%D1%88%D0%BA%D0%BE%D0%BB%D1%8B',
                'Детские центры': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%94%D0%B5%D1%82%D1%81%D0%BA%D0%B8%D0%B5%20%D1%86%D0%B5%D0%BD%D1%82%D1%80%D1%8B'
            },
            'Красота и здоровье': {
                'Стоматологии': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%A1%D1%82%D0%BE%D0%BC%D0%B0%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D0%B8',
                'Косметология': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%9A%D0%BE%D1%81%D0%BC%D0%B5%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D1%8F'
            }
        };
        const getCategoryPage = (sphereName, categoryName) =>
            categoryPages?.[sphereName]?.[categoryName] || 'catalog.html';
        const getSphereLandingPage = (sphere) => {
            const sphereName = String(sphere?.name || '').trim();
            const firstCategory = Array.isArray(sphere?.categories) ? sphere.categories.find((item) => String(item?.name || '').trim()) : null;
            const categoryName = String(firstCategory?.name || '').trim();
            return (sphereName && categoryName && getCategoryPage(sphereName, categoryName)) || 'catalog.html';
        };

        if (categoriesGrid) {
            const sphereIcons = {
                'Торговля': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M3 5h8l-1 6H4z"/><path d="M4 5l1-2h4l1 2" fill="none"/></svg>',
                'Еда': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M5 2v4M7 2v4M9 2v4"/><path d="M4 6h6v6H4z" fill="none"/></svg>',
                'Авто': '<svg viewBox="0 0 14 14" aria-hidden="true"><rect x="2.5" y="5" width="9" height="4.5" rx="1"/><circle cx="4.5" cy="10.5" r="1"/><circle cx="9.5" cy="10.5" r="1"/></svg>',
                'Обучение': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M2 5l5-2 5 2-5 2z"/><path d="M4 6.2V9c0 .9 1.6 1.8 3 1.8S10 9.9 10 9V6.2" fill="none"/></svg>',
                'Красота и здоровье': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2l1.2 2.2L10.5 5 8.2 6.2 7 8.5 5.8 6.2 3.5 5l2.3-.8z"/></svg>'
            };
            const getSphereIcon = (name) =>
                sphereIcons[name] || '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M3 10l4-8 4 8z"/><path d="M5 7h4" fill="none"/></svg>';
            manifestPromise.then((manifest) => {
                const spheres = normalizeManifest(manifest).spheres || [];
                if (!spheres.length) {
                    if (categoriesAcc instanceof HTMLElement) categoriesAcc.style.display = 'none';
                    return;
                }
                categoriesGrid.innerHTML = spheres
                    .map((sphere) => {
                        const name = String(sphere?.name || '').trim();
                        if (!name) return '';
                        const href = getSphereLandingPage(sphere);
                        return `<a class="chip" href="${href}"><span class="icon" aria-hidden="true">${getSphereIcon(name)}</span><span class="chip-text">${name}</span></a>`;
                    })
                    .join('');
            });
        }

        const collectionsGrid = menu.querySelector('[data-mobile-collections-grid]');
        const collectionsAcc = menu.querySelector('[data-mobile-acc=\"collections\"]');
        if (collectionsGrid) {
            manifestPromise.then((manifest) => {
                const rawCollections = (Array.isArray(manifest.collections) && manifest.collections.length)
                    ? manifest.collections
                    : defaultCollections;
                const collections = rawCollections.filter((item) => String(item?.name || '').trim() !== 'Проверено');
                if (!collections.length) {
                    if (collectionsAcc instanceof HTMLElement) collectionsAcc.style.display = 'none';
                    collectionsGrid.innerHTML = '';
                    return;
                }
                if (collectionsAcc instanceof HTMLElement) collectionsAcc.style.display = '';
                const currentTag = String(new URLSearchParams(window.location.search).get('tag') || '').trim().toLowerCase();
                collectionsGrid.innerHTML = collections
                    .map((col) => {
                        const name = String(col?.name || '').trim();
                        if (!name) return '';
                        const href = `catalog.html?tag=${encodeURIComponent(name)}`;
                        const isActive = currentTag && name.toLowerCase() === currentTag ? ' is-active' : '';
                        return `<a class=\"chip mobile-collections-chip${isActive}\" href=\"${href}\"><span class=\"chip-text\">${name}</span></a>`;
                    })
                    .join('');
            });
        }
    }

    const header = document.querySelector('.site-header');
    if (header) {
        const setHeaderState = () => {
            header.classList.toggle('scrolled', window.scrollY > 10 || header.classList.contains('dropdown-open'));
        };
        window.setHeaderState = setHeaderState;
        window.setHeaderState = setHeaderState;
        setHeaderState();
        window.addEventListener('scroll', setHeaderState, { passive: true });
    }

    const headerSearchForms = document.querySelectorAll('.header-search');
    if (headerSearchForms.length) {
        headerSearchForms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const input = form.querySelector('input[type="search"], input[name="q"]');
                const q = (input?.value || '').trim();
                const url = new URL(window.location.href);
                if (q) url.searchParams.set('q', q);
                else url.searchParams.delete('q');
                window.location.href = url.toString();
            });
        });
    }

    const setupCategoriesDropdown = (manifest) => {
        const dropdown = document.querySelector('[data-categories-dropdown]');
        if (!dropdown) return;
        const trigger = dropdown.querySelector('[data-categories-trigger]');
        const panel = dropdown.querySelector('[data-categories-panel]');
        const backdrop = document.querySelector('[data-categories-backdrop]');
        const list = dropdown.querySelector('[data-categories-list]');
        const titleEl = dropdown.querySelector('[data-categories-title]');
        const subgridEl = dropdown.querySelector('[data-categories-subgrid]');
        if (!trigger || !panel || !list || !titleEl || !subgridEl) return;
        const spheres = Array.isArray(normalizeManifest(manifest)?.spheres) ? normalizeManifest(manifest).spheres : [];
        if (!spheres.length) {
            dropdown.remove();
            return;
        }

        const escapeHtml = (value) =>
            String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');

        list.innerHTML = spheres
            .map(
                (sphere, index) =>
                    `<button class="categories-item${index === 0 ? ' active' : ''}" type="button" data-index="${index}"><span>${escapeHtml(sphere?.name || '')}</span></button>`
            )
            .join('');

        const categoryPages = {
            'Торговля': {
                'Аптеки': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%90%D0%BF%D1%82%D0%B5%D0%BA%D0%B8',
                'Электроника': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%AD%D0%BB%D0%B5%D0%BA%D1%82%D1%80%D0%BE%D0%BD%D0%B8%D0%BA%D0%B0'
            },
            'Еда': {
                'Пекарни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9F%D0%B5%D0%BA%D0%B0%D1%80%D0%BD%D0%B8',
                'Кофейни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9A%D0%BE%D1%84%D0%B5%D0%B9%D0%BD%D0%B8'
            },
            'Авто': {
                'Автосервисы': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D1%81%D0%B5%D1%80%D0%B2%D0%B8%D1%81%D1%8B%20%D0%B8%20%D0%A1%D0%A2%D0%9E',
                'Автомойки': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D0%BC%D0%BE%D0%B9%D0%BA%D0%B8'
            },
            'Обучение': {
                'Языковые школы': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%AF%D0%B7%D1%8B%D0%BA%D0%BE%D0%B2%D1%8B%D0%B5%20%D1%88%D0%BA%D0%BE%D0%BB%D1%8B',
                'Детские центры': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%94%D0%B5%D1%82%D1%81%D0%BA%D0%B8%D0%B5%20%D1%86%D0%B5%D0%BD%D1%82%D1%80%D1%8B'
            },
            'Красота и здоровье': {
                'Стоматологии': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%A1%D1%82%D0%BE%D0%BC%D0%B0%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D0%B8',
                'Косметология': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%9A%D0%BE%D1%81%D0%BC%D0%B5%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D1%8F'
            }
        };
        const getCategoryPage = (sphereName, categoryName) =>
            categoryPages?.[sphereName]?.[categoryName] || 'catalog.html';

        const renderSubcats = (sphereIndex) => {
            const sphere = spheres[Math.max(0, Math.min(sphereIndex, spheres.length - 1))];
            const sphereName = String(sphere?.name || '').trim();
            const items = Array.isArray(sphere?.categories) ? sphere.categories : [];
            titleEl.textContent = sphereName || 'Категории';
            subgridEl.innerHTML = items.length
                ? items
                    .map((item) => {
                        const categoryName = String(item?.name || '').trim();
                        if (!categoryName) return '';
                        const href = getCategoryPage(sphereName, categoryName);
                        return `<a href="${href}">${escapeHtml(categoryName)}</a>`;
                    })
                    .join('')
                : `<a href="catalog.html">Смотреть все франшизы в отрасли</a>`;
        };

        const setActive = (index) => {
            const i = Math.max(0, Math.min(index, spheres.length - 1));
            const btns = Array.from(list.querySelectorAll('.categories-item'));
            btns.forEach((btn, idx) => btn.classList.toggle('active', idx === i));
            renderSubcats(i);
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
            header?.classList.toggle('dropdown-open', isOpen);
            if (backdrop) backdrop.classList.toggle('open', isOpen);
            trigger.setAttribute('aria-expanded', String(isOpen));
            window.setHeaderState?.();
            if (!isOpen) trigger.blur();
        };
        const setExpanded = (isOpen) => {
            trigger.setAttribute('aria-expanded', String(isOpen));
        };
        const openDropdown = () => {
            clearDropdownTimer();
            setDropdownOpen(true);
        };
        const closeDropdown = () => setDropdownOpen(false);
        let dropdownCloseTimer = null;
        const clearDropdownTimer = () => {
            if (dropdownCloseTimer) {
                window.clearTimeout(dropdownCloseTimer);
                dropdownCloseTimer = null;
            }
        };
        const closeDropdownSoon = () => {
            clearDropdownTimer();
            dropdownCloseTimer = window.setTimeout(() => setDropdownOpen(false), 140);
        };
        dropdown.addEventListener('mouseenter', openDropdown);
        dropdown.addEventListener('mouseleave', closeDropdownSoon);
        panel.addEventListener('mouseenter', openDropdown);
        panel.addEventListener('mouseleave', closeDropdownSoon);

        if (backdrop) {
            backdrop.addEventListener('click', () => setDropdownOpen(false));
        }

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Node)) return;
            if (!dropdown.contains(target)) setDropdownOpen(false);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && trigger.getAttribute('aria-expanded') === 'true') {
                setDropdownOpen(false);
                trigger.blur();
            }
        });

        setActive(0);
    };

    manifestPromise.then((manifest) => setupCategoriesDropdown(normalizeManifest(manifest)));


    const sphereLandingPages = {
        'Авто': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D1%81%D0%B5%D1%80%D0%B2%D0%B8%D1%81%D1%8B%20%D0%B8%20%D0%A1%D0%A2%D0%9E',
        'Еда': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9F%D0%B5%D0%BA%D0%B0%D1%80%D0%BD%D0%B8',
        'Обучение': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%AF%D0%B7%D1%8B%D0%BA%D0%BE%D0%B2%D1%8B%D0%B5%20%D1%88%D0%BA%D0%BE%D0%BB%D1%8B',
        'Красота и здоровье': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%A1%D1%82%D0%BE%D0%BC%D0%B0%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D0%B8',
        'Торговля': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%90%D0%BF%D1%82%D0%B5%D0%BA%D0%B8'
    };
    const categoryPages = {
        'Авто': {
            'Автосервисы': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D1%81%D0%B5%D1%80%D0%B2%D0%B8%D1%81%D1%8B%20%D0%B8%20%D0%A1%D0%A2%D0%9E',
            'Автомойки': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D0%BC%D0%BE%D0%B9%D0%BA%D0%B8'
        },
        'Еда': {
            'Пекарни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9F%D0%B5%D0%BA%D0%B0%D1%80%D0%BD%D0%B8',
            'Кофейни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9A%D0%BE%D1%84%D0%B5%D0%B9%D0%BD%D0%B8'
        },
        'Обучение': {
            'Языковые школы': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%AF%D0%B7%D1%8B%D0%BA%D0%BE%D0%B2%D1%8B%D0%B5%20%D1%88%D0%BA%D0%BE%D0%BB%D1%8B',
            'Детские центры': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%94%D0%B5%D1%82%D1%81%D0%BA%D0%B8%D0%B5%20%D1%86%D0%B5%D0%BD%D1%82%D1%80%D1%8B'
        },
        'Красота и здоровье': {
            'Стоматологии': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%A1%D1%82%D0%BE%D0%BC%D0%B0%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D0%B8',
            'Косметология': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%9A%D0%BE%D1%81%D0%BC%D0%B5%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D1%8F'
        },
        'Торговля': {
            'Аптеки': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%90%D0%BF%D1%82%D0%B5%D0%BA%D0%B8',
            'Электроника': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%AD%D0%BB%D0%B5%D0%BA%D1%82%D1%80%D0%BE%D0%BD%D0%B8%D0%BA%D0%B0'
        }
    };
    const imagePool = [
        'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&fit=crop&w=1200&q=70',
        'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1200&q=70',
        'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1200&q=70',
        'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=70',
        'https://images.unsplash.com/photo-1606811971618-4486d14f3f99?auto=format&fit=crop&w=1200&q=70'
    ];

    const franchisePrimaryImages = {
        'sovetskaya-apteka': 'https://picsum.photos/seed/sovetskaya-apteka-card/1600/900',
        'apteka-zdorovo': 'https://picsum.photos/seed/apteka-zdorovo-card/1600/900',
        'apteka-gorod': 'https://picsum.photos/seed/apteka-gorod-card/1600/900',
        'techno-shop': 'https://picsum.photos/seed/techno-shop-card/1600/900',
        'fit-service': 'https://picsum.photos/seed/fit-service-card/1600/900',
        'carwash-24': 'https://picsum.photos/seed/carwash-24-card/1600/900',
        'tyre-service-pro': 'https://picsum.photos/seed/tyre-service-pro-card/1600/900',
        'detailing-lab': 'https://picsum.photos/seed/detailing-lab-card/1600/900',
        'avtomoyka-city': 'https://picsum.photos/seed/avtomoyka-city-card/1600/900',
        'nastoyashaya-pekarna': 'https://picsum.photos/seed/nastoyashaya-pekarna-card/1600/900',
        'pekarnya-dom': 'https://picsum.photos/seed/pekarnya-dom-card/1600/900',
        'kafe-kruzhka': 'https://picsum.photos/seed/kafe-kruzhka-card/1600/900',
        'coffeeway': 'https://picsum.photos/seed/coffeeway-card/1600/900',
        'lingua-club': 'https://picsum.photos/seed/lingua-club-card/1600/900',
        'english-room': 'https://picsum.photos/seed/english-room-card/1600/900',
        'it-school': 'https://picsum.photos/seed/it-school-card/1600/900',
        'kids-club': 'https://picsum.photos/seed/kids-club-card/1600/900',
        'semeynaya-stomatologiya': 'https://picsum.photos/seed/semeynaya-stomatologiya-card/1600/900',
        'dental-plus': 'https://picsum.photos/seed/dental-plus-card/1600/900',
        'cosmo-studio': 'https://picsum.photos/seed/cosmo-studio-card/1600/900',
    };
    const defaultCollections = [
        { name: 'Все франшизы' },
        { name: 'Популярные франшизы' },
        { name: 'Новые франшизы' },
        { name: 'Для начинающих' },
        { name: 'Быстрая окупаемость' },
        { name: 'Без роялти' },
        { name: 'Без паушального взноса' },
        { name: 'Премиум' }
    ];
    const franchiseUrlMap = {
        'sovetskaya-apteka': 'franchise-sovetskaya-apteka.html',
        'apteka-zdorovo': 'franchise-apteka-zdorovo.html',
        'apteka-gorod': 'franchise-apteka-gorod.html',
        'techno-shop': 'franchise-techno-shop.html',
        'fit-service': 'franchise-fit-service.html',
        'carwash-24': 'franchise-carwash-24.html',
        'tyre-service-pro': 'franchise-tyre-service-pro.html',
        'detailing-lab': 'franchise-detailing-lab.html',
        'avtomoyka-city': 'franchise-avtomoyka-city.html',
        'nastoyashaya-pekarna': 'franchise-nastoyashaya-pekarna.html',
        'pekarnya-dom': 'franchise-pekarnya-dom.html',
        'kafe-kruzhka': 'franchise-kafe-kruzhka.html',
        'coffeeway': 'franchise-coffeeway.html',
        'lingua-club': 'franchise-lingua-club.html',
        'english-room': 'franchise-english-room.html',
        'it-school': 'franchise-it-school.html',
        'kids-club': 'franchise-kids-club.html',
        'semeynaya-stomatologiya': 'franchise-semeynaya-stomatologiya.html',
        'dental-plus': 'franchise-dental-plus.html',
        'cosmo-studio': 'franchise-cosmo-studio.html'
    };
    const resolveFranchiseUrl = (item, fallbackValue = 'franchise.html') => {
        const directUrl = String(item?.url || '').trim();
        if (directUrl && !directUrl.includes('?')) return directUrl;
        const id = String(item?.meta?.id || item?.slug || '').trim();
        if (id && franchiseUrlMap[id]) return franchiseUrlMap[id];
        if (id) return `franchise-${id}.html`;
        return fallbackValue;
    };
    manifestPromise.then((manifest) => {
        const normalized = normalizeManifest(manifest);
        const spheres = (normalized.spheres || [])
            .map((item) => String(item?.name || '').trim())
            .filter(Boolean);
        const sphereCategories = {};
        (normalized.spheres || []).forEach((sphere) => {
            const sphereName = String(sphere?.name || '').trim();
            if (!sphereName) return;
            sphereCategories[sphereName] = (sphere?.categories || [])
                .map((cat) => String(cat?.name || '').trim())
                .filter(Boolean);
        });
        const categoryPool = Array.from(new Set(Object.values(sphereCategories).flat()));

        const collectionsLink = document.querySelector('[data-nav-collections]');
        const collectionsList = document.querySelector('[data-collections-list]');
        const collectionsTabs = document.querySelector('[data-collections-tabs]');
        const manifestCollections = Array.isArray(normalized.collections) ? normalized.collections : [];
        const inferredCollectionTags = [];
        (normalized.spheres || []).forEach((sphere) => {
            (sphere?.categories || []).forEach((category) => {
                (category?.franchises || []).forEach((franchise) => {
                    const rawTags = franchise?.meta?.tags;
                    const tags = Array.isArray(rawTags)
                        ? rawTags
                        : (typeof rawTags === 'string' ? rawTags.split(/[|,]/) : []);
                    tags.forEach((tag) => {
                        inferredCollectionTags.push({ name: String(tag || '').trim() });
                    });
                });
            });
        });
        const mergedCollectionMap = new Map();
        [...defaultCollections, ...manifestCollections, ...inferredCollectionTags].forEach((item) => {
            const name = String(item?.name || '').trim();
            if (!name || name === 'Проверено' || mergedCollectionMap.has(name)) return;
            mergedCollectionMap.set(name, { name });
        });
        const items = Array.from(mergedCollectionMap.values());
        if (collectionsLink instanceof HTMLElement) collectionsLink.style.display = '';
        if (collectionsList) {
            collectionsList.innerHTML = items
                .map((item, idx) => {
                    const safe = String(item.name).replaceAll('"', '&quot;');
                    const href = idx === 0 ? 'catalog.html' : `catalog.html?tag=${encodeURIComponent(item.name)}`;
                    return `<li><a class="sidebar-link${idx === 0 ? ' active' : ''}" href="${href}" data-tag="${safe}">${item.name}</a></li>`;
                })
                .join('');
        }
        if (collectionsTabs) {
            collectionsTabs.innerHTML = items
                .map((item, idx) => {
                    const safe = String(item.name).replaceAll('"', '&quot;');
                    const href = idx === 0 ? 'catalog.html' : `catalog.html?tag=${encodeURIComponent(item.name)}`;
                    return `<a class="seg${idx === 0 ? ' active' : ''}" href="${href}" data-tag="${safe}">${item.name}</a>`;
                })
                .join('');
        }

        const formatMoney = (value) => String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        const normalizeCardMeta = (meta) => {
            const franchiseId = String(meta?.id || meta?.slug || '').trim();
            const preferred = franchisePrimaryImages[franchiseId] || '';
            const fromList = Array.isArray(meta?.images)
                ? meta.images.map((value) => String(value || '').trim()).filter(Boolean)
                : [];
            const single = String(meta?.image || '').trim();
            const images = Array.from(new Set([...(preferred ? [preferred] : []), ...(fromList.length ? fromList : []), ...(single ? [single] : [])]));
            return {
                ...(meta || {}),
                image: images[0] || single,
                images
            };
        };

        const catalogCards = document.querySelector('.catalog-cards');
        const flatFranchises = [];
        const resolveCardImage = (meta) => {
            const images = Array.isArray(meta?.images)
                ? meta.images.map((value) => String(value || '').trim()).filter(Boolean)
                : [];
            const firstImage = String(meta?.image || '').trim();
            if (images.length) return images[0];
            if (firstImage) return firstImage;
            return '';
        };
        const getCollectionTags = (meta) => {
            const tags = new Set(
                (Array.isArray(meta?.tags) ? meta.tags : typeof meta?.tags === 'string' ? meta.tags.split('|') : [])
                    .map((tag) => String(tag || '').trim())
                    .filter(Boolean)
            );
            const id = String(meta?.id || '').trim();
            if (['lingua-club', 'nastoyashaya-pekarna', 'pekarnya-dom', 'english-room'].includes(id)) tags.add('Для начинающих');
            if (['lingua-club', 'nastoyashaya-pekarna', 'apteka-zdorovo', 'coffeeway'].includes(id)) tags.add('Быстрая окупаемость');
            if (['coffeeway', 'kids-club', 'techno-shop'].includes(id)) tags.add('Без роялти');
            if (['english-room', 'it-school', 'kids-club'].includes(id)) tags.add('Без паушального взноса');
            if (['fit-service', 'carwash-24', 'dental-plus', 'tyre-service-pro'].includes(id)) tags.add('Премиум');
            return Array.from(tags);
        };
        (normalized.spheres || []).forEach((sphere) => {
            const sphereName = String(sphere?.name || '').trim();
            (sphere?.categories || []).forEach((category) => {
                const categoryName = String(category?.name || '').trim();
                (category?.franchises || []).forEach((fr) => {
                    flatFranchises.push({
                        sphere: sphereName,
                        category: categoryName,
                        url: String(fr?.url || '').trim(),
                        meta: normalizeCardMeta(fr?.meta || {})
                    });
                });
            });
        });

        if (catalogCards && window.__hydrateCardsFromFranchisePages) {
            window.__hydrateCardsFromFranchisePages(catalogCards);
        }

        const popularCards = document.querySelectorAll('.popular-card');
        popularCards.forEach((card) => {
            const brand = card.querySelector('.popular-brand');
            let desc = card.querySelector('.popular-desc');
            if (!desc) {
                desc = document.createElement('div');
                desc.className = 'popular-desc';
                if (brand) {
                    brand.after(desc);
                } else {
                    card.appendChild(desc);
                }
            }
            const title = (card.dataset.name || brand?.textContent || '').trim();
            const details = (card.dataset.desc || '').trim();
            const category = (card.dataset.category || '').trim();
            desc.textContent = details || (category ? `франшиза ${category}` : title);
        });

        const splitInvestMeta = (meta) => {
            if (!meta) return;
            const text = meta.textContent || '';
            const match = text.match(/^Инвестиции\s+от\s+(.+)$/);
            if (!match) return;
            meta.innerHTML = `<span class="meta-label">Инвестиции от</span><span class="meta-value">${match[1]}</span>`;
        };

        document.querySelectorAll('.popular-meta').forEach(splitInvestMeta);

        const filterCard = document.querySelector('.filter-card');
        let setAdvancedState = null;
        if (filterCard) {
            const toggleBtn = filterCard.querySelector('.filter-toggle');
            const advancedBlock = filterCard.querySelector('.filter-advanced');
            const reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            let advancedAnimation = null;

            const updateToggleState = (isOpen) => {
                if (!toggleBtn) return;
                toggleBtn.setAttribute('aria-expanded', String(isOpen));
                toggleBtn.textContent = isOpen ? 'Свернуть дополнительные фильтры' : 'Показать дополнительные фильтры';
            };

            const resetAdvancedInlineStyles = () => {
                if (!(advancedBlock instanceof HTMLElement)) return;
                advancedBlock.style.removeProperty('height');
                advancedBlock.style.removeProperty('overflow');
                advancedBlock.style.removeProperty('opacity');
                advancedBlock.style.removeProperty('transform');
                advancedBlock.style.removeProperty('pointer-events');
            };

            const cancelAdvancedAnimation = () => {
                if (!advancedAnimation) return;
                advancedAnimation.onfinish = null;
                advancedAnimation.oncancel = null;
                advancedAnimation.cancel();
                advancedAnimation = null;
                resetAdvancedInlineStyles();
            };

            const animateAdvancedOpen = () => {
                if (!(advancedBlock instanceof HTMLElement)) return;
                filterCard.classList.remove('advanced-collapsed');
                const targetHeight = advancedBlock.scrollHeight;
                advancedBlock.style.height = '0px';
                advancedBlock.style.overflow = 'hidden';
                advancedBlock.style.opacity = '0';
                advancedBlock.style.transform = 'translateY(-10px)';
                advancedBlock.style.pointerEvents = 'none';

                advancedAnimation = advancedBlock.animate(
                    [
                        { height: '0px', opacity: 0, transform: 'translateY(-10px)' },
                        { height: `${targetHeight}px`, opacity: 1, transform: 'translateY(0)' }
                    ],
                    {
                        duration: 360,
                        easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                        fill: 'forwards'
                    }
                );

                advancedAnimation.onfinish = () => {
                    advancedAnimation = null;
                    filterCard.classList.remove('advanced-collapsed');
                    resetAdvancedInlineStyles();
                };
                advancedAnimation.oncancel = () => {
                    advancedAnimation = null;
                };
            };

            const animateAdvancedClose = () => {
                if (!(advancedBlock instanceof HTMLElement)) return;
                filterCard.classList.remove('advanced-collapsed');
                const startHeight = advancedBlock.scrollHeight;
                advancedBlock.style.height = `${startHeight}px`;
                advancedBlock.style.overflow = 'hidden';
                advancedBlock.style.opacity = '1';
                advancedBlock.style.transform = 'translateY(0)';
                advancedBlock.style.pointerEvents = 'none';

                advancedAnimation = advancedBlock.animate(
                    [
                        { height: `${startHeight}px`, opacity: 1, transform: 'translateY(0)' },
                        { height: '0px', opacity: 0, transform: 'translateY(-10px)' }
                    ],
                    {
                        duration: 320,
                        easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                        fill: 'forwards'
                    }
                );

                advancedAnimation.onfinish = () => {
                    advancedAnimation = null;
                    filterCard.classList.add('advanced-collapsed');
                    resetAdvancedInlineStyles();
                };
                advancedAnimation.oncancel = () => {
                    advancedAnimation = null;
                };
            };

            setAdvancedState = (isOpen, options = {}) => {
                const instant = options.instant === true;
                updateToggleState(isOpen);

                if (!(advancedBlock instanceof HTMLElement)) {
                    filterCard.classList.toggle('advanced-collapsed', !isOpen);
                    return;
                }

                cancelAdvancedAnimation();

                if (instant || reducedMotion) {
                    filterCard.classList.toggle('advanced-collapsed', !isOpen);
                    resetAdvancedInlineStyles();
                    return;
                }

                if (isOpen) animateAdvancedOpen();
                else animateAdvancedClose();
            };

            if (toggleBtn) {
                setAdvancedState(false, { instant: true });
                toggleBtn.addEventListener('click', () => {
                    const isOpen = !filterCard.classList.contains('advanced-collapsed');
                    setAdvancedState(!isOpen);
                });
            }
        }

        const filterModal = document.querySelector('#filter-modal');
        const filterOpenBtn = document.querySelector('[data-filter-open]');
        const filterSheetBody = filterModal?.querySelector('.filter-sheet-body');
        const filterCloseBtns = filterModal?.querySelectorAll('[data-filter-close]') || [];
        const filterOriginalParent = filterCard?.parentElement || null;
        const filterOriginalNext = filterCard?.nextElementSibling || null;

        const restoreFilterCard = () => {
            if (!filterCard || !filterOriginalParent) return;
            if (filterOriginalNext && filterOriginalNext.parentElement === filterOriginalParent) {
                filterOriginalParent.insertBefore(filterCard, filterOriginalNext);
            } else {
                filterOriginalParent.appendChild(filterCard);
            }
        };

        const openFilterModal = () => {
            if (!filterCard || !filterModal || !filterSheetBody) return;
            filterModal.classList.add('active');
            filterModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            if (setAdvancedState) setAdvancedState(true, { instant: true });
            filterSheetBody.appendChild(filterCard);
            filterCard.style.removeProperty('display');
        };

        const closeFilterModal = () => {
            if (!filterCard || !filterModal) return;
            filterModal.classList.remove('active');
            filterModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            restoreFilterCard();
            if (window.innerWidth <= 900) {
                filterCard.style.display = 'none';
            } else {
                filterCard.style.removeProperty('display');
            }
        };

        if (filterOpenBtn) {
            filterOpenBtn.addEventListener('click', () => {
                openFilterModal();
            });
        }

        filterCloseBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                closeFilterModal();
            });
        });

        if (filterModal) {
            filterModal.addEventListener('click', (event) => {
                const target = event.target;
                if (target instanceof HTMLElement && target.hasAttribute('data-filter-close')) {
                    closeFilterModal();
                }
            });
        }

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && filterModal?.classList.contains('active')) {
                closeFilterModal();
            }
        });

        window.addEventListener('resize', () => {
            if (!filterCard) return;
            if (window.innerWidth > 900) {
                if (filterModal?.classList.contains('active')) closeFilterModal();
                filterCard.style.removeProperty('display');
            } else if (!filterModal?.classList.contains('active')) {
                filterCard.style.display = 'none';
            }
        });

        const tagsWrap = document.querySelector('.catalog-tags-wrap');
        if (tagsWrap) {
            const tags = tagsWrap.querySelector('.catalog-tags');
            const toggle = tagsWrap.querySelector('.catalog-tags-toggle');
            if (tags && toggle) {
                const setTagsState = (isOpen) => {
                    tags.classList.toggle('expanded', isOpen);
                    toggle.setAttribute('aria-expanded', String(isOpen));
                    toggle.textContent = isOpen ? 'Свернуть подборки' : 'Показать все';
                    toggle.dataset.state = isOpen ? 'open' : 'closed';
                };
                setTagsState(false);
                toggle.addEventListener('click', () => {
                    const isOpen = tags.classList.contains('expanded');
                    setTagsState(!isOpen);
                });
            }
        }

        const filterState = {
            sphere: 'Все сферы',
            category: 'Все категории',
            q: '',
            investMin: null,
            investMax: null,
            paybackMin: null,
            paybackMax: null,
            profitMin: null,
            verified: false,
            tag: 'Все франшизы'
        };

        const activeFilters = { ...filterState };

        const filterOptions = {
            sphere: [{ label: 'Все сферы', value: 'Все сферы' }, ...spheres.map((item) => ({ label: item, value: item }))],
            category: [{ label: 'Все категории', value: 'Все категории' }, ...categoryPool.map((item) => ({ label: item, value: item }))],
            invest: [
                { label: 'Любые вложения', min: null, max: null },
                { label: 'До 300 000 ₽', min: null, max: 300000 },
                { label: '300–700 тыс ₽', min: 300000, max: 700000 },
                { label: '700 тыс – 1.5 млн ₽', min: 700000, max: 1500000 },
                { label: '1.5–3 млн ₽', min: 1500000, max: 3000000 },
                { label: 'От 3 млн ₽', min: 3000000, max: null }
            ],
            payback: [
                { label: 'Любая окупаемость', min: null, max: null },
                { label: 'До 6 мес', min: null, max: 6 },
                { label: '6–12 мес', min: 6, max: 12 },
                { label: '12–18 мес', min: 12, max: 18 },
                { label: 'От 18 мес', min: 18, max: null }
            ],
            profit: [
                { label: 'Любая прибыль', min: null },
                { label: 'от 100 000 ₽', min: 100000 },
                { label: 'от 200 000 ₽', min: 200000 },
                { label: 'от 300 000 ₽', min: 300000 },
                { label: 'от 500 000 ₽', min: 500000 },
                { label: 'от 1 000 000 ₽', min: 1000000 }
            ]
        };

        const investRange = document.querySelector('#invest-range');
        const investValue = document.querySelector('#invest-value');
        const profitRange = document.querySelector('#profit-range');
        const profitValue = document.querySelector('#profit-value');
        const investBtn = document.querySelector('.filter-select[data-filter="invest"]');
        const profitBtn = document.querySelector('.filter-select[data-filter="profit"]');

        const investRangeMax = investRange ? Number(investRange.max) : 3000000;
        const profitRangeMax = profitRange ? Number(profitRange.max) : 1000000;

        let investPresetButtons = [];
        let profitPresetButtons = [];

        const setInvestLabel = (label) => {
            if (investValue) investValue.textContent = label;
            if (investBtn) investBtn.textContent = label;
        };

        const setProfitLabel = (label) => {
            if (profitValue) profitValue.textContent = label;
            if (profitBtn) profitBtn.textContent = label;
        };

        const resetInvestFilter = () => {
            filterState.investMin = null;
            filterState.investMax = null;
            if (investRange) investRange.value = String(investRangeMax);
            setInvestLabel('Любые вложения');
            if (investSelect) investSelect.value = '0';
            if (investPresetButtons.length) {
                investPresetButtons.forEach((item) => item.classList.remove('active'));
            }
        };

        const resetProfitFilter = () => {
            filterState.profitMin = null;
            if (profitRange) profitRange.value = String(profitRangeMax);
            setProfitLabel('Любая прибыль');
            if (profitSelect) profitSelect.value = '0';
            if (profitPresetButtons.length) {
                profitPresetButtons.forEach((item) => item.classList.remove('active'));
            }
        };

        const setInvestMax = (value, keepMin = false) => {
            if (!Number.isFinite(value)) return;
            const clamped = Math.max(50000, Math.min(value, investRangeMax));
            filterState.investMax = clamped >= investRangeMax ? null : clamped;
            if (!keepMin) {
                filterState.investMin = null;
            }
            if (investRange) investRange.value = String(clamped);
            const label = clamped >= investRangeMax ? 'Любые вложения' : `До ${formatMoney(clamped)} ₽`;
            setInvestLabel(label);
            if (investPresetButtons.length) {
                investPresetButtons.forEach((item) => {
                    const val = Number(item.dataset.invest);
                    item.classList.toggle('active', Number.isFinite(val) && val === clamped && clamped < investRangeMax);
                });
            }
        };

        const setProfitMin = (value) => {
            if (!Number.isFinite(value)) return;
            const clamped = Math.max(0, Math.min(value, profitRangeMax));
            filterState.profitMin = clamped >= profitRangeMax ? null : clamped;
            if (profitRange) profitRange.value = String(clamped);
            const label = clamped >= profitRangeMax ? 'Любая прибыль' : `от ${formatMoney(clamped)} ₽`;
            setProfitLabel(label);
            if (profitPresetButtons.length) {
                profitPresetButtons.forEach((item) => {
                    const val = Number(item.dataset.profit);
                    item.classList.toggle('active', Number.isFinite(val) && val === clamped && clamped < profitRangeMax);
                });
            }
        };


        if (investRange) {
            investRange.addEventListener('input', () => {
                setInvestMax(Number(investRange.value));
            });
        }

        if (profitRange) {
            profitRange.addEventListener('input', () => {
                setProfitMin(Number(profitRange.value));
            });
        }

        investPresetButtons = Array.from(document.querySelectorAll('[data-invest]'));
        investPresetButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const value = Number(btn.dataset.invest);
                if (Number.isFinite(value)) {
                    investPresetButtons.forEach((item) => item.classList.remove('active'));
                    btn.classList.add('active');
                    setInvestMax(value);
                }
            });
        });

        profitPresetButtons = Array.from(document.querySelectorAll('[data-profit]'));
        profitPresetButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const value = Number(btn.dataset.profit);
                if (Number.isFinite(value)) {
                    profitPresetButtons.forEach((item) => item.classList.remove('active'));
                    btn.classList.add('active');
                    setProfitMin(value);
                }
            });
        });

        const sortState = {
            key: 'default'
        };

        const sortOptions = [
            { key: 'default', label: 'Сортировка: по умолчанию' },
            { key: 'popularity', label: 'Сортировка: по популярности' },
            { key: 'date-desc', label: 'Сортировка: новые выше' },
            { key: 'date-asc', label: 'Сортировка: старые выше' },
            { key: 'invest-asc', label: 'Сортировка: вложения меньше' },
            { key: 'invest-desc', label: 'Сортировка: вложения больше' }
        ];

        const paginationEl = document.querySelector('.pagination');
        const paginationState = {
            page: 1,
            pageSize: 48
        };

        const calcPageSize = () => 48;

        const syncUrlState = (filters, page) => {
            const url = new URL(window.location.href);
            const lockedSphere = (document.body.dataset.defaultSphere || '').trim();
            const lockedCategory = (document.body.dataset.defaultCategory || '').trim();
            const q = (filters?.q || '').trim();
            const sphere = (filters?.sphere || '').trim();
            const category = (filters?.category || '').trim();
            const tag = (filters?.tag || '').trim();
            if (q) url.searchParams.set('q', q);
            else url.searchParams.delete('q');
            if (!lockedSphere && sphere && sphere !== 'Все сферы') url.searchParams.set('sphere', sphere);
            else url.searchParams.delete('sphere');
            if (!lockedCategory && category && category !== 'Все категории') url.searchParams.set('category', category);
            else url.searchParams.delete('category');
            if (tag && tag !== 'Все франшизы') url.searchParams.set('tag', tag);
            else url.searchParams.delete('tag');
            if (!Number.isFinite(page) || page <= 1) url.searchParams.delete('page');
            else url.searchParams.set('page', String(page));
            window.history.replaceState(null, '', url);
        };

        const updateCatalogHeading = () => {
            const catalogTitle = document.querySelector('.catalog-hero .page-title');
            const titleNode = document.querySelector('title');
            const headingText = 'Каталог франшиз';
            if (catalogTitle) catalogTitle.textContent = headingText;
            if (titleNode) titleNode.textContent = `${headingText} | Франшиза`;
        };

        const updateCatalogBreadcrumbs = () => {
            const crumbs = document.querySelector('.catalog-hero .breadcrumbs');
            if (!crumbs) return;
            const sphere = activeFilters.sphere !== 'Все сферы' ? activeFilters.sphere : '';
            const category = activeFilters.category !== 'Все категории' ? activeFilters.category : '';
            const sphereHref = sphereLandingPages[sphere] || 'catalog.html';
            const categoryHref = categoryPages[sphere]?.[category] || sphereHref;
            const parts = [
                '<span><a href="index.html">Главная</a></span>',
                '<span><a href="catalog.html">Каталог франшиз</a></span>'
            ];
            if (sphere) parts.push(`<span><a href="${sphereHref}">${String(sphere).replaceAll('<', '&lt;').replaceAll('>', '&gt;')}</a></span>`);
            if (category) parts.push(`<span><a href="${categoryHref}">${String(category).replaceAll('<', '&lt;').replaceAll('>', '&gt;')}</a></span>`);
            crumbs.innerHTML = parts.join('');
        };

        const getPaginationModel = (totalPages, currentPage) => {
            if (totalPages <= 7) return Array.from({ length: totalPages }, (_, i) => i + 1);
            if (currentPage <= 4) return [1, 2, 3, 4, 5, '…', totalPages];
            if (currentPage >= totalPages - 3) return [1, '…', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
            return [1, '…', currentPage - 1, currentPage, currentPage + 1, '…', totalPages];
        };

        const renderPagination = (totalPages, currentPage) => {
            if (!paginationEl) return;
            if (totalPages <= 1) {
                paginationEl.innerHTML = '';
                paginationEl.hidden = true;
                paginationEl.style.display = 'none';
                return;
            }
            paginationEl.hidden = false;
            paginationEl.style.display = '';
            paginationEl.innerHTML = '';

            const makeBtn = (label, { page, isNav = false, disabled = false, current = false, ariaLabel } = {}) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `page-btn${isNav ? ' page-nav' : ''}${current ? ' active' : ''}`;
                btn.textContent = String(label);
                if (ariaLabel) btn.setAttribute('aria-label', ariaLabel);
                if (current) btn.setAttribute('aria-current', 'page');
                if (disabled) btn.disabled = true;
                if (!disabled && typeof page === 'number') {
                    btn.addEventListener('click', () => {
                        updatePagination({ page, resetPage: false, scrollToTop: true });
                    });
                }
                return btn;
            };

            paginationEl.appendChild(
                makeBtn('‹', {
                    page: Math.max(1, currentPage - 1),
                    isNav: true,
                    disabled: currentPage <= 1,
                    ariaLabel: 'Предыдущая страница'
                })
            );

            const model = getPaginationModel(totalPages, currentPage);
            model.forEach((item) => {
                if (item === '…') {
                    const span = document.createElement('span');
                    span.className = 'page-ellipsis';
                    span.setAttribute('aria-hidden', 'true');
                    span.textContent = '…';
                    paginationEl.appendChild(span);
                    return;
                }
                const pageNum = Number(item);
                paginationEl.appendChild(
                    makeBtn(pageNum, {
                        page: pageNum,
                        current: pageNum === currentPage
                    })
                );
            });

            paginationEl.appendChild(
                makeBtn('›', {
                    page: Math.min(totalPages, currentPage + 1),
                    isNav: true,
                    disabled: currentPage >= totalPages,
                    ariaLabel: 'Следующая страница'
                })
            );
        };

        const updatePagination = ({ page, resetPage = false, scrollToTop = false } = {}) => {
            if (!paginationEl) return;
            const list = document.querySelector('.catalog-cards');
            if (!list) return;
            paginationState.pageSize = calcPageSize();
            const cards = Array.from(list.querySelectorAll('.popular-card'));
            const filteredCards = cards.filter((card) => !card.classList.contains('is-filtered-out'));

            const totalPages = Math.max(1, Math.ceil(filteredCards.length / paginationState.pageSize));
            if (resetPage) paginationState.page = 1;
            if (typeof page === 'number' && Number.isFinite(page)) paginationState.page = page;
            paginationState.page = Math.max(1, Math.min(paginationState.page, totalPages));

            const start = (paginationState.page - 1) * paginationState.pageSize;
            const end = start + paginationState.pageSize;
            const visibleOnPage = filteredCards.slice(start, end);
            const inPage = new Set(visibleOnPage);

            cards.forEach((card) => {
                if (card.classList.contains('is-filtered-out')) {
                    card.classList.remove('is-paged-out');
                    return;
                }
                card.classList.toggle('is-paged-out', !inPage.has(card));
            });

            renderPagination(totalPages, paginationState.page);
            syncUrlState(activeFilters, paginationState.page);

            const countEl = document.querySelector('#catalog-count');
            const countMobile = document.querySelector('#catalog-count-mobile');
            const shown = visibleOnPage.length;
            const total = filteredCards.length;
            const countText = `Показано франшиз: ${shown} из ${total}`;
            if (countEl) countEl.textContent = countText;
            if (countMobile) countMobile.textContent = countText;

            if (scrollToTop) {
                const topAnchor = document.querySelector('.catalog-hero') || document.querySelector('.catalog-toolbar') || list;
                if (topAnchor) {
                    const top = Math.max(0, window.scrollY + topAnchor.getBoundingClientRect().top - 12);
                    window.scrollTo({ top, behavior: 'auto' });
                }
            }
        };

        let paginationResizeRaf = 0;
        window.addEventListener('resize', () => {
            if (!paginationEl) return;
            window.cancelAnimationFrame(paginationResizeRaf);
            paginationResizeRaf = window.requestAnimationFrame(() => {
                updatePagination({ resetPage: false });
            });
        });

        const applySort = (key = sortState.key) => {
            const list = document.querySelector('.catalog-cards');
            if (!list) return;
            const cards = Array.from(list.querySelectorAll('.popular-card'));
            cards.sort((a, b) => {
                if (key === 'popularity') return Number(b.dataset.popularity) - Number(a.dataset.popularity);
                if (key === 'date-desc') return Number(b.dataset.date) - Number(a.dataset.date);
                if (key === 'date-asc') return Number(a.dataset.date) - Number(b.dataset.date);
                if (key === 'invest-asc') return Number(a.dataset.invest) - Number(b.dataset.invest);
                if (key === 'invest-desc') return Number(b.dataset.invest) - Number(a.dataset.invest);
                return Number(a.dataset.order) - Number(b.dataset.order);
            });
            cards.forEach((card) => list.appendChild(card));
            updatePagination({ resetPage: false });
        };

        const initSelect = (selector, options, onChange) => {
            const select = document.querySelector(selector);
            if (!select) return null;
            select.innerHTML = options
                .map((option, index) => `<option value="${index}">${option.label}</option>`)
                .join('');
            select.addEventListener('change', () => {
                const idx = Number(select.value);
                const option = options[idx];
                if (option) onChange(option);
            });
            return select;
        };

        const renderNativeValueSelect = (select, values) => {
            if (!select) return;
            const escapeHtml = (value) =>
                String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            select.innerHTML = values
                .map((value) => `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`)
                .join('');
        };

        const sphereSelect = document.querySelector('[data-filter="sphere"]');
        const categorySelect = document.querySelector('[data-filter="category"]');
        const allSphereValues = ['Все сферы', ...spheres];
        const allCategoryValues = ['Все категории', ...categoryPool];

        const getCategoryValuesForSphere = (sphereValue) => {
            const sphere = String(sphereValue || 'Все сферы').trim() || 'Все сферы';
            if (sphere === 'Все сферы') return allCategoryValues;
            const scopedCategories = Array.isArray(sphereCategories[sphere]) ? sphereCategories[sphere] : [];
            return ['Все категории', ...scopedCategories];
        };

        const syncCategorySelect = (sphereValue, preferredCategory = null) => {
            const values = getCategoryValuesForSphere(sphereValue);
            renderNativeValueSelect(categorySelect, values);
            const preferred = (preferredCategory || '').trim();
            const next = preferred && values.includes(preferred) ? preferred : 'Все категории';
            if (categorySelect) categorySelect.value = next;
            filterState.category = next;
        };

        if (spheres.length) {
            renderNativeValueSelect(sphereSelect, allSphereValues);
            if (sphereSelect) sphereSelect.value = 'Все сферы';
            filterState.sphere = 'Все сферы';
            syncCategorySelect('Все сферы');
        } else {
            sphereSelect?.remove();
            categorySelect?.remove();
        }

        if (sphereSelect) {
            sphereSelect.addEventListener('change', () => {
                const nextSphere = (sphereSelect.value || 'Все сферы').trim() || 'Все сферы';
                filterState.sphere = nextSphere;
                syncCategorySelect(nextSphere, 'Все категории');
            });
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', () => {
                const nextCategory = (categorySelect.value || 'Все категории').trim() || 'Все категории';
                filterState.category = nextCategory;
            });
        }

        const paybackSelect = initSelect('[data-filter="payback"]', filterOptions.payback, (option) => {
            filterState.paybackMin = option.min ?? null;
            filterState.paybackMax = option.max ?? null;
        });

        const sortSelect = initSelect('[data-sort]', sortOptions.map((opt) => ({ label: opt.label, value: opt.key })), (option) => {
            sortState.key = option.value;
            applySort(option.value);
        });

        if (sortSelect) {
            sortSelect.value = '0';
            sortState.key = sortOptions[0].key;
            applySort(sortState.key);
        }

        const investSelect = initSelect('[data-filter="invest"]', filterOptions.invest, (option) => {
            filterState.investMin = option.min ?? null;
            filterState.investMax = option.max ?? null;
            if (investRange) {
                investRange.value = String(option.max ?? investRangeMax);
            }
            setInvestLabel(option.label);
        });

        const profitSelect = initSelect('[data-filter="profit"]', filterOptions.profit, (option) => {
            const targetValue = option.min ?? profitRangeMax;
            setProfitMin(targetValue);
            setProfitLabel(option.label);
        });

        if (investSelect) investSelect.value = '0';
        if (profitSelect) profitSelect.value = '0';
        resetInvestFilter();
        resetProfitFilter();

        const verifiedInput = document.querySelector('.filter-check input[name="verified"]');
        if (verifiedInput) {
            verifiedInput.addEventListener('change', () => {
                filterState.verified = verifiedInput.checked;
            });
        }

        const tagControls = document.querySelectorAll('[data-tag]');
        const setTagFilter = (tag) => {
            const normalizedTag = String(tag || 'Все франшизы').trim() || 'Все франшизы';
            const isVerifiedTag = normalizedTag === 'Проверено';
            filterState.tag = isVerifiedTag ? 'Все франшизы' : normalizedTag;
            if (isVerifiedTag) {
                filterState.verified = true;
                if (verifiedInput) verifiedInput.checked = true;
            }
            tagControls.forEach((control) => {
                const isActive = control.dataset.tag === filterState.tag;
                control.classList.toggle('active', isActive);
            });
        };

        tagControls.forEach((control) => {
            control.addEventListener('click', (event) => {
                event.preventDefault();
                const tag = control.dataset.tag || 'Все франшизы';
                setTagFilter(tag);
                commitFilters();
            });
        });

        const applyFilters = () => {
            const cards = Array.from(document.querySelectorAll('.catalog-cards .popular-card'));
            const total = cards.length;
            const queryTokens = (activeFilters.q || '')
                .trim()
                .toLowerCase()
                .split(/\s+/)
                .filter(Boolean);
            let shown = 0;
            cards.forEach((card) => {
                const sphere = card.dataset.sphere || '';
                const category = (card.dataset.category || '').trim();
                const invest = Number(card.dataset.invest || 0);
                const payback = Number(card.dataset.payback || 0);
                const profit = Number(card.dataset.profit || 0);
                const verified = card.dataset.verified === 'true';
                const tags = (card.dataset.tags || '').split('|').filter(Boolean);
                const searchText = `${card.dataset.name || ''} ${card.dataset.desc || ''}`.toLowerCase();

                let visible = true;
                if (queryTokens.length) {
                    visible = visible && queryTokens.every((token) => searchText.includes(token));
                }
                if (activeFilters.sphere !== 'Все сферы' && sphere !== activeFilters.sphere) visible = false;
                if (activeFilters.category !== 'Все категории' && category !== activeFilters.category) visible = false;

                if (activeFilters.investMin !== null) {
                    visible = visible && invest >= activeFilters.investMin;
                }
                if (activeFilters.investMax !== null) {
                    visible = visible && invest <= activeFilters.investMax;
                }

                if (activeFilters.paybackMin !== null) {
                    visible = visible && payback >= activeFilters.paybackMin;
                }
                if (activeFilters.paybackMax !== null) {
                    visible = visible && payback <= activeFilters.paybackMax;
                }

                if (activeFilters.profitMin !== null) {
                    visible = visible && profit >= activeFilters.profitMin;
                }

                if (activeFilters.verified) {
                    visible = visible && verified;
                }

                if (activeFilters.tag !== 'Все франшизы') {
                    visible = visible && tags.includes(activeFilters.tag);
                }

                card.classList.toggle('is-filtered-out', !visible);
                if (visible) shown += 1;
            });
        };

        const commitFilters = () => {
            Object.assign(activeFilters, filterState);
            applyFilters();
            updatePagination({ resetPage: true });
            updateCatalogHeading();
            updateCatalogBreadcrumbs?.();
        };

        const applyButton = document.querySelector('.filter-btn');
        if (applyButton) {
            applyButton.addEventListener('click', (event) => {
                event.preventDefault();
                commitFilters();
            });
        }

        const setNativeValue = (select, value) => {
            if (!select || !value) return;
            const v = String(value).trim();
            if (!v) return;
            const option = Array.from(select.options).find((opt) => opt.value === v);
            if (option) select.value = v;
        };

        const params = new URLSearchParams(window.location.search);
        const qParam = (params.get('q') || '').trim();
        const defaultSphere = (document.body.dataset.defaultSphere || '').trim();
        const defaultCategory = (document.body.dataset.defaultCategory || '').trim();
        const sphereParam = (params.get('sphere') || defaultSphere || '').trim();
        const categoryParam = (params.get('category') || defaultCategory || '').trim();
        const tagParam = (params.get('tag') || '').trim();
        const pageParam = Number(params.get('page') || 1);

        paginationState.page = Number.isFinite(pageParam) && pageParam > 0 ? pageParam : 1;

        if (qParam) {
            filterState.q = qParam;
            document.querySelectorAll('.header-search input[name="q"]').forEach((input) => {
                input.value = qParam;
            });
        }

        const currentSphere = (defaultSphere || '').trim();
        const currentCategory = (defaultCategory || '').trim();

        if (sphereParam) {
            setNativeValue(sphereSelect, sphereParam);
            const nextSphere = (sphereSelect?.value || 'Все сферы').trim() || 'Все сферы';
            filterState.sphere = nextSphere;
            syncCategorySelect(nextSphere, currentCategory || null);
        }

        if (categoryParam) {
            setNativeValue(categorySelect, categoryParam);
            filterState.category = (categorySelect?.value || 'Все категории').trim() || 'Все категории';
        }

        setTagFilter(tagParam || 'Все франшизы');
        Object.assign(activeFilters, filterState);
        updateCatalogHeading();
        updateCatalogBreadcrumbs();
        applyFilters();
        updatePagination({ resetPage: false });
    });
})();

/* ===== franchise.js ===== */
(() => {
    const path = (window.location.pathname.split('/').pop() || '').toLowerCase();
    const isFranchisePath = path.startsWith('franchise');
    const hasFranchiseDataset = !!document.body?.dataset?.defaultFranchiseId;
    if (!isFranchisePath && !hasFranchiseDataset) return;
    const manifestPromise = window.__loadFranchiseManifest
        ? window.__loadFranchiseManifest()
        : Promise.resolve({ spheres: [], collections: [] });

    const catalogPageMap = {
        'Торговля': {
            'Аптеки': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%90%D0%BF%D1%82%D0%B5%D0%BA%D0%B8',
            'Электроника': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%AD%D0%BB%D0%B5%D0%BA%D1%82%D1%80%D0%BE%D0%BD%D0%B8%D0%BA%D0%B0'
        },
        'Еда': {
            'Пекарни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9F%D0%B5%D0%BA%D0%B0%D1%80%D0%BD%D0%B8',
            'Кофейни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9A%D0%BE%D1%84%D0%B5%D0%B9%D0%BD%D0%B8'
        },
        'Авто': {
            'Автосервисы': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D1%81%D0%B5%D1%80%D0%B2%D0%B8%D1%81%D1%8B%20%D0%B8%20%D0%A1%D0%A2%D0%9E',
            'Автомойки': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D0%BC%D0%BE%D0%B9%D0%BA%D0%B8'
        },
        'Обучение': {
            'Языковые школы': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%AF%D0%B7%D1%8B%D0%BA%D0%BE%D0%B2%D1%8B%D0%B5%20%D1%88%D0%BA%D0%BE%D0%BB%D1%8B',
            'Детские центры': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%94%D0%B5%D1%82%D1%81%D0%BA%D0%B8%D0%B5%20%D1%86%D0%B5%D0%BD%D1%82%D1%80%D1%8B'
        },
        'Красота и здоровье': {
            'Стоматологии': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%A1%D1%82%D0%BE%D0%BC%D0%B0%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D0%B8',
            'Косметология': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%9A%D0%BE%D1%81%D0%BC%D0%B5%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D1%8F'
        }
    };
    const getCategoryPage = (sphereName, categoryName) =>
        catalogPageMap?.[sphereName]?.[categoryName] || `catalog.html?sphere=${encodeURIComponent(sphereName)}&category=${encodeURIComponent(categoryName)}`;
    const getSphereLandingPage = (sphere) => {
        const sphereName = String(sphere?.name || '').trim();
        const firstCategory = Array.isArray(sphere?.categories) ? sphere.categories.find((item) => String(item?.name || '').trim()) : null;
        const categoryName = String(firstCategory?.name || '').trim();
        return sphereName && categoryName
            ? getCategoryPage(sphereName, categoryName)
            : (sphereName ? `catalog.html?sphere=${encodeURIComponent(sphereName)}` : 'catalog.html');
    };
    const franchiseUrlMap = {
        'sovetskaya-apteka': 'franchise-sovetskaya-apteka.html',
        'apteka-zdorovo': 'franchise-apteka-zdorovo.html',
        'apteka-gorod': 'franchise-apteka-gorod.html',
        'techno-shop': 'franchise-techno-shop.html',
        'fit-service': 'franchise-fit-service.html',
        'carwash-24': 'franchise-carwash-24.html',
        'tyre-service-pro': 'franchise-tyre-service-pro.html',
        'detailing-lab': 'franchise-detailing-lab.html',
        'avtomoyka-city': 'franchise-avtomoyka-city.html',
        'nastoyashaya-pekarna': 'franchise-nastoyashaya-pekarna.html',
        'pekarnya-dom': 'franchise-pekarnya-dom.html',
        'kafe-kruzhka': 'franchise-kafe-kruzhka.html',
        'coffeeway': 'franchise-coffeeway.html',
        'lingua-club': 'franchise-lingua-club.html',
        'english-room': 'franchise-english-room.html',
        'it-school': 'franchise-it-school.html',
        'kids-club': 'franchise-kids-club.html',
        'semeynaya-stomatologiya': 'franchise-semeynaya-stomatologiya.html',
        'dental-plus': 'franchise-dental-plus.html',
        'cosmo-studio': 'franchise-cosmo-studio.html'
    };
    const resolveFranchiseUrl = (item, fallbackValue = 'franchise.html') => {
        const directUrl = String(item?.url || '').trim();
        if (directUrl && !directUrl.includes('?')) return directUrl;
        const id = String(item?.meta?.id || item?.slug || '').trim();
        if (id && franchiseUrlMap[id]) return franchiseUrlMap[id];
        if (id) return `franchise-${id}.html`;
        return fallbackValue;
    };

    const fallbackFranchises = {};

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

        const mobileMenuList = menu.querySelector('.mobile-menu-list');
        if (mobileMenuList instanceof HTMLElement && !mobileMenuList.querySelector('[data-mobile-acc="collections"]')) {
            const collectionsAcc = document.createElement('div');
            collectionsAcc.className = 'mobile-acc';
            collectionsAcc.setAttribute('data-mobile-acc', 'collections');
            collectionsAcc.innerHTML = `
            <button class="mobile-acc-trigger" type="button" aria-expanded="false" aria-controls="mobile-collections" data-mobile-acc-trigger="collections">
              Подборки <span class="mobile-chev" aria-hidden="true"></span>
            </button>
            <div class="mobile-acc-content" id="mobile-collections">
              <div class="mobile-category-grid" data-mobile-collections-grid></div>
            </div>
          `;
            const contactsLink = mobileMenuList.querySelector('a.mobile-menu-link[href*="contacts"]');
            if (contactsLink) mobileMenuList.insertBefore(collectionsAcc, contactsLink);
            else mobileMenuList.appendChild(collectionsAcc);
        }

        const accTriggers = Array.from(menu.querySelectorAll('[data-mobile-acc-trigger]'));
        const accBlocks = Array.from(menu.querySelectorAll('[data-mobile-acc]'));
        const setAccOpen = (key, open) => {
            const block = menu.querySelector(`[data-mobile-acc=\"${key}\"]`);
            const trigger = menu.querySelector(`[data-mobile-acc-trigger=\"${key}\"]`);
            if (!block || !trigger) return;
            block.classList.toggle('open', open);
            trigger.setAttribute('aria-expanded', String(open));
        };

        accTriggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const key = trigger.getAttribute('data-mobile-acc-trigger');
                if (!key) return;
                const isOpen = menu.querySelector(`[data-mobile-acc=\"${key}\"]`)?.classList.contains('open');
                accBlocks.forEach((block) => {
                    const otherKey = block.getAttribute('data-mobile-acc');
                    if (otherKey && otherKey !== key) setAccOpen(otherKey, false);
                });
                setAccOpen(key, !isOpen);
            });
        });

        const categoriesGrid = menu.querySelector('[data-mobile-categories-grid]');
        const categoriesAcc = menu.querySelector('[data-mobile-acc="categories"]');
        const categoryPages = {
            'Торговля': {
                'Аптеки': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%90%D0%BF%D1%82%D0%B5%D0%BA%D0%B8',
                'Электроника': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%AD%D0%BB%D0%B5%D0%BA%D1%82%D1%80%D0%BE%D0%BD%D0%B8%D0%BA%D0%B0'
            },
            'Еда': {
                'Пекарни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9F%D0%B5%D0%BA%D0%B0%D1%80%D0%BD%D0%B8',
                'Кофейни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9A%D0%BE%D1%84%D0%B5%D0%B9%D0%BD%D0%B8'
            },
            'Авто': {
                'Автосервисы': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D1%81%D0%B5%D1%80%D0%B2%D0%B8%D1%81%D1%8B%20%D0%B8%20%D0%A1%D0%A2%D0%9E',
                'Автомойки': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D0%BC%D0%BE%D0%B9%D0%BA%D0%B8'
            },
            'Обучение': {
                'Языковые школы': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%AF%D0%B7%D1%8B%D0%BA%D0%BE%D0%B2%D1%8B%D0%B5%20%D1%88%D0%BA%D0%BE%D0%BB%D1%8B',
                'Детские центры': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%94%D0%B5%D1%82%D1%81%D0%BA%D0%B8%D0%B5%20%D1%86%D0%B5%D0%BD%D1%82%D1%80%D1%8B'
            },
            'Красота и здоровье': {
                'Стоматологии': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%A1%D1%82%D0%BE%D0%BC%D0%B0%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D0%B8',
                'Косметология': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%9A%D0%BE%D1%81%D0%BC%D0%B5%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D1%8F'
            }
        };
        const getCategoryPage = (sphereName, categoryName) =>
            categoryPages?.[sphereName]?.[categoryName] || `catalog.html?sphere=${encodeURIComponent(sphereName)}&category=${encodeURIComponent(categoryName)}`;
        const getSphereLandingPage = (sphere) => {
            const sphereName = String(sphere?.name || '').trim();
            const firstCategory = Array.isArray(sphere?.categories) ? sphere.categories.find((item) => String(item?.name || '').trim()) : null;
            const categoryName = String(firstCategory?.name || '').trim();
            return (sphereName && categoryName && getCategoryPage(sphereName, categoryName)) || (sphereName ? `catalog.html?sphere=${encodeURIComponent(sphereName)}` : 'catalog.html');
        };

        if (categoriesGrid) {
            manifestPromise.then((manifest) => {
                const spheres = manifest.spheres || [];
                if (!spheres.length) {
                    if (categoriesAcc instanceof HTMLElement) categoriesAcc.style.display = 'none';
                    return;
                }
                categoriesGrid.innerHTML = spheres
                    .map((sphere) => {
                        const name = String(sphere?.name || '').trim();
                        if (!name) return '';
                        const href = getSphereLandingPage(sphere);
                        return `<a class="chip" href="${href}"><span class="icon" aria-hidden="true"></span><span class="chip-text">${name}</span></a>`;
                    })
                    .join('');
            });
        }

        const collectionsGrid = menu.querySelector('[data-mobile-collections-grid]');
        const collectionsAcc = menu.querySelector('[data-mobile-acc=\"collections\"]');
        if (collectionsGrid) {
            manifestPromise.then((manifest) => {
                const collections = (manifest.collections || []).filter((item) => String(item?.name || '').trim() !== 'Проверено');
                if (!collections.length) {
                    if (collectionsAcc instanceof HTMLElement) collectionsAcc.style.display = 'none';
                    return;
                }
                const currentTag = String(new URLSearchParams(window.location.search).get('tag') || '').trim().toLowerCase();
                collectionsGrid.innerHTML = collections
                    .map((col) => {
                        const name = String(col?.name || '').trim();
                        if (!name) return '';
                        const href = `catalog.html?tag=${encodeURIComponent(name)}`;
                        const isActive = currentTag && name.toLowerCase() === currentTag ? ' is-active' : '';
                        return `<a class=\"chip mobile-collections-chip${isActive}\" href=\"${href}\"><span class=\"chip-text\">${name}</span></a>`;
                    })
                    .join('');
            });
        }
    }

    const header = document.querySelector('.site-header');
    if (header) {
        const isSolid = document.body.classList.contains('header-solid');
        const setHeaderState = () => {
            header.classList.toggle('scrolled', isSolid || window.scrollY > 10);
        };
        setHeaderState();
        if (!isSolid) {
            window.addEventListener('scroll', setHeaderState, { passive: true });
        }
    }

    const headerSearchForms = document.querySelectorAll('.header-search');
    if (headerSearchForms.length) {
        headerSearchForms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const input = form.querySelector('input[type="search"], input[name="q"]');
                const q = (input?.value || '').trim();
                const url = new URL('catalog.html', window.location.href);
                if (q) url.searchParams.set('q', q);
                window.location.href = url.toString();
            });
        });
    }

    const setupCategoriesDropdown = (manifest) => {
        const dropdown = document.querySelector('[data-categories-dropdown]');
        if (!dropdown) return;
        const trigger = dropdown.querySelector('[data-categories-trigger]');
        const panel = dropdown.querySelector('[data-categories-panel]');
        const list = dropdown.querySelector('[data-categories-list]');
        const titleEl = dropdown.querySelector('[data-categories-title]');
        const subgridEl = dropdown.querySelector('[data-categories-subgrid]');
        if (!trigger || !panel || !list || !titleEl || !subgridEl) return;

        const spheres = Array.isArray(manifest?.spheres) ? manifest.spheres : [];
        if (!spheres.length) {
            dropdown.remove();
            return;
        }

        const escapeHtml = (value) =>
            String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');

        list.innerHTML = spheres
            .map(
                (sphere, index) =>
                    `<button class="categories-item${index === 0 ? ' active' : ''}" type="button" data-index="${index}"><span class="icon" aria-hidden="true"></span><span>${escapeHtml(sphere?.name || '')}</span></button>`
            )
            .join('');

        const renderSubcats = (sphereIndex) => {
            const sphere = spheres[Math.max(0, Math.min(sphereIndex, spheres.length - 1))];
            const sphereName = String(sphere?.name || '').trim();
            const items = Array.isArray(sphere?.categories) ? sphere.categories : [];
            titleEl.textContent = sphereName || 'Категории';
            subgridEl.innerHTML = items.length
                ? items
                    .map((item) => {
                        const categoryName = String(item?.name || '').trim();
                        if (!categoryName) return '';
                        const href = getCategoryPage(sphereName, categoryName);
                        return `<a href="${href}">${escapeHtml(categoryName)}</a>`;
                    })
                    .join('')
                : `<a href="catalog.html?sphere=${encodeURIComponent(sphereName)}">Смотреть все франшизы в отрасли</a>`;
        };

        const setActive = (index) => {
            const i = Math.max(0, Math.min(index, spheres.length - 1));
            const btns = Array.from(list.querySelectorAll('.categories-item'));
            btns.forEach((btn, idx) => btn.classList.toggle('active', idx === i));
            renderSubcats(i);
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
        }); const setExpanded = (isOpen) => trigger.setAttribute('aria-expanded', String(isOpen));
        const setDropdownOpen = (isOpen) => {
            dropdown.classList.toggle('is-open', isOpen);
            setExpanded(isOpen);
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
            setDropdownOpen(true);
        };
        const closeDropdownSoon = () => {
            clearDropdownTimer();
            dropdownCloseTimer = window.setTimeout(() => setDropdownOpen(false), 120);
        };
        dropdown.addEventListener('mouseenter', openDropdown);
        dropdown.addEventListener('mouseleave', closeDropdownSoon);
        dropdown.addEventListener('focusin', openDropdown);
        dropdown.addEventListener('focusout', (event) => {
            const next = event.relatedTarget;
            if (next instanceof Node && dropdown.contains(next)) return;
            closeDropdownSoon();
        });
        panel.addEventListener('mouseenter', openDropdown);
        panel.addEventListener('mouseleave', closeDropdownSoon);

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            clearDropdownTimer();
            setDropdownOpen(!dropdown.classList.contains('is-open'));
        });

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Node)) return;
            if (!dropdown.contains(target)) setDropdownOpen(false);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && trigger.getAttribute('aria-expanded') === 'true') {
                setDropdownOpen(false);
                trigger.blur();
            }
        });

        setActive(0);
    };

    manifestPromise.then((manifest) => setupCategoriesDropdown(manifest));

    const params = new URLSearchParams(window.location.search);
    const defaultFranchiseId = (document.body.dataset.defaultFranchiseId || '').trim();
    const franchiseId = (params.get('id') || defaultFranchiseId || '').trim();

    manifestPromise.then((manifest) => {
        const flat = [];
        (manifest.spheres || []).forEach((sphere) => {
            const sphereName = String(sphere?.name || '').trim();
            (sphere?.categories || []).forEach((category) => {
                const categoryName = String(category?.name || '').trim();
                (category?.franchises || []).forEach((fr) => {
                    flat.push({
                        sphere: sphereName,
                        category: categoryName,
                        slug: String(fr?.slug || '').trim(),
                        url: String(fr?.url || '').trim(),
                        meta: fr?.meta || {}
                    });
                });
            });
        });

        let current = flat.find((item) => {
            const id = String(item.meta?.id || '').trim();
            return id === franchiseId || item.slug === franchiseId;
        });

        if (!current && fallbackFranchises[franchiseId]) {
            const fallback = fallbackFranchises[franchiseId];
            current = {
                sphere: fallback.sphere,
                category: fallback.category,
                slug: franchiseId,
                url: '',
                meta: { ...fallback.meta }
            };
        }

        if (!current && flat.length) {
            current = flat[0];
        }

        if (!current) {
            const fallbackFirstEntry = Object.entries(fallbackFranchises)[0];
            if (fallbackFirstEntry) {
                const [fallbackSlug, fallbackData] = fallbackFirstEntry;
                current = {
                    sphere: fallbackData.sphere,
                    category: fallbackData.category,
                    slug: fallbackSlug,
                    url: "",
                    meta: { ...fallbackData.meta }
                };
            }
        }

        if (!current) return;

        const meta = current.meta || {};
        const brand = String(meta.brand || meta.name || '').trim() || franchiseId;
        const subtitleText = String(meta.subtitle || meta.lead || meta.desc || '').trim();

        const escapeCrumb = (value) => String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
        const breadcrumbs = document.querySelector('.page-head .breadcrumbs');
        if (breadcrumbs) {
            const sphereName = String(current.sphere || '').trim();
            const categoryName = String(current.category || '').trim();
            const sphereHref = sphereName ? ('catalog.html?sphere=' + encodeURIComponent(sphereName)) : 'catalog.html';
            const categoryHref = (sphereName && categoryName)
                ? ('catalog.html?sphere=' + encodeURIComponent(sphereName) + '&category=' + encodeURIComponent(categoryName))
                : sphereHref;
            const headingText = String(document.querySelector('.page-head .page-title')?.textContent || brand).trim();
            breadcrumbs.innerHTML = [
                '<span><a href="index.html">Главная</a></span>',
                '<span><a href="catalog.html">Каталог франшиз</a></span>',
                sphereName ? `<span><a href="${sphereHref}">${escapeCrumb(sphereName)}</a></span>` : '',
                categoryName ? `<span><a href="${categoryHref}">${escapeCrumb(categoryName)}</a></span>` : '',
                `<span><a href="${window.location.pathname + window.location.search + window.location.hash}">${escapeCrumb(headingText)}</a></span>`
            ].filter(Boolean).join('');
        }

        const placeholderImage = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800"><rect width="1200" height="800" rx="32" fill="#f1f3f5"/><path d="M180 560l180-180 120 120 180-220 300 280H180z" fill="#d7dde4"/><circle cx="330" cy="250" r="55" fill="#d7dde4"/></svg>');
        const escapeHtml = (value) =>
            String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        const formatMoney = (value) => String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        const normalizeCardMeta = (meta) => {
            const fromList = Array.isArray(meta?.images)
                ? meta.images.map((value) => String(value || '').trim()).filter(Boolean)
                : [];
            const single = String(meta?.image || '').trim();
            const images = Array.from(new Set([...fromList, ...(single ? [single] : [])]));
            return {
                ...(meta || {}),
                images
            };
        };
        const resolveImages = (meta, limit = 10) => {
            const fromList = Array.isArray(meta?.images)
                ? meta.images.map((value) => String(value || '').trim()).filter(Boolean)
                : [];
            const single = String(meta?.image || '').trim();
            const seedBase = String(meta?.id || meta?.brand || single || 'franchise').toLowerCase().replace(/[^a-z0-9а-яё]+/gi, '-');
            const fallbackSeedImages = Array.from({ length: Math.max(limit - (single ? 1 : 0), 0) }, (_, index) =>
                `https://picsum.photos/seed/${encodeURIComponent(seedBase)}-${index + 1}/1600/900`
            );
            const images = fromList.length ? fromList : (single ? [single, ...fallbackSeedImages] : fallbackSeedImages);
            if (!images.length) return [placeholderImage];
            return images.slice(0, limit);
        };

        const sphereLandingPages = {
            'Авто': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D1%81%D0%B5%D1%80%D0%B2%D0%B8%D1%81%D1%8B%20%D0%B8%20%D0%A1%D0%A2%D0%9E',
            'Еда': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9F%D0%B5%D0%BA%D0%B0%D1%80%D0%BD%D0%B8',
            'Обучение': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%AF%D0%B7%D1%8B%D0%BA%D0%BE%D0%B2%D1%8B%D0%B5%20%D1%88%D0%BA%D0%BE%D0%BB%D1%8B',
            'Красота и здоровье': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%A1%D1%82%D0%BE%D0%BC%D0%B0%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D0%B8',
            'Торговля': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%90%D0%BF%D1%82%D0%B5%D0%BA%D0%B8'
        };
        const categoryPages = {
            'Авто': {
                'Автосервисы': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D1%81%D0%B5%D1%80%D0%B2%D0%B8%D1%81%D1%8B%20%D0%B8%20%D0%A1%D0%A2%D0%9E',
                'Автомойки': 'catalog.html?sphere=%D0%90%D0%B2%D1%82%D0%BE&category=%D0%90%D0%B2%D1%82%D0%BE%D0%BC%D0%BE%D0%B9%D0%BA%D0%B8'
            },
            'Еда': {
                'Пекарни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9F%D0%B5%D0%BA%D0%B0%D1%80%D0%BD%D0%B8',
                'Кофейни': 'catalog.html?sphere=%D0%95%D0%B4%D0%B0&category=%D0%9A%D0%BE%D1%84%D0%B5%D0%B9%D0%BD%D0%B8'
            },
            'Обучение': {
                'Языковые школы': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%AF%D0%B7%D1%8B%D0%BA%D0%BE%D0%B2%D1%8B%D0%B5%20%D1%88%D0%BA%D0%BE%D0%BB%D1%8B',
                'Детские центры': 'catalog.html?sphere=%D0%9E%D0%B1%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5&category=%D0%94%D0%B5%D1%82%D1%81%D0%BA%D0%B8%D0%B5%20%D1%86%D0%B5%D0%BD%D1%82%D1%80%D1%8B'
            },
            'Красота и здоровье': {
                'Стоматологии': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%A1%D1%82%D0%BE%D0%BC%D0%B0%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D0%B8',
                'Косметология': 'catalog.html?sphere=%D0%9A%D1%80%D0%B0%D1%81%D0%BE%D1%82%D0%B0%20%D0%B8%20%D0%B7%D0%B4%D0%BE%D1%80%D0%BE%D0%B2%D1%8C%D0%B5&category=%D0%9A%D0%BE%D1%81%D0%BC%D0%B5%D1%82%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D1%8F'
            },
            'Торговля': {
                'Аптеки': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%90%D0%BF%D1%82%D0%B5%D0%BA%D0%B8',
                'Электроника': 'catalog.html?sphere=%D0%A2%D0%BE%D1%80%D0%B3%D0%BE%D0%B2%D0%BB%D1%8F&category=%D0%AD%D0%BB%D0%B5%D0%BA%D1%82%D1%80%D0%BE%D0%BD%D0%B8%D0%BA%D0%B0'
            }
        };
        const fallbackList = [
            'https://picsum.photos/seed/franchise-1/1600/900',
            'https://picsum.photos/seed/franchise-2/1600/900',
            'https://picsum.photos/seed/franchise-3/1600/900',
            'https://picsum.photos/seed/franchise-4/1600/900',
            'https://picsum.photos/seed/franchise-5/1600/900',
            'https://picsum.photos/seed/franchise-6/1600/900',
            'https://picsum.photos/seed/franchise-7/1600/900',
            'https://picsum.photos/seed/franchise-8/1600/900',
            'https://picsum.photos/seed/franchise-9/1600/900',
            'https://picsum.photos/seed/franchise-10/1600/900'
        ];
        const mainImage = document.querySelector('[data-gallery-main]');
        const galleryMain = document.querySelector('.gallery-main');
        const countLabel = document.querySelector('[data-gallery-count]');
        const galleryThumbs = document.querySelector('.gallery-thumbs');
        const prevBtn = document.querySelector('.gallery-nav-prev');
        const nextBtn = document.querySelector('.gallery-nav-next');
        const thumbsScroller = document.querySelector('.gallery-thumbs');
        const thumbsPrev = document.querySelector('.thumbs-nav-prev');
        const thumbsNext = document.querySelector('.thumbs-nav-next');
        const galleryImages = resolveImages(meta, 10);
        const loadedImages = new Set();

        if (mainImage && galleryThumbs && countLabel) {
            galleryThumbs.innerHTML = galleryImages
                .map((src, index) => `
              <button class="gallery-thumb${index === 0 ? ' is-active' : ''}" type="button" data-gallery-thumb data-full="${escapeHtml(src)}" aria-label="Фото ${index + 1}" aria-selected="${index === 0 ? 'true' : 'false'}">
                <img src="${escapeHtml(src)}" alt="" loading="${index === 0 ? 'eager' : 'lazy'}" decoding="async">
              </button>
            `)
                .join('');

            const thumbs = Array.from(galleryThumbs.querySelectorAll('[data-gallery-thumb]'));
            let currentIndex = 0;
            const updateCount = (index = currentIndex) => {
                const total = Math.max(loadedImages.size, 1);
                const current = Math.min(index + 1, total);
                countLabel.textContent = `${current} / ${total}`;
            };
            const showImage = (index, options = {}) => {
                const { skipScroll = false } = options;
                const total = thumbs.length || galleryImages.length;
                if (!total) return;
                const safeIndex = (index + total) % total;
                const src = galleryImages[safeIndex] || placeholderImage;
                thumbs.forEach((button, buttonIndex) => {
                    const active = buttonIndex === safeIndex;
                    button.classList.toggle('is-active', active);
                    button.setAttribute('aria-selected', String(active));
                });
                currentIndex = safeIndex;
                updateCount(safeIndex);
                mainImage.style.opacity = '0.45';
                mainImage.src = src;
                if (!skipScroll) {
                    thumbs[safeIndex]?.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
                }
            };

            mainImage.addEventListener('load', () => {
                mainImage.style.opacity = '1';
            });
            mainImage.addEventListener('error', () => {
                if (mainImage.src !== placeholderImage) {
                    mainImage.src = placeholderImage;
                    return;
                }
                mainImage.style.opacity = '1';
            });

            thumbs.forEach((thumb, index) => {
                thumb.addEventListener('click', (event) => {
                    event.preventDefault();
                    showImage(index);
                });
                const thumbImg = thumb.querySelector('img');
                if (thumbImg) {
                    thumbImg.addEventListener('load', () => {
                        loadedImages.add(thumb.dataset.full || thumbImg.src || '');
                        updateCount();
                    }, { once: true });
                    thumbImg.addEventListener('error', () => {
                        thumbImg.src = placeholderImage;
                    }, { once: true });
                }
            });

            prevBtn?.addEventListener('click', () => showImage(currentIndex - 1));
            nextBtn?.addEventListener('click', () => showImage(currentIndex + 1));

            const swipeTarget = galleryMain instanceof HTMLElement
                ? galleryMain
                : (mainImage.parentElement instanceof HTMLElement ? mainImage.parentElement : null);
            if (swipeTarget instanceof HTMLElement && swipeTarget.dataset.gallerySwipeBound !== '1') {
                let touchStartX = null;
                let touchStartY = null;
                const resetSwipe = () => {
                    touchStartX = null;
                    touchStartY = null;
                };
                swipeTarget.addEventListener('touchstart', (event) => {
                    if (event.target instanceof Element && event.target.closest('.gallery-nav')) return;
                    const touch = event.changedTouches?.[0];
                    if (!touch) return;
                    touchStartX = touch.clientX;
                    touchStartY = touch.clientY;
                }, { passive: true });
                swipeTarget.addEventListener('touchcancel', resetSwipe, { passive: true });
                swipeTarget.addEventListener('touchend', (event) => {
                    const touch = event.changedTouches?.[0];
                    if (!touch || touchStartX === null || touchStartY === null) {
                        resetSwipe();
                        return;
                    }
                    const deltaX = touch.clientX - touchStartX;
                    const deltaY = touch.clientY - touchStartY;
                    resetSwipe();
                    if (Math.abs(deltaX) < 36) return;
                    if (Math.abs(deltaY) > Math.abs(deltaX) * 0.8) return;
                    showImage(deltaX < 0 ? currentIndex + 1 : currentIndex - 1);
                }, { passive: true });
                swipeTarget.dataset.gallerySwipeBound = '1';
            }

            if (thumbsScroller && thumbsPrev && thumbsNext) {
                const updateThumbButtons = () => {
                    const maxScroll = thumbsScroller.scrollWidth - thumbsScroller.clientWidth;
                    thumbsPrev.disabled = thumbsScroller.scrollLeft <= 4;
                    thumbsNext.disabled = thumbsScroller.scrollLeft >= maxScroll - 4;
                };
                const getThumbStep = () => {
                    const firstThumb = thumbsScroller.querySelector('.gallery-thumb');
                    if (!(firstThumb instanceof HTMLElement)) return Math.max(1, thumbsScroller.clientWidth * 0.2);
                    const styles = window.getComputedStyle(thumbsScroller);
                    const gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;
                    return Math.max(1, firstThumb.getBoundingClientRect().width + gap);
                };
                const scrollThumbs = (dir) => {
                    const step = getThumbStep();
                    const maxScroll = Math.max(0, thumbsScroller.scrollWidth - thumbsScroller.clientWidth);
                    const current = thumbsScroller.scrollLeft;
                    const epsilon = 0.5;
                    const nextIndex = dir > 0
                        ? Math.floor((current + epsilon) / step) + 1
                        : Math.ceil((current - epsilon) / step) - 1;
                    const target = Math.max(0, Math.min(maxScroll, nextIndex * step));
                    thumbsScroller.scrollTo({ left: target, behavior: 'smooth' });
                };
                thumbsPrev.addEventListener('click', () => scrollThumbs(-1));
                thumbsNext.addEventListener('click', () => scrollThumbs(1));
                thumbsScroller.addEventListener('scroll', updateThumbButtons, { passive: true });
                updateThumbButtons();
            }

            showImage(0, { skipScroll: true });
        }

        const fallbackItems = Object.entries(fallbackFranchises).map(([slug, fallback]) => ({
            sphere: fallback.sphere,
            category: fallback.category,
            slug,
            url: '',
            meta: { ...fallback.meta, id: fallback.meta?.id || slug }
        }));
        const franchiseItems = flat.concat(fallbackItems).map((item, index) => {
            const metaItem = normalizeCardMeta(item.meta || {});
            const brand = String(metaItem.brand || metaItem.name || '').trim() || 'Франшиза';
            const desc = String(metaItem.desc || '').trim() || (item.category ? `Франшиза ${item.category.toLowerCase()}` : '');
            const invest = Number(metaItem.invest || 0);
            const images = resolveImages(metaItem, 10);
            const image = images[0] || placeholderImage;
            const href = resolveFranchiseUrl(item, 'franchise.html');
            return {
                ...item,
                href,
                brand,
                desc,
                image,
                images,
                invest
            };
        });

        const syntheticBlueprints = [
            ['Авто', 'Автосервисы'],
            ['Авто', 'Автомойки'],
            ['Еда', 'Пекарни'],
            ['Еда', 'Кофейни'],
            ['Торговля', 'Аптеки'],
            ['Торговля', 'Электроника'],
            ['Обучение', 'Языковые школы'],
            ['Обучение', 'Детские центры'],
            ['Красота и здоровье', 'Стоматологии'],
            ['Красота и здоровье', 'Косметология']
        ];
        const syntheticItems = fallbackList.map((imageSrc, index) => {
            const [sphereName, categoryName] = syntheticBlueprints[index % syntheticBlueprints.length];
            const id = `synthetic-${index + 1}`;
            const invest = 320000 + ((index + 3) * 73000);
            const popularity = 95 - (index % 14) * 3;
            const tagSet = ['Популярные франшизы', ...(index % 2 === 0 ? ['Новые франшизы'] : [])];
            const brand = `${categoryName} ${index + 1}`;
            const desc = `Франшиза направления «${categoryName.toLowerCase()}» с готовой моделью запуска.`;
            return {
                sphere: sphereName,
                category: categoryName,
                slug: id,
                url: 'catalog.html',
                href: 'catalog.html',
                brand,
                desc,
                image: imageSrc,
                images: [imageSrc],
                invest,
                meta: {
                    id,
                    brand,
                    name: brand,
                    desc,
                    image: imageSrc,
                    images: [imageSrc],
                    invest,
                    popularity,
                    tags: tagSet
                }
            };
        });
        const poolItems = franchiseItems.concat(syntheticItems);

        const renderSliderCards = (slider, items) => {
            if (!slider) return;
            slider.innerHTML = items.map((item) => `
            <article class="preview-card">
              <div class="preview-media"><img src="${escapeHtml((Array.isArray(item.images) && item.images[0]) || item.image || placeholderImage)}" alt="${escapeHtml(item.brand)}" loading="lazy" decoding="async"></div>
              <div class="preview-brand">${escapeHtml(item.brand)}</div>
              <div class="preview-desc">${escapeHtml(item.desc)}</div>
              <div class="preview-meta">Инвестиции: от ${escapeHtml(formatMoney(item.invest || 0))} руб.</div>
            </article>
          `).join('');
            slider.querySelectorAll('.preview-media img').forEach((img) => {
                img.addEventListener('error', () => {
                    img.src = placeholderImage;
                }, { once: true });
            });
        };

        const currentSphere = String(current.sphere || '').trim();
        const currentCategory = String(current.category || '').trim();
        const currentId = String(meta.id || current.slug || franchiseId || '').trim();

        const byPopularity = (a, b) => Number(b.meta?.popularity || 0) - Number(a.meta?.popularity || 0);
        const sameNotCurrent = (item) => String(item.meta?.id || item.slug || '').trim() !== currentId;
        const getItemId = (item) => String(item.meta?.id || item.slug || item.brand || '').trim();
        const uniqueById = (items) => {
            const seen = new Set();
            return items.filter((item) => {
                const id = getItemId(item);
                if (!id || seen.has(id)) return false;
                seen.add(id);
                return true;
            });
        };
        const shuffled = (items) =>
            items
                .slice()
                .sort((a, b) => ((getItemId(a) + currentId).localeCompare(getItemId(b) + currentId, 'ru')))
                .sort(() => Math.random() - 0.5);
        const pickRandom = (items, count) => shuffled(uniqueById(items)).slice(0, count);
        const getTags = (item) => {
            const tags = Array.isArray(item.meta?.tags)
                ? item.meta.tags
                : typeof item.meta?.tags === 'string'
                    ? item.meta.tags.split('|')
                    : [];
            return tags.map((tag) => String(tag || '').trim()).filter(Boolean);
        };

        const nonCurrentPool = poolItems.filter(sameNotCurrent);
        const sameCategoryPool = nonCurrentPool.filter((item) => String(item.category || '').trim() === currentCategory);
        const sameSpherePool = nonCurrentPool.filter((item) => String(item.sphere || '').trim() === currentSphere);
        const popularPool = nonCurrentPool.filter((item) => getTags(item).includes('Популярные франшизы'));

        let similarItems = uniqueById([
            ...pickRandom(sameCategoryPool, 6),
            ...pickRandom(sameSpherePool, 6),
            ...pickRandom(nonCurrentPool.sort(byPopularity), 10)
        ]).slice(0, 10);

        if (!similarItems.length) {
            similarItems = pickRandom(nonCurrentPool, 10);
        }

        let popularItems = uniqueById([
            ...pickRandom(popularPool.sort(byPopularity), 10),
            ...pickRandom(nonCurrentPool.sort(byPopularity), 10)
        ]).slice(0, 10);

        if (!popularItems.length) {
            popularItems = pickRandom(nonCurrentPool, 10);
        }

        renderSliderCards(document.querySelector('[data-slider="similar"]'), similarItems);
        renderSliderCards(document.querySelector('[data-slider="popular"]'), popularItems);

        const sliderBlocks = document.querySelectorAll('.slider-section');
        sliderBlocks.forEach((block) => {
            const slider = block.querySelector('.slider');
            const prev = block.querySelector('[data-slider-prev]');
            const next = block.querySelector('[data-slider-next]');
            if (!slider || !prev || !next) return;

            const updateState = () => {
                const maxScroll = slider.scrollWidth - slider.clientWidth;
                prev.disabled = slider.scrollLeft <= 4;
                next.disabled = slider.scrollLeft >= maxScroll - 4;
            };

            const scrollByStep = (dir) => {
                slider.scrollBy({ left: dir * slider.clientWidth * 0.92, behavior: 'smooth' });
            };

            prev.addEventListener('click', () => scrollByStep(-1));
            next.addEventListener('click', () => scrollByStep(1));
            slider.addEventListener('scroll', updateState, { passive: true });
            window.addEventListener('resize', updateState);
            updateState();
        });

        (function () {
            var gallery = document.querySelector('.gallery-card');
            if (!gallery || gallery.getAttribute('data-gallery-fixed') === 'true') return;
            var mainWrap = gallery.querySelector('.gallery-main');
            var mainImage = gallery.querySelector('[data-gallery-main]');
            var countLabel = gallery.querySelector('[data-gallery-count]');
            var thumbsBar = gallery.querySelector('.gallery-thumbs');
            var thumbsWrap = gallery.querySelector('.thumbs-wrap');
            var prevBtn = gallery.querySelector('.gallery-nav-prev');
            var nextBtn = gallery.querySelector('.gallery-nav-next');
            var thumbsPrev = gallery.querySelector('.thumbs-nav-prev');
            var thumbsNext = gallery.querySelector('.thumbs-nav-next');
            var sourceThumbs = Array.prototype.slice.call(gallery.querySelectorAll('.gallery-thumb[data-full]'));
            var images = sourceThumbs.map(function (thumb) {
                return thumb.getAttribute('data-full') || (thumb.querySelector('img') && thumb.querySelector('img').src) || '';
            }).filter(function (src) { return !!src; });

            if (!mainWrap || !mainImage || !thumbsBar || !prevBtn || !nextBtn || !thumbsPrev || !thumbsNext || !images.length) return;
            gallery.setAttribute('data-gallery-fixed', 'true');

            mainWrap.style.aspectRatio = '16 / 9';
            mainImage.style.aspectRatio = '16 / 9';
            mainImage.style.objectFit = 'cover';
            mainImage.style.pointerEvents = 'none';
            mainImage.style.transition = 'opacity 0.16s ease';

            var newPrevBtn = prevBtn.cloneNode(true);
            var newNextBtn = nextBtn.cloneNode(true);
            var newThumbsPrev = thumbsPrev.cloneNode(true);
            var newThumbsNext = thumbsNext.cloneNode(true);
            prevBtn.parentNode.replaceChild(newPrevBtn, prevBtn);
            nextBtn.parentNode.replaceChild(newNextBtn, nextBtn);
            thumbsPrev.parentNode.replaceChild(newThumbsPrev, thumbsPrev);
            thumbsNext.parentNode.replaceChild(newThumbsNext, thumbsNext);

            thumbsBar.innerHTML = images.map(function (src, index) {
                return '<button class="gallery-thumb' + (index === 0 ? ' is-active' : '') + '" type="button" data-gallery-thumb data-full="' + src.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" aria-label="Фото ' + (index + 1) + '" aria-selected="' + (index === 0 ? 'true' : 'false') + '">' +
                    '<img src="' + src.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" alt="" loading="' + (index === 0 ? 'eager' : 'lazy') + '" decoding="async">' +
                    '</button>';
            }).join('');

            var thumbs = Array.prototype.slice.call(thumbsBar.querySelectorAll('[data-gallery-thumb]'));
            var currentIndex = 0;

            function updateCount() {
                if (countLabel) countLabel.textContent = (currentIndex + 1) + ' / ' + images.length;
            }

            function updateThumbState() {
                for (var i = 0; i < thumbs.length; i++) {
                    var active = i === currentIndex;
                    thumbs[i].classList.toggle('is-active', active);
                    thumbs[i].setAttribute('aria-selected', active ? 'true' : 'false');
                }
            }

            function showImage(index, skipScroll) {
                var total = images.length;
                if (!total) return;
                currentIndex = (index + total) % total;
                updateThumbState();
                updateCount();
                mainImage.style.opacity = '0.45';
                mainImage.src = images[currentIndex];
                if (!skipScroll && thumbs[currentIndex] && thumbs[currentIndex].scrollIntoView) {
                    thumbs[currentIndex].scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
                }
            }

            newPrevBtn.addEventListener('click', function () { showImage(currentIndex - 1); });
            newNextBtn.addEventListener('click', function () { showImage(currentIndex + 1); });

            if (mainWrap instanceof HTMLElement && mainWrap.dataset.gallerySwipeBound !== '1') {
                var touchStartX = null;
                var touchStartY = null;
                var resetSwipe = function () {
                    touchStartX = null;
                    touchStartY = null;
                };
                mainWrap.addEventListener('touchstart', function (event) {
                    if (event.target instanceof Element && event.target.closest('.gallery-nav')) return;
                    var touch = event.changedTouches && event.changedTouches[0];
                    if (!touch) return;
                    touchStartX = touch.clientX;
                    touchStartY = touch.clientY;
                }, { passive: true });
                mainWrap.addEventListener('touchcancel', resetSwipe, { passive: true });
                mainWrap.addEventListener('touchend', function (event) {
                    var touch = event.changedTouches && event.changedTouches[0];
                    if (!touch || touchStartX === null || touchStartY === null) {
                        resetSwipe();
                        return;
                    }
                    var deltaX = touch.clientX - touchStartX;
                    var deltaY = touch.clientY - touchStartY;
                    resetSwipe();
                    if (Math.abs(deltaX) < 36) return;
                    if (Math.abs(deltaY) > Math.abs(deltaX) * 0.8) return;
                    showImage(deltaX < 0 ? currentIndex + 1 : currentIndex - 1);
                }, { passive: true });
                mainWrap.dataset.gallerySwipeBound = '1';
            }

            mainImage.addEventListener('load', function () { mainImage.style.opacity = '1'; });
            mainImage.addEventListener('error', function () { mainImage.style.opacity = '1'; });

            for (var j = 0; j < thumbs.length; j++) {
                (function (index) {
                    thumbs[index].addEventListener('click', function (event) {
                        event.preventDefault();
                        showImage(index);
                    });
                })(j);
            }

            function updateThumbButtons() {
                var maxScroll = thumbsBar.scrollWidth - thumbsBar.clientWidth;
                newThumbsPrev.disabled = thumbsBar.scrollLeft <= 4;
                newThumbsNext.disabled = thumbsBar.scrollLeft >= maxScroll - 4;
            }

            function getThumbStep() {
                var firstThumb = thumbsBar.querySelector('.gallery-thumb');
                if (!(firstThumb instanceof HTMLElement)) return Math.max(1, thumbsBar.clientWidth * 0.2);
                var styles = window.getComputedStyle(thumbsBar);
                var gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;
                return Math.max(1, firstThumb.getBoundingClientRect().width + gap);
            }

            function scrollThumbs(dir) {
                var step = getThumbStep();
                var maxScroll = Math.max(0, thumbsBar.scrollWidth - thumbsBar.clientWidth);
                var current = thumbsBar.scrollLeft;
                var epsilon = 0.5;
                var nextIndex = dir > 0
                    ? Math.floor((current + epsilon) / step) + 1
                    : Math.ceil((current - epsilon) / step) - 1;
                var target = Math.max(0, Math.min(maxScroll, nextIndex * step));
                thumbsBar.scrollTo({ left: target, behavior: 'smooth' });
            }

            newThumbsPrev.addEventListener('click', function () {
                scrollThumbs(-1);
            });
            newThumbsNext.addEventListener('click', function () {
                scrollThumbs(1);
            });
            thumbsBar.addEventListener('scroll', updateThumbButtons, { passive: true });
            thumbsWrap && thumbsWrap.addEventListener('wheel', function () { setTimeout(updateThumbButtons, 0); }, { passive: true });

            showImage(0, true);
            updateThumbButtons();
        })();
    });
})();

/* ===== shared.js ===== */
(() => {
    window.__FRANCHISE_SHARED__ = true;

    const setupModernAnimations = () => {
        const reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reducedMotion) return;
        const path = (window.location.pathname.split('/').pop() || 'index.html').toLowerCase();
        const isHomePage = path === '' || path === 'index' || path === 'index.html';
        const isCatalogPage = path === 'catalog' || path === 'catalog.html';
        const isFranchisePage = /^franchise(?:-[a-z0-9-]+)?\.html$/i.test(path);

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
            !isFranchisePage ? document.querySelector('.franchise-head') : null
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
            '.popular-section'
        ].join(', ');

        const prepared = new WeakSet();
        let sequence = 0;

        const observer = ('IntersectionObserver' in window)
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

    const setupHomeStatsCounter = () => {
        const path = (window.location.pathname.split('/').pop() || 'index.html').toLowerCase();
        const isIndexPage = path === '' || path === 'index' || path === 'index.html';
        if (!isIndexPage) return;

        const statNodes = Array.from(document.querySelectorAll('.about-stats .stat-value'));
        if (!statNodes.length) return;

        const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const parseNumericText = (text) => {
            const source = String(text || '').trim();
            const match = source.match(/(\d[\d\s]*)/);
            if (!match || typeof match.index !== 'number') return null;

            const numericPart = String(match[1]).replace(/\s+/g, '');
            const target = Number(numericPart);
            if (!Number.isFinite(target)) return null;

            const prefix = source.slice(0, match.index);
            const suffix = source.slice(match.index + match[1].length);
            return { target, prefix, suffix };
        };

        const formatNumber = (value) => Number(value).toLocaleString('ru-RU');
        const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
        const COUNTER_DURATION_MS = 980;

        const runCounter = (node, meta) => {
            if (!(node instanceof HTMLElement) || !meta) return;
            if (node.dataset.counterPlayed === '1') return;
            node.dataset.counterPlayed = '1';

            const { target, prefix, suffix } = meta;
            if (reduceMotion) {
                node.textContent = `${prefix}${formatNumber(target)}${suffix}`;
                return;
            }

            const duration = COUNTER_DURATION_MS;
            let startedAt = 0;
            let lastRendered = 0;

            const frame = (ts) => {
                if (!startedAt) startedAt = ts;
                const progress = Math.min(1, (ts - startedAt) / duration);
                const eased = easeOutCubic(progress);
                let current = Math.floor(target * eased);
                if (current < lastRendered) current = lastRendered;
                if (progress >= 1) current = target;

                node.textContent = `${prefix}${formatNumber(current)}${suffix}`;
                lastRendered = current;

                if (progress < 1) {
                    window.requestAnimationFrame(frame);
                }
            };

            node.textContent = `${prefix}0${suffix}`;
            window.requestAnimationFrame(frame);
        };

        const counters = statNodes
            .map((node) => ({ node, meta: parseNumericText(node.textContent) }))
            .filter((item) => item.meta && item.meta.target >= 0);
        if (!counters.length) return;

        const root = document.querySelector('.about-stats') || counters[0].node;
        const target = root instanceof Element ? root : counters[0].node;
        let hasScrolled = window.scrollY > 0;
        let isVisible = false;
        let started = false;

        const cleanupCallbacks = [];

        const runAllCounters = () => {
            if (started) return;
            started = true;
            cleanupCallbacks.forEach((fn) => fn());
            counters.forEach(({ node, meta }) => runCounter(node, meta));
        };

        const maybeStart = () => {
            if (!hasScrolled || !isVisible) return;
            runAllCounters();
        };

        const markScrolled = () => {
            hasScrolled = true;
            maybeStart();
        };

        window.addEventListener('scroll', markScrolled, { passive: true, once: true });
        cleanupCallbacks.push(() => window.removeEventListener('scroll', markScrolled));

        if (!('IntersectionObserver' in window)) {
            const updateVisibility = () => {
                if (!(target instanceof Element)) {
                    isVisible = true;
                    maybeStart();
                    return;
                }
                const rect = target.getBoundingClientRect();
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                const visibleTop = Math.max(rect.top, 0);
                const visibleBottom = Math.min(rect.bottom, viewportHeight);
                const visibleHeight = Math.max(0, visibleBottom - visibleTop);
                const ratio = visibleHeight / Math.max(1, rect.height);
                isVisible = ratio >= 0.32;
                maybeStart();
            };

            window.addEventListener('scroll', updateVisibility, { passive: true });
            window.addEventListener('resize', updateVisibility, { passive: true });
            cleanupCallbacks.push(() => window.removeEventListener('scroll', updateVisibility));
            cleanupCallbacks.push(() => window.removeEventListener('resize', updateVisibility));
            updateVisibility();
            return;
        }

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (entry.target !== target) return;
                isVisible = entry.isIntersecting && entry.intersectionRatio >= 0.32;
                maybeStart();
                if (started) obs.disconnect();
            });
        }, { threshold: [0, 0.32, 1] });

        observer.observe(target);
        cleanupCallbacks.push(() => observer.disconnect());

        if (hasScrolled) {
            maybeStart();
        }
    };

    const setupMobileToc = () => {
        const blocks = document.querySelectorAll('.toc-mobile');
        if (!blocks.length) return;

        blocks.forEach((block) => {
            if (block.dataset.tocReady === '1') return;
            const title = block.querySelector('.toc-title');
            const list = block.querySelector('.toc-list');
            if (!title || !list) return;

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'toc-mobile-toggle';
            toggle.setAttribute('aria-expanded', 'false');
            toggle.textContent = title.textContent?.trim() || 'Содержание';

            title.replaceWith(toggle);
            block.classList.remove('is-open');
            list.hidden = true;

            toggle.addEventListener('click', () => {
                const isOpen = block.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', String(isOpen));
                list.hidden = !isOpen;
            });

            block.dataset.tocReady = '1';
        });
    };

    const setupFranchiseMobileTextLists = () => {
        const path = (window.location.pathname.split('/').pop() || '').toLowerCase();
        const isFranchisePage = /^franchise(?:-[a-z0-9-]+)?\.html$/i.test(path);
        if (!isFranchisePage) return;

        const contentRoot = document.querySelector('.content-section > div');
        if (!(contentRoot instanceof HTMLElement)) return;
        if (contentRoot.querySelector('[data-mobile-list-block]')) return;

        const listBlock = document.createElement('section');
        listBlock.className = 'franchise-mobile-lists';
        listBlock.setAttribute('data-mobile-list-block', '');
        listBlock.setAttribute('aria-label', 'Ключевые пункты запуска');
        listBlock.innerHTML = `
      <h2>Ключевые пункты запуска</h2>
      <div class="franchise-mobile-lists-grid">
        <article class="franchise-mobile-list-card">
          <h3>Что обычно включает поддержка</h3>
          <ul class="franchise-mobile-bullets">
            <li>Анализ локации и проверка помещения под формат.</li>
            <li>Пошаговый план открытия и контроль сроков.</li>
            <li>Обучение собственника и ключевых сотрудников.</li>
            <li>Маркетинговый запуск и сопровождение на старте.</li>
          </ul>
        </article>
        <article class="franchise-mobile-list-card">
          <h3>Этапы старта по шагам</h3>
          <ol class="franchise-mobile-steps">
            <li>Оставляете заявку и получаете консультацию менеджера.</li>
            <li>Согласовываете условия и подписываете договор.</li>
            <li>Подготавливаете точку и команду к открытию.</li>
            <li>Запускаете объект и выходите на рабочие показатели.</li>
          </ol>
        </article>
      </div>
    `;

        contentRoot.appendChild(listBlock);
    };

    const setupLeadFormEnhancements = () => {
        const normalizePhoneDigits = (value) => {
            let digits = String(value || '').replace(/\D/g, '');
            if (digits.startsWith('7') || digits.startsWith('8')) digits = digits.slice(1);
            return digits.slice(0, 10);
        };

        const formatRuPhone = (value) => {
            const digits = normalizePhoneDigits(value);
            if (!digits.length) return '';
            let out = '+7';
            if (digits.length > 0) out += ` (${digits.slice(0, 3)}`;
            if (digits.length >= 3) out += ')';
            if (digits.length > 3) out += ` ${digits.slice(3, 6)}`;
            if (digits.length > 6) out += `-${digits.slice(6, 8)}`;
            if (digits.length > 8) out += `-${digits.slice(8, 10)}`;
            return out;
        };

        const getFieldLabelText = (input) => {
            if (!(input instanceof HTMLInputElement)) return '';
            const directLabel = input.closest('label');
            if (directLabel) return String(directLabel.textContent || '').trim().toLowerCase();
            const id = String(input.id || '').trim();
            if (!id) return '';
            const externalLabel = document.querySelector(`label[for="${CSS.escape(id)}"]`);
            return String(externalLabel?.textContent || '').trim().toLowerCase();
        };

        const forms = document.querySelectorAll('form');
        forms.forEach((form) => {
            if (!(form instanceof HTMLFormElement)) return;

            const contactInputs = form.querySelectorAll('input[type="tel"], input[type="email"], input[type="text"]');
            if (contactInputs.length) form.setAttribute('autocomplete', 'on');

            form.querySelectorAll('input[type="email"]').forEach((input) => {
                if (!(input instanceof HTMLInputElement)) return;
                input.autocomplete = 'email';
                input.inputMode = 'email';
                if (!input.name) input.name = 'email';
            });

            form.querySelectorAll('input[type="text"], input:not([type])').forEach((input) => {
                if (!(input instanceof HTMLInputElement)) return;
                const haystack = [
                    String(input.id || '').toLowerCase(),
                    String(input.name || '').toLowerCase(),
                    String(input.placeholder || '').toLowerCase(),
                    getFieldLabelText(input)
                ].join(' ');
                if (!/(^|[\s_-])(name|имя)([\s_-]|$)/i.test(haystack)) return;
                input.autocomplete = 'name';
                if (!input.name) input.name = 'name';
            });

            form.querySelectorAll('input[type="tel"]').forEach((input) => {
                if (!(input instanceof HTMLInputElement)) return;

                input.autocomplete = 'tel';
                input.inputMode = 'tel';
                input.placeholder = '+7 (___) ___-__-__';
                if (!input.name) input.name = 'phone';

                const syncValue = (rawValue = input.value) => {
                    input.value = formatRuPhone(rawValue);
                    const caretPos = input.value.length;
                    input.setSelectionRange(caretPos, caretPos);
                };

                if (input.dataset.phoneMaskReady === '1') return;

                if (String(input.value || '').trim()) {
                    input.value = formatRuPhone(input.value);
                }

                input.addEventListener('input', () => {
                    syncValue(input.value);
                });

                input.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const text = event.clipboardData?.getData('text') || '';
                    syncValue(text);
                });

                input.dataset.phoneMaskReady = '1';
            });
        });
    };

    const ensureConsentRequiredOnForms = () => {
        const forms = document.querySelectorAll('form');
        const showMobileConsentFeedback = () => {
            const isMobileViewport = !!(window.matchMedia && window.matchMedia('(max-width: 900px)').matches);
            if (!isMobileViewport) return;
            if (typeof window.__showLeadFeedback === 'function') {
                window.__showLeadFeedback('Подтвердите согласие с политикой конфиденциальности.', true);
            }
        };
        forms.forEach((form) => {
            if (!(form instanceof HTMLFormElement)) return;
            const consent = form.querySelector('label.consent input[type="checkbox"], input[type="checkbox"][name="consent"]');
            if (!(consent instanceof HTMLInputElement)) return;
            const isLeadForm = form.matches('.form-grid, .ask-form-grid, .final-form, .selection-popup-form');
            const submitControls = Array.from(
                form.querySelectorAll('button[type="submit"], input[type="submit"]')
            ).filter((node) => node instanceof HTMLButtonElement || node instanceof HTMLInputElement);

            consent.required = true;
            if (!consent.name) consent.name = 'consent';
            consent.removeAttribute('checked');

            if (consent.dataset.defaultFixed !== '1') {
                consent.checked = false;
                consent.dataset.defaultFixed = '1';
            }

            const updateValidity = () => {
                consent.setCustomValidity(consent.checked ? '' : 'Подтвердите согласие с политикой конфиденциальности.');
            };
            updateValidity();

            if (consent.dataset.validationBound !== '1') {
                consent.addEventListener('change', updateValidity);
                consent.addEventListener('input', updateValidity);
                consent.dataset.validationBound = '1';
            }

            submitControls.forEach((control) => {
                control.disabled = false;
                control.removeAttribute('aria-disabled');
            });

            if (isLeadForm) return;

            if (form.dataset.consentClickGuardBound !== '1') {
                submitControls.forEach((control) => {
                    control.addEventListener('click', (event) => {
                        if (consent.checked) return;
                        event.preventDefault();
                        event.stopPropagation();
                        updateValidity();
                        consent.reportValidity();
                        showMobileConsentFeedback();
                        consent.focus();
                    }, true);
                });
                form.dataset.consentClickGuardBound = '1';
            }

            if (form.dataset.consentGuardBound === '1') return;
            form.addEventListener('submit', (event) => {
                if (consent.checked) {
                    consent.setCustomValidity('');
                    return;
                }
                event.preventDefault();
                updateValidity();
                consent.reportValidity();
                showMobileConsentFeedback();
                consent.focus();
            });
            form.dataset.consentGuardBound = '1';
        });
    };

    const setupLeadFormSubmissionFeedback = () => {
        const leadForms = Array.from(
            document.querySelectorAll('form.form-grid, form.ask-form-grid, form.final-form')
        ).filter((form) => form instanceof HTMLFormElement);

        const normalizePhoneDigits = (value) => {
            let digits = String(value || '').replace(/\D/g, '');
            if (digits.startsWith('7') || digits.startsWith('8')) digits = digits.slice(1);
            return digits.slice(0, 10);
        };

        let feedbackNode = document.querySelector('[data-lead-feedback]');
        if (!(feedbackNode instanceof HTMLElement)) {
            feedbackNode = document.createElement('div');
            feedbackNode.className = 'lead-feedback';
            feedbackNode.setAttribute('data-lead-feedback', '');
            feedbackNode.setAttribute('aria-hidden', 'true');
            feedbackNode.hidden = true;
            feedbackNode.innerHTML = `
        <div class="lead-feedback-backdrop" data-lead-feedback-close></div>
        <div class="lead-feedback-card" role="alertdialog" aria-modal="true" aria-label="Сообщение формы">
          <div class="lead-feedback-mark" data-lead-feedback-mark aria-hidden="true">
            <svg class="lead-feedback-check" viewBox="0 0 80 80" focusable="false" aria-hidden="true">
              <circle class="lead-feedback-check-circle" cx="40" cy="40" r="30"></circle>
              <path class="lead-feedback-check-path" d="M26 40.5l9 9 19-19"></path>
            </svg>
          </div>
          <p class="lead-feedback-text" data-lead-feedback-text></p>
          <button class="btn btn-primary lead-feedback-btn" type="button" data-lead-feedback-close>Понятно</button>
        </div>
      `;
            document.body.appendChild(feedbackNode);
        }

        const feedbackText = feedbackNode.querySelector('[data-lead-feedback-text]');
        const feedbackCard = feedbackNode.querySelector('.lead-feedback-card');
        const feedbackMark = feedbackNode.querySelector('[data-lead-feedback-mark]');
        const closeControls = feedbackNode.querySelectorAll('[data-lead-feedback-close]');
        if (!(feedbackText instanceof HTMLElement) || !(feedbackCard instanceof HTMLElement)) return;

        const closeFeedback = () => {
            feedbackNode.hidden = true;
            feedbackNode.setAttribute('aria-hidden', 'true');
            feedbackCard.classList.remove('is-error');
            feedbackCard.classList.remove('is-success');
            if (feedbackMark instanceof HTMLElement) feedbackMark.classList.remove('animate');
        };

        closeControls.forEach((control) => {
            if (!(control instanceof HTMLElement) || control.dataset.bound === '1') return;
            control.addEventListener('click', closeFeedback);
            control.dataset.bound = '1';
        });

        if (feedbackNode.dataset.escBound !== '1') {
            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                if (feedbackNode.hidden) return;
                closeFeedback();
            });
            feedbackNode.dataset.escBound = '1';
        }

        const successHeadlineMarkup = '<strong>Заявка отправлена</strong><span>В ближайшее время менеджер свяжется с вами.</span>';

        const showFeedback = (text, isError = false) => {
            if (!isError && /заявка отправлена/i.test(String(text || ''))) {
                feedbackText.innerHTML = successHeadlineMarkup;
            } else {
                feedbackText.textContent = text;
            }
            feedbackCard.classList.toggle('is-error', isError);
            feedbackCard.classList.toggle('is-success', !isError);
            if (feedbackMark instanceof HTMLElement) {
                feedbackMark.classList.remove('animate');
                if (!isError) {
                    void feedbackMark.offsetWidth;
                    feedbackMark.classList.add('animate');
                }
            }
            feedbackNode.hidden = false;
            feedbackNode.setAttribute('aria-hidden', 'false');
        };
        window.__showLeadFeedback = showFeedback;
        const isOptionalField = (field) => {
            if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) {
                return true;
            }
            if (field.hasAttribute('data-optional')) return true;
            const hint = [
                String(field.name || ''),
                String(field.id || ''),
                String(field.placeholder || ''),
                String(field.getAttribute('aria-label') || '')
            ].join(' ').toLowerCase();
            return /comment|коммент/i.test(hint);
        };

        const getFillableFields = (form) =>
            Array.from(form.querySelectorAll('input, textarea, select')).filter((field) => {
                if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) return false;
                if (field.disabled || field.readOnly) return false;
                if (field instanceof HTMLInputElement) {
                    const blockedTypes = new Set(['hidden', 'submit', 'button', 'reset', 'image', 'file', 'checkbox', 'radio']);
                    if (blockedTypes.has((field.type || '').toLowerCase())) return false;
                }
                return true;
            });

        const updatePhoneValidity = (field) => {
            if (!(field instanceof HTMLInputElement) || field.type !== 'tel') return;
            const digits = normalizePhoneDigits(field.value);
            if (!digits.length) {
                field.setCustomValidity('Заполните поле');
            } else if (digits.length < 10) {
                field.setCustomValidity('Введите номер телефона полностью.');
            } else {
                field.setCustomValidity('');
            }
        };

        if (!leadForms.length) return;

        leadForms.forEach((form) => {
            if (!(form instanceof HTMLFormElement)) return;
            if (form.dataset.leadFormFeedbackBound === '1') return;

            const fields = getFillableFields(form);
            fields.forEach((field) => {
                if (!isOptionalField(field)) {
                    field.required = true;
                }
                if (field instanceof HTMLInputElement && field.type === 'tel') {
                    field.autocomplete = 'tel';
                    field.inputMode = 'tel';
                    field.setAttribute('pattern', '^\\+7 \\(\\d{3}\\) \\d{3}-\\d{2}-\\d{2}$');
                }

                if (field.dataset.leadValidationBound !== '1') {
                    field.addEventListener('input', () => {
                        field.setCustomValidity('');
                        if (field instanceof HTMLInputElement && field.type === 'tel') {
                            updatePhoneValidity(field);
                        }
                    });
                    field.addEventListener('change', () => {
                        field.setCustomValidity('');
                        if (field instanceof HTMLInputElement && field.type === 'tel') {
                            updatePhoneValidity(field);
                        }
                    });
                    field.dataset.leadValidationBound = '1';
                }
            });

            form.addEventListener('submit', (event) => {
                if (event.defaultPrevented) return;
                event.preventDefault();

                const consent = form.querySelector('label.consent input[type="checkbox"], input[type="checkbox"][name="consent"]');

                fields.forEach((field) => {
                    if (field instanceof HTMLInputElement && field.type === 'tel') {
                        updatePhoneValidity(field);
                        return;
                    }
                    field.setCustomValidity('');
                    if (!field.required) return;
                    const rawValue = field instanceof HTMLSelectElement ? field.value : String(field.value || '');
                    if (!String(rawValue).trim()) {
                        field.setCustomValidity('Заполните поле');
                    }
                });

                const invalidField = fields.find((field) => !field.checkValidity());
                if (invalidField) {
                    invalidField.reportValidity();
                    invalidField.focus();
                    showFeedback('Пожалуйста, заполните обязательные поля формы перед отправкой.', true);
                    return;
                }

                if (consent instanceof HTMLInputElement && !consent.checked) {
                    consent.setCustomValidity('Подтвердите согласие с политикой конфиденциальности.');
                    consent.reportValidity();
                    consent.focus();
                    return;
                }

                if (consent instanceof HTMLInputElement) {
                    consent.setCustomValidity('');
                }

                showFeedback('Заявка отправлена. В ближайшее время менеджер свяжется с вами.');
                form.reset();

                if (consent instanceof HTMLInputElement) {
                    consent.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            form.dataset.leadFormFeedbackBound = '1';
        });
    };

    const setupSelectionRequestPopup = () => {
        const triggerPattern = /(получить подбор|связаться с франчайзером)/i;
        const triggers = Array.from(
            document.querySelectorAll('a.btn.btn-primary[href], button.btn.btn-primary, [data-selection-open]')
        ).filter((node) => {
            if (!(node instanceof HTMLElement)) return false;
            if (node.matches('[data-selection-open]')) return true;
            if (node.closest('.side-contact')) return true;
            const label = `${String(node.textContent || '').trim()} ${String(node.getAttribute('aria-label') || '').trim()}`.trim();
            return triggerPattern.test(label);
        });
        if (!triggers.length) return;

        const normalizePhoneDigits = (value) => {
            let digits = String(value || '').replace(/\D/g, '');
            if (digits.startsWith('7') || digits.startsWith('8')) digits = digits.slice(1);
            return digits.slice(0, 10);
        };

        const formatRuPhone = (value) => {
            const digits = normalizePhoneDigits(value);
            if (!digits.length) return '';
            let out = '+7';
            if (digits.length > 0) out += ` (${digits.slice(0, 3)}`;
            if (digits.length >= 3) out += ')';
            if (digits.length > 3) out += ` ${digits.slice(3, 6)}`;
            if (digits.length > 6) out += `-${digits.slice(6, 8)}`;
            if (digits.length > 8) out += `-${digits.slice(8, 10)}`;
            return out;
        };

        let popupNode = document.querySelector('[data-selection-popup]');
        if (!(popupNode instanceof HTMLElement)) {
            popupNode = document.createElement('div');
            popupNode.className = 'selection-popup';
            popupNode.setAttribute('data-selection-popup', '');
            popupNode.setAttribute('aria-hidden', 'true');
            popupNode.hidden = true;
            popupNode.innerHTML = `
        <div class="selection-popup-backdrop" data-selection-close></div>
        <div class="selection-popup-card" role="dialog" aria-modal="true" aria-labelledby="selection-popup-title">
          <button class="selection-popup-close" type="button" aria-label="Закрыть" data-selection-close>×</button>
          <h2 class="selection-popup-title" id="selection-popup-title">Подберем франшизы под ваш бюджет</h2>
          <p class="selection-popup-subtitle">Оставьте имя и телефон. В ближайшее время менеджер свяжется с вами.</p>
          <form class="selection-popup-form" data-selection-form novalidate>
            <label class="selection-popup-field" for="selection-popup-name">
              <span>Имя</span>
              <input id="selection-popup-name" class="input" type="text" name="name" autocomplete="name" placeholder="Как к вам обращаться" required data-selection-name>
            </label>
            <label class="selection-popup-field" for="selection-popup-phone">
              <span>Телефон</span>
              <input id="selection-popup-phone" class="input" type="tel" name="phone" autocomplete="tel" inputmode="tel" placeholder="+7 (___) ___-__-__" required data-selection-phone>
            </label>
            <label class="consent selection-popup-consent">
              <input type="checkbox" name="consent" required data-selection-consent>
              <span>Я соглашаюсь на обработку персональных данных и принимаю <a href="privacy-policy.html">политику конфиденциальности</a>.</span>
            </label>
            <button class="btn btn-primary selection-popup-submit" type="submit">Отправить заявку</button>
          </form>
        </div>
      `;
            document.body.appendChild(popupNode);
        }

        const popupForm = popupNode.querySelector('[data-selection-form]');
        const nameInput = popupNode.querySelector('[data-selection-name]');
        const phoneInput = popupNode.querySelector('[data-selection-phone]');
        const consentInput = popupNode.querySelector('[data-selection-consent]');
        if (
            !(popupForm instanceof HTMLFormElement) ||
            !(nameInput instanceof HTMLInputElement) ||
            !(phoneInput instanceof HTMLInputElement) ||
            !(consentInput instanceof HTMLInputElement)
        ) {
            return;
        }

        let activeTrigger = null;
        let hideTimerId = 0;

        const closePopup = () => {
            popupNode.classList.remove('is-open');
            document.body.classList.remove('modal-open');
            if (hideTimerId) window.clearTimeout(hideTimerId);
            hideTimerId = window.setTimeout(() => {
                popupNode.hidden = true;
                popupNode.setAttribute('aria-hidden', 'true');
                hideTimerId = 0;
            }, 220);
            if (activeTrigger instanceof HTMLElement) activeTrigger.focus({ preventScroll: true });
        };

        const openPopup = (trigger = null) => {
            activeTrigger = trigger instanceof HTMLElement ? trigger : null;
            if (hideTimerId) {
                window.clearTimeout(hideTimerId);
                hideTimerId = 0;
            }
            popupNode.hidden = false;
            popupNode.setAttribute('aria-hidden', 'false');
            requestAnimationFrame(() => {
                document.body.classList.add('modal-open');
                popupNode.classList.add('is-open');
            });
            window.setTimeout(() => {
                nameInput.focus({ preventScroll: true });
            }, 80);
        };

        popupNode.querySelectorAll('[data-selection-close]').forEach((control) => {
            if (!(control instanceof HTMLElement) || control.dataset.selectionCloseBound === '1') return;
            control.addEventListener('click', closePopup);
            control.dataset.selectionCloseBound = '1';
        });

        if (popupNode.dataset.selectionEscBound !== '1') {
            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                if (popupNode.hidden) return;
                closePopup();
            });
            popupNode.dataset.selectionEscBound = '1';
        }

        if (phoneInput.dataset.selectionMaskBound !== '1') {
            const syncPhoneValue = (rawValue = phoneInput.value) => {
                phoneInput.value = formatRuPhone(rawValue);
                const caret = phoneInput.value.length;
                phoneInput.setSelectionRange(caret, caret);
            };

            phoneInput.addEventListener('input', () => {
                phoneInput.setCustomValidity('');
                syncPhoneValue(phoneInput.value);
            });

            phoneInput.addEventListener('paste', (event) => {
                event.preventDefault();
                const text = event.clipboardData?.getData('text') || '';
                phoneInput.setCustomValidity('');
                syncPhoneValue(text);
            });

            phoneInput.dataset.selectionMaskBound = '1';
        }

        if (nameInput.dataset.selectionFieldBound !== '1') {
            nameInput.addEventListener('input', () => {
                nameInput.setCustomValidity('');
            });
            nameInput.dataset.selectionFieldBound = '1';
        }

        if (consentInput.dataset.selectionFieldBound !== '1') {
            consentInput.addEventListener('change', () => {
                consentInput.setCustomValidity(consentInput.checked ? '' : 'Подтвердите согласие с политикой конфиденциальности.');
            });
            consentInput.dataset.selectionFieldBound = '1';
        }

        if (popupForm.dataset.selectionSubmitBound !== '1') {
            popupForm.addEventListener('submit', (event) => {
                event.preventDefault();

                nameInput.setCustomValidity('');
                phoneInput.setCustomValidity('');
                consentInput.setCustomValidity('');

                if (!String(nameInput.value || '').trim()) {
                    nameInput.setCustomValidity('Заполните поле');
                    nameInput.reportValidity();
                    nameInput.focus();
                    return;
                }

                const phoneDigits = normalizePhoneDigits(phoneInput.value);
                if (!phoneDigits.length) {
                    phoneInput.setCustomValidity('Заполните поле');
                    phoneInput.reportValidity();
                    phoneInput.focus();
                    return;
                }

                if (phoneDigits.length < 10) {
                    phoneInput.setCustomValidity('Введите номер телефона полностью.');
                    phoneInput.reportValidity();
                    phoneInput.focus();
                    return;
                }

                if (!consentInput.checked) {
                    consentInput.setCustomValidity('Подтвердите согласие с политикой конфиденциальности.');
                    consentInput.reportValidity();
                    consentInput.focus();
                    return;
                }

                phoneInput.value = formatRuPhone(phoneInput.value);
                closePopup();
                popupForm.reset();

                if (typeof window.__showLeadFeedback === 'function') {
                    window.__showLeadFeedback('Заявка отправлена. В ближайшее время менеджер свяжется с вами.');
                }
            });
            popupForm.dataset.selectionSubmitBound = '1';
        }

        triggers.forEach((trigger) => {
            if (!(trigger instanceof HTMLElement) || trigger.dataset.selectionPopupBound === '1') return;
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                openPopup(trigger);
            });
            trigger.dataset.selectionPopupBound = '1';
        });
    };

    const ensureHeaderMenuParity = () => {
        const header = document.querySelector('.site-header');
        const dropdown = document.querySelector('[data-categories-dropdown]');
        const panel = document.querySelector('[data-categories-panel]');
        const list = document.querySelector('[data-categories-list]');
        const trigger = document.querySelector('[data-categories-trigger]');
        const collectionsDropdown = document.querySelector('[data-collections-dropdown]');
        const collectionsPanel = document.querySelector('[data-collections-panel]');
        const collectionsTrigger = document.querySelector('[data-collections-trigger]');
        const collectionsSubgrid = document.querySelector('[data-collections-subgrid-header]');
        if (!header || !dropdown || !panel || !trigger) return;

        const isDropdownOpen = (el) => !!el && (el.classList.contains('is-open') || el.classList.contains('open'));

        let backdrop = document.querySelector('[data-categories-backdrop]');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'catalog-dropdown-backdrop';
            backdrop.setAttribute('data-categories-backdrop', '');
            document.body.appendChild(backdrop);
        }

        const syncOpenState = () => {
            syncPanelTop();
            const isCategoriesOpen = isDropdownOpen(dropdown);
            const isCollectionsOpen = isDropdownOpen(collectionsDropdown);
            const opened = isCategoriesOpen || isCollectionsOpen;
            header.classList.toggle('dropdown-open', opened);
            backdrop.classList.toggle('open', opened);
            trigger.setAttribute('aria-expanded', String(isCategoriesOpen));
            if (collectionsTrigger) {
                collectionsTrigger.setAttribute('aria-expanded', String(isCollectionsOpen));
            }
        };

        const syncPanelTop = () => {
            if (!(panel instanceof HTMLElement) || !(header instanceof HTMLElement)) return;
            const headerInner = header.querySelector('.header-inner');
            const anchor = headerInner instanceof HTMLElement ? headerInner : header;
            const headerBottom = Math.round(anchor.getBoundingClientRect().bottom);
            const panelTop = Math.max(0, headerBottom - 1);
            panel.style.setProperty('top', `${panelTop}px`, 'important');
            if (collectionsPanel instanceof HTMLElement) {
                collectionsPanel.style.setProperty('top', `${panelTop}px`, 'important');
            }
        };

        syncOpenState();
        const observer = new MutationObserver(syncOpenState);
        observer.observe(dropdown, { attributes: true, attributeFilter: ['class'] });
        if (collectionsDropdown) {
            observer.observe(collectionsDropdown, { attributes: true, attributeFilter: ['class'] });
        }
        window.addEventListener('resize', syncPanelTop);
        window.addEventListener('scroll', syncPanelTop, { passive: true });

        backdrop.addEventListener('click', () => {
            dropdown.classList.remove('is-open', 'open');
            collectionsDropdown?.classList.remove('is-open', 'open');
            syncOpenState();
        });

        if (collectionsDropdown && collectionsTrigger) {
            let collectionsCloseTimer = null;
            const clearCollectionsTimer = () => {
                if (collectionsCloseTimer) {
                    window.clearTimeout(collectionsCloseTimer);
                    collectionsCloseTimer = null;
                }
            };
            const setCollectionsOpen = (isOpen) => {
                if (isOpen) {
                    dropdown.classList.remove('is-open', 'open');
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
            collectionsPanel?.addEventListener('mouseenter', openCollections);
            collectionsPanel?.addEventListener('mouseleave', closeCollectionsSoon);
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
            dropdown.addEventListener('mouseenter', closeCollectionsIfOpen);
            dropdown.addEventListener('focusin', closeCollectionsIfOpen);
        }

        if (collectionsSubgrid) {
            const fallbackCollections = [
                'Все франшизы',
                'Популярные франшизы',
                'Новые франшизы',
                'Для начинающих',
                'Быстрая окупаемость',
                'Без роялти',
                'Без паушального взноса',
                'Премиум'
            ];
            const escapeHtml = (value) =>
                String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            const uniqueNames = (items) => {
                const seen = new Set();
                return items
                    .map((name) => String(name || '').trim())
                    .filter(Boolean)
                    .filter((name) => {
                        if (seen.has(name)) return false;
                        seen.add(name);
                        return true;
                    });
            };
            const preferredOrder = new Map(fallbackCollections.map((name, idx) => [name, idx]));
            const renderCollections = (names) => {
                const ordered = uniqueNames(names).sort((a, b) => {
                    const ai = preferredOrder.has(a) ? preferredOrder.get(a) : Number.MAX_SAFE_INTEGER;
                    const bi = preferredOrder.has(b) ? preferredOrder.get(b) : Number.MAX_SAFE_INTEGER;
                    if (ai !== bi) return ai - bi;
                    return a.localeCompare(b, 'ru');
                });
                collectionsSubgrid.innerHTML = ordered
                    .map((name) => {
                        const href = name === 'Все франшизы'
                            ? 'catalog.html'
                            : `catalog.html?tag=${encodeURIComponent(name)}`;
                        return `<a href="${href}">${escapeHtml(name)}</a>`;
                    })
                    .join('');
            };

            if (window.__loadFranchiseManifest) {
                window.__loadFranchiseManifest()
                    .then((manifest) => {
                        const fromManifest = Array.isArray(manifest?.collections)
                            ? manifest.collections.map((item) => String(item?.name || '').trim())
                            : [];
                        const merged = [...fallbackCollections, ...fromManifest].filter((name) => name !== 'Проверено');
                        renderCollections(merged);
                    })
                    .catch(() => renderCollections(fallbackCollections));
            } else {
                renderCollections(fallbackCollections);
            }
        }

        if (list) {
            const stripIcons = () => {
                list.querySelectorAll('.icon').forEach((node) => node.remove());
            };
            stripIcons();
            const listObserver = new MutationObserver(stripIcons);
            listObserver.observe(list, { childList: true, subtree: true });
        }

        const ensureMobileCategoryIcons = () => {
            const grid = document.querySelector('[data-mobile-categories-grid]');
            if (!grid) return;
            const iconMap = {
                'Торговля': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M3 5h8l-1 6H4z"/><path d="M4 5l1-2h4l1 2" fill="none"/></svg>',
                'Еда': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M5 2v4M7 2v4M9 2v4"/><path d="M4 6h6v6H4z" fill="none"/></svg>',
                'Авто': '<svg viewBox="0 0 14 14" aria-hidden="true"><rect x="2.5" y="5" width="9" height="4.5" rx="1"/><circle cx="4.5" cy="10.5" r="1"/><circle cx="9.5" cy="10.5" r="1"/></svg>',
                'Обучение': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M2 5l5-2 5 2-5 2z"/><path d="M4 6.2V9c0 .9 1.6 1.8 3 1.8S10 9.9 10 9V6.2" fill="none"/></svg>',
                'Красота и здоровье': '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2l1.2 2.2L10.5 5 8.2 6.2 7 8.5 5.8 6.2 3.5 5l2.3-.8z"/></svg>'
            };
            const fallback = '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M3 10l4-8 4 8z"/><path d="M5 7h4" fill="none"/></svg>';
            grid.querySelectorAll('.chip').forEach((chip) => {
                const text = (chip.querySelector('.chip-text')?.textContent || '').trim();
                const iconEl = chip.querySelector('.icon');
                if (!iconEl) return;
                if (iconEl.querySelector('svg')) return;
                iconEl.innerHTML = iconMap[text] || fallback;
            });
        };
        ensureMobileCategoryIcons();
        const mobileIconsTarget = document.querySelector('[data-mobile-categories-grid]');
        if (mobileIconsTarget) {
            const iconsObserver = new MutationObserver(ensureMobileCategoryIcons);
            iconsObserver.observe(mobileIconsTarget, { childList: true, subtree: true });
        }

        const ensureFallbackCategories = () => {
            const titleEl = dropdown.querySelector('[data-categories-title]');
            const subgridEl = dropdown.querySelector('[data-categories-subgrid]');
            if (!list || !titleEl || !subgridEl) return;

            const hasList = list.querySelectorAll('.categories-item').length > 0;
            const hasSubcats = subgridEl.querySelectorAll('a').length > 0;
            if (hasList && hasSubcats) return;

            const fallbackSpheres = [
                { name: 'Торговля', categories: ['Аптеки', 'Электроника'] },
                { name: 'Еда', categories: ['Пекарни', 'Кофейни'] },
                { name: 'Авто', categories: ['Автосервисы и СТО', 'Автомойки'] },
                { name: 'Обучение', categories: ['Языковые школы', 'Детские центры'] },
                { name: 'Красота и здоровье', categories: ['Стоматологии', 'Косметология'] }
            ];

            const makeLink = (sphereName, categoryName) => {
                const url = new URL('catalog.html', window.location.href);
                url.searchParams.set('sphere', sphereName);
                url.searchParams.set('category', categoryName);
                return url.pathname + url.search;
            };

            list.innerHTML = fallbackSpheres
                .map(
                    (sphere, index) =>
                        `<button class="categories-item${index === 0 ? ' active' : ''}" type="button" data-shared-index="${index}"><span>${sphere.name}</span></button>`
                )
                .join('');

            const render = (index) => {
                const safeIndex = Math.max(0, Math.min(index, fallbackSpheres.length - 1));
                const sphere = fallbackSpheres[safeIndex];
                titleEl.textContent = sphere.name;
                subgridEl.innerHTML = sphere.categories
                    .map((categoryName) => `<a href="${makeLink(sphere.name, categoryName)}">${categoryName}</a>`)
                    .join('');
                list.querySelectorAll('.categories-item').forEach((btn, i) => {
                    btn.classList.toggle('active', i === safeIndex);
                });
            };

            list.addEventListener('mouseover', (event) => {
                const btn = event.target.closest('[data-shared-index]');
                if (!btn) return;
                const idx = Number(btn.getAttribute('data-shared-index'));
                if (!Number.isNaN(idx)) render(idx);
            });
            list.addEventListener('focusin', (event) => {
                const btn = event.target.closest('[data-shared-index]');
                if (!btn) return;
                const idx = Number(btn.getAttribute('data-shared-index'));
                if (!Number.isNaN(idx)) render(idx);
            });
            list.addEventListener('click', (event) => {
                const btn = event.target.closest('[data-shared-index]');
                if (!btn) return;
                const idx = Number(btn.getAttribute('data-shared-index'));
                if (!Number.isNaN(idx)) render(idx);
            });

            render(0);
        };

        ensureFallbackCategories();
        window.setTimeout(ensureFallbackCategories, 200);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setupModernAnimations();
            setupHomeStatsCounter();
            setupLeadFormEnhancements();
            ensureConsentRequiredOnForms();
            setupLeadFormSubmissionFeedback();
            setupSelectionRequestPopup();
            ensureHeaderMenuParity();
            setupFranchiseMobileTextLists();
            setupMobileToc();
        }, { once: true });
    } else {
        setupModernAnimations();
        setupHomeStatsCounter();
        setupLeadFormEnhancements();
        ensureConsentRequiredOnForms();
        setupLeadFormSubmissionFeedback();
        setupSelectionRequestPopup();
        ensureHeaderMenuParity();
        setupFranchiseMobileTextLists();
        setupMobileToc();
    }
})();
