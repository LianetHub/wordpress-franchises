/**
 * @deprecated Используйте CustomSelect из app.js (window.CustomSelect / window.initCustomSelects).
 */
export const select = () => {
    if (typeof window.initCustomSelects === 'function') {
        window.initCustomSelects(document);
    }
};
