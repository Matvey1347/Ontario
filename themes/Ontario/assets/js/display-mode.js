(() => {
  const config = window.ontarioDisplayMode || {};
  const storageKey = config.storageKey || 'ontario_display_preference';
  const cookieName = config.cookieName || 'ontario_display_preference';
  const syncReloadKey = `${storageKey}_sync`;
  const root = document.documentElement;
  const isPreview = Boolean(config.isPreview);
  let lastFocusedElement = null;
  let initialized = false;

  function log(...args) {
    console.info('[Ontario display mode]', ...args);
  }

  function getModal() {
    return document.getElementById('display-choice-modal');
  }

  function getSwitcher() {
    return document.getElementById('displayModeSwitch');
  }

  function isValidMode(value) {
    return value === 'simple' || value === 'full';
  }

  function setCookie(value) {
    document.cookie = `${cookieName}=${value}; path=/; max-age=15552000; SameSite=Lax`;
  }

  function getStoredPreference() {
    const cookiePattern = new RegExp(`(?:^|;\\s*)${cookieName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}=(simple|full)(?:;|$)`);
    const cookieMatch = document.cookie.match(cookiePattern);

    if (cookieMatch && isValidMode(cookieMatch[1])) {
      log('Using cookie display preference.', { value: cookieMatch[1], cookieName });
      return cookieMatch[1];
    }

    try {
      const stored = window.localStorage.getItem(storageKey);
      log('LocalStorage display preference read.', { value: stored, storageKey, isPreview });
      return isValidMode(stored) ? stored : '';
    } catch (error) {
      log('Unable to read LocalStorage display preference.', error);
      return '';
    }
  }

  function setStoredPreference(value) {
    if (!isValidMode(value)) {
      log('Ignored invalid display mode choice.', value);
      return;
    }

    try {
      window.localStorage.setItem(storageKey, value);
      log('Stored display preference in LocalStorage.', { value, storageKey, isPreview });
    } catch (error) {
      log('Unable to store display preference in LocalStorage.', error);
    }

    setCookie(value);
    log('Stored display preference in cookie.', { value, cookieName, isPreview });
  }

  function applyModeClass(value) {
    root.classList.remove('ontario-display-full', 'ontario-display-simple', 'ontario-display-choice');
    root.classList.add(value === 'simple' ? 'ontario-display-simple' : value === 'full' ? 'ontario-display-full' : 'ontario-display-choice');

    if (document.body) {
      document.body.classList.remove('ontario-display-full', 'ontario-display-simple', 'ontario-display-choice');
      document.body.classList.add(value === 'simple' ? 'ontario-display-simple' : value === 'full' ? 'ontario-display-full' : 'ontario-display-choice');
    }
  }

  function trapFocus(event) {
    const modal = getModal();

    if (event.key !== 'Tab' || !modal?.classList.contains('is-open')) {
      return;
    }

    const focusable = [...modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')]
      .filter((element) => !element.hasAttribute('disabled'));

    if (!focusable.length) {
      event.preventDefault();
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function openModal() {
    const modal = getModal();

    if (!modal) {
      log('Cannot open display choice modal because modal element is missing.');
      return;
    }

    lastFocusedElement = document.activeElement;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body?.classList.add('modal-open');
    log('Display choice modal opened.');
    window.setTimeout(() => modal.querySelector('[data-display-choice="simple"]')?.focus(), 30);
  }

  function closeModal() {
    const modal = getModal();

    if (!modal) {
      log('Cannot close display choice modal because modal element is missing.');
      return;
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body?.classList.remove('modal-open');
    log('Display choice modal closed.');

    if (lastFocusedElement instanceof HTMLElement) {
      lastFocusedElement.focus();
    }
  }

  function chooseMode(value) {
    if (!isValidMode(value)) {
      return;
    }

    setStoredPreference(value);
    applyModeClass(value);
    closeModal();
    window.location.reload();
  }

  function ensureModalOpen(reason) {
    const modal = getModal();

    log('Checking whether display choice modal should open.', {
      reason,
      showChoiceModal: config.showChoiceModal,
      hasModal: Boolean(modal),
      hasSwitcher: Boolean(getSwitcher()),
      isAlreadyOpen: modal?.classList.contains('is-open') || false,
      ariaHidden: modal?.getAttribute('aria-hidden') || 'missing',
      cookie: document.cookie,
      localStorageValue: (() => {
        try {
          return window.localStorage.getItem(storageKey);
        } catch (error) {
          return 'unavailable';
        }
      })()
    });

    if (!config.showChoiceModal || !modal) {
      return;
    }

    if (!modal.classList.contains('is-open')) {
      openModal();
    }
  }

  function init() {
    const modal = getModal();
    const switcher = getSwitcher();

    if (!modal) {
      log('Display choice modal markup is still missing at init.', {
        config,
        hasSwitcher: Boolean(switcher),
        readyState: document.readyState
      });
      return false;
    }

    if (initialized) {
      ensureModalOpen('re-init');
      return true;
    }

    initialized = true;

    const storedPreference = getStoredPreference();

    log('Display mode bootstrap.', {
      config,
      isPreview,
      storedPreference,
      bodyClass: document.body?.className || '',
      hasModal: Boolean(modal),
      hasSwitcher: Boolean(switcher),
      readyState: document.readyState
    });

    applyModeClass(storedPreference || 'choice');

    if (config.siteMode === 'choice' && config.effectiveMode === 'choice' && storedPreference) {
      let syncFlag = '';

      try {
        syncFlag = window.sessionStorage.getItem(syncReloadKey) || '';
      } catch (error) {}

      if (syncFlag !== storedPreference) {
        try {
          window.sessionStorage.setItem(syncReloadKey, storedPreference);
        } catch (error) {}

        log('Reloading page once to sync stored display preference.', storedPreference);
        window.location.reload();
        return true;
      }

      try {
        window.sessionStorage.removeItem(syncReloadKey);
      } catch (error) {}
    }

    modal.querySelectorAll('[data-display-choice]').forEach((button) => {
      button.addEventListener('click', () => chooseMode(button.getAttribute('data-display-choice') || ''));
    });

    switcher?.addEventListener('click', openModal);

    document.addEventListener('keydown', (event) => {
      const currentModal = getModal();

      if (currentModal?.classList.contains('is-open')) {
        trapFocus(event);
      }
    });

    ensureModalOpen('init');
    return true;
  }

  init();
  document.addEventListener('DOMContentLoaded', init);
  window.addEventListener('load', init);
})();
