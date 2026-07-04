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

    results.forEach((item, index) => {
        html += renderDiseaseCard(item, index + 1);
    });

    return html;
}

function renderDiseaseCard(item, rank)
{
    const badge = getMatchBadge(item.matchLevel);
    const confidence = getConfidence(item.rankingScore);
    const severity = getSeverity(item.disease.severity_level);

    const matched = item.matchedSymptoms.map(symptom => `
        <div class="small mb-1 text-success">
            ✓ ${symptom.symptom_en}
        </div>
        `).join("");

    let missing = `
        <div class="small text-success">
            ✓ All major symptoms matched
        </div>
        `;

        if(item.missingSymptoms.length){

            missing = item.missingSymptoms
                .slice(0,3)
                .map(symptom => `
                    <div class="small text-muted">
                        ○ ${symptom.symptom_en}
                    </div>
                `)
                .join("");
        }
        let rankText = '';
        if(rank == 1){
            rankText = `#${rank} Best Match`;
        }else{
             rankText = `#${rank}`;
        }

    return `
<div class="card shadow-sm mb-3 disease-card">

    <div class="card-body">

        <div class="d-flex justify-content-between align-items-start">

            <div>
                <span class="badge bg-dark">
                    ${rankText}
                </span>
                <h4 class="mb-2 fw-bold">
                    ${item.disease.disease_en}
                </h4>

                <div class="d-flex flex-wrap gap-2">

                    <span class="badge ${badge.className}">
                        ${badge.text}
                    </span>

                    <span class="badge bg-primary">
                        ${Math.round(item.rankingScore)}% Overall
                    </span>

                    <span class="badge bg-info text-dark">
                        ${item.coverage}% Coverage
                    </span>

                    <span class="badge bg-${confidence.color}">
                        ${confidence.label}
                    </span>
                    <span class="badge bg-${severity.color} text-dark">
                        <i class="${severity.icon} text-light"></i>
                        ${severity.label}
                    </span>
                </div>

            </div>

        </div>

        <div class="row mt-3">

            <div class="col-lg-6">

                <h6 class="fw-semibold mb-2 text-success">
                    ✓ Matched Symptoms
                </h6>

                <div class="mt-2">

                    ${matched}

                </div>

            </div>

            <div class="col-lg-6">

                ${renderProgress(
                    "Your Symptoms Match",
                    item.userMatchScore,
                    "success"
                )}

            </div>

        </div>

        <div class="row mt-3">

            <div class="col-lg-6">

                <h6 class="fw-semibold mb-2 text-secondary">
                    ○ Common Missing Symptoms
                </h6>

                <div class="mt-2">

                    <span class="badge bg-light text-dark border">

                        ${missing}

                    </span>

                </div>

            </div>

            <div class="col-lg-6">

                ${renderProgress(
                    "Disease Symptom Coverage",
                    item.coverage,
                    "primary"
                )}

            </div>

        </div>

        ${renderConfidence(item)}

        <div class="row mt-3">

            <div class="col-12">

                ${renderSeverity(item)}

            </div>

        </div>

        ${renderSection(
            "Key Facts",
            renderQuickFacts(item)
        )}


        ${renderSection(
            "Overview",
            renderOverview(item)
        )}

        

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

            <div class="border-top mt-4 pt-3">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                    <div class="d-flex gap-2">

                        <button
                            class="btn btn-outline-secondary btn-sm" aria-label="Save disease"
                            disabled>

                            ♡ Save

                        </button>

                        <button
                            class="btn btn-outline-primary btn-sm compare-btn"
                            data-id="${item.disease.id}">

                            ⇄ Compare

                        </button>

                    </div>

                    <a
                        href="/disease/${item.disease.slug}"
                        class="btn btn-primary" aria-label="View disease details">

                        View Details

                    </a>

                </div>

            </div>

            

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
                    class="progress-bar bg-${color} rounded-pill" role="progressbar"

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
 * UI Render Helpers
 ******************************************************************/
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

function renderOverview(item)
{
    const overview =
        item.disease.overview_en ??
        item.disease.overview ??
        "";

    const preview =
        previewOverview(overview);

    return `

        <div class="overview-box">

            <p class="mb-2">

                ${preview}

            </p>

            ${
                overview &&
                overview.length > preview.length
                ?

                `
                    <a
                        href="/disease/${item.disease.slug}"
                        class="small fw-semibold text-primary text-decoration-none">

                        Read More →

                    </a>
                `

                :

                ""

            }

        </div>

    `;
}

document.addEventListener("click", function(event){

    const button = event.target.closest(".compare-btn");

    if(!button){
        return;
    }

    alert(
        "Disease comparison will be available in the next release."
    );

});

function getScoreClass(score)
{
    if (score >= 90) return "text-success";
    if (score >= 75) return "text-primary";
    if (score >= 50) return "text-warning";
    return "text-secondary";
}

function getConfidence(score)
{
    if (score >= 90)
    {
        return {
            level: "very-high",
            label: "Very High Match",
            color: "success",
            stars: 5,
            icon: "bi bi-patch-check-fill"
        };
    }

    if (score >= 75)
    {
        return {
            level: "high",
            label: "High Match",
            color: "primary",
            stars: 4,
            icon: "bi bi-check-circle-fill"
        };
    }

    if (score >= 60)
    {
        return {
            level: "moderate",
            label: "Moderate Match",
            color: "info",
            stars: 3,
            icon: "bi bi-info-circle-fill"
        };
    }

    if (score >= 40)
    {
        return {
            level: "low",
            label: "Low Match",
            color: "warning",
            stars: 2,
            icon: "bi bi-exclamation-circle-fill"
        };
    }

    return {
        level: "weak",
        label: "Weak Match",
        color: "secondary",
        stars: 1,
        icon: "bi bi-dash-circle-fill"
    };
}

function renderStars(count, color = "warning")
{
    let html = "";

    for (let i = 1; i <= 5; i++)
    {
        html += i <= count
            ? `<i class="bi bi-star-fill text-${color}"></i>`
            : `<i class="bi bi-star text-muted"></i>`;
    }

    return html;
}

function renderConfidence(item)
{
    const score = item.rankingScore;

    const confidence = getConfidence(score);

    return `

<div class="row mt-4">

    <div class="col-lg-6 mb-3">

        <div class="confidence-box h-100">

            <div class="d-flex align-items-start">

                <i class="${confidence.icon}
                          text-${confidence.color}
                          fs-2
                          me-3"></i>

                <div class="flex-grow-1">

                    <h6 class="mb-1">

                        Clinical Confidence

                    </h6>

                    <div class="fw-bold text-${confidence.color}">

                        ${confidence.label}

                    </div>

                    <div class="mt-2">

                        ${renderStars(
                            confidence.stars,
                            confidence.color
                        )}

                    </div>

                </div>

                <div>

                    <span class="badge bg-${confidence.color}">

                        ${Math.round(score)}%

                    </span>

                </div>

            </div>

        </div>

    </div>

    <div class="col-lg-6 mb-3">

        <div class="confidence-box h-100">

            <h6 class="mb-3">

                Confidence Reason

            </h6>

            ${renderConfidenceReason(item)}

        </div>

    </div>

</div>

`;
}

function renderConfidenceReason(item)
{
    const matched = item.matchedSymptoms.length;

    const missing = item.missingSymptoms.length;

    let html = "";

    html += `
        <div class="small">
            <i class="bi bi-check-circle-fill text-success me-1"></i>
            ${matched} selected symptom${matched !== 1 ? "s" : ""} matched.
        </div>
    `;

    html += `
        <div class="small mt-1">
            <i class="bi bi-graph-up-arrow text-primary me-1"></i>
            Disease coverage is ${item.coverage}%.
        </div>
    `;

    if (missing === 0)
    {
        html += `
            <div class="small mt-1 text-success">
                <i class="bi bi-stars me-1"></i>
                All major symptoms are present.
            </div>
        `;
    }
    else
    {
        html += `
            <div class="small mt-1 text-muted">
                <i class="bi bi-info-circle me-1"></i>
                ${missing} common symptom${missing !== 1 ? "s are" : " is"} not present.
            </div>
        `;
    }

    return html;
}

function renderSection(title, content, icon = "")
{
    return `
        <div class="mt-4">

            <h6 class="fw-semibold mb-3">

                ${icon ? `<i class="bi ${icon} me-2"></i>` : ""}

                ${title}

            </h6>

            ${content}

        </div>
    `;
}

function getSeverity(level)
{
    switch(level)
    {
        case "emergency":
            return {
                label: "Emergency",
                color: "danger",
                icon: "bi bi-exclamation-triangle-fill"
            };

        case "urgent":
            return {
                label: "Urgent",
                color: "warning",
                icon: "bi bi-alarm-fill"
            };

        case "moderate":
            return {
                label: "Moderate",
                color: "info",
                icon: "bi bi-info-circle-fill"
            };

        default:
            return {
                label: "Mild",
                color: "success",
                icon: "bi bi-heart-fill"
            };
    }
}

function renderSeverity(item)
{
    const severity =
        getSeverity(item.disease.severity_level);
    const note =
    item.disease.urgency_note
    ??
    "Consult a qualified healthcare professional if symptoms persist or worsen.";

    return `

        <div
            class="severity-box border-start border-4 border-${severity.color}">

            <div class="d-flex align-items-start">

                <i class="${severity.icon}
                          fs-2
                          text-${severity.color}
                          me-3"></i>

                <div class="flex-grow-1">

                    <h6 class="mb-1">

                        Severity

                    </h6>

                    <div
                        class="fw-bold text-${severity.color}">

                        ${severity.label}

                    </div>

                    <p class="small text-muted mt-2 mb-0">

                        ${note}

                    </p>

                </div>

            </div>

        </div>

    `;
}

function renderQuickFact(fact)
{
    return `

        <div class="col-md-6 mb-3">

            <div class="quick-fact-card h-100">

                <div class="d-flex">

                    <i class="bi ${fact.icon} fs-4 me-3"></i>

                    <div>

                        <div class="fw-semibold">

                            ${fact.title}

                        </div>

                        <div class="small text-muted">

                            ${previewText(fact.value)}

                        </div>

                    </div>

                </div>

            </div>

        </div>

    `;
}

function renderQuickFacts(item)
{

    let html = `
        <div class="row mt-3">
    `;

    const facts = getQuickFacts(item);

        if (facts.length === 0)
        {
            return "";
        }

        facts.forEach(fact => {

            html += renderQuickFact(fact);

        });

    html += "</div>";

    return html;
}

function previewText(text, limit = 70)
{
     if(!text)
    {
        return "Information coming soon";
    }

    text = text.replace(/\s+/g, " ").trim();

    if (text.length <= limit)
    {
        return text;
    }

    return text.substring(0, limit).trim() + "...";
}

function getQuickFacts(item)
{
    const disease = item.disease;

    return [

        {
            title: "Cause",
            icon: "bi-bug",
            value: disease.causes_en
        },

        {
            title: "Risk Factors",
            icon: "bi-exclamation-triangle",
            value: disease.risk_factors_en
        },

        {
            title: "Diagnosis",
            icon: "bi-heart-pulse",
            value: disease.diagnosis_en
        },

        {
            title: "Prevention",
            icon: "bi-shield-check",
            value: disease.prevention_en
        }

    ].filter(fact => fact.value);
}

function previewOverview(text, limit = 220)
{
    if (!text)
    {
        return "Detailed overview will be available soon.";
    }

    text = text.replace(/\s+/g, " ").trim();

    if (text.length <= limit)
    {
        return text;
    }

    let sentenceEnd = text.lastIndexOf(".", limit);

    if (sentenceEnd > 100)
    {
        return text.substring(0, sentenceEnd + 1);
    }

    let space = text.lastIndexOf(" ", limit);

    if (space > 100)
    {
        return text.substring(0, space) + "...";
    }

    return text.substring(0, limit) + "...";
}
/******************************************************************
 * End of File
 ******************************************************************/