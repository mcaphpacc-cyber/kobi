// Database/network-free checks against the actual checker script and result lifecycle.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/symptom-checker.js'), 'utf8');
const elements = new Map();
function element(id) {
    if (!elements.has(id)) {
        const classes = new Set(['d-none']);
        elements.set(id, {innerHTML:'',textContent:'',value:'',disabled:false,
            classList:{add:k=>classes.add(k),remove:k=>classes.delete(k),contains:k=>classes.has(k),toggle:(k,on)=>{if(on)classes.add(k);else classes.delete(k);}},
            addEventListener(){},querySelectorAll(){return [];},focus(){}});
    }
    return elements.get(id);
}
let calls = 0;
const context = vm.createContext({document:{getElementById:element,addEventListener(){},querySelectorAll(){return [];}},
    window:{KOBI:{baseUrl:'/kobi/public/'},addEventListener(){}},
    sessionStorage:{setItem(){},getItem(){return null;}},localStorage:{getItem(){return null;},setItem(){}},
    console,setTimeout,clearTimeout,fetch:()=>{calls++;return Promise.resolve({ok:true,json:()=>Promise.resolve({summary:{selectedSymptoms:[1],totalMatches:0,displayedMatches:0},results:[],save_context:{key:'a'.repeat(64)}})});}});
vm.runInContext(source,context);
let checks = 0;
function check(pass,label){assert.ok(pass,label);checks++;}
vm.runInContext("state.selectedSymptoms=[{id:1,name:'Fever'}];state.results={summary:{selectedSymptoms:[1],totalMatches:0,displayedMatches:0},results:[],save_context:{key:'a'.repeat(64)}};renderMatches();",context);
check(!element('saveCheckButton').disabled,'completed trusted zero-result state can save');
check(element('savedCheckKey').value==='a'.repeat(64),'opaque key populated');
check(!element('savedCheckControls').classList.contains('d-none'),'save visible');
check(element('resultsSummary').innerHTML.includes('It does not provide a medical diagnosis.'),'disclaimer retained');
vm.runInContext('renderSelected();',context);
check(element('saveCheckButton').disabled&&element('savedCheckKey').value==='','selection lifecycle invalidates save');
vm.runInContext("state.results.save_context=null;renderMatches();",context);
check(element('saveCheckButton').disabled,'restored/anonymous response without capture cannot save');
vm.runInContext("state.results.save_context={key:'a'.repeat(64)};state.selectedSymptoms=[{id:2,name:'Cough'}];renderMatches();",context);
check(element('saveCheckButton').disabled&&element('savedCheckKey').value==='','response/selection mismatch denied');
vm.runInContext('clearSymptoms();',context);
check(element('savedCheckControls').classList.contains('d-none'),'Clear hides save');
for(const [score,label] of [[99,'Very High Match'],[90,'Very High Match'],[89.99,'High Match'],[75,'High Match'],[74.99,'Moderate Match'],[60,'Moderate Match'],[59.99,'Low Match'],[40,'Low Match'],[39.99,'Weak Match'],[0,'Weak Match']]) {
    check(vm.runInContext(`getConfidence(${score}).label`,context)===label,'existing match label boundary');
}
const item = {matchedSymptoms:[{id:1,symptom_en:'Fever'}],missingSymptoms:[{id:3,symptom_en:'Fatigue'}],coverage:50,rankingScore:50};
context.fixture=item;
const reason=vm.runInContext('renderConfidenceReason(fixture)',context);
const explanation=vm.runInContext('renderExplanation(fixture)',context);
for(const text of ['1 selected symptom matched.','Disease coverage is 50%.','1 common symptom is not present.'])check(reason.includes(text),'snapshot reason parity');
for(const text of ['✓ Fever matched','• Fatigue is commonly associated'])check(explanation.includes(text),'snapshot explanation parity');
check(vm.runInContext('renderConfidence(fixture)',context).includes('50% match'),'existing percentage formatter');
vm.runInContext("state.selectedSymptoms=[{id:1,name:'Fever'}];",context);
vm.runInContext('loadMatches()',context).then(()=>{
    check(calls===1,'one match request, no save-side recomputation');
    check(!element('saveCheckButton').disabled,'successful response enables Save');
    console.log(`PASS: ${checks} checker lifecycle/presentation checks (no network/database).`);
}).catch(error=>{console.error(error);process.exitCode=1;});
