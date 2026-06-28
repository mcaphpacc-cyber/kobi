/******************************************************************
 *
 * KOBI Core v0.2.0
 * Symptom Checker
 *
 * Release A - Part A
 *
 ******************************************************************/

"use strict";

/******************************************************************
 * Configuration
 ******************************************************************/

const CONFIG = {

    SEARCH_DELAY: 250,

    MIN_SEARCH_LENGTH: 2,

    MAX_RESULTS: 10,

    MAX_MISSING_SYMPTOMS: 3

};

/******************************************************************
 * Application State
 ******************************************************************/

const state = {

    selectedSymptoms: [],

    timer: null,

    results: null

};

/******************************************************************
 * Cached DOM Elements
 ******************************************************************/

const dom = {

    selectedContainer:
        document.getElementById("selectedSymptoms"),

    counter:
        document.getElementById("symptomCounter"),

    searchBox:
        document.getElementById("symptomSearch"),

    searchResults:
        document.getElementById("searchResults"),

    matchResults:
        document.getElementById("matchResults"),

    button:
        document.getElementById("findConditions")

};

/******************************************************************
 * Application Initialization
 ******************************************************************/

document.addEventListener("DOMContentLoaded", init);

function init()
{
    bindSearch();

    bindPopularSymptoms();

    bindButton();

    bindOutsideClick();

    renderSelected();
}

/******************************************************************
 * Event Binding
 ******************************************************************/

function bindSearch()
{
    dom.searchBox.addEventListener(
        "keyup",
        onSearchKeyUp
    );
}

function bindPopularSymptoms()
{
    document
        .querySelectorAll(".symptom-chip")
        .forEach(chip => {

            chip.addEventListener("click", () => {

                addSymptom(

                    Number(chip.dataset.id),

                    chip.dataset.name

                );

            });

        });
}

function bindButton()
{
    dom.button.addEventListener(
        "click",
        loadMatches
    );
}

function bindOutsideClick()
{
    document.addEventListener("click", event => {

        if (
            !dom.searchResults.contains(event.target)
            &&
            event.target !== dom.searchBox
        ) {

            dom.searchResults.innerHTML = "";

        }

    });
}

/******************************************************************
 * Search
 ******************************************************************/

function onSearchKeyUp()
{
    clearTimeout(state.timer);

    const keyword =
        dom.searchBox.value.trim();

    if (
        keyword.length
        <
        CONFIG.MIN_SEARCH_LENGTH
    ) {

        dom.searchResults.innerHTML = "";

        return;

    }

    state.timer = setTimeout(

        () => {

            loadSymptoms(keyword);

        },

        CONFIG.SEARCH_DELAY

    );
}

function loadSymptoms(keyword)
{
    fetch(

        "./api/symptoms?q="

        +

        encodeURIComponent(keyword)

    )

    .then(response => response.json())

    .then(renderSuggestions)

    .catch(console.error);
}

/******************************************************************
 * Autocomplete Rendering
 ******************************************************************/

function renderSuggestions(items)
{
    dom.searchResults.innerHTML = "";

    if (items.length === 0) {

        dom.searchResults.innerHTML = `

            <div
                class="list-group-item text-muted">

                No symptoms found.

            </div>

        `;

        return;
    }

    items.forEach(item => {

        if (

            state.selectedSymptoms.find(

                s => s.id == item.id

            )

        ) {

            return;

        }

        const button =
            document.createElement("button");

        button.type = "button";

        button.className =
            "list-group-item list-group-item-action";

        button.textContent =
            item.symptom_en;

        button.addEventListener(

            "click",

            () => {

                addSymptom(

                    item.id,

                    item.symptom_en

                );

                dom.searchBox.value = "";

                dom.searchResults.innerHTML = "";

                dom.searchBox.focus();

            }

        );

        dom.searchResults.appendChild(button);

    });
}

/******************************************************************
 * Selected Symptoms
 ******************************************************************/

function addSymptom(id, name)
{
    if (

        state.selectedSymptoms.find(

            symptom => symptom.id == id

        )

    ) {

        return;

    }

    state.selectedSymptoms.push({

        id,

        name

    });

    renderSelected();
}

/******************************************************************
 * Selected Symptoms Rendering
 ******************************************************************/

function renderSelected()
{
    if (state.selectedSymptoms.length === 0) {

        dom.selectedContainer.innerHTML = `
            <p class="text-muted mb-0">
                No symptoms selected.
            </p>
        `;

    } else {

        let html = "";

        state.selectedSymptoms.forEach(symptom => {

            html += `
                <div class="selected-item">

                    <span>${symptom.name}</span>

                    <span
                        class="remove-symptom"
                        data-id="${symptom.id}">

                        ×

                    </span>

                </div>
            `;

        });

        dom.selectedContainer.innerHTML = html;
    }

    dom.counter.textContent =
        state.selectedSymptoms.length;

    dom.button.disabled =
        state.selectedSymptoms.length === 0;

    bindRemoveButtons();
}

/******************************************************************
 * Remove Symptoms
 ******************************************************************/

function bindRemoveButtons()
{
    document

        .querySelectorAll(".remove-symptom")

        .forEach(button => {

            button.addEventListener(

                "click",

                () => {

                    removeSymptom(

                        Number(button.dataset.id)

                    );

                }

            );

        });
}

function removeSymptom(id)
{
    state.selectedSymptoms =

        state.selectedSymptoms.filter(

            symptom => symptom.id !== id

        );

    renderSelected();
}

/******************************************************************
 * Match API
 ******************************************************************/

