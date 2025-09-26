// Async Hit Counter Increment (optional)
(function(){
  if (!window.fetch || !window.TR199X_HITCFG) return;
  const cfg = window.TR199X_HITCFG;
  setTimeout(()=>{
    const body = new URLSearchParams();
    body.append('type', cfg.type);
    if (cfg.postId) body.append('post_id', cfg.postId);

    fetch(cfg.endpoint, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: body.toString(),
      credentials:'same-origin'
    }).then(r=>r.json()).then(data=>{
      if (data && data.hits && cfg.target) {
        const el = document.querySelector(cfg.target);
        if (el && !el.dataset.updated) {
          el.dataset.updated = '1';
          el.textContent = data.hits;
        }
      }
    }).catch(()=>{});
  }, cfg.delay || 800);
})();