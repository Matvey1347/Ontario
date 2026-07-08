(() => {
  const osmConfig = window.ontarioSiteManager || {};
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
    document.querySelectorAll('.card, .strip-card').forEach((card) => {
      card.addEventListener('pointermove', (event) => {
        setLocalPointerVars(card, event, '--glow-x', '--glow-y');
      });
    });
  }

  function initHeroField() {
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

    const openButtons = document.querySelectorAll('[data-modal-open]');
    const closeButtons = modal.querySelectorAll('[data-modal-close]');
    const firstField = modal.querySelector('input, textarea, button');
    let lastFocusedElement = null;

    function openModal(event) {
      event?.preventDefault();
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
    const quickModal = document.getElementById('quick-contact-modal');
    const successModal = document.getElementById('success-modal');

    closeModal(quickModal);
    openModal(successModal);
    window.setTimeout(() => successModal?.querySelector('button[data-success-close]')?.focus(), 80);
  }

  function initSuccessModal() {
    const successModal = document.getElementById('success-modal');
    if (!successModal) return;

    successModal.querySelectorAll('[data-success-close]').forEach((button) => {
      button.addEventListener('click', () => closeModal(successModal));
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && successModal.classList.contains('is-open')) {
        closeModal(successModal);
      }
    });
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
        phoneInput.setCustomValidity('');
      });

      phoneInput.addEventListener('blur', () => {
        const digits = getCanadianPhoneDigits(phoneInput.value);

        if (digits.length > 0 && digits.length < 10) {
          phoneInput.setCustomValidity('Enter a valid Canadian phone number.');
        } else {
          phoneInput.setCustomValidity('');
        }
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
    steps.forEach((step, index) => step.classList.toggle('active', index === currentStep));
    stepLabel.textContent = `Step ${currentStep + 1} of ${steps.length}`;
    progressBar.style.width = `${((currentStep + 1) / steps.length) * 100}%`;
    prevBtn.style.visibility = currentStep === 0 ? 'hidden' : 'visible';
    nextBtn.textContent = currentStep === steps.length - 1 ? 'Submit Case Review' : 'Next Step';
  }

  function validateCurrentStep() {
    const fields = [...steps[currentStep].querySelectorAll('input, select, textarea')];

    for (const field of fields) {
      if (!field.checkValidity()) {
        field.reportValidity();
        return false;
      }
    }

    return true;
  }

  nextBtn?.addEventListener('click', () => {
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
    setFormMessage(form, '');

    submitLead(formData)
      .then(() => {
        nextBtn.textContent = 'Submitted';
        showSuccessModal();
      })
      .catch((error) => {
        nextBtn.disabled = false;
        nextBtn.textContent = 'Submit Case Review';
        setFormMessage(form, error.message || 'Unable to submit the form.', 'error');
      });
  });

  document.getElementById('quickForm')?.addEventListener('submit', (event) => {
    event.preventDefault();

    const quickForm = event.currentTarget;
    const submitButton = quickForm.querySelector('button[type="submit"]');

    if (!quickForm.checkValidity()) {
      quickForm.reportValidity();
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
    setFormMessage(quickForm, '');

    submitLead(formData)
      .then(() => {
        submitButton.textContent = 'Sent';
        showSuccessModal();
      })
      .catch((error) => {
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

  document.querySelectorAll('.faq-question').forEach((button) => {
    button.addEventListener('click', () => {
      const item = button.closest('.faq-item');
      const wasOpen = item.classList.contains('open');

      document.querySelectorAll('.faq-item').forEach((faq) => {
        faq.classList.remove('open');
        faq.querySelector('.faq-question span').textContent = '+';
      });

      if (!wasOpen) {
        item.classList.add('open');
        button.querySelector('span').textContent = '−';
      }
    });
  });

  initCustomSelects();
  initPhoneMask();
  initScrollReveal();
  initPointerGlow();
  initHeroField();
  initQuickContactModal();
  initSuccessModal();
  updateForm();
})();
