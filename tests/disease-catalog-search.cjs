'use strict';
// Exercise actual catalog functions with synthetic DOM rows; no requests or database.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync('public/assets/js/disease-catalog.js', 'utf8');
function definition(name) {
    const start = source.indexOf('    function ' + name + '(');
    assert(start >= 0, 'Missing production function ' + name);
    const end = source.indexOf('\n    function ', start + 1);
    return source.slice(start, end < 0 ? source.lastIndexOf('});') : end);
}
let checks = 0;
function check(ok, message) { assert.ok(ok, message); ++checks; }
const rows = [
    {dataset: {name: 'Acne', bodySystem: 'Skin Hair & Nails', symptoms: 'Skin irritation', gender: 'both', symptomCount: '3'}},
    {dataset: {name: 'Skin Disorder', bodySystem: 'Skin Hair & Nails', symptoms: 'Rash', gender: 'female', symptomCount: '5'}},
    {dataset: {name: 'Heart Failure', bodySystem: 'Heart & Blood Vessels', symptoms: 'Chest pain', gender: 'male', symptomCount: '2'}},
];
const state = {keyword: '', gender: 'all', sort: 'az', currentPage: 1, compare: []};
const links = ['?body=skin', '?featured=1', '?recent=1', ''].map(q => ({href: 'https://example.test/nested/diseases' + q}));
const location = {pathname: '/nested/diseases', search: '?body=skin', href: 'https://example.test/nested/diseases?body=skin'};
const dom = {counter: {textContent: ''}, compareNow: {addEventListener(name, fn) { this[name] = fn; }}};
const history = {pushState(_, __, path) { location.search = new URL(path, location.href).search; this.path = path; }, replaceState(_, __, path) { this.pushState(_, __, path); }};
const context = vm.createContext({rows, state, dom, history, URL, URLSearchParams, console: {log() {}}, window: {location, KOBI: {baseUrl: 'https://example.test/nested'}}, document: {querySelectorAll(selector) { assert.equal(selector, '.body-filter-bar a[href]'); return links; }}});
vm.runInContext(['containsWord', 'calculateScore', 'filterRows', 'sortRows', 'updateCounter', 'updateUrl', 'bindCompareNow'].map(definition).join('\n'), context);
const run = code => vm.runInContext(code, context);
for (const term of ['a', 'ac', 'acn', 'acne', 'c', 'cn', 'cne', 'ne', 'ACN', 'Acn', ' acn ']) {
    state.keyword = term;
    check(run('filterRows()').includes(rows[0]), 'Acne substring ' + term);
}
for (const term of ['skin', 'ski', 'hair', 'hai', 'nail', 'nai', 'nails', 'ail', 'HAI', 'Hair & Nai']) {
    state.keyword = term;
    check(run('filterRows()').includes(rows[0]), 'Body-system substring ' + term);
}
state.keyword = 'not present'; check(run('filterRows().length') === 0, 'Nonmatch excluded');
state.keyword = 'CHEST PA'; check(run('filterRows()')[0] === rows[2], 'Existing symptoms remain searchable');
state.keyword = ''; check(run('filterRows().length') === 3, 'Clear restores loaded subset');
state.keyword = 'ski'; state.gender = 'female'; check(run('filterRows()').length === 1 && run('filterRows()')[0] === rows[1], 'Gender intersects partial search');
state.gender = 'all'; state.keyword = '';
for (const [sort, names] of [['az', ['Acne', 'Heart Failure', 'Skin Disorder']], ['za', ['Skin Disorder', 'Heart Failure', 'Acne']], ['mostSymptoms', ['Skin Disorder', 'Acne', 'Heart Failure']], ['leastSymptoms', ['Heart Failure', 'Acne', 'Skin Disorder']]]) {
    state.sort = sort;
    check(JSON.stringify(run('sortRows(filterRows()).map(r => r.dataset.name)')) === JSON.stringify(names), 'Existing sort ' + sort);
}
state.keyword = 'ski'; const relevance = run('filterRows()');
state.sort = 'za'; check(run('sortRows(filterRows())')[0] === relevance[0], 'Existing relevance-first behavior during search retained');
run('updateCounter(filterRows().length)'); check(dom.counter.textContent === 2, 'Counter reflects filtered set');
state.gender = 'female'; state.currentPage = 4; run('updateUrl()');
let url = new URL(history.path, location.href);
check(url.searchParams.get('body') === 'skin' && url.searchParams.get('search') === 'ski' && url.searchParams.get('gender') === 'female', 'Local controls preserve server body filter');
for (const link of links) {
    const p = new URL(link.href).searchParams;
    check(p.get('search') === 'ski' && p.get('gender') === 'female' && p.get('sort') === 'za' && !p.has('page'), 'Subset links preserve search/gender/sort and reset page');
}
check(new URL(links[1].href).searchParams.get('featured') === '1' && new URL(links[2].href).searchParams.get('recent') === '1', 'Featured/Recent targets unchanged');
state.keyword = ''; state.gender = 'all'; state.sort = 'az'; state.currentPage = 1; run('updateUrl()');
url = new URL(history.path, location.href);
check(url.searchParams.get('body') === 'skin' && !url.searchParams.has('search'), 'Clearing search preserves selected subset');
check(links.every(link => !new URL(link.href).searchParams.has('search')), 'Clearing search updates filter links');
for (const subset of ['featured', 'recent']) {
    location.search = '?' + subset + '=1'; state.keyword = 'acn'; run('updateUrl()');
    check(new URL(history.path, location.href).searchParams.get(subset) === '1', 'Keep server subset ' + subset);
}
state.compare = [{slug: 'heart-failure'}, {slug: 'heart-attack'}]; run('bindCompareNow()'); dom.compareNow.click();
check(location.href === 'https://example.test/nested/compare/result?d1=heart-failure&d2=heart-attack', 'Previous Compare URL fix unchanged');
console.log('Disease Catalog partial search: ' + checks + ' checks passed (no network/database).');
