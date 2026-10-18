document.addEventListener('DOMContentLoaded', function () {

    const status =
        document.getElementById('status');

    const stoppedOnWrapper =
        document.getElementById('stoppedOnWrapper');

    const stoppedOn =
        document.getElementById('stopped_on');

    if (!status || !stoppedOnWrapper || !stoppedOn) {
        return;
    }

    function updateStoppedDateVisibility()
    {
        if (status.value === 'current')
        {
            stoppedOnWrapper.classList.add('d-none');
            stoppedOn.value = '';
            stoppedOn.required = false;
        }
        else if (status.value === 'stopped')
        {
            stoppedOnWrapper.classList.remove('d-none');
            stoppedOn.required = true;
        }
        else
        {
            stoppedOnWrapper.classList.remove('d-none');
            stoppedOn.required = false;
        }
    }

    status.addEventListener(
        'change',
        updateStoppedDateVisibility
    );

    updateStoppedDateVisibility();

});



document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('generic_name');

    if (!input) {
        return;
    }

    let debounceTimer = null;
    let activeRequest = null;

    const wrapper = document.createElement('div');

    wrapper.className = 'position-relative';

    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const dropdown = document.createElement('div');

    dropdown.className =
        'list-group position-absolute w-100 shadow-sm d-none';

    dropdown.style.zIndex = '1050';

    wrapper.appendChild(dropdown);

    const hideDropdown = () => {
        dropdown.innerHTML = '';
        dropdown.classList.add('d-none');
    };

    const showDropdown = () => {
        dropdown.classList.remove('d-none');
    };

    const renderSuggestions = (results) => {
        dropdown.innerHTML = '';

        if (!results.length) {
            hideDropdown();
            return;
        }

        results.forEach((item) => {
            const button = document.createElement('button');

            button.type = 'button';
            button.className =
                'list-group-item list-group-item-action';

            button.textContent = item.canonical_name;

            button.addEventListener('click', (event) => {
                event.preventDefault();

                selectSubstance(item.canonical_name);
            });

            dropdown.appendChild(button);
        });

        showDropdown();
    };

    const getCurrentSearchTerm = () => {
        const value = input.value;

        const commaIndex = value.lastIndexOf(',');

        if (commaIndex === -1) {
            return value.trim();
        }

        return value
            .substring(commaIndex + 1)
            .trim();
    };

    const selectSubstance = (canonicalName) => {
        const value = input.value.trim();

        const commaIndex = value.lastIndexOf(',');

        let existing = [];

        if (commaIndex !== -1) {
            existing = value
                .substring(0, commaIndex)
                .split(',')
                .map(item => item.trim())
                .filter(Boolean);
        }

        const duplicate = existing.some(
            item =>
                item.toLowerCase() ===
                canonicalName.toLowerCase()
        );

        if (duplicate) {
            hideDropdown();
            return;
        }

        existing.push(canonicalName);

        input.value = existing.join(', ');

        hideDropdown();

        input.focus();
    };

    const search = async () => {
        const keyword = getCurrentSearchTerm();

        if (keyword.length < 2) {
            hideDropdown();
            return;
        }

        if (activeRequest) {
            activeRequest.abort();
        }

        activeRequest = new AbortController();

        try {
            const response = await fetch(
                `${window.KOBI.apiBase}/substances/search?q=${encodeURIComponent(keyword)}`,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    signal: activeRequest.signal
                }
            );

            if (!response.ok) {
                hideDropdown();
                return;
            }

            const results = await response.json();

            renderSuggestions(results);
        }
        catch (error) {
            if (error.name !== 'AbortError') {
                hideDropdown();
            }
        }
        finally {
            activeRequest = null;
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);

        debounceTimer = setTimeout(
            search,
            300
        );
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideDropdown();
        }
    });

    document.addEventListener('click', (event) => {
        if (!wrapper.contains(event.target)) {
            hideDropdown();
        }
    });
});
