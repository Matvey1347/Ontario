(() => {
  const osmConfig = window.ontarioSiteManager || {};
  const successUrl = osmConfig.successUrl || `${window.location.origin}/success/`;
  const effectiveDisplayMode = osmConfig.effectiveDisplayMode || 'full';
  const isSimpleDisplay = effectiveDisplayMode === 'simple';
  const submissionState = {
    quickSubmitted: false,
    caseSubmitted: false
  };
  const nav = document.getElementById('nav');
  const menuBtn = document.getElementById('menuBtn');

  menuBtn?.addEventListener('click', () => {
    nav.classList.toggle('open');
  });

  document.querySelectorAll('.nav-links a').forEach((link) => {
    link.addEventListener('click', () => nav.classList.remove('open'));
  });

  function initScrollReveal() {
    const revealGroups = [
      '.hero-content',
      '.hero-visual',
      '.strip-card',
      '.section-head',
      '.card',
      '.scanner-panel',
      '.form-card',
      '.report',
      '.process-copy-card',
      '.process-image-frame',
      '.warning-panel',
      '.faq-item',
      '.cta-band'
    ];
    const elements = [...document.querySelectorAll(revealGroups.join(','))]
      .filter((element) => !element.closest('.modal'));

    elements.forEach((element, index) => {
      if (isSimpleDisplay) {
        element.classList.add('is-visible');
        return;
      }

      element.classList.add('reveal');

      if (element.matches('.hero-content, .scanner-panel, .process-copy-card')) {
        element.classList.add('from-left');
      }

      if (element.matches('.hero-visual, .form-card, .report, .process-image-frame')) {
        element.classList.add('from-right');
      }

      element.style.setProperty('--reveal-delay', `${Math.min((index % 6) * 70, 350)}ms`);
    });

    if (!('IntersectionObserver' in window)) {
      elements.forEach((element) => element.classList.add('is-visible'));
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, {
      threshold: 0.16,
      rootMargin: '0px 0px -8% 0px'
    });

    elements.forEach((element) => observer.observe(element));
  }

  function setLocalPointerVars(element, event, xName, yName) {
    const rect = element.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;

    element.style.setProperty(xName, `${x.toFixed(2)}%`);
    element.style.setProperty(yName, `${y.toFixed(2)}%`);
  }

  function initPointerGlow() {
    if (isSimpleDisplay) return;

    document.querySelectorAll('.card, .strip-card').forEach((card) => {
      card.addEventListener('pointermove', (event) => {
        setLocalPointerVars(card, event, '--glow-x', '--glow-y');
      });
    });
  }

  function initHeroField() {
    if (isSimpleDisplay) return;

    const hero = document.querySelector('.hero');
    if (!hero) return;

    hero.addEventListener('pointermove', (event) => {
      setLocalPointerVars(hero, event, '--hero-x', '--hero-y');

      const rect = hero.getBoundingClientRect();
      const x = (event.clientX - rect.left) / rect.width - 0.5;
      const y = (event.clientY - rect.top) / rect.height - 0.5;
      const shiftX = x * 28;
      const shiftY = y * 22;

      hero.style.setProperty('--hero-shift-x', `${shiftX.toFixed(2)}px`);
      hero.style.setProperty('--hero-shift-y', `${shiftY.toFixed(2)}px`);
      hero.style.setProperty('--hero-shift-rx', `${(shiftX * -0.35).toFixed(2)}px`);
      hero.style.setProperty('--hero-shift-ry', `${(shiftY * -0.35).toFixed(2)}px`);
    });

    hero.addEventListener('pointerleave', () => {
      hero.style.setProperty('--hero-x', '50%');
      hero.style.setProperty('--hero-y', '34%');
      hero.style.setProperty('--hero-shift-x', '0px');
      hero.style.setProperty('--hero-shift-y', '0px');
      hero.style.setProperty('--hero-shift-rx', '0px');
      hero.style.setProperty('--hero-shift-ry', '0px');
    });
  }

  function initQuickContactModal() {
    const modal = document.getElementById('quick-contact-modal');
    if (!modal) return;

    const quickForm = document.getElementById('quickForm');
    const openButtons = document.querySelectorAll('[data-modal-open]');
    const closeButtons = modal.querySelectorAll('[data-modal-close]');
    const firstField = modal.querySelector('input, textarea, button');
    let lastFocusedElement = null;

    function openModal(event) {
      event?.preventDefault();
      if (submissionState.quickSubmitted) {
        showSuccessModal();
        return;
      }
      lastFocusedElement = document.activeElement;
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modal-open');
      window.setTimeout(() => firstField?.focus(), 80);
    }

    function closeModal() {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');

      if (lastFocusedElement instanceof HTMLElement) {
        lastFocusedElement.focus();
      }
    }

    openButtons.forEach((button) => {
      button.addEventListener('click', openModal);
    });

    closeButtons.forEach((button) => {
      button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }
    });

    if (quickForm) {
      quickForm.addEventListener('submit', (event) => {
        if (submissionState.quickSubmitted) {
          event.preventDefault();
          showSuccessModal();
        }
      });
    }
  }

  function openModal(modal) {
    if (!modal) return;

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
  }

  function closeModal(modal) {
    if (!modal) return;

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');

    if (!document.querySelector('.modal.is-open')) {
      document.body.classList.remove('modal-open');
    }
  }

  function showSuccessModal() {
    closeModal(document.getElementById('quick-contact-modal'));
    closeModal(document.getElementById('success-modal'));
    window.location.href = successUrl;
  }

  function initSuccessModal() {
    return;
  }

  function resetFormState(formElement) {
    if (!formElement) return;

    setFormLoading(formElement, false);
    formElement.reset();
    formElement.classList.remove('is-submitted');
    formElement.removeAttribute('aria-busy');
    formElement.dataset.validationStarted = '0';
    formElement.querySelectorAll('.has-error').forEach((element) => element.classList.remove('has-error'));
    formElement.querySelectorAll('.is-invalid').forEach((element) => element.classList.remove('is-invalid'));
    formElement.querySelectorAll('.field-error').forEach((element) => element.remove());
    setFormMessage(formElement, '');
  }

  function renderCaseFormSuccessState() {
    if (form) {
      setFormLoading(form, false);
      resetFormState(form);
    }

    currentStep = 0;
    updateForm();
    closeModal(document.getElementById('quick-contact-modal'));
    closeModal(document.getElementById('success-modal'));
    window.location.href = successUrl;
  }

  function initWelcomeModal() {
    const welcomeModal = document.getElementById('welcome-modal');
    if (!welcomeModal || !osmConfig.showWelcomeModal) return;

    const storageKey = `ontarioWelcomeSeen:${osmConfig.siteId || 'default'}`;

    function wasSeen() {
      try {
        if (window.localStorage.getItem(storageKey) === '1') {
          return true;
        }
      } catch (error) {
        return document.cookie.includes(`${storageKey}=1`);
      }

      return document.cookie.includes(`${storageKey}=1`);
    }

    function markSeen() {
      try {
        window.localStorage.setItem(storageKey, '1');
      } catch (error) {
        document.cookie = `${storageKey}=1; path=/; max-age=31536000; SameSite=Lax`;
      }
    }

    function closeWelcomeModal() {
      markSeen();
      closeModal(welcomeModal);
    }

    if (wasSeen()) {
      return;
    }

    welcomeModal.querySelectorAll('[data-welcome-close]').forEach((button) => {
      button.addEventListener('click', closeWelcomeModal);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && welcomeModal.classList.contains('is-open')) {
        closeWelcomeModal();
      }
    });

    window.setTimeout(() => openModal(welcomeModal), 220);
  }

  function initCustomSelects() {
    const selects = document.querySelectorAll('select');

    if (typeof Choices === 'undefined') {
      document.body.classList.add('choices-fallback');
      return;
    }

    selects.forEach((select) => {
      new Choices(select, {
        searchEnabled: false,
        shouldSort: false,
        itemSelectText: '',
        allowHTML: false,
        placeholder: true,
        classNames: {
          containerOuter: ['choices', 'custom-select']
        }
      });
    });
  }

  function getCanadianPhoneDigits(value) {
    let digits = value.replace(/\D/g, '').slice(0, 11);

    if (digits.startsWith('1')) {
      digits = digits.slice(1);
    }

    return digits.slice(0, 10);
  }

  function formatCanadianPhone(value) {
    const digits = getCanadianPhoneDigits(value);

    if (digits.length === 0) return '';
    if (digits.length <= 3) return `(${digits}`;
    if (digits.length <= 6) return `(${digits.slice(0, 3)}) ${digits.slice(3)}`;

    return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
  }

  function initPhoneMask() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');

    phoneInputs.forEach((phoneInput) => {
      phoneInput.addEventListener('input', () => {
        phoneInput.value = formatCanadianPhone(phoneInput.value);
      });
    });
  }

  const steps = [...document.querySelectorAll('.form-step')];
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  const stepLabel = document.getElementById('stepLabel');
  const progressBar = document.getElementById('progressBar');
  const form = document.getElementById('caseForm');
  let currentStep = 0;

  function setFormMessage(formElement, message, type = 'error') {
    if (!formElement) return;

    let status = formElement.querySelector('.form-status');

    if (!status) {
      status = document.createElement('div');
      status.className = 'form-status';
      formElement.appendChild(status);
    }

    status.textContent = message;
    status.dataset.state = type;
  }

  function ensureLoadingOverlay(formElement) {
    let overlay = formElement.querySelector('.form-loading-overlay');

    if (overlay) {
      return overlay;
    }

    overlay = document.createElement('div');
    overlay.className = 'form-loading-overlay';
    overlay.setAttribute('hidden', 'hidden');
    overlay.innerHTML = `
      <div class="form-loading-spinner" aria-hidden="true"></div>
      <div class="form-loading-title">Submitting your request...</div>
      <div class="form-loading-copy">Please wait a moment while we process your form.</div>
    `;
    formElement.appendChild(overlay);

    return overlay;
  }

  function setFormLoading(formElement, isLoading, message = 'Submitting your request...') {
    if (!formElement) return;

    const overlay = ensureLoadingOverlay(formElement);
    formElement.classList.toggle('is-loading', isLoading);
    formElement.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    formElement.querySelectorAll('input, select, textarea, button').forEach((field) => {
      if (field.classList.contains('hp-field')) {
        return;
      }

      field.disabled = isLoading;
    });

    if (isLoading) {
      overlay.hidden = false;
      const title = overlay.querySelector('.form-loading-title');

      if (title) {
        title.textContent = message;
      }

      setFormMessage(formElement, '');
      return;
    }

    overlay.hidden = true;
    setFormMessage(formElement, '');
  }

  function resetTransientUi() {
    const quickForm = document.getElementById('quickForm');

    submissionState.quickSubmitted = false;
    submissionState.caseSubmitted = false;

    resetFormState(form);
    resetFormState(quickForm);
    currentStep = 0;
    updateForm();
    closeModal(document.getElementById('quick-contact-modal'));
    closeModal(document.getElementById('success-modal'));
    closeModal(document.getElementById('welcome-modal'));
    document.body.classList.remove('modal-open');
  }

  async function submitLead(formData) {
    const response = await fetch(osmConfig.restEndpoint, {
      method: 'POST',
      body: formData
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Unable to submit the form right now.');
    }

    return payload;
  }

  function updateForm() {
    if (!form || !stepLabel || !progressBar || !prevBtn || !nextBtn || !steps.length) {
      return;
    }

    steps.forEach((step, index) => step.classList.toggle('active', index === currentStep));
    stepLabel.textContent = `Step ${currentStep + 1} of ${steps.length}`;
    progressBar.style.width = `${((currentStep + 1) / steps.length) * 100}%`;
    prevBtn.style.visibility = currentStep === 0 ? 'hidden' : 'visible';
    nextBtn.textContent = currentStep === steps.length - 1 ? 'Submit Case Review' : 'Next Step';
  }

  function getFieldContainer(field) {
    return field.closest('.field') || field.closest('.checkbox-field');
  }

  function getPhoneWrapper(field) {
    return field.type === 'tel' ? field.closest('.phone-input') : null;
  }

  function hasStartedValidation(formElement) {
    return !!(formElement && formElement.dataset.validationStarted === '1');
  }

  function startValidation(formElement) {
    if (!formElement) return;
    formElement.dataset.validationStarted = '1';
  }

  function setFieldError(field, message) {
    const container = getFieldContainer(field);
    const phoneWrapper = getPhoneWrapper(field);

    if (!container) return;

    container.classList.add('has-error');
    if (phoneWrapper) {
      phoneWrapper.classList.add('has-error');
    } else {
      field.classList.add('is-invalid');
    }

    let error = container.querySelector('.field-error');

    if (!error) {
      error = document.createElement('div');
      error.className = 'field-error';
      container.appendChild(error);
    }

    error.textContent = message;
  }

  function clearFieldError(field) {
    const container = getFieldContainer(field);
    const phoneWrapper = getPhoneWrapper(field);

    if (!container) return;

    container.classList.remove('has-error');
    if (phoneWrapper) {
      phoneWrapper.classList.remove('has-error');
      field.classList.remove('is-invalid');
    } else {
      field.classList.remove('is-invalid');
    }

    const error = container.querySelector('.field-error');

    if (error) {
      error.remove();
    }
  }

  function getFieldErrorMessage(field) {
    const value = field.type === 'checkbox' ? field.checked : String(field.value || '').trim();

    if (field.hasAttribute('required') && !value) {
      return 'This field is required.';
    }

    if (field.type === 'email' && value) {
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!emailPattern.test(String(field.value).trim())) {
        return 'Enter a valid email address.';
      }
    }

    if (field.type === 'tel' && value) {
      const digits = getCanadianPhoneDigits(String(field.value));

      if (digits.length < 10) {
        return 'Enter a valid Canadian phone number.';
      }
    }

    return '';
  }

  function attachFieldValidation(formElement) {
    if (!formElement) return;

    formElement.querySelectorAll('input, select, textarea').forEach((field) => {
      const eventName = field.type === 'checkbox' || field.tagName === 'SELECT' ? 'change' : 'input';

      field.addEventListener(eventName, () => {
        if (!hasStartedValidation(formElement)) {
          return;
        }

        const message = getFieldErrorMessage(field);

        if (message) {
          setFieldError(field, message);
        } else {
          clearFieldError(field);
        }
      });
    });
  }

  function validateFields(fields) {
    let firstInvalidField = null;
    let isValid = true;

    fields.forEach((field) => {
      const message = getFieldErrorMessage(field);

      if (message) {
        setFieldError(field, message);
        isValid = false;

        if (!firstInvalidField) {
          firstInvalidField = field;
        }
      } else {
        clearFieldError(field);
      }
    });

    if (firstInvalidField) {
      firstInvalidField.focus();
    }

    return isValid;
  }

  function validateCurrentStep() {
    if (!steps.length || !steps[currentStep]) {
      return false;
    }

    const fields = [...steps[currentStep].querySelectorAll('input, select, textarea')];
    return validateFields(fields);
  }

  nextBtn?.addEventListener('click', () => {
    if (submissionState.caseSubmitted) {
      showSuccessModal();
      return;
    }

    startValidation(form);
    if (!validateCurrentStep()) return;

    if (currentStep < steps.length - 1) {
      currentStep += 1;
      updateForm();
      return;
    }

    const formData = new FormData(form);
    const phone = formData.get('phone');

    if (phone) {
      formData.set('phone', `+1 ${phone}`);
    }
    formData.set('formType', 'caseForm');

    nextBtn.disabled = true;
    nextBtn.textContent = 'Submitting...';
    setFormLoading(form, true);

    submitLead(formData)
      .then(() => {
        submissionState.caseSubmitted = true;
        nextBtn.textContent = 'Submitted';
        renderCaseFormSuccessState();
      })
      .catch((error) => {
        setFormLoading(form, false);
        nextBtn.disabled = false;
        nextBtn.textContent = 'Submit Case Review';
        setFormMessage(form, error.message || 'Unable to submit the form.', 'error');
      });
  });

  document.getElementById('quickForm')?.addEventListener('submit', (event) => {
    event.preventDefault();

    if (submissionState.quickSubmitted) {
      showSuccessModal();
      return;
    }

    const quickForm = event.currentTarget;
    const submitButton = quickForm.querySelector('button[type="submit"]');
    startValidation(quickForm);

    const quickFields = [...quickForm.querySelectorAll('input, select, textarea')];

    if (!validateFields(quickFields)) {
      return;
    }

    const formData = new FormData(quickForm);
    const phone = formData.get('phone');

    if (phone) {
      formData.set('phone', `+1 ${phone}`);
    }

    formData.set('formType', 'quickForm');
    submitButton.textContent = 'Sending...';
    submitButton.disabled = true;
    setFormLoading(quickForm, true, 'Sending your message...');

    submitLead(formData)
      .then(() => {
        submissionState.quickSubmitted = true;
        setFormLoading(quickForm, false);
        submitButton.textContent = 'Sent';
        resetFormState(quickForm);
        submitButton.textContent = 'Send Message';
        submitButton.disabled = false;
        showSuccessModal();
      })
      .catch((error) => {
        setFormLoading(quickForm, false);
        submitButton.textContent = 'Send Message';
        submitButton.disabled = false;
        setFormMessage(quickForm, error.message || 'Unable to send the form.', 'error');
      });
  });

  prevBtn?.addEventListener('click', () => {
    if (currentStep > 0) {
      currentStep -= 1;
      updateForm();
    }
  });

  const faqItems = [...document.querySelectorAll('.faq-item')];

  function setFaqState(item, shouldOpen) {
    if (!item) return;

    const answer = item.querySelector('.faq-answer');
    const icon = item.querySelector('.faq-question span');

    if (!answer) return;

    if (shouldOpen) {
      item.classList.add('open');
      answer.style.maxHeight = `${answer.scrollHeight}px`;

      if (icon) {
        icon.textContent = '−';
      }
    } else {
      answer.style.maxHeight = `${answer.scrollHeight}px`;

      window.requestAnimationFrame(() => {
        item.classList.remove('open');
        answer.style.maxHeight = '0px';
      });

      if (icon) {
        icon.textContent = '+';
      }
    }
  }

  faqItems.forEach((item) => {
    const answer = item.querySelector('.faq-answer');

    if (answer) {
      answer.style.maxHeight = item.classList.contains('open') ? `${answer.scrollHeight}px` : '0px';
    }
  });

  document.querySelectorAll('.faq-question').forEach((button) => {
    button.addEventListener('click', () => {
      const item = button.closest('.faq-item');

      if (!item) {
        return;
      }

      const wasOpen = item.classList.contains('open');

      faqItems.forEach((faq) => {
        if (faq !== item) {
          setFaqState(faq, false);
        }
      });

      setFaqState(item, !wasOpen);
    });
  });

  window.addEventListener('resize', () => {
    faqItems.forEach((item) => {
      if (!item.classList.contains('open')) {
        return;
      }

      const answer = item.querySelector('.faq-answer');

      if (answer) {
        answer.style.maxHeight = `${answer.scrollHeight}px`;
      }
    });
  });

  initCustomSelects();
  initPhoneMask();
  attachFieldValidation(form);
  attachFieldValidation(document.getElementById('quickForm'));
  initScrollReveal();
  initPointerGlow();
  initHeroField();
  initQuickContactModal();
  initSuccessModal();
  initWelcomeModal();
  updateForm();

  window.addEventListener('pageshow', (event) => {
    if (!event.persisted) {
      return;
    }

    resetTransientUi();
  });
})();
