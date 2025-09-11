// Retro 199X core JS: nav toggle + reduced motion flag
(function(){
  const navToggle = document.querySelector('.tr-nav-toggle');
  const nav = document.getElementById('site-navigation');
  
  if (navToggle && nav){
    // Toggle function
    const toggleNav = () => {
      nav.classList.toggle('tr-collapsed');
      navToggle.setAttribute('aria-expanded', (!nav.classList.contains('tr-collapsed')).toString());
    };
    
    // Click handler
    navToggle.addEventListener('click', toggleNav);
    
    // Initialize collapsed state on mobile and listen for viewport changes
    const mobileQuery = window.matchMedia('(max-width: 980px)');
    
    const handleMobileChange = (e) => {
      if (e.matches) {
        // Mobile: ensure nav is collapsed
        nav.classList.add('tr-collapsed');
        navToggle.setAttribute('aria-expanded', 'false');
      } else {
        // Desktop: ensure nav is visible
        nav.classList.remove('tr-collapsed');
        navToggle.setAttribute('aria-expanded', 'true');
      }
    };
    
    // Initial state
    handleMobileChange(mobileQuery);
    
    // Listen for viewport changes with browser compatibility
    if (mobileQuery.addEventListener) {
      mobileQuery.addEventListener('change', handleMobileChange);
    } else {
      // Fallback for older browsers
      mobileQuery.addListener(handleMobileChange);
    }
  }
  
  // Reduced motion detection
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    document.body.classList.add('prefers-reduced-motion');
  }
})();