(() => {
  const config = window.ontarioSiteManager || {};
  const i18n = config.i18n || {};
  const form = document.getElementById('caseForm');
  const steps = [...document.querySelectorAll('.form-step')];
  const previous = document.getElementById('prevBtn');
  const next = document.getElementById('nextBtn');
  const label = document.getElementById('stepLabel');
  const progress = document.getElementById('progressBar');
  let step = 0;

  const t = (key, fallback) => (typeof i18n[key] === 'string' && i18n[key]) || fallback;

  function preserveAttribution(target) {
    if (!target) return;
    const fields = ['gclid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
    const params = new URLSearchParams(window.location.search);
    fields.forEach((key) => {
      const value = params.get(key);
      const storageKey = `ontarioAttribution:${key}`;
      let preserved = value || '';
      try {
        if (value) window.sessionStorage.setItem(storageKey, value);
        if (!preserved) preserved = window.sessionStorage.getItem(storageKey) || '';
      } catch (error) {}
      const field = target.querySelector(`[name="${key === 'gclid' ? 'zc_gad' : key}"]`);
      if (field && preserved) field.value = preserved;
    });
  }

  function initMenu() {
    const nav = document.getElementById('nav');
    const button = document.getElementById('menuBtn');
    if (!nav || !button) return;
    button.addEventListener('click', () => {
      const open = nav.classList.toggle('open');
      button.setAttribute('aria-expanded', String(open));
    });
    nav.querySelectorAll('.nav-links a').forEach((link) => link.addEventListener('click', () => {
      nav.classList.remove('open');
      button.setAttribute('aria-expanded', 'false');
    }));
  }

  function initLanguageSwitcher() {
    const switcher = document.querySelector('[data-language-switcher]');
    const button = switcher?.querySelector('.language-switcher__button');
    const menu = switcher?.querySelector('.language-switcher__menu');
    if (!switcher || !button || !menu) return;
    const close = () => { button.setAttribute('aria-expanded', 'false'); menu.hidden = true; };
    button.addEventListener('click', () => menu.hidden ? (button.setAttribute('aria-expanded', 'true'), menu.hidden = false) : close());
    document.addEventListener('click', (event) => { if (!switcher.contains(event.target)) close(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
  }

  function canadianDigits(value) {
    let digits = String(value).replace(/\D/g, '').slice(0, 11);
    if (digits.startsWith('1')) digits = digits.slice(1);
    return digits.slice(0, 10);
  }

  function formatCanadianPhone(value) {
    const digits = canadianDigits(value);
    if (digits.length <= 3) return digits ? `(${digits}` : '';
    if (digits.length <= 6) return `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
    return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
  }

  function fieldContainer(field) { return field.closest('.field') || field.closest('.checkbox-field'); }
  function clearError(field) {
    const container = fieldContainer(field);
    if (!container) return;
    container.classList.remove('has-error');
    field.closest('.phone-input')?.classList.remove('has-error');
    field.classList.remove('is-invalid');
    field.removeAttribute('aria-invalid');
    container.querySelector('.field-error')?.remove();
  }
  function showError(field, message) {
    const container = fieldContainer(field);
    if (!container) return;
    container.classList.add('has-error');
    field.closest('.phone-input')?.classList.add('has-error');
    field.classList.add('is-invalid');
    field.setAttribute('aria-invalid', 'true');
    let error = container.querySelector('.field-error');
    if (!error) { error = document.createElement('div'); error.className = 'field-error'; error.setAttribute('role', 'alert'); container.append(error); }
    error.textContent = message;
  }
  function fieldError(field) {
    const value = field.type === 'checkbox' ? field.checked : String(field.value || '').trim();
    if (field.required && !value) return t('required', 'This field is required.');
    if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return t('invalidEmail', 'Enter a valid email address.');
    if (field.type === 'tel' && value) {
      if (field.dataset.phoneMode === 'international') {
        const digits = String(field.value).replace(/\D/g, '');
        if (digits.length < 6 || digits.length > 15) return t('invalidPhone', 'Enter a valid phone number.');
      } else if (canadianDigits(field.value).length < 10) return t('invalidCanadianPhone', 'Enter a valid Canadian phone number.');
    }
    return '';
  }
  function validate(fields) {
    let invalid = null;
    fields.forEach((field) => { const error = fieldError(field); if (error) { showError(field, error); invalid ||= field; } else clearError(field); });
    invalid?.focus();
    return !invalid;
  }
  function setStatus(target, message) {
    let status = target.querySelector('.form-status');
    if (!status) { status = document.createElement('div'); status.className = 'form-status'; status.setAttribute('role', 'status'); target.append(status); }
    status.textContent = message;
  }
  function update() {
    if (!form || !steps.length) return;
    steps.forEach((item, index) => item.classList.toggle('active', index === step));
    label.textContent = t('stepLabel', 'Step {current} of {total}').replace('{current}', step + 1).replace('{total}', steps.length);
    progress.style.width = `${((step + 1) / steps.length) * 100}%`;
    previous.style.visibility = step ? 'visible' : 'hidden';
    next.textContent = step === steps.length - 1 ? t('submitCase', 'Submit Your Case') : t('nextStep', 'Next Step');
  }
  function phonePayload(input) {
    if (input.dataset.phoneMode !== 'international') return `+1 ${input.value}`;
    const country = input.closest('.phone-input')?.querySelector('[data-phone-country]')?.selectedOptions?.[0]?.dataset?.dialCode || '+1';
    return `+${country.replace(/\D/g, '')}${String(input.value).replace(/\D/g, '')}`;
  }
  async function submit() {
    const data = new FormData(form);
    const formGclid = form.querySelector('[name="zc_gad"]');
    const existingGclid = [...document.querySelectorAll('[name="zc_gad"]')].find((item) => item.value.trim());
    if (formGclid?.value.trim() || existingGclid?.value.trim()) data.set('zc_gad', formGclid?.value.trim() || existingGclid.value.trim());
    const phone = form.querySelector('#phone');
    if (phone?.value) data.set('phone', phonePayload(phone));
    data.set('formType', 'caseForm');
    next.disabled = true; next.textContent = t('submitting', 'Submitting...');
    try {
      const response = await fetch(config.restEndpoint, { method: 'POST', body: data });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok || !payload.success) throw new Error(payload.message || t('submitError', 'Unable to submit the form right now.'));
      window.location.href = `${window.location.origin}/success/`;
    } catch (error) {
      next.disabled = false; next.textContent = t('submitCase', 'Submit Your Case'); setStatus(form, error.message || t('submitError', 'Unable to submit the form right now.'));
    }
  }

  function initQuickForm() {
    const quickForm = document.getElementById('newsiteQuickForm');
    if (!quickForm) return;
    preserveAttribution(quickForm);
    quickForm.querySelectorAll('input, textarea').forEach((field) => field.addEventListener(field.type === 'checkbox' ? 'change' : 'input', () => {
      const error = fieldError(field);
      error ? showError(field, error) : clearError(field);
    }));
    quickForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const fields = [...quickForm.querySelectorAll('input, textarea')].filter((field) => field.name !== 'website' && field.type !== 'hidden');
      if (!validate(fields)) return;
      const button = quickForm.querySelector('button[type="submit"]');
      const defaultButtonText = button.textContent;
      const data = new FormData(quickForm);
      const phone = quickForm.querySelector('[name="phone"]');
      if (phone?.value) data.set('phone', phonePayload(phone));
      button.disabled = true;
      button.textContent = t('submitting', 'Submitting...');
      try {
        const response = await fetch(config.restEndpoint, { method: 'POST', body: data });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || !payload.success) throw new Error(payload.message || t('submitError', 'Unable to submit the form right now.'));
        window.location.href = `${window.location.origin}/success/`;
      } catch (error) {
        button.disabled = false;
        button.textContent = defaultButtonText;
        setStatus(quickForm, error.message || t('submitError', 'Unable to submit the form right now.'));
      }
    });
  }

  function initTestimonialCarousels() {
    document.querySelectorAll('[data-testimonial-carousel]').forEach((carousel) => {
      const slides = [...carousel.querySelectorAll('[data-testimonial-slide]')];
      const dots = [...carousel.querySelectorAll('[data-testimonial-dot]')];
      if (slides.length < 2) return;
      let active = 0;
      const show = (index) => {
        active = (index + slides.length) % slides.length;
        slides.forEach((slide, slideIndex) => {
          const isActive = slideIndex === active;
          slide.classList.toggle('is-active', isActive);
          slide.setAttribute('aria-hidden', String(!isActive));
        });
        dots.forEach((dot, dotIndex) => dot.classList.toggle('is-active', dotIndex === active));
      };
      carousel.querySelector('[data-testimonial-previous]')?.addEventListener('click', () => show(active - 1));
      carousel.querySelector('[data-testimonial-next]')?.addEventListener('click', () => show(active + 1));
    });
  }

  document.querySelectorAll('input[type="tel"]').forEach((input) => input.addEventListener('input', () => {
    input.value = input.dataset.phoneMode === 'international' ? input.value.replace(/[^\d\s()-]/g, '') : formatCanadianPhone(input.value);
  }));
  form?.querySelectorAll('input, select, textarea').forEach((field) => field.addEventListener(field.type === 'checkbox' || field.tagName === 'SELECT' ? 'change' : 'input', () => {
    if (form.dataset.validationStarted === '1') { const error = fieldError(field); error ? showError(field, error) : clearError(field); }
  }));
  next?.addEventListener('click', () => {
    form.dataset.validationStarted = '1';
    if (!validate([...steps[step].querySelectorAll('input, select, textarea')])) return;
    if (step < steps.length - 1) { step += 1; update(); return; }
    submit();
  });
  previous?.addEventListener('click', () => { if (step) { step -= 1; update(); } });
  preserveAttribution(form); initQuickForm(); initTestimonialCarousels(); initMenu(); initLanguageSwitcher(); update();
})();
