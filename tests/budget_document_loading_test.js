const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const requests = [], writes = [];
const context = {
    BaseCrudModule: class {}, console: {error() {}},
    window: {location: {pathname: '/renja'}, Ajax: {request(options) {
        const xhr = {abort() {this.aborted = true;}};
        requests.push({options, xhr}); return xhr;
    }}},
    $: selector => ({html: html => writes.push({selector, html})}),
};
vm.createContext(context);
vm.runInContext(fs.readFileSync(__dirname + '/../public/assets/js/modules/anggaran-document.js', 'utf8') + '\nglobalThis.Module = AnggaranDocumentModule;', context);
const moduleInstance = Object.create(context.Module.prototype);
Object.assign(moduleInstance, {root: {isConnected: true}, route: '/renja', table: 'renja'});
context.window.activeBudgetDocument = moduleInstance;
let renders = 0;
moduleInstance.loadDocument('/first', () => renders++);
moduleInstance.loadDocument('/second', () => renders++);
assert(requests[0].xhr.aborted, 'Superseded requests are aborted');
requests[0].options.success({success: true, data: []});
assert.equal(renders, 0, 'Late responses must not repaint a newer request');
requests[1].options.success({success: true, data: []});
assert.equal(renders, 1);
assert(requests[1].options.silent, 'Loading data should not announce success before rendering');
moduleInstance.loadDocument('/error', () => renders++);
requests.at(-1).options.error();
assert(writes.at(-1).html.includes('Muat ulang daftar'), 'Failure replaces the spinner with a retry action');
moduleInstance.loadDocument('/bad-payload', () => renders++);
requests.at(-1).options.success({success: true, data: {}});
assert.equal(renders, 1, 'Invalid responses must not reach rendering');
moduleInstance.loadDocument('/render-error', () => {throw new Error('broken render');});
assert.doesNotThrow(() => requests.at(-1).options.success({success: true, data: []}));
assert(writes.at(-1).html.includes('Muat ulang daftar'));
moduleInstance.loadDocument('/old-page', () => renders++);
context.window.location.pathname = '/dpa';
requests.at(-1).options.success({success: true, data: []});
assert.equal(renders, 1, 'Responses after navigation must be ignored');
context.window.location.pathname = '/renja';
moduleInstance.root.isConnected = false;
requests.at(-1).options.error();
assert.equal(writes.length, 3, 'Disconnected pages must not write failure messages to the active page');
console.log('PASS: Superseded requests, route changes, malformed data, render failures and retry UI');

(async () => {
    const pending = [], rendered = [], initialized = [];
    const routerContext = {
        window: {activeBudgetDocument: {request: {abort() {}}}, app: {loadModule: url => initialized.push(url)}},
        fetch: (url, options) => new Promise(resolve => pending.push({url, options, resolve})),
        AbortController, console,
        $: () => ({html: html => rendered.push(html)}),
    };
    vm.createContext(routerContext);
    vm.runInContext(fs.readFileSync(__dirname + '/../public/assets/js/core/spa-router.js', 'utf8') + '\nglobalThis.Router = SpaRouter;', routerContext);
    const router = Object.create(routerContext.Router.prototype);
    router.loadClientModule('/renja');
    router.loadClientModule('/dpa');
    assert(pending[0].options.signal.aborted);
    // Deliberately resolve out of order, even though the old fetch was cancelled.
    pending[1].resolve({text: async () => 'DPA'});
    await new Promise(resolve => setImmediate(resolve));
    pending[0].resolve({text: async () => 'RENJA'});
    await new Promise(resolve => setImmediate(resolve));
    assert.deepEqual(rendered, ['DPA']);
    assert.deepEqual(initialized, ['/dpa']);
    console.log('PASS: Fast navigation cannot replace the latest page with a late response');
})().catch(error => {console.error(error); process.exitCode = 1;});

{
    const scripts = [], initialized = [];
    const context = {
        window: {location: {pathname: '/renja'}, appUrl: path => path, ASSET_VERSION: 'test'},
        AjaxEngine: class {}, AppConfig: {apiUrl: '/'}, console,
        document: {createElement: () => ({}), body: {appendChild: script => scripts.push(script)}},
    };
    vm.createContext(context);
    vm.runInContext(fs.readFileSync(__dirname + '/../public/assets/js/app.js', 'utf8') + '\nglobalThis.App = App;', context);
    const app = Object.create(context.App.prototype);
    app.syncStateFromUrl = () => {};
    app.initModuleInstance = name => initialized.push(name);
    app.loadModule('/renja');
    context.window.location.pathname = '/dpa';
    app.loadModule('/dpa');
    assert.equal(scripts.length, 1, 'Budget routes share one pending script, avoiding duplicate class declarations');
    context.window.location.pathname = '/dashboard';
    scripts[0].onload();
    assert.equal(initialized.length, 0, 'A late script must not initialize over another module');
    context.window.location.pathname = '/dppa';
    app.loadModule('/dppa');
    assert.deepEqual(initialized, ['anggaran-document']);
    console.log('PASS: Shared budget scripts load once and initialize only on a matching route');
}
