'use strict';
// DOM contract tests only; no browser, web requests or fixture files.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
let checks = 0;
function check(ok) { assert.ok(ok); ++checks; }
function setup(value) {
    class Element {
        constructor() { this.value = ''; this.listeners = {}; this.children = []; this.textContent = ''; }
        addEventListener(name, fn) { this.listeners[name] = fn; }
        replaceChildren() { this.children = []; }
        appendChild(child) { this.children.push(child); }
        setAttribute() {}
        focus() {}
        requestSubmit() { this.listeners.submit({preventDefault() {}}); }
    }
    const ids = Object.fromEntries(['substanceSearchForm','substanceQuery','substanceResults','substanceSearchStatus'].map(id => [id,new Element()]));
    ids.substanceQuery.value = value;
    const requests = [];
    vm.runInNewContext(fs.readFileSync('public/assets/js/substance-search.js','utf8'), {
        document: {getElementById: id => ids[id], createElement: () => new Element(), addEventListener: (name, fn) => fn()},
        window: {KOBI: {apiBase:'http://localhost/kobi/public/api'}}, URL, AbortController,
        fetch: (url,options) => new Promise(resolve => requests.push({url,options,resolve})),
    });
    return {ids,requests};
}
const tick = () => new Promise(resolve => setImmediate(resolve));
(async () => {
    const empty = setup(''); check(empty.requests.length === 0);
    const test = setup('Paracetamol'); check(test.requests.length === 1);
    check(test.requests[0].url.pathname === '/kobi/public/api/substances/search');
    check(test.requests[0].url.searchParams.get('q') === 'Paracetamol');
    test.requests[0].resolve({ok:true,json:async () => [{substance_id:1552,canonical_name:'Paracetamol'}]}); await tick();
    check(test.ids.substanceResults.children[0].textContent === 'Paracetamol');
    check(test.ids.substanceSearchStatus.textContent === '1 matching registry name shown.');
    test.ids.substanceQuery.value='Para';test.ids.substanceQuery.listeners.input();
    check(test.ids.substanceResults.children.length === 0);
    test.ids.substanceSearchForm.requestSubmit();
    test.ids.substanceQuery.value='Allo';test.ids.substanceQuery.listeners.input();
    check(test.requests[1].options.signal.aborted);
    test.ids.substanceSearchForm.requestSubmit();
    test.requests[2].resolve({ok:true,json:async () => [{canonical_name:'Allopurinol'}]});await tick();
    test.requests[1].resolve({ok:true,json:async () => [{canonical_name:'Old result'}]});await tick();
    check(test.ids.substanceResults.children[0].textContent === 'Allopurinol');
    test.ids.substanceSearchForm.requestSubmit();test.requests[3].resolve({ok:false});await tick();
    check(test.ids.substanceResults.children.length === 0 && test.ids.substanceSearchStatus.textContent.includes('unavailable'));
    test.ids.substanceSearchForm.requestSubmit();test.requests[4].resolve({ok:true,json:async () => [{canonical_name:'<script>text only</script>'}]});await tick();
    check(test.ids.substanceResults.children[0].textContent === '<script>text only</script>');
    console.log(`Substance search: ${checks} DOM contract checks passed.`);
})().catch(error => { console.error(error); process.exitCode=1; });