function loadMatches()
{
    if (
        state.selectedSymptoms.length === 0
    ) {

        return;

    }

    dom.button.disabled = true;

    dom.button.innerHTML = `
        <span
            class="spinner-border spinner-border-sm me-2">
        </span>

        Finding Matches...
    `;

    const ids =

        state.selectedSymptoms

            .map(symptom => symptom.id)

            .join(",");

    fetch(

        "./api/symptom-checker?symptoms="

        +

        ids

    )

    .then(response => response.json())

    .then(data => {

        state.results = data;

        renderMatches();

    })

    .catch(error => {

        console.error(error);

    })

    .finally(() => {

        dom.button.disabled = false;

        dom.button.innerHTML =
            "Find Possible Conditions";

    });
}

/******************************************************************
 * Results
 ******************************************************************/

function renderMatches()
{
    const response =
        state.results;

    const summary =
        response.summary;

    const results =
        response.results;

    dom.matchResults.innerHTML = "";

    if (results.length === 0) {

        dom.matchResults.innerHTML = `

            <div class="alert alert-info">

                No matching conditions found.

            </div>

        `;

        return;

    }

    document.getElementById("resultsSummary").innerHTML =
        renderSummary(summary);

    dom.matchResults.innerHTML =
        renderDiseaseCards(results);
}

/******************************************************************
 * Results Summary
 ******************************************************************/

function renderSummary(summary)
{
    const selected =

        summary.selectedSymptoms

            .map(id => {

                const symptom =

                    state.selectedSymptoms.find(

                        s => s.id == id

                    );

                if (!symptom)
                    return "";

                return `

                    <span
                        class="badge bg-primary me-1 mb-1">

                        ${symptom.name}

                    </span>

                `;

            })

            .join("");

    return `

        <div class="card shadow-sm mb-4">

            <div class="card-body">

                <h4>

                    Results Summary

                </h4>

                <p class="mt-3">

                    <strong>

                        Selected Symptoms

                    </strong>

                </p>

                ${selected}

                <hr>

                <p>

                    Showing

                    <strong>

                        ${summary.displayedMatches}

                    </strong>

                    of

                    <strong>

                        ${summary.totalMatches}

                    </strong>

                    matching conditions.

                </p>

                <div class="alert alert-warning mb-0">

                    KOBI Symptom Checker is an educational
                    tool.

                    It does not provide a medical diagnosis.

                </div>

            </div>

        </div>

    `;
}

/******************************************************************
 * Disease Cards
 ******************************************************************/

function renderDiseaseCards(results)
{
    let html = "";

    results.forEach(item => {

        html += renderDiseaseCard(item);

    });

    return html;
}

function renderDiseaseCard(item)
{
    const badge = getMatchBadge(item.matchLevel);

    return `

        <div class="card shadow-sm mb-4">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start">

                    <div>

                        <h4 class="mb-1">

                            ${item.disease.disease_en}

                        </h4>

                        <span class="badge ${badge.className}">

                            ${badge.text}

                        </span>

                    </div>

                    <div class="text-end">

                        <div class="small text-muted">

                            Ranking

                        </div>

                        <strong>

                            ${item.rankingScore}

                        </strong>

                    </div>

                </div>

                <hr>

                <div class="mb-3">

                    <strong>

                        Matched Symptoms

                    </strong>

                    <div class="mt-2">

                        ${renderMatchedSymptoms(item)}

                    </div>

                </div>

                <div class="mb-3">

                    <strong>

                        Missing Common Symptoms

                    </strong>

                    <div class="mt-2">

                        ${renderMissingSymptoms(item)}

                    </div>

                </div>

                ${renderProgress(
                    "User Match",
                    item.userMatchScore,
                    "success"
                )}

                ${renderProgress(
                    "Disease Coverage",
                    item.coverage,
                    "primary"
                )}

                <div class="mt-4 text-end">

                    <a

                        href="./disease/${item.disease.slug}"

                        class="btn btn-outline-primary">

                        View Disease →

                    </a>

                </div>

            </div>

        </div>

    `;
}

/******************************************************************
 * Symptoms
 ******************************************************************/

function renderMatchedSymptoms(item)
{
    return item.matchedSymptoms

        .map(symptom => `

            <span
                class="badge bg-success me-1 mb-1">

                ✓ ${symptom.symptom_en}

            </span>

        `)

        .join("");
}

function renderMissingSymptoms(item)
{
    return item.missingSymptoms

        .slice(0, CONFIG.MAX_MISSING_SYMPTOMS)

        .map(symptom => `

            <span
                class="badge bg-light text-dark border me-1 mb-1">

                ${symptom.symptom_en}

            </span>

        `)

        .join("");
}

/******************************************************************
 * Progress Bars
 ******************************************************************/

function renderProgress(title, value, color)
{
    return `

        <div class="mb-3">

            <div
                class="d-flex justify-content-between">

                <small>

                    ${title}

                </small>

                <small>

                    ${value}%

                </small>

            </div>

            <div class="progress">

                <div

                    class="progress-bar bg-${color}"

                    style="width:${value}%">

                </div>

            </div>

        </div>

    `;
}

/******************************************************************
 * Match Badge
 ******************************************************************/

function getMatchBadge(level)
{
    switch(level){

        case "strong":

            return {

                className: "bg-success",

                text: "Strong Symptom Overlap"

            };

        case "moderate":

            return {

                className: "bg-warning text-dark",

                text: "Moderate Symptom Overlap"

            };

        default:

            return {

                className: "bg-secondary",

                text: "Limited Symptom Overlap"

            };

    }
}

/******************************************************************
 * End of File
 ******************************************************************/