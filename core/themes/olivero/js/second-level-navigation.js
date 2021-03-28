/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function (Drupal) {
  var isDesktopNav = Drupal.olivero.isDesktopNav;
  var secondLevelNavMenus = document.querySelectorAll('.primary-nav__menu-item--has-children');

  function toggleSubNav(topLevelMenuITem, toState) {
    var buttonSelector = '.primary-nav__button-toggle, .primary-nav__menu-link--button';
    var button = topLevelMenuITem.querySelector(buttonSelector);
    var state = toState !== undefined ? toState : button.getAttribute('aria-expanded') !== 'true';

    if (state) {
      if (isDesktopNav()) {
        secondLevelNavMenus.forEach(function (el) {
          el.querySelector(buttonSelector).setAttribute('aria-expanded', 'false');
          el.querySelector('.primary-nav__menu--level-2').classList.remove('is-active');
        });
      }

      button.setAttribute('aria-expanded', 'true');
      topLevelMenuITem.querySelector('.primary-nav__menu--level-2').classList.add('is-active');
    } else {
      button.setAttribute('aria-expanded', 'false');
      topLevelMenuITem.classList.remove('is-touch-event');
      topLevelMenuITem.querySelector('.primary-nav__menu--level-2').classList.remove('is-active');
    }
  }

  Drupal.olivero.toggleSubNav = toggleSubNav;
  secondLevelNavMenus.forEach(function (el) {
    var button = el.querySelector('.primary-nav__button-toggle, .primary-nav__menu-link--button');
    button.removeAttribute('aria-hidden');
    button.removeAttribute('tabindex');
    el.addEventListener('touchstart', function () {
      el.classList.add('is-touch-event');
    }, {
      passive: true
    });
    el.addEventListener('mouseover', function () {
      if (isDesktopNav() && !el.classList.contains('is-touch-event')) {
        el.classList.add('is-active-mouseover-event');
        toggleSubNav(el, true);
        setTimeout(function () {
          el.classList.remove('is-active-mouseover-event');
        }, 500);
      }
    });
    button.addEventListener('click', function () {
      if (!el.classList.contains('is-active-mouseover-event')) {
        toggleSubNav(el);
      }
    });
    el.addEventListener('mouseout', function () {
      if (isDesktopNav()) {
        toggleSubNav(el, false);
      }
    });
  });

  function closeAllSubNav() {
    secondLevelNavMenus.forEach(function (el) {
      toggleSubNav(el, false);
    });
  }

  Drupal.olivero.closeAllSubNav = closeAllSubNav;

  function areAnySubNavsOpen() {
    var subNavsAreOpen = false;
    secondLevelNavMenus.forEach(function (el) {
      var button = el.querySelector('.primary-nav__button-toggle, .primary-nav__menu-link--button');
      var state = button.getAttribute('aria-expanded') === 'true';

      if (state) {
        subNavsAreOpen = true;
      }
    });
    return subNavsAreOpen;
  }

  Drupal.olivero.areAnySubNavsOpen = areAnySubNavsOpen;
  document.addEventListener('keyup', function (e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
      if (isDesktopNav()) closeAllSubNav();
    }
  });
  document.addEventListener('touchstart', function (e) {
    if (areAnySubNavsOpen() && !e.target.matches('.primary-nav__menu-item--has-children, .primary-nav__menu-item--has-children *')) {
      closeAllSubNav();
    }
  }, {
    passive: true
  });
})(Drupal);