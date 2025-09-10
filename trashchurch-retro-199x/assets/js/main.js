// Retro 199X core JS: nav toggle + reduced motion flag
(function(){
  const navToggle = document.getElementById('tr-nav-toggle');
  const nav = document.getElementById('tr-primary-nav');
  if (navToggle && nav){
    navToggle.addEventListener('click', ()=>{
      nav.classList.toggle('tr-collapsed');
      navToggle.setAttribute('aria-expanded', (!nav.classList.contains('tr-collapsed')).toString());
    });
    if (window.matchMedia('(max-width: 980px)').matches){
      nav.classList.add('tr-collapsed');
      navToggle.setAttribute('aria-expanded','false');
    }
  }
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    document.body.classList.add('prefers-reduced-motion');
  }
})();