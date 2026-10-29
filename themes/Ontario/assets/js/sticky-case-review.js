(() => {
  const button = document.querySelector('[data-sticky-case-review]');
  if (!button) return;

  const scanner = document.getElementById('scanner');
  const footer = document.querySelector('.site-footer');
  const revealAfter = 360;
  let scannerVisible = false;
  let footerVisible = false;

  const updateVisibility = () => {
    const shouldShow = window.scrollY > revealAfter && !scannerVisible && !footerVisible;
    button.classList.toggle('is-visible', shouldShow);
    button.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
    button.tabIndex = shouldShow ? 0 : -1;
  };

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.target === scanner) scannerVisible = entry.isIntersecting;
        if (entry.target === footer) footerVisible = entry.isIntersecting;
      });
      updateVisibility();
    }, { rootMargin: '-15% 0px -15% 0px' });

    if (scanner) observer.observe(scanner);
    if (footer) observer.observe(footer);
  }

  window.addEventListener('scroll', updateVisibility, { passive: true });
  window.addEventListener('resize', updateVisibility);
  updateVisibility();
})();
