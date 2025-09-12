// Comet image trail (cursor follower)
(function(){
  if (typeof TR199X_COMET === 'undefined') return;

  // Respect reduced motion
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  // NEW: allow coarse pointers when allowCoarse=true, otherwise bail
  var allowCoarse = (typeof TR199X_COMET.allowCoarse !== 'undefined') ? !!TR199X_COMET.allowCoarse : false;
  if (!allowCoarse && window.matchMedia('(pointer: coarse)').matches) return;

  const count = Math.min(Math.max(TR199X_COMET.count || 10,1),40);
  const smooth = TR199X_COMET.smooth || 0.25;
  const imgSrc = TR199X_COMET.image;
  const fade = !!TR199X_COMET.fade;

  const nodes = [];
  const pos = [];
  for (let i=0;i<count;i++){
    const el = document.createElement('img');
    el.src = imgSrc;
    el.className = 'tr-comet';
    if (fade) el.style.opacity = (1 - i/(count*1.15)).toFixed(2);
    el.style.transform = 'translate(-80px,-80px)';
    document.body.appendChild(el);
    nodes.push(el);
    pos.push({x:-80,y:-80});
  }

  const target = {x:-80,y:-80};
  document.addEventListener('mousemove',(e)=>{
    target.x = e.clientX + 6;
    target.y = e.clientY + 6;
  },{passive:true});

  function step(){
    pos[0].x += (target.x - pos[0].x)*smooth;
    pos[0].y += (target.y - pos[0].y)*smooth;

    for(let i=1;i<count;i++){
      pos[i].x += (pos[i-1].x - pos[i].x)*smooth;
      pos[i].y += (pos[i-1].y - pos[i].y)*smooth;
    }
    for(let i=0;i<count;i++){
      nodes[i].style.transform = `translate(${pos[i].x}px,${pos[i].y}px)`;
    }
    requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
})();
