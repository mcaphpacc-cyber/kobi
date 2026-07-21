let activeIndex = -1;

const searchBox =
    document.getElementById(
        'homeSearch'
    );

const results =
    document.getElementById(
        'homeSearchResults'
    );

    searchBox.addEventListener(
    'input',
    async e =>
{
    const keyword =
        e.target.value.trim();

    if (keyword.length < 2)
    {
        results.innerHTML = '';

        return;
    }

    const response =
        await fetch(

            window.KOBI.apiBase +

            '/search/suggestions?q=' +

            encodeURIComponent(keyword)

        );

    const diseases =
        await response.json();

    renderResults(
        diseases
    );

});

function renderResults(
    diseases
)
{
    //results.innerHTML = '';
    results.style.display = 'block';

    diseases.forEach(
        disease =>
    {
        results.innerHTML +=

        `
        <a

            href="${window.KOBI.baseUrl}disease/${disease.slug}"

            class="list-group-item list-group-item-action">

            <i class="bi bi-search me-2"></i>

            ${disease.disease_en}

        </a>
        `;

    });
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

    if (!items.length)
        return;

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

        case 'Enter':

            if (activeIndex >= 0)
            {
                e.preventDefault();

                items[activeIndex].click();
            }

            return;
        case 'Escape':

            results.innerHTML = '';

            results.style.display = 'none';

            activeIndex = -1;

            return;

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