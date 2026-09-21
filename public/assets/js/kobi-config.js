(function () {
    const html = document.documentElement;

    window.KOBI = Object.freeze({
        baseUrl: html.dataset.baseUrl || '',
        apiBase: html.dataset.apiBase || '',
        locale: html.lang || ''
    });
})();