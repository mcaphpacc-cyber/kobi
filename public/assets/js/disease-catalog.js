/**
 * ==========================================================================
 * KOBI Core
 * Disease Catalog
 * Version : 1.0
 * ==========================================================================
 */

document.addEventListener("DOMContentLoaded", () => {

    "use strict";

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    */

    const config = {

        tableSelector: "#diseaseTable tbody",

        rowSelector: ".disease-row",

        hiddenClass: "d-none"

    };

    /*
    |--------------------------------------------------------------------------
    | Application State
    |--------------------------------------------------------------------------
    */

    const state = {

        keyword: "",

        gender: "all",

        sort: "az",

        compare: [],

        currentPage: 1,

        perPage: 25

    };

    /*
    |--------------------------------------------------------------------------
    | DOM Cache
    |--------------------------------------------------------------------------
    */

    const dom = {

        search:
            document.getElementById(
                "diseaseSearch"
            ),

        sort:
            document.getElementById(
                "sortDiseases"
            ),

        gender:
            document.getElementById(
                "genderFilter"
            ),

        table:
            document.querySelector(
                config.tableSelector
            ),

        counter:
            document.getElementById(
                "visibleDiseaseCount"
            ),

        empty:
            document.getElementById(
                "noDiseaseResults"
            ),
        
        compareToolbar:
            document.getElementById(
                "compareToolbar"
            ),

        compareCounter:
            document.getElementById(
                "compareCounter"
            ),

        compareNow:
            document.getElementById(
                "compareNow"
            ),

        compareList:
            document.getElementById(
                "compareList"
            ),
        
        pagination:
            document.getElementById(
                "catalogPagination"
            )

    };

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (!dom.table) {

        console.error(
            "KOBI Catalog: Table not found."
        );

        return;

    }

    /*
    |--------------------------------------------------------------------------
    | Cached Rows
    |--------------------------------------------------------------------------
    */

    const rows = Array.from(

        dom.table.querySelectorAll(

            config.rowSelector

        )

    );

    /*
    |--------------------------------------------------------------------------
    | Initialization
    |--------------------------------------------------------------------------
    */

    function init()
    {

        bindRowClicks();

        bindEvents();

        updateCatalog();

        bindSymptomTags();

        bindCompareButtons();

        bindCompareNow();

        updateCompareUI();

        console.log(
            "KOBI Disease Catalog initialized."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Event Binding
    |--------------------------------------------------------------------------
    */

    function bindEvents()
    {

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (dom.search) {

            dom.search.addEventListener(

                "input",

                () => {

                    state.keyword =

                        dom.search
                            .value
                            .trim()
                            .toLowerCase();
                    
                    state.currentPage = 1;

                    updateCatalog();

                }

            );

            dom.search.addEventListener(

                "keydown",

                event => {

                    if (event.key === "Escape") {

                        dom.search.value = "";

                        state.keyword = "";

                        state.currentPage = 1;
                        updateCatalog();

                    }

                }

            );

        }

        /*
        |--------------------------------------------------------------------------
        | Gender
        |--------------------------------------------------------------------------
        */

        if (dom.gender) {

            dom.gender.addEventListener(

                "change",

                () => {

                    state.gender =

                        dom.gender.value;

                    state.currentPage = 1;

                    updateCatalog();

                }

            );

        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        if (dom.sort) {

            dom.sort.addEventListener(

                "change",

                () => {

                    state.sort =

                        dom.sort.value;

                    state.currentPage = 1;

                    updateCatalog();

                }

            );

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Update Catalog
    |--------------------------------------------------------------------------
    */

    function updateCatalog()
    {

        let filtered = filterRows();

        filtered = sortRows(filtered);

        renderRows(filtered);

        updateCounter(

            filtered.length

        );

        updateEmptyState(

            filtered.length

        );

    }

        /*
    |--------------------------------------------------------------------------
    | Filter Rows
    |--------------------------------------------------------------------------
    */

    function filterRows()
    {

        let filtered = [

            ...rows

        ];

        /*
        |--------------------------------------------------------------------------
        | Search Filter
        |--------------------------------------------------------------------------
        */

        const keyword = state.keyword.trim().toLowerCase();

        if (keyword !== "") {

            filtered = filtered
                .map(row => ({
                    row,
                    score: calculateScore(row, state.keyword)
                }))
                .filter(item => item.score > 0)
                .sort((a, b) => b.score - a.score)
                .map(item => item.row);

        }

        /*
        |--------------------------------------------------------------------------
        | Gender Filter
        |--------------------------------------------------------------------------
        */

        if (state.gender !== "all") {

            filtered = filtered.filter(

                row =>

                    row.dataset.gender ===

                    state.gender

            );

        }

        
        return filtered;

    }

    function matchesSearch(row)
    {
        const words = row.dataset.search.split(/\s+/);

        return words.some(word =>
            word.startsWith(state.keyword)
        );
    }

    function calculateScore(row, keyword)
    {
        let score = 0;

        const name = row.dataset.name || "";
        const body = row.dataset.bodySystem || "";
        const symptoms = row.dataset.symptoms || "";

        if (containsWord(name, keyword))
            score += 100;

        if (containsWord(body, keyword))
            score += 80;

        if (containsWord(symptoms, keyword))
            score += 60;

        if (keyword === "ear" && score >0) {

            console.log(
                row.dataset.name,
                row.dataset.bodySystem,
                row.dataset.symptoms,
                score
            );

        }

        return score;
    }

    /*
    |--------------------------------------------------------------------------
    | Sort Rows
    |--------------------------------------------------------------------------
    */

    function sortRows(filtered)
    {
        console.log(
    state.sort,
    filtered.map(row => ({
        name: row.dataset.name,
        symptoms: row.dataset.symptomCount
    })).slice(0, 5)
);
        
        // When searching, keep relevance order
        if (state.keyword.trim() !== "") {
            return filtered;
        }

        switch (state.sort) {

            case "za":

                filtered.sort(
                    (a, b) =>
                        b.dataset.name.localeCompare(a.dataset.name)
                );

                break;

            case "mostSymptoms":

                filtered.sort(
                    (a, b) =>
                        Number(b.dataset.symptomCount) -
                        Number(a.dataset.symptomCount)
                );

                break;

            case "leastSymptoms":

                filtered.sort(
                    (a, b) =>
                        Number(a.dataset.symptomCount) -
                        Number(b.dataset.symptomCount)
                );

                break;

            default:

                filtered.sort(
                    (a, b) =>
                        a.dataset.name.localeCompare(b.dataset.name)
                );

        }

        return filtered;
    }

    /*
    |--------------------------------------------------------------------------
    | Render Rows
    |--------------------------------------------------------------------------
    */

    function renderRows(filtered)
    {
        rows.forEach(

            row =>

                row.classList.add(

                    config.hiddenClass

                )

        );

        const totalItems =
            filtered.length;

        const totalPages =
            Math.max(
                1,
                Math.ceil(
                    totalItems /
                    state.perPage
                )
            );

        if (
            state.currentPage >
            totalPages
        )
        {
            state.currentPage =
                totalPages;
        }

        const start =
            (
                state.currentPage - 1
            ) * state.perPage;

        const end =
            start +
            state.perPage;

        const pageRows =
            filtered.slice(
                start,
                end
            );

        pageRows.forEach(

            row => {

                row.classList.remove(

                    config.hiddenClass

                );

                dom.table.appendChild(

                    row

                );

            }

        );

        renderPagination(
            totalPages
        );

    }

    function renderPagination(totalPages)
{
    dom.pagination.innerHTML = "";

    if (totalPages <= 1)
    {
        return;
    }

    const createItem = (
        label,
        page,
        active = false,
        disabled = false
    ) =>
    {
        const li =
            document.createElement("li");

        li.className =
            "page-item";

        if (active)
            li.classList.add(
                "active"
            );

        if (disabled)
            li.classList.add(
                "disabled"
            );

        const link =
            document.createElement("a");

        link.href = "#";

        link.className =
            "page-link";

        link.textContent =
            label;

        if (!disabled)
        {
            link.addEventListener(
                "click",
                function (e)
                {
                    e.preventDefault();

                    state.currentPage =
                        page;

                    updateCatalog();
                }
            );
        }

        li.appendChild(link);

        dom.pagination.appendChild(li);
    };

    createItem(
        "«",
        state.currentPage - 1,
        false,
        state.currentPage === 1
    );

    for (
        let page = 1;
        page <= totalPages;
        page++
    )
    {
        createItem(
            page,
            page,
            page === state.currentPage
        );
    }

    createItem(
        "»",
        state.currentPage + 1,
        false,
        state.currentPage === totalPages
    );
}

    /*
    |--------------------------------------------------------------------------
    | Update Counter
    |--------------------------------------------------------------------------
    */

    function updateCounter(total)
    {

        if (!dom.counter) {

            return;

        }

        dom.counter.textContent =

            total;

    }

    /*
    |--------------------------------------------------------------------------
    | Update Empty State
    |--------------------------------------------------------------------------
    */

    function updateEmptyState(total)
    {

        if (!dom.empty) {

            return;

        }

        dom.empty.classList.toggle(

            config.hiddenClass,

            total !== 0

        );

    }
        /*
    |--------------------------------------------------------------------------
    | Clickable Rows
    |--------------------------------------------------------------------------
    */

    function bindRowClicks()
    {

        rows.forEach(row => {

            row.style.cursor = "pointer";

            row.addEventListener(

                "click",

                event => {

                    /*
                    ----------------------------------------------------------
                    | Ignore clicks on links/buttons
                    ----------------------------------------------------------
                    */

                    if (

                        event.target.closest(

                            "a, button"

                        )

                    ) {

                        return;

                    }

                    /*
                    ----------------------------------------------------------
                    | Navigate to disease detail
                    ----------------------------------------------------------
                    */

                    const url = row.dataset.url;

                    if (url) {

                        window.location.href = url;

                    }

                }

            );

        });

    }

    function containsWord(text, keyword)
    {
        text = (text || "").toLowerCase().trim();
        keyword = (keyword || "").toLowerCase().trim();

        if (keyword === "") {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Phrase Search
        |--------------------------------------------------------------------------
        |
        | If the user searches multiple words like:
        | "ear pain"
        | "chest pain"
        | "high blood pressure"
        |
        | search for the complete phrase.
        |
        */

        if (keyword.includes(" ")) {
            return text.includes(keyword);
        }

        /*
        |--------------------------------------------------------------------------
        | Single Word Search
        |--------------------------------------------------------------------------
        */

        const words = text
            .split(/[\s,()/-]+/)
            .filter(Boolean);

        return words.some(word => {

            if (word === keyword)
                return true;

            // Singular / plural matching
            if (
                word.endsWith("s") &&
                word.slice(0, -1) === keyword
            ) {
                return true;
            }

            return false;

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Utility Functions
    |--------------------------------------------------------------------------
    */

    function getVisibleRows()
    {

        return rows.filter(

            row =>

                !row.classList.contains(

                    config.hiddenClass

                )

        );

    }

    function refresh()
    {

        updateCatalog();

    }

    /*
    |--------------------------------------------------------------------------
    | Initialize Module
    |--------------------------------------------------------------------------
    */

    init();

   function bindSymptomTags()
    {

        document.querySelectorAll(".symptom-tag")

            .forEach(tag => {

                tag.addEventListener("click", event => {

                    event.stopPropagation();

                    const symptom = tag.dataset.symptom;

                    state.keyword = symptom.trim().toLowerCase();

                    dom.search.value = symptom;

                    state.currentPage = 1;

                    updateCatalog();

                    dom.search.focus();

                    dom.search.scrollIntoView({

                        behavior: "smooth",

                        block: "center"

                    });

                });

            });

    }

    function updateCompareUI()
    {
        const count = state.compare.length;

        if (count === 0)
        {
            dom.compareToolbar.classList.add("d-none");
        }
        else
        {
            dom.compareToolbar.classList.remove("d-none");
        }

        dom.compareCounter.textContent =
            `${count} Selected`;

        dom.compareList.innerHTML =
            state.compare
                .map(item =>
                    `<div class='mx-1'>
                        <i class="bi bi-check-circle-fill text-success"></i>
                        ${item.name}
                    </div>`
                )
                .join("");

        dom.compareNow.disabled = count !== 2;

        document
            .querySelectorAll(".compare-btn")
            .forEach(button => {

                const slug = button.dataset.slug;

                if (state.compare.includes(slug))
                {
                    button.classList.remove("btn-outline-primary");
                    button.classList.add("btn-success");

                    button.innerHTML =
                        '<i class="bi bi-check-lg"></i> Added';
                }
                else
                {
                    button.classList.remove("btn-success");
                    button.classList.add("btn-outline-primary");

                    button.innerHTML =
                        '<i class="bi bi-plus-lg"></i> Compare';
                }

            });
        
    }

    function bindCompareButtons()
    {
        document
            .querySelectorAll(".compare-btn")
            .forEach(button => {

                button.onclick = function ()
                {
                    const slug = this.dataset.slug;
                    //const name = this.dataset.name;

                    const index = state.compare.findIndex(
                        item => item.slug === slug
                    );

                    // Remove if already selected
                    if (index !== -1)
                    {
                        state.compare.splice(index, 1);

                        updateCompareUI();

                        return;
                    }

                    // Maximum two diseases
                    if (state.compare.length >= 2)
                    {
                        alert(
                            "You can compare only two diseases."
                        );

                        return;
                    }

                    state.compare.push({

                        slug,

                        name: this.dataset.name

                    });

                    updateCompareUI();
                };

            });
    }

    function bindCompareNow()
    {
        dom.compareNow.addEventListener("click", function ()
        {
            if (state.compare.length !== 2)
            {
                return;
            }

            const d1 = state.compare[0].slug;
            const d2 = state.compare[1].slug;

            window.location.href =
                `${window.KOBI.baseUrl}compare/result?d1=${encodeURIComponent(d1)}&d2=${encodeURIComponent(d2)}`;
                    });
    }

    function getDiseaseName(slug)
    {
        const button = document.querySelector(
            `.compare-btn[data-slug="${slug}"]`
        );

        return button ? button.dataset.name : slug;
    }

});

