/* GOT FITNEZZ dashboard behaviour. Everything still works (more simply) without JavaScript. */
(function () {
  'use strict';

  function $(selector, scope) { return (scope || document).querySelector(selector); }
  function $$(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }
  var csrfMeta = $('meta[name="csrf-token"]');
  var csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';
  var base = (function () {
    var link = $('a[href$="/admin"]');
    var href = link ? link.getAttribute('href') : '/admin';
    return href.replace(/\/admin$/, '');
  })();

  function post(url, body) {
    return fetch(base + url, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch', 'X-CSRF-Token': csrf }
    }).then(function (r) {
      return r.json().catch(function () { return { ok: false, errors: ['The server returned an unexpected answer (' + r.status + ').'] }; });
    });
  }

  /* Sidebar on small screens --------------------------------------------- */
  var sidebar = $('[data-sidebar]');
  var sidebarToggle = $('[data-sidebar-toggle]');
  if (sidebar && sidebarToggle) {
    sidebarToggle.addEventListener('click', function () {
      var open = !sidebar.classList.contains('is-open');
      sidebar.classList.toggle('is-open', open);
      sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) { var first = $('a', sidebar); if (first) first.focus(); }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
        sidebar.classList.remove('is-open');
        sidebarToggle.setAttribute('aria-expanded', 'false');
        sidebarToggle.focus();
      }
    });
  }

  /* Confirmations ----------------------------------------------------------- */
  $$('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  /* Status dropdowns save immediately --------------------------------------- */
  $$('form[data-autosubmit]').forEach(function (form) {
    var button = $('[data-autosubmit-button]', form);
    if (button) button.hidden = true;
    $$('select', form).forEach(function (select) {
      select.addEventListener('change', function () { form.submit(); });
    });
  });

  /* Warn before leaving with unsaved changes --------------------------------- */
  var dirty = false;
  $$('form[data-dirty-warn]').forEach(function (form) {
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('change', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
  });
  window.addEventListener('beforeunload', function (e) {
    if (dirty) { e.preventDefault(); e.returnValue = ''; }
  });
  function markDirty() { dirty = true; }

  /* Character counters --------------------------------------------------------- */
  $$('[data-counter]').forEach(function (input) {
    var limit = parseInt(input.getAttribute('data-counter'), 10);
    var out = document.createElement('span');
    out.className = 'char-count';
    out.setAttribute('aria-live', 'polite');
    input.insertAdjacentElement('afterend', out);
    var update = function () {
      var n = input.value.length;
      out.textContent = n + ' characters' + (n > limit ? ' — a little long, aim for ' + limit : '');
      out.classList.toggle('is-over', n > limit);
    };
    input.addEventListener('input', update);
    update();
  });

  /* Media picker modal ------------------------------------------------------------ */
  var modal = $('[data-media-modal]');
  var modalGrid = modal ? $('[data-picker-grid]', modal) : null;
  var modalCallback = null;
  var modalReturn = null;

  function closeModal() {
    if (!modal) return;
    modal.hidden = true;
    modalCallback = null;
    if (modalReturn) modalReturn.focus();
  }
  function openPicker(kind, onPick, returnFocus) {
    if (!modal) return;
    modalCallback = onPick;
    modalReturn = returnFocus;
    modalGrid.innerHTML = '<p class="muted">Loading…</p>';
    modal.hidden = false;
    $('[data-modal-close]', modal).focus();
    fetch(base + '/admin/media/list?kind=' + encodeURIComponent(kind), {
      credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' }
    }).then(function (r) { return r.json(); }).then(function (data) {
      modalGrid.innerHTML = '';
      if (!data.items || !data.items.length) {
        modalGrid.innerHTML = '<p class="muted">Nothing in the library yet. Close this window and use “Upload new”.</p>';
        return;
      }
      data.items.forEach(function (item) {
        var btn = document.createElement('button');
        btn.type = 'button';
        if (item.thumb) {
          var img = document.createElement('img');
          img.src = item.thumb; img.alt = ''; img.loading = 'lazy';
          btn.appendChild(img);
        } else {
          var vid = document.createElement('span');
          vid.textContent = '▶ Video';
          btn.appendChild(vid);
        }
        var name = document.createElement('span');
        name.textContent = item.name;
        btn.appendChild(name);
        btn.setAttribute('aria-label', 'Use ' + item.name);
        btn.addEventListener('click', function () {
          var cb = modalCallback;
          closeModal();
          if (cb) cb(item);
        });
        modalGrid.appendChild(btn);
      });
    }).catch(function () {
      modalGrid.innerHTML = '<p class="muted">The library could not be loaded. Please try again.</p>';
    });
  }
  if (modal) {
    $('[data-modal-close]', modal).addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeModal(); });
  }

  /* Photo / video fields ------------------------------------------------------------ */
  function setMedia(field, item) {
    var input = $('[data-media-input]', field);
    var preview = $('[data-media-preview]', field);
    var meta = $('[data-media-meta]', field);
    var clear = $('[data-media-clear]', field);
    input.value = item ? item.id : '';
    preview.innerHTML = '';
    if (item) {
      if (item.kind === 'video') {
        var v = document.createElement('video');
        v.src = item.url; v.muted = true; v.playsInline = true; v.preload = 'metadata';
        preview.appendChild(v);
      } else {
        var img = document.createElement('img');
        img.src = item.thumb; img.alt = '';
        preview.appendChild(img);
      }
      meta.textContent = item.name + (item.width ? ' · ' + item.width + '×' + item.height : '') + (item.size ? ' · ' + item.size : '') + (item.kind === 'image' && !item.alt ? ' · no description yet' : '');
    } else {
      var empty = document.createElement('span');
      empty.className = 'media-field__empty';
      empty.textContent = field.getAttribute('data-kind') === 'video' ? 'No video chosen' : 'No photo chosen';
      preview.appendChild(empty);
      meta.textContent = '';
    }
    if (clear) clear.hidden = !item;
    markDirty();
    updateItemThumb(field);
  }

  function uploadFiles(files, onEach, statusEl) {
    var list = Array.prototype.slice.call(files);
    var done = 0;
    var failed = [];
    if (statusEl) { statusEl.classList.remove('is-error'); statusEl.textContent = 'Uploading ' + list.length + ' file' + (list.length === 1 ? '' : 's') + '…'; }
    var next = function () {
      if (!list.length) {
        if (statusEl) {
          statusEl.textContent = failed.length ? failed.join(' ') : (done === 1 ? 'Uploaded.' : done + ' files uploaded.');
          statusEl.classList.toggle('is-error', failed.length > 0);
        }
        return Promise.resolve();
      }
      var file = list.shift();
      var body = new FormData();
      body.append('file', file);
      body.append('_csrf', csrf);
      return post('/admin/media/upload', body).then(function (result) {
        (result.items || []).forEach(function (item) { done++; onEach(item); });
        (result.errors || []).forEach(function (err) { failed.push(err); });
        if (statusEl && list.length) statusEl.textContent = 'Uploaded ' + done + '… ' + list.length + ' to go.';
      }).catch(function () {
        failed.push(file.name + ': upload failed. Check your connection.');
      }).then(next);
    };
    return next();
  }

  function bindMediaField(field) {
    if (field.getAttribute('data-bound')) return;
    field.setAttribute('data-bound', '1');
    var kind = field.getAttribute('data-kind') || 'image';
    var status = $('[data-media-status]', field);
    var choose = $('[data-media-choose]', field);
    choose.addEventListener('click', function () {
      openPicker(kind, function (item) { setMedia(field, item); }, choose);
    });
    $('[data-media-upload]', field).addEventListener('change', function (e) {
      if (!e.target.files.length) return;
      uploadFiles([e.target.files[0]], function (item) { setMedia(field, item); }, status);
      e.target.value = '';
    });
    var clear = $('[data-media-clear]', field);
    if (clear) clear.addEventListener('click', function () { setMedia(field, null); });
  }
  $$('[data-media-field]').forEach(bindMediaField);

  /* Repeatable lists (training options, photos, FAQs…) ------------------------------- */
  function refreshList(list) {
    var items = $$(':scope > [data-list-items] > [data-list-item]', list);
    var empty = $('[data-list-empty]', list);
    if (empty) empty.hidden = items.length > 0;
    var max = parseInt(list.getAttribute('data-max') || '50', 10);
    var add = $('[data-list-add]', list);
    if (add) add.disabled = items.length >= max;
  }

  function updateItemTitle(item) {
    var title = $('[data-item-title]', item);
    if (!title) return;
    var key = title.getAttribute('data-title-field');
    var source = key ? $('[name$="[' + key + ']"]', item) : null;
    var value = source && source.value ? source.value.trim() : '';
    title.textContent = value || title.getAttribute('data-fallback');
  }

  function updateItemBadge(item) {
    var badge = $('[data-item-badge]', item);
    var toggle = $('[name$="[visible]"]', item);
    if (!badge || !toggle) return;
    badge.textContent = toggle.checked ? 'Shown' : 'Hidden';
    item.classList.toggle('is-hidden-item', !toggle.checked);
  }

  function updateItemThumb(node) {
    var item = node.closest ? node.closest('[data-list-item]') : null;
    if (!item) return;
    var head = $('.list-item__head', item);
    var firstImage = $('[data-media-field][data-kind="image"] [data-media-preview] img', item);
    var thumb = $('.list-item__thumb', head);
    if (firstImage) {
      if (!thumb) {
        thumb = document.createElement('img');
        thumb.className = 'list-item__thumb';
        thumb.alt = '';
        head.insertBefore(thumb, head.firstChild);
      }
      thumb.src = firstImage.src;
    } else if (thumb) {
      thumb.remove();
    }
  }

  function bindItem(item) {
    var toggle = $('[data-item-toggle]', item);
    var body = $('.list-item__body', item);
    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') !== 'true';
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      body.hidden = !open;
    });
    $$('[data-move]', item).forEach(function (btn) {
      if (btn.closest('[data-list-item]') !== item) return;
      btn.addEventListener('click', function () {
        var up = btn.getAttribute('data-move') === 'up';
        var sibling = up ? item.previousElementSibling : item.nextElementSibling;
        if (!sibling) return;
        if (up) item.parentNode.insertBefore(item, sibling);
        else item.parentNode.insertBefore(sibling, item);
        btn.focus();
        markDirty();
      });
    });
    var remove = $('[data-remove]', item);
    remove.addEventListener('click', function () {
      var hasContent = $$('input[type="text"], textarea', item).some(function (i) { return i.value.trim() !== ''; })
        || $$('[data-media-input]', item).some(function (i) { return i.value !== ''; });
      if (hasContent && !window.confirm('Remove this item? (It is only removed from the website after you save and publish.)')) return;
      var list = item.closest('[data-list]');
      item.remove();
      refreshList(list);
      markDirty();
    });
    var titleEl = $('[data-item-title]', item);
    var titleKey = titleEl ? titleEl.getAttribute('data-title-field') : '';
    item.addEventListener('input', function (e) {
      var name = e.target.name || '';
      if (titleKey && name.slice(-(titleKey.length + 2)) === '[' + titleKey + ']') updateItemTitle(item);
    });
    item.addEventListener('change', function (e) {
      if (e.target.matches('[name$="[visible]"]')) updateItemBadge(item);
    });
    $$('[data-media-field]', item).forEach(bindMediaField);
    bindHours(item);
  }

  var keyCounter = 0;
  function addItem(list, prefill) {
    var template = $('template[data-list-template]', list);
    var key = 'n' + Date.now().toString(36) + (keyCounter++);
    var html = template.innerHTML.replace(/KEYPLACEHOLDER/g, key);
    var wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    var item = wrap.firstElementChild;
    $('[data-list-items]', list).appendChild(item);
    bindItem(item);
    refreshList(list);
    markDirty();
    if (prefill) prefill(item);
    return item;
  }

  $$('[data-list]').forEach(function (list) {
    $$(':scope > [data-list-items] > [data-list-item]', list).forEach(bindItem);
    var add = $('[data-list-add]', list);
    if (add) {
      add.addEventListener('click', function () {
        var item = addItem(list);
        var firstInput = $('input[type="text"], textarea, [data-media-choose]', item);
        if (firstInput) firstInput.focus();
      });
    }
    var bulk = $('[data-bulk-upload]', list);
    if (bulk) {
      bulk.addEventListener('change', function (e) {
        var files = e.target.files;
        if (!files.length) return;
        var max = parseInt(list.getAttribute('data-max') || '50', 10);
        uploadFiles(files, function (media) {
          if ($$(':scope > [data-list-items] > [data-list-item]', list).length >= max) return;
          addItem(list, function (item) {
            var field = $('[data-media-field][data-kind="image"]', item);
            if (field) setMedia(field, media);
            var toggle = $('[data-item-toggle]', item);
            toggle.setAttribute('aria-expanded', 'false');
            $('.list-item__body', item).hidden = true;
          });
        }, $('[data-bulk-status]', list));
        e.target.value = '';
      });
    }
    refreshList(list);
  });

  /* Opening hours ------------------------------------------------------------------------ */
  function bindHours(scope) {
    $$('[data-hours-field]', scope).forEach(function (field) {
      if (field.getAttribute('data-bound')) return;
      field.setAttribute('data-bound', '1');
      $$('[data-hours-more]', field).forEach(function (btn) {
        var row = btn.closest('.hours-row');
        var update = function () { btn.hidden = !$('[data-extra][hidden]', row); };
        btn.addEventListener('click', function () {
          var next = $('[data-extra][hidden]', row);
          if (next) { next.hidden = false; var input = $('input', next); if (input) input.focus(); }
          update();
        });
        update();
      });
      var copy = $('[data-hours-copy]', field);
      if (copy) {
        copy.addEventListener('click', function () {
          var monday = $$('.hours-row[data-day="mon"] .hours-session', field);
          ['tue', 'wed', 'thu', 'fri', 'sat'].forEach(function (day) {
            var sessions = $$('.hours-row[data-day="' + day + '"] .hours-session', field);
            monday.forEach(function (src, i) {
              var inputs = $$('input', src);
              var target = $$('input', sessions[i]);
              target[0].value = inputs[0].value;
              target[1].value = inputs[1].value;
              sessions[i].hidden = src.hidden && !inputs[0].value && !inputs[1].value;
            });
          });
          markDirty();
          copy.textContent = 'Copied — remember to save';
        });
      }
    });
  }
  bindHours(document);

  /* Colours and contrast --------------------------------------------------------------------- */
  function hexToRgb(hex) {
    var m = /^#?([0-9a-f]{6})$/i.exec(hex || '');
    if (!m) return null;
    var n = parseInt(m[1], 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
  }
  function luminance(rgb) {
    var c = rgb.map(function (v) { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
  }
  function contrast(a, b) {
    var x = hexToRgb(a), y = hexToRgb(b);
    if (!x || !y) return null;
    var l1 = luminance(x), l2 = luminance(y);
    return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
  }
  var contrastBox = $('[data-contrast-check]');
  function colourOf(key) {
    var input = $('[name="f[theme][' + key + ']"]');
    return input ? input.value : '';
  }
  function checkContrast() {
    if (!contrastBox) return;
    var pairs = [
      ['Main text on page background', 'text', 'bg', 4.5],
      ['Secondary text on page background', 'muted', 'bg', 4.5],
      ['Main text on panels', 'text', 'surface', 4.5],
      ['Button text on accent', 'accent_text', 'accent', 4.5],
      ['Accent on page background', 'accent', 'bg', 3]
    ];
    contrastBox.innerHTML = '<strong>Readability check</strong>';
    pairs.forEach(function (p) {
      var ratio = contrast(colourOf(p[1]), colourOf(p[2]));
      if (ratio === null) return;
      var row = document.createElement('div');
      row.className = 'contrast-check__row';
      var sw = document.createElement('span');
      sw.className = 'contrast-check__swatch';
      sw.style.background = colourOf(p[2]);
      sw.style.color = colourOf(p[1]);
      sw.textContent = 'Aa';
      var label = document.createElement('span');
      label.textContent = p[0] + ': ' + ratio.toFixed(1) + ':1 ';
      var verdict = document.createElement('span');
      verdict.className = ratio >= p[3] ? 'good' : 'bad';
      verdict.textContent = ratio >= p[3] ? 'Good' : 'Too low — hard to read';
      row.appendChild(sw); row.appendChild(label); row.appendChild(verdict);
      contrastBox.appendChild(row);
    });
  }
  $$('[data-color-field]').forEach(function (field) {
    var picker = $('[data-color-picker]', field);
    var text = $('[data-color-text]', field);
    var reset = $('[data-color-reset]', field);
    picker.addEventListener('input', function () { text.value = picker.value.toUpperCase(); markDirty(); checkContrast(); });
    text.addEventListener('input', function () {
      var v = text.value.trim();
      if (v && v[0] !== '#') v = '#' + v;
      if (/^#[0-9a-f]{6}$/i.test(v)) { picker.value = v; checkContrast(); }
    });
    if (reset) reset.addEventListener('click', function () {
      text.value = reset.getAttribute('data-color-reset');
      picker.value = text.value;
      markDirty();
      checkContrast();
    });
  });
  checkContrast();

  /* Section order: drag and drop, or arrow buttons --------------------------------------------- */
  $$('[data-sortable]').forEach(function (listEl) {
    var dragged = null;
    $$('[data-sortable-item]', listEl).forEach(function (row) {
      if (row.hasAttribute('data-fixed')) return;
      row.setAttribute('draggable', 'true');
      row.addEventListener('dragstart', function (e) {
        dragged = row;
        row.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', ''); } catch (err) { /* Firefox needs data */ }
      });
      row.addEventListener('dragend', function () {
        row.classList.remove('is-dragging');
        $$('.is-drop-target', listEl).forEach(function (r) { r.classList.remove('is-drop-target'); });
        dragged = null;
      });
      row.addEventListener('dragover', function (e) {
        if (!dragged || dragged === row) return;
        e.preventDefault();
        row.classList.add('is-drop-target');
      });
      row.addEventListener('dragleave', function () { row.classList.remove('is-drop-target'); });
      row.addEventListener('drop', function (e) {
        e.preventDefault();
        row.classList.remove('is-drop-target');
        if (!dragged || dragged === row) return;
        var rows = $$('[data-sortable-item]', listEl);
        if (rows.indexOf(dragged) < rows.indexOf(row)) listEl.insertBefore(dragged, row.nextSibling);
        else listEl.insertBefore(dragged, row);
        markDirty();
      });
      $$('[data-move]', row).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var up = btn.getAttribute('data-move') === 'up';
          var sibling = up ? row.previousElementSibling : row.nextElementSibling;
          if (!sibling || (up && sibling.hasAttribute('data-fixed'))) return;
          if (up) listEl.insertBefore(row, sibling);
          else listEl.insertBefore(sibling, row);
          btn.focus();
          markDirty();
        });
      });
    });
  });

  /* Preview screen sizes: the page is drawn at real device width, then scaled to fit ---- */
  var stage = $('[data-preview-stage]');
  if (stage) {
    var box = $('[data-preview-box]', stage);
    var frame = $('[data-preview-frame]', stage);
    var widths = { desktop: 1366, tablet: 820, phone: 390 };
    var device = stage.getAttribute('data-device-current') || 'desktop';
    var fit = function () {
      var target = widths[device];
      var available = box.clientWidth;
      var scale = Math.min(1, available / target);
      frame.style.width = target + 'px';
      frame.style.height = (box.clientHeight / scale) + 'px';
      frame.style.transform = scale < 1 ? 'scale(' + scale + ')' : '';
    };
    $$('[data-device]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        device = btn.getAttribute('data-device');
        stage.className = 'preview-stage preview-stage--' + device;
        $$('[data-device]').forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
        fit();
      });
    });
    window.addEventListener('resize', fit);
    fit();
  }

  /* Media library drag-and-drop upload ----------------------------------------------------------------- */
  var dropzone = $('[data-dropzone]');
  if (dropzone) {
    var input = $('[data-dropzone-input]', dropzone);
    var listEl = $('[data-upload-list]', dropzone);
    var run = function (files) {
      if (!files || !files.length) return;
      listEl.innerHTML = '';
      var rows = Array.prototype.map.call(files, function (file) {
        var li = document.createElement('li');
        li.innerHTML = '<span></span><span>Waiting…</span>';
        li.firstChild.textContent = file.name;
        listEl.appendChild(li);
        return li;
      });
      var i = 0;
      var anyDone = false;
      var next = function () {
        if (i >= files.length) {
          if (anyDone) setTimeout(function () { window.location.reload(); }, 900);
          return;
        }
        var file = files[i];
        var row = rows[i];
        i++;
        row.lastChild.textContent = 'Uploading…';
        var body = new FormData();
        body.append('file', file);
        body.append('_csrf', csrf);
        post('/admin/media/upload', body).then(function (result) {
          if (result.items && result.items.length) {
            anyDone = true;
            row.lastChild.textContent = 'Done';
            row.className = 'is-done';
          } else {
            row.lastChild.textContent = (result.errors && result.errors[0]) || 'Upload failed';
            row.className = 'is-error';
          }
        }).catch(function () {
          row.lastChild.textContent = 'Upload failed — check your connection';
          row.className = 'is-error';
        }).then(next);
      };
      next();
    };
    input.addEventListener('change', function () { run(input.files); });
    ['dragenter', 'dragover'].forEach(function (type) {
      dropzone.addEventListener(type, function (e) { e.preventDefault(); dropzone.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (type) {
      dropzone.addEventListener(type, function (e) { e.preventDefault(); dropzone.classList.remove('is-over'); });
    });
    dropzone.addEventListener('drop', function (e) { run(e.dataTransfer.files); });
  }
})();
