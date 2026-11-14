/* Shared navbar and footer. Edit them here once and every page updates.
   Each page needs <div id="fs-navbar"></div> and <div id="fs-footer"></div>. */
(function () {
  // Change a link here when a page gets built (e.g. login: 'login.html').
  var links = {
    home: '../../Resources/Views/home.html',
    login: '#',
    register: '#',
    nav: [
      { label: 'How it works', href: '../../Resources/Views/home.html#steps' },
      { label: 'Features', href: '../../Resources/Views/home.html#features' },
      { label: 'Roles', href: '../../Resources/Views/home.html#roles' },
      { label: 'Evidence Vault', href: '../../public/index.php?page=evidence' }
    ]
  };

  var navItems = links.nav.map(function (n) {
    return '<li class="nav-item"><a class="nav-link" href="' + n.href + '">' + n.label + '</a></li>';
  }).join('');

  var navbar =
    '<header class="fs-nav"><nav class="container navbar navbar-expand-lg p-0" aria-label="Main">' +
    '<a class="fs-brand" href="' + links.home + '"><span class="fs-mark" aria-hidden="true"></span>FairShare</a>' +
    '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#fsMenu" aria-controls="fsMenu" aria-expanded="false" aria-label="Toggle menu"><span class="navbar-toggler-icon"></span></button>' +
    '<div class="collapse navbar-collapse justify-content-end" id="fsMenu">' +
    '<ul class="navbar-nav">' + navItems + '</ul>' +
    '<div class="fs-nav-actions">' +
    '<a class="fs-btn fs-btn-ghost fs-btn-sm" href="' + links.login + '">Sign in</a>' +
    '<a class="fs-btn fs-btn-primary fs-btn-sm" href="' + links.register + '">Create account</a>' +
    '</div></div></nav></header>';

  var footer =
    '<footer class="fs-footer"><div class="container">' +
    '<span><strong>FairShare</strong>, group project contribution and fairness tracker</span>' +
    '<span>CSE470 project &middot; <a href="' + links.login + '">Sign in</a></span>' +
    '</div></footer>';

  var nav = document.getElementById('fs-navbar');
  var foot = document.getElementById('fs-footer');
  if (nav) nav.innerHTML = navbar;
  if (foot) foot.innerHTML = footer;
})();
