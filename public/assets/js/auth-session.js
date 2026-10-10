// A restored page or another tab can hold a token from an earlier session.
(() => {
  const form = document.getElementById('formLogin');
  if (!form) return;
  let pending = false;
  const button = document.querySelector('button[type="submit"][form="formLogin"]');
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (pending) return;
    pending = true;
    if (button) { button.disabled = true; button.classList.add('loading'); }
    try {
      const response = await fetch((window.APP_BASE_PATH || '') + '/login/session', {
        credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' },
      });
      const session = await response.json();
      if (!response.ok || !session.success || !/^[a-f0-9]{64}$/.test(session.csrf_token || '')) {
        throw new Error('Login session unavailable');
      }
      form.querySelector('[name="_csrf"]').value = session.csrf_token;
      HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
      const message = form.querySelector('.error.message');
      if (message) message.textContent = 'Sesi login belum dapat diperbarui. Silakan coba lagi.';
      form.classList.add('error');
      pending = false;
      if (button) { button.disabled = false; button.classList.remove('loading'); }
    }
  });
})();
