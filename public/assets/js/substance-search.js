'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('substanceSearchForm');
    if (!form) { return; }

    const input = document.getElementById('substanceQuery');
    const results = document.getElementById('substanceResults');
    const status = document.getElementById('substanceSearchStatus');
    let version = 0;
    let request = null;

    function clearSearch() {
        ++version;
        request?.abort();
        request = null;
        results.replaceChildren();
        results.setAttribute('aria-busy', 'false');
        status.textContent = 'Enter a substance name and select Search.';
    }

    input.addEventListener('input', clearSearch);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        clearSearch();
        const query = input.value.trim();
        if (!query) { input.focus(); return; }

        const currentVersion = version;
        request = new AbortController();
        results.setAttribute('aria-busy', 'true');
        status.textContent = 'Searching registry names…';

        try {
            const endpoint = new URL('substances/search', window.KOBI.apiBase.replace(/\/+$/, '') + '/');
            endpoint.searchParams.set('q', query);
            const response = await fetch(endpoint, {
                headers: { 'Accept': 'application/json' },
                signal: request.signal,
            });
            if (!response.ok) { throw new Error('Search unavailable'); }
            const entries = await response.json();
            if (!Array.isArray(entries) || entries.length > 10 ||
                entries.some(entry => !entry || typeof entry.canonical_name !== 'string')) {
                throw new Error('Invalid search response');
            }
            if (currentVersion !== version) { return; }

            entries.forEach(entry => {
                const item = document.createElement('li');
                item.className = 'list-group-item text-break';
                item.textContent = entry.canonical_name;
                results.appendChild(item);
            });
            status.textContent = entries.length
                ? `${entries.length} matching registry name${entries.length === 1 ? '' : 's'} shown.`
                : 'No matching registry names found. Try another name or a shorter prefix.';
        } catch (error) {
            if (currentVersion === version) {
                results.replaceChildren();
                status.textContent = 'Substance search is unavailable right now. Please try again.';
            }
        } finally {
            if (currentVersion === version) {
                results.setAttribute('aria-busy', 'false');
                request = null;
            }
        }
    });
    if (input.value.trim()) { form.requestSubmit(); }
});
