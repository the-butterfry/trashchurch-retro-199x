(function(){
  // Build CSS for selected font family
  function buildFamilyCSS(slug){
    if (!window.TR199X_FONTMAP || !TR199X_FONTMAP.families) return '';
    var fam = TR199X_FONTMAP.families[slug];
    if (!fam) return '';
    var css = '/* live preview font: ' + slug + ' */\n';
    (fam.faces || []).forEach(function(face){
      var srcParts = [];
      if (face.srcs && face.srcs.woff2) srcParts.push("url('"+face.srcs.woff2+"') format('woff2')");
      if (face.srcs && face.srcs.woff)  srcParts.push("url('"+face.srcs.woff+"') format('woff')");
      if (face.srcs && face.srcs.ttf)   srcParts.push("url('"+face.srcs.ttf+"') format('truetype')");
      if (!srcParts.length) return;
      var w = parseInt(face.weight || 400, 10);
      var s = (face.style === 'italic') ? 'italic' : 'normal';
      css += "@font-face{font-family:'TR199X-Header';font-style:"+s+";font-weight:"+w+";font-display:swap;src:"+srcParts.join(', ')+";}\n";
    });
    css += ":root{--tr-header-font:'TR199X-Header', var(--tr-font);}"; 
    return css;
  }

  // Build CSS for typography (title/tagline)
  function buildTypoCSS(state){
    var t_weight = +state.t_weight || 900;
    var t_ls     = (state.t_ls !== undefined) ? +state.t_ls : 2;
    var t_lh     = (state.t_lh !== undefined) ? +state.t_lh : 1.1;
    var t_min    = (state.t_min !== undefined) ? +state.t_min : 2.4;
    var t_vw     = (state.t_vw !== undefined) ? +state.t_vw  : 6.0;
    var t_max    = (state.t_max !== undefined) ? +state.t_max : 3.8;

    var g_weight = +state.g_weight || 400;
    var g_ls     = (state.g_ls !== undefined) ? +state.g_ls : 1;
    var g_fs_px  = (state.g_fs_px !== undefined) ? +state.g_fs_px : 14;

    var css = '';
    css += ".tr-title{font-weight:"+t_weight+";letter-spacing:"+t_ls+"px;line-height:"+t_lh+";font-size:clamp("+t_min+"rem, "+t_vw+"vw, "+t_max+"rem);}\n";
    css += ".tr-tagline{font-weight:"+g_weight+";letter-spacing:"+g_ls+"px;font-size:"+g_fs_px+"px;}\n";
    return css;
  }

  // Inject CSS into <head>
  function applyStyle(id, css){
    var node = document.getElementById(id);
    if (!css) {
      if (node && node.parentNode) node.parentNode.removeChild(node);
      return;
    }
    if (!node) {
      node = document.createElement('style');
      node.id = id;
      node.type = 'text/css';
      document.head.appendChild(node);
    }
    node.textContent = css;
  }

  // Live binders
  function bindFamily(){
    if (!(window.wp && wp.customize)) return;
    wp.customize('tr199x_header_font_family', function(value){
      value.bind(function(slug){
        applyStyle('tr199x-header-font-preview', slug ? buildFamilyCSS(slug) : '');
      });
    });
  }

  function currentTypoState(){
    return (window.TR199X_FONTMAP && TR199X_FONTMAP.typo) ? Object.assign({}, TR199X_FONTMAP.typo) : {};
  }

  function bindTypo(){
    if (!(window.wp && wp.customize)) return;
    var state = currentTypoState();

    function update(){
      applyStyle('tr199x-header-typo-preview', buildTypoCSS(state));
    }

    // Title
    wp.customize('tr199x_header_title_weight', function(v){ v.bind(function(x){ state.t_weight = +x; update(); }); });
    wp.customize('tr199x_header_title_letter_spacing', function(v){ v.bind(function(x){ state.t_ls = +x; update(); }); });
    wp.customize('tr199x_header_title_line_height', function(v){ v.bind(function(x){ state.t_lh = +x; update(); }); });
    wp.customize('tr199x_header_title_size_min', function(v){ v.bind(function(x){ state.t_min = +x; update(); }); });
    wp.customize('tr199x_header_title_size_vw', function(v){ v.bind(function(x){ state.t_vw = +x; update(); }); });
    wp.customize('tr199x_header_title_size_max', function(v){ v.bind(function(x){ state.t_max = +x; update(); }); });

    // Tagline
    wp.customize('tr199x_header_tagline_weight', function(v){ v.bind(function(x){ state.g_weight = +x; update(); }); });
    wp.customize('tr199x_header_tagline_letter_spacing', function(v){ v.bind(function(x){ state.g_ls = +x; update(); }); });
    wp.customize('tr199x_header_tagline_font_size_px', function(v){ v.bind(function(x){ state.g_fs_px = +x; update(); }); });

    // Initial apply
    update();
  }

  // Initial apply on load for current selections
  (function init(){
    if (window.TR199X_FONTMAP && TR199X_FONTMAP.selected) {
      applyStyle('tr199x-header-font-preview', buildFamilyCSS(TR199X_FONTMAP.selected));
    }
    if (window.TR199X_FONTMAP && TR199X_FONTMAP.typo) {
      applyStyle('tr199x-header-typo-preview', buildTypoCSS(TR199X_FONTMAP.typo));
    }
    bindFamily();
    bindTypo();
  })();
})();
