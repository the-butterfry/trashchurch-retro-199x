// Retro 199X core JS: nav toggle + reduced motion flag (robust selectors)
(function(){
  const navToggle = document.getElementById('tr-nav-toggle') || document.querySelector('.tr-nav-toggle');
  const nav = document.getElementById('tr-primary-nav') || document.getElementById('site-navigation') || document.querySelector('.tr-nav');

  if (navToggle && nav) {
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

  // --- PRAYER MODAL AJAX HANDLERS ---
  document.addEventListener('DOMContentLoaded', function() {
    // Event delegation for dynamically injected prayer modal form
    document.body.addEventListener('submit', function(e) {
      if (e.target && e.target.id === 'prayer-modal-form') {
        e.preventDefault();
        var form = new FormData(e.target);
        form.append('action', 'prayer_modal_update');
        fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
          method: 'POST',
          body: form
        })
        .then(r => r.text())
        .then(msg => {
          document.getElementById('prayer-modal').style.display = 'none';
          location.reload();
        })
        .catch(function(error) {
          alert("AJAX error: " + error);
          console.error(error);
        });
      }
    });

    // Delete button handler for prayer modal
    document.body.addEventListener('click', function(e) {
      if (e.target && e.target.id === 'prayer-delete-btn') {
        if (confirm('Are you sure you want to delete this ad?')) {
          fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=prayer_delete&id=' + e.target.dataset.id + '&_wpnonce=' + e.target.getAttribute('data-nonce')
          })
          .then(r => r.text())
          .then(msg => {
            location.reload();
          })
          .catch(function(error) {
            alert("Delete AJAX error: " + error);
            console.error(error);
          });
        }
      }
    });
  });
})();