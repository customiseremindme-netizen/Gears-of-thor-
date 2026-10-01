/* GOT FITNEZZ — website behaviour (no external libraries). */
(function () {
  'use strict';

  var root = document.documentElement;
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  var desktop = window.matchMedia('(min-width: 1024px) and (pointer: fine)');
  window.gotReady = true;

  function $(selector, scope) { return (scope || document).querySelector(selector); }
  function $$(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }

  /* Hero entrance ---------------------------------------------------------- */
  var hero = $('[data-hero]');
  if (hero) {
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { hero.classList.add('is-ready'); });
    });
  }

  /* Header: transparent over the hero, solid after scrolling ---------------- */
  var header = $('[data-header]');
  if (header && !header.classList.contains('is-static')) {
    var onScrollHeader = function () {
      header.classList.toggle('is-solid', window.scrollY > 40);
    };
    onScrollHeader();
    window.addEventListener('scroll', onScrollHeader, { passive: true });
  }

  /* Mobile menu -------------------------------------------------------------- */
  var toggle = $('[data-menu-toggle]');
  var menu = $('[data-menu]');
  var outside = $$('main, footer, .mobile-bar, .skip-link');

  function setMenu(open, restoreFocus) {
    if (!toggle || !menu) return;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    menu.classList.toggle('is-open', open);
    if (open) {
      menu.removeAttribute('inert');
      outside.forEach(function (el) { el.setAttribute('inert', ''); });
      document.body.style.overflow = 'hidden';
      if (header) header.classList.add('is-solid');
      var first = $('a', menu);
      if (first) setTimeout(function () { first.focus(); }, 60);
    } else {
      menu.setAttribute('inert', '');
      outside.forEach(function (el) { el.removeAttribute('inert'); });
      document.body.style.overflow = '';
      if (header && !header.classList.contains('is-static')) header.classList.toggle('is-solid', window.scrollY > 40);
      if (restoreFocus) toggle.focus();
    }
    updateBar();
  }
  if (toggle && menu) {
    toggle.addEventListener('click', function () {
      setMenu(toggle.getAttribute('aria-expanded') !== 'true', true);
    });
    $$('[data-menu-link]', menu).forEach(function (link) {
      link.addEventListener('click', function () { setMenu(false, false); });
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') setMenu(false, true);
    });
    window.matchMedia('(min-width: 1100px)').addEventListener('change', function (mq) {
      if (mq.matches) setMenu(false, false);
    });
  }

  /* Highlight the menu link of the section in view --------------------------- */
  var navLinks = $$('[data-nav-link], [data-menu-link]').filter(function (a) {
    return (a.getAttribute('href') || '').charAt(0) === '#' && a.getAttribute('href').length > 1;
  });
  if ('IntersectionObserver' in window && navLinks.length) {
    var spy = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var id = '#' + entry.target.id;
        navLinks.forEach(function (a) {
          if (a.getAttribute('href') === id) a.setAttribute('aria-current', 'location');
          else a.removeAttribute('aria-current');
        });
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    $$('main section[id]').forEach(function (section) { spy.observe(section); });
  }

  /* Reveal sections as they scroll into view ----------------------------------- */
  var revealItems = $$('.reveal, .reveal-img');
  if ('IntersectionObserver' in window && !reduceMotion.matches) {
    var revealer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-in');
          revealer.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    revealItems.forEach(function (el) { revealer.observe(el); });
  } else {
    revealItems.forEach(function (el) { el.classList.add('is-in'); });
  }

  /* Gentle parallax on large screens --------------------------------------------- */
  var parallaxItems = $$('[data-parallax]');
  var ticking = false;
  function parallax() {
    ticking = false;
    var vh = window.innerHeight;
    parallaxItems.forEach(function (el) {
      var speed = parseFloat(el.getAttribute('data-parallax')) || 0;
      var box = (el.closest('[data-parallax-frame]') || el).getBoundingClientRect();
      if (box.bottom < -200 || box.top > vh + 200) return;
      var offset = (box.top + box.height / 2 - vh / 2) * speed;
      el.style.transform = 'translate3d(0,' + offset.toFixed(1) + 'px,0)';
    });
  }
  function requestParallax() {
    if (!ticking) { ticking = true; requestAnimationFrame(parallax); }
  }
  function setupParallax() {
    var on = desktop.matches && !reduceMotion.matches && parallaxItems.length;
    window.removeEventListener('scroll', requestParallax);
    if (on) {
      window.addEventListener('scroll', requestParallax, { passive: true });
      parallax();
    } else {
      parallaxItems.forEach(function (el) { el.style.transform = ''; });
    }
  }
  setupParallax();
  desktop.addEventListener('change', setupParallax);
  reduceMotion.addEventListener('change', setupParallax);

  /* Moving text strip: pause button, and pause while off screen ------------------ */
  $$('[data-marquee]').forEach(function (strip) {
    var button = $('[data-marquee-toggle]', strip);
    var userPaused = false;
    if (button) {
      button.addEventListener('click', function () {
        userPaused = !userPaused;
        button.setAttribute('aria-pressed', userPaused ? 'true' : 'false');
        strip.classList.toggle('is-paused', userPaused);
      });
    }
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        var visible = entries[0].isIntersecting;
        strip.classList.toggle('is-paused', userPaused || !visible);
      }).observe(strip);
    }
  });

  /* Hero video: large screens only, never with reduced motion or data saver ----- */
  var video = $('[data-hero-video]');
  if (video) {
    var saveData = navigator.connection && navigator.connection.saveData;
    var wide = window.matchMedia('(min-width: 900px)').matches;
    if (wide && !saveData && !reduceMotion.matches) {
      video.src = video.getAttribute('data-src');
      video.addEventListener('playing', function () { video.classList.add('is-playing'); });
      var play = video.play();
      if (play && play.catch) play.catch(function () { /* autoplay blocked: the photo stays */ });
    }
  }

  /* Opening hours: "Open now" in India Standard Time ------------------------------ */
  function nowInIndia() {
    try {
      var parts = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Kolkata', weekday: 'short', hour: '2-digit', minute: '2-digit', hourCycle: 'h23'
      }).formatToParts(new Date());
      var get = function (type) {
        for (var i = 0; i < parts.length; i++) if (parts[i].type === type) return parts[i].value;
        return '';
      };
      return { day: get('weekday').toLowerCase().slice(0, 3), minutes: parseInt(get('hour'), 10) * 60 + parseInt(get('minute'), 10) };
    } catch (e) { return null; }
  }
  function toMinutes(t) { var p = t.split(':'); return parseInt(p[0], 10) * 60 + parseInt(p[1], 10); }
  function format12(t) {
    var p = t.split(':'); var h = parseInt(p[0], 10); var suffix = h >= 12 ? 'PM' : 'AM';
    var h12 = h % 12 === 0 ? 12 : h % 12;
    return h12 + ':' + p[1] + ' ' + suffix;
  }
  var dayOrder = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
  var dayNames = { mon: 'Monday', tue: 'Tuesday', wed: 'Wednesday', thu: 'Thursday', fri: 'Friday', sat: 'Saturday', sun: 'Sunday' };

  function openStatus(days) {
    var now = nowInIndia();
    if (!now || dayOrder.indexOf(now.day) === -1) return null;
    var today = days[now.day] || [];
    for (var i = 0; i < today.length; i++) {
      var open = toMinutes(today[i].open); var close = toMinutes(today[i].close);
      if (now.minutes >= open && now.minutes < close) return { open: true, text: 'Open now · until ' + format12(today[i].close) };
      if (now.minutes < open) return { open: false, text: 'Closed now · opens ' + format12(today[i].open) };
    }
    for (var d = 1; d <= 7; d++) {
      var key = dayOrder[(dayOrder.indexOf(now.day) + d) % 7];
      var sessions = days[key] || [];
      if (sessions.length) {
        return { open: false, text: 'Closed now · opens ' + (d === 1 ? 'tomorrow' : dayNames[key]) + ' ' + format12(sessions[0].open) };
      }
    }
    return null;
  }
  function updateStatus() {
    $$('[data-open-status]').forEach(function (el) {
      var days;
      try { days = JSON.parse(el.getAttribute('data-hours') || '{}'); } catch (e) { return; }
      var status = openStatus(days);
      if (!status) return;
      var target = $('[data-today-text]', el) || el;
      target.textContent = status.text;
      el.classList.toggle('is-open', status.open);
      el.hidden = false;
    });
  }
  updateStatus();
  setInterval(updateStatus, 60000);

  /* Photo lightbox ---------------------------------------------------------------- */
  var dialog = $('[data-lightbox]');
  var galleryButtons = $$('[data-lightbox-item]');
  if (dialog && galleryButtons.length && typeof dialog.showModal === 'function') {
    var img = $('[data-lightbox-img]', dialog);
    var caption = $('[data-lightbox-caption]', dialog);
    var counter = $('[data-lightbox-count]', dialog);
    var current = 0;
    var opener = null;
    var touchX = null;

    var show = function (index) {
      current = (index + galleryButtons.length) % galleryButtons.length;
      var button = galleryButtons[current];
      img.removeAttribute('srcset');
      img.src = button.getAttribute('data-full');
      if (button.getAttribute('data-srcset')) {
        img.setAttribute('srcset', button.getAttribute('data-srcset'));
        img.setAttribute('sizes', '100vw');
      }
      img.alt = button.getAttribute('data-alt') || '';
      caption.textContent = button.getAttribute('data-caption') || '';
      caption.hidden = !caption.textContent;
      counter.textContent = (current + 1) + ' / ' + galleryButtons.length;
    };
    galleryButtons.forEach(function (button, index) {
      button.addEventListener('click', function () {
        opener = button;
        show(index);
        dialog.showModal();
        document.body.style.overflow = 'hidden';
      });
    });
    var multiple = galleryButtons.length > 1;
    $('[data-lightbox-prev]', dialog).hidden = !multiple;
    $('[data-lightbox-next]', dialog).hidden = !multiple;
    $('[data-lightbox-prev]', dialog).addEventListener('click', function () { show(current - 1); });
    $('[data-lightbox-next]', dialog).addEventListener('click', function () { show(current + 1); });
    $('[data-lightbox-close]', dialog).addEventListener('click', function () { dialog.close(); });
    dialog.addEventListener('close', function () {
      document.body.style.overflow = '';
      if (opener) opener.focus();
    });
    dialog.addEventListener('click', function (event) {
      if (event.target === dialog || event.target.classList.contains('lightbox__frame')) dialog.close();
    });
    dialog.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') { event.preventDefault(); show(current - 1); }
      if (event.key === 'ArrowRight') { event.preventDefault(); show(current + 1); }
    });
    dialog.addEventListener('touchstart', function (event) { touchX = event.touches[0].clientX; }, { passive: true });
    dialog.addEventListener('touchend', function (event) {
      if (touchX === null) return;
      var dx = event.changedTouches[0].clientX - touchX;
      if (Math.abs(dx) > 50 && multiple) show(current + (dx < 0 ? 1 : -1));
      touchX = null;
    });
  }

  /* Mobile action bar ---------------------------------------------------------------- */
  var bar = $('[data-mobile-bar]');
  var formArea = $('#enquire');
  var formInView = false;
  function updateBar() {
    if (!bar) return;
    var menuOpen = toggle && toggle.getAttribute('aria-expanded') === 'true';
    var pastHero = hero ? window.scrollY > hero.offsetHeight * 0.55 : window.scrollY > 200;
    bar.classList.toggle('is-visible', pastHero && !formInView && !menuOpen);
  }
  if (bar) {
    window.addEventListener('scroll', updateBar, { passive: true });
    if (formArea && 'IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        formInView = entries[0].isIntersecting;
        updateBar();
      }, { threshold: 0.12 }).observe(formArea);
    }
    updateBar();
  }

  /* Enquiry form ----------------------------------------------------------------------- */
  var form = $('[data-enquiry-form]');
  if (!form) return;

  var success = $('[data-form-success]');
  var alertBox = $('[data-form-alert]', form);
  var alertText = $('[data-form-alert-text]', form);
  var retry = $('[data-form-retry]', form);
  var submit = $('[data-submit]', form);
  var tokenInput = $('[data-form-token]', form);
  var sending = false;

  var rules = {
    name: function (v) {
      v = v.trim();
      if (!v) return 'Please enter your name.';
      if (v.length < 2 || v.length > 80) return 'Please enter a name between 2 and 80 characters.';
      return '';
    },
    phone: function (v) {
      var compact = v.replace(/[\s\-().]/g, '');
      if (!compact) return 'Please enter your mobile number.';
      if (/^(?:\+?91|0)?[6-9]\d{9}$/.test(compact)) return '';
      if (/^(?:\+|00)[1-9]\d{7,14}$/.test(compact) && !/^(?:\+|00)91/.test(compact)) return '';
      return 'Please enter a valid 10-digit mobile number.';
    },
    goal: function (v) { return v ? '' : 'Please choose your fitness goal.'; },
    time: function (v) { return v ? '' : 'Please choose a time that suits you.'; },
    message: function (v) { return v.length > 1000 ? 'Please keep your message under 1,000 characters.' : ''; },
    consent: function (v, el) { return el.checked ? '' : 'Please tick the box so the team can contact you about your enquiry.'; }
  };

  function setError(name, message) {
    var input = form.elements[name];
    var error = $('[data-error-for="' + name + '"]', form);
    if (!input || !error) return;
    var wrap = input.closest('.field');
    error.textContent = message || '';
    error.hidden = !message;
    if (wrap) wrap.classList.toggle('has-error', !!message);
    if (message) input.setAttribute('aria-invalid', 'true');
    else input.removeAttribute('aria-invalid');
  }

  function validate() {
    var firstInvalid = null;
    Object.keys(rules).forEach(function (name) {
      var input = form.elements[name];
      if (!input) return;
      var message = rules[name](input.value || '', input);
      setError(name, message);
      if (message && !firstInvalid) firstInvalid = input;
    });
    return firstInvalid;
  }

  function showAlert(message, canRetry) {
    alertText.textContent = message;
    retry.hidden = !canRetry;
    alertBox.hidden = false;
    alertBox.scrollIntoView({ block: 'center', behavior: reduceMotion.matches ? 'auto' : 'smooth' });
  }

  function setLoading(on) {
    sending = on;
    submit.classList.toggle('is-loading', on);
    submit.setAttribute('aria-busy', on ? 'true' : 'false');
    submit.disabled = on;
  }

  function resetTurnstile() {
    if (window.turnstile && typeof window.turnstile.reset === 'function') {
      try { window.turnstile.reset(); } catch (e) { /* ignore */ }
    }
  }

  function send(isRetry) {
    setLoading(true);
    var data = new FormData(form);
    return fetch(form.getAttribute('action').split('#')[0], {
      method: 'POST',
      body: data,
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' },
      credentials: 'same-origin'
    }).then(function (response) {
      return response.json().catch(function () { return { ok: false, code: 'server' }; });
    }).then(function (result) {
      if (result.token && tokenInput) tokenInput.value = result.token;
      if (result.ok) {
        form.reset();
        form.hidden = true;
        alertBox.hidden = true;
        if (result.message) $('[data-success-message]', success).textContent = result.message;
        success.hidden = false;
        success.focus();
        return;
      }
      if ((result.code === 'expired' || result.code === 'invalid_token') && !isRetry) {
        return send(true);
      }
      resetTurnstile();
      if (result.code === 'validation' && result.errors) {
        var first = null;
        Object.keys(result.errors).forEach(function (name) {
          setError(name, result.errors[name]);
          if (!first && form.elements[name]) first = form.elements[name];
        });
        showAlert(result.message || 'Please check the highlighted fields.', false);
        if (first) first.focus();
        return;
      }
      showAlert(result.message || 'Sorry — something went wrong. Your details are still here, so please try again.', true);
    }).catch(function () {
      resetTurnstile();
      showAlert('We couldn’t send your enquiry. Please check your internet connection — your details are still here — and try again.', true);
    }).then(function () {
      setLoading(false);
    });
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (sending) return;
    alertBox.hidden = true;
    if (form.getAttribute('data-preview')) {
      showAlert('Sending is switched off in preview. The form works on the live website.', false);
      return;
    }
    var invalid = validate();
    if (invalid) {
      invalid.focus();
      return;
    }
    send(false);
  });

  Object.keys(rules).forEach(function (name) {
    var input = form.elements[name];
    if (!input) return;
    var evt = input.type === 'checkbox' || input.tagName === 'SELECT' ? 'change' : 'input';
    input.addEventListener(evt, function () {
      if (input.getAttribute('aria-invalid') === 'true') setError(name, rules[name](input.value || '', input));
    });
  });

  var again = $('[data-form-again]');
  if (again) {
    again.addEventListener('click', function (event) {
      event.preventDefault();
      success.hidden = true;
      form.hidden = false;
      if (form.elements.name) form.elements.name.focus();
    });
  }

  // "Ask about this plan" buttons fill in the message for the visitor.
  $$('[data-enquire-plan]').forEach(function (link) {
    link.addEventListener('click', function () {
      var message = form.elements.message;
      if (message && !message.value.trim()) {
        message.value = 'I would like to know more about the ' + link.getAttribute('data-enquire-plan') + ' plan.';
      }
    });
  });
})();
