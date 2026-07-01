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
    document
    .getElementById("emptyState")
    .classList.add("d-none");
    
    if (state.selectedSymptoms.length === 0)
    {
        showEmptyState(
            "Select one or more symptoms to begin.",
            "Start by searching for symptoms or choosing from the popular symptoms below."
        );

        return;
    }

    showLoading();

    dom.button.disabled = true;

    dom.button.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2"></span>
        Finding Matches...
    `;

    const ids = state.selectedSymptoms
        .map(symptom => symptom.id)
        .join(",");

    fetch("./api/symptom-checker?symptoms=" + ids)

        .then(response => response.json())

        .then(data => {

            hideLoading();

            state.results = data;

            if (data.length === 0)
            {
                hideLoading();

                showEmptyState(
                    "No matching conditions found.",
                    "Try adding more symptoms or removing one of your selected symptoms."
                );

                return;
            }

            renderMatches();

        })

        .catch(error => {

            hideLoading();

            console.error(error);

        })

        .finally(() => {

            dom.button.disabled = true;

            dom.button.innerHTML =
                "Find Possible Conditions";

        });
}

function updateResultsSummary(results)
{
    const summary =
        document.getElementById("resultsSummary");

    summary.classList.remove("d-none");

    document.getElementById(
        "summarySelected"
    ).textContent = selectedSymptoms.length;

    document.getElementById(
        "summaryMatches"
    ).textContent = results.length;

    document.getElementById(
        "summaryShowing"
    ).textContent =
        `Top ${Math.min(results.length, 10)}`;
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
    
    //updateResultsSummary(results);

    dom.matchResults.innerHTML = "";

    if (results.length === 0) {

        dom.matchResults.innerHTML = `

            <div class="alert alert-info">

                No matching conditions found.

            </div>

        `;

        document
            .getElementById("resultsSummary")
            .classList.add("d-none");

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

    const matched = item.matchedSymptoms
        .map(symptom => `
            <span class="badge bg-success me-1 mb-1">
                ✓ ${symptom.symptom_en}
            </span>
        `)
        .join("");

    let missing = `
            <span class="text-success">

                No common symptoms missing

            </span>
            `;

    if (item.missingSymptoms.length > 0)
    {
        missing = item.missingSymptoms
            .slice(0, 2)
            .map(symptom => symptom.symptom_en)
            .join(", ");

        if (item.missingSymptoms.length > 2)
        {
            missing += ` +${item.missingSymptoms.length - 2} more`;
        }
    }

    return `
<div class="card shadow-sm mb-3 disease-card">

    <div class="card-body">

        <div class="d-flex justify-content-between align-items-start mb-2">

            <div>

                <h5 class="mb-1">
                    ${item.disease.disease_en}
                </h5>

                <span class="badge ${badge.className} px-2 py-1">
                    ${badge.text}
                </span>

            </div>

            <div class="text-end">

                <div class="fs-2 fw-bold text-primary">

                    ${Math.round(item.rankingScore)}%

                </div>

                <small class="text-muted d-block">

                    Overall Match

                </small>

                <!-- Removed duplicate badge -->

            </div>

        </div>

        <div class="row mt-3">

            <div class="col-lg-6">

                <strong>✓ Matched Symptoms</strong>

                <div class="mt-2">

                    ${matched}

                </div>

            </div>

            <div class="col-lg-6">

                ${renderProgress(
                    "User Match",
                    item.userMatchScore,
                    "success"
                )}

            </div>

        </div>

        <div class="row mt-3">

            <div class="col-lg-6">

                <strong>○ Missing Symptoms</strong>

                <div class="mt-2">

                    <span class="badge bg-light text-dark border">

                        ${missing}

                    </span>

                </div>

            </div>

            <div class="col-lg-6">

                ${renderProgress(
                    "Disease Coverage",
                    item.coverage,
                    "primary"
                )}

            </div>

        </div>

        <div class="mt-3">

            <strong>Why this matched</strong>

            <div
                class="bg-light border rounded p-3 mt-2">

                ${renderExplanation(item)}

            </div>

        </div>

        <div class="small text-muted fst-italic mt-3">

            Educational information only.
            This is not a medical diagnosis.

        </div>
        <div class="text-end mt-4">

            <a
                href="/disease/${item.disease.slug}"
                class="btn btn-primary btn-sm px-4">

                Learn More →

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
                class="d-flex justify-content-between mb-1">

                <small class="fw-semibold">

                    ${title}

                </small>

                <small class="text-muted">

                    ${value}%

                </small>

            </div>

            <div class="progress rounded-pill" style="height:10px;">

                <div
                    class="progress-bar bg-${color} rounded-pill"

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

function renderExplanation(item)
{
    let html = "";

    item.matchedSymptoms.forEach(symptom => {

        html += `
            <div class="small text-success mb-1">

                ✓ ${symptom.symptom_en} matched

            </div>
        `;

    });

    item.missingSymptoms
        .slice(0,2)
        .forEach(symptom => {

            html += `
                <div class="small text-muted">

                    • ${symptom.symptom_en} is commonly associated

                </div>
            `;

        });

    return html;
}

function showLoading()
{
    document
        .getElementById("loadingState")
        .classList.remove("d-none");

    document
        .getElementById("resultsSummary")
        .classList.add("d-none");
}

function hideLoading()
{
    document
        .getElementById("loadingState")
        .classList.add("d-none");
}

function showEmptyState(title, message)
{
    document
        .getElementById("loadingState")
        .classList.add("d-none");

    document
        .getElementById("resultsSummary")
        .classList.add("d-none");

    document
        .getElementById("matchResults")
        .innerHTML = "";

    document
        .getElementById("emptyTitle")
        .textContent = title;

    document
        .getElementById("emptyMessage")
        .textContent = message;

    document
        .getElementById("emptyState")
        .classList.remove("d-none");
}
/******************************************************************
 * End of File
 ******************************************************************/