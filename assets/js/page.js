(() => {
  'use strict';
  const config = window.OBRA_PUBLIC;
  if (!config) return;
  window.obraLogoContrast = config.logo;
  document.querySelectorAll('[data-legal-open]').forEach(button => {
    button.addEventListener('click', () => document.getElementById(button.dataset.legalOpen)?.showModal());
  });
  if (config.openingAt) {
    setInterval(() => { if (Date.now() >= config.openingAt) location.reload(); }, 1000);
  }
  const counter = document.querySelector('.obra-countdown');
  const tick = () => {
    if (!counter) return;
    const seconds = Math.max(0, Math.floor((Number(counter.dataset.time) - Date.now()) / 1000));
    [Math.floor(seconds / 86400), Math.floor(seconds % 86400 / 3600), Math.floor(seconds % 3600 / 60), seconds % 60]
      .forEach((value, i) => { counter.querySelectorAll('strong')[i].textContent = String(value).padStart(2, '0'); });
  };
  tick();
  if (counter) setInterval(tick, 1000);
  const form = document.querySelector('.obra-form');
  form?.addEventListener('submit', async event => {
    event.preventDefault();
    if (config.editor) return;
    const button = form.querySelector('button');
    const status = form.querySelector('.status');
    button.disabled = true;
    const body = new FormData(form);
    body.set('action', 'obra_subscribe');
    body.set('nonce', config.nonce);
    body.set('language', config.language);
    try {
      const response = await fetch(config.ajax, { method: 'POST', body });
      const result = await response.json();
      if (!result.success) throw new Error('Submission failed');
      status.textContent = config.success;
      form.reset();
    } catch (error) {
      status.textContent = config.error;
    } finally {
      button.disabled = false;
    }
  });
  if (config.editor) {
    for (const [selector, key] of [['.obra-title', 'title'], ['.obra-copy', 'text'], ['.obra-eyebrow', 'eyebrow']]) {
      const element = document.querySelector(selector);
      element.contentEditable = 'true';
      element.dataset.obraEdit = key;
    }
  }
})();
