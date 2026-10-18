// Database-free checks and optional isolated browser fixture for the production script.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const script = fs.readFileSync(path.join(root, 'public/assets/js/health-medicine.js'), 'utf8');
const substances = [{id: 101, canonical_name: 'Paracetamol'}, {id: 102, canonical_name: 'Paracetamol sodium'}];

if (process.argv.includes('--fixture')) {
    // Only synthetic GET responses; no application bootstrap, sessions, DB or persistence.
    const html = `<!doctype html><html lang="en"><head><meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="../public/assets/css/bootstrap.min.css"><title>Medication autocomplete test fixture</title>
        <script src="health-medicine-autocomplete-fixture.php?asset=config" defer></script><script src="../public/assets/js/health-medicine.js" defer></script></head>
        <body><main class="container py-4"><h1 class="h3">Medication autocomplete test fixture</h1>
        <p>Synthetic suggestions only. No account, database or saving.</p>
        <div><div class="mb-3"><label for="generic_name" class="form-label">Salt / Generic Name</label>
        <input id="generic_name" name="generic_name" class="form-control" maxlength="500" placeholder="e.g. Silodosin">
        <div class="form-text">Enter the salt / generic name if known or verified.</div></div>
        <label for="status">Status</label><select id="status" class="form-select"><option value="current">Current</option><option value="stopped">Stopped</option></select>
        <div id="stoppedOnWrapper"><label for="stopped_on">Stopped on</label><input id="stopped_on" type="date"></div>
        </div></main></body></html>`;
    const fixture = `<?php
// Temporary synthetic fixture: no application bootstrap, database or session.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }
header('Cache-Control: no-store');
if (isset($_GET['asset'])) {
    header('Content-Type: text/javascript');
    echo 'window.KOBI = {apiBase: "/kobi/tests/health-medicine-autocomplete-fixture.php"};'; exit;
}
if (isset($_SERVER['PATH_INFO'])) {
    header('Content-Type: application/json');
    $items = json_decode('${JSON.stringify(substances)}', true);
    echo json_encode(array_values(array_filter($items, fn($item) =>
        str_contains(strtolower($item['canonical_name']), strtolower($_GET['q'] ?? ''))))); exit;
}
?>${html}`;
    fs.writeFileSync(path.join(__dirname, 'health-medicine-autocomplete-fixture.php'), fixture, {flag: 'wx'});
    console.log('Temporary fixture created. Remove tests/health-medicine-autocomplete-fixture.php after browser checks.');
} else {
    class Element {
        constructor() { this.children = []; this.handlers = {}; this.style = {}; this.value = ''; this.classes = new Set(); }
        get classList() { return {add: name => this.classes.add(name), remove: name => this.classes.delete(name), contains: name => this.classes.has(name)}; }
        set innerHTML(value) { assert.equal(value, ''); this.children = []; }
        appendChild(child) { this.children.push(child); child.parentNode = this; }
        insertBefore(child) { this.appendChild(child); }
        addEventListener(type, fn) { (this.handlers[type] ??= []).push(fn); }
        fire(type, values = {}) { for (const fn of this.handlers[type] || []) fn({preventDefault() {}, ...values}); }
        contains(target) { return target === this || this.children.some(child => child.contains(target)); }
        focus() { focused = this; focusCalls++; }
    }
    let focused, focusCalls = 0, timer, requested, response = substances, checks = 0;
    const check = (condition, message) => { assert.ok(condition, message); checks++; };
    const input = new Element(), parent = new Element(), status = new Element(), stopped = new Element(), date = new Element();
    parent.appendChild(input); status.value = 'current'; date.value = '2020-01-01';
    const document = new Element();
    document.getElementById = id => ({generic_name: input, status, stoppedOnWrapper: stopped, stopped_on: date})[id];
    document.createElement = () => new Element();
    vm.runInNewContext(script, {document, window: {KOBI: {apiBase: '/api'}}, AbortController,
        clearTimeout: () => {timer = null;}, setTimeout: (fn, ms) => {assert.equal(ms, 300); timer = fn;},
        fetch: async (url, options) => { requested = [url, options]; return {ok: true, json: async () => response}; }});
    document.fire('DOMContentLoaded');
    const dropdown = parent.children[1].children[1];
    const search = async value => {input.value = value; input.fire('input'); await timer();};
    (async () => {
        check(stopped.classList.contains('d-none') && date.value === '' && !date.required, 'Current medication behavior unchanged');
        status.value = 'stopped'; status.fire('change');
        check(!stopped.classList.contains('d-none') && date.required, 'Stopped date behavior unchanged');
        await search('para');
        check(requested[0] === '/api/substances/search?q=para' && requested[1].method === 'GET', 'Search contract unchanged');
        check(dropdown.children.length === 2 && dropdown.children[0].textContent === 'Paracetamol', 'Ordering and display names unchanged');
        const button = dropdown.children[0];
        check(button.type === 'button' && !button.handlers.mousedown && button.handlers.click.length === 1, 'One native activation handler; no mousedown selection');
        button.fire('mousedown');
        check(input.value === 'para' && focusCalls === 0, 'Mousedown does not select twice');
        button.fire('click');
        check(input.value === 'Paracetamol' && focused === input && focusCalls === 1, 'Click selects once and returns focus');
        check(dropdown.children.length === 0 && dropdown.classList.contains('d-none'), 'Selection closes suggestions');
        await search('Ibuprofen, para'); dropdown.children[0].fire('click');
        check(input.value === 'Ibuprofen, Paracetamol', 'Existing comma-separated value semantics unchanged');
        await search('paracetamol, para'); dropdown.children[0].fire('click');
        check(input.value === 'paracetamol, para' && dropdown.children.length === 0, 'Existing duplicate handling unchanged');
        await search('para'); input.fire('keydown', {key: 'Escape'});
        check(dropdown.children.length === 0, 'Escape closes suggestions');
        response = []; await search('nothing');
        check(dropdown.children.length === 0 && dropdown.classList.contains('d-none'), 'Empty results hidden');
        response = substances; await search('para');
        check(dropdown.children.length === 2, 'Repeated search restores suggestions');
        document.fire('click', {target: new Element()});
        check(dropdown.children.length === 0, 'Outside click closes suggestions');
        await search('p'); check(dropdown.children.length === 0, 'Short search remains hidden');
        check(substances[0].id === 101 && substances[1].id === 102, 'Source IDs untouched; existing selection remains name-only');
        console.log(`${checks} database-free medication autocomplete checks passed. Native Enter/Space require browser fixture checks.`);
    })().catch(error => {console.error(error); process.exitCode = 1;});
}
