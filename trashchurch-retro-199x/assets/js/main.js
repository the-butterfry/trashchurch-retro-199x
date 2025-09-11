// Retro 199X core JS: nav toggle + reduced motion flag (robust selectors)
(function(){
  const navToggle = document.getElementById('tr-nav-toggle') || document.querySelector('.tr-nav-toggle');
  const nav = document.getElementById('tr-primary-nav') || document.getElementById('site-navigation') || document.querySelector('.tr-nav');

  if (navToggle && nav){
    const setExpandedState = (expanded) => {
      navToggle.setAttribute('aria-expanded', String(expanded));
      if (!navToggle.getAttribute('aria-controls') && nav.id) {
        navToggle.setAttribute('aria-controls', nav.id);
      }
    };

    const toggleNav = () => {
      nav.classList.toggle('tr-collapsed');
      setExpandedState(!nav.classList.contains('tr-collapsed'));
    };

    navToggle.addEventListener('click', (e) => {
      e.preventDefault();
      toggleNav();
    });

    const mq = window.matchMedia('(max-width: 980px)');
    const applyInitial = (m) => {
      if (m.matches) {
        nav.classList.add('tr-collapsed');
        setExpandedState(false);
      } else {
        nav.classList.remove('tr-collapsed');
        setExpandedState(true);
      }
    };

    applyInitial(mq);
    if (typeof mq.addEventListener === 'function') {
      mq.addEventListener('change', (e) => applyInitial(e));
    } else if (typeof mq.addListener === 'function') {
      mq.addListener(applyInitial);
    }
  }

  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    document.body.classList.add('prefers-reduced-motion');
  }
})();
