let activeIndex = -1;
let searchVersion = 0;

const searchBox =
    document.getElementById(
        'homeSearch'
    );

const results =
    document.getElementById(
        'homeSearchResults'
    );

const searchButton = searchBox.closest('.input-group').querySelector('button');

function clearResults()
{
    results.replaceChildren();
    results.style.display = 'none';
    activeIndex = -1;
}

async function searchDiseases()
{
    const version = ++searchVersion;
    const keyword = searchBox.value.trim();
    clearResults();

    if (keyword.length < 2) { return; }

    const endpoint = new URL('search/suggestions', window.KOBI.apiBase.replace(/\/+$/, '') + '/');
    endpoint.searchParams.set('q', keyword);

    try {
        const response = await fetch(endpoint);
        if (!response.ok) { throw new Error('Disease search unavailable.'); }
        const diseases = await response.json();
        if (version !== searchVersion) { return; }
        renderResults(diseases);
    } catch (error) {
        if (version === searchVersion) {
            clearResults();
            console.error('Unable to search diseases. Please retry.');
        }
    }
}

searchBox.addEventListener('input', searchDiseases);
searchButton.addEventListener('click', searchDiseases);

function renderResults(
    diseases
)
{
    clearResults();

    diseases.forEach(
        disease =>
    {
        const link = document.createElement('a');
        link.href = new URL('disease/' + encodeURIComponent(disease.slug), window.KOBI.baseUrl.replace(/\/+$/, '') + '/').href;
        link.className = 'list-group-item list-group-item-action';
        const icon = document.createElement('i');
        icon.className = 'bi bi-search me-2';
        link.append(icon, document.createTextNode(disease.disease_en));
        results.appendChild(link);

    });
    results.style.display = diseases.length ? 'block' : 'none';
}

function highlightActive(items)
{
    items.forEach(item =>
        item.classList.remove('active')
    );

    if (
        activeIndex >= 0 &&
        activeIndex < items.length
    ) {
        items[activeIndex]
            .classList.add('active');

        items[activeIndex]
            .scrollIntoView({
                block: 'nearest'
            });
    }
}

searchBox.addEventListener(
    'keydown',
    e =>
{
    const items =
        results.querySelectorAll('a');

    if (e.key === 'Enter') {
        e.preventDefault();
        if (activeIndex >= 0 && items[activeIndex] && results.style.display !== 'none') {
            items[activeIndex].click();
        } else {
            searchDiseases();
        }
        return;
    }

    if (e.key === 'Escape') {
        ++searchVersion;
        clearResults();
        return;
    }

    if (!items.length) { return; }

    switch (e.key)
    {
        case 'ArrowDown':

            e.preventDefault();

            activeIndex =
                Math.min(
                    activeIndex + 1,
                    items.length - 1
                );

            break;

        case 'ArrowUp':

            e.preventDefault();

            activeIndex =
                Math.max(
                    activeIndex - 1,
                    0
                );

            break;

        default:

            return;
    }

    highlightActive(items);

});

document.addEventListener(
    'click',
    e =>
{
    if (
        !searchBox.contains(e.target) &&
        !results.contains(e.target)
    ) {
        results.style.display = 'none';
    }
});
