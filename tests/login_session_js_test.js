const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const source = fs.readFileSync(__dirname + '/../public/assets/js/auth-session.js', 'utf8');
(async () => {
  for (const fail of [false, true]) {
    let listener, submitted = 0, requests = 0;
    let token = { value: 'old-token' }, message = { textContent: '' };
    const classes = new Set();
    const classList = { add: x => classes.add(x), remove: x => classes.delete(x) };
    const form = { addEventListener: (name, fn) => listener = fn, classList,
      querySelector: selector => selector === '[name="_csrf"]' ? token : message };
    const button = { disabled: false, classList };
    const context = { document: { getElementById: () => form, querySelector: () => button },
      window: { APP_BASE_PATH: '/sesendoknew' },
      HTMLFormElement: { prototype: { submit() { assert.strictEqual(this, form); submitted++; } } },
      fetch: async (url, options) => {
        requests++;
        assert.strictEqual(url, '/sesendoknew/login/session');
        assert.strictEqual(options.credentials, 'same-origin');
        assert.strictEqual(options.cache, 'no-store');
        if (fail) throw new Error('offline');
        return { ok: true, json: async () => ({ success: true, csrf_token: 'a'.repeat(64) }) };
      },
    };
    vm.runInNewContext(source, context);
    let prevented = 0;
    const first = listener({ preventDefault() { prevented++; } });
    const duplicate = listener({ preventDefault() { prevented++; } });
    await Promise.all([first, duplicate]);
    assert.strictEqual(prevented, 2);
    assert.strictEqual(requests, 1);
    if (fail) {
      assert.strictEqual(submitted, 0);
      assert.strictEqual(button.disabled, false);
      assert(classes.has('error'));
      assert(message.textContent.includes('coba lagi'));
      console.log('PASS: refresh failure stops submission and allows retry');
    } else {
      assert.strictEqual(submitted, 1);
      assert.strictEqual(token.value, 'a'.repeat(64));
      console.log('PASS: stale token refreshed before one native login submission');
    }
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
