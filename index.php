<?php
require __DIR__ . '/lib/helpers.php';
session_start();
$csrf = csrf_token();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$limits = cfg()['limits'];
$jsLimits = [
    'maxChar'      => (int)($limits['max_comment'] ?? 20000),
    'maxFiles'     => (int)($limits['max_attachments'] ?? 5),
    'maxFileSize'  => (int)($limits['max_attachment_size'] ?? 8 * 1024 * 1024),
    'maxTotalSize' => (int)($limits['max_attachment_total'] ?? 20 * 1024 * 1024),
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>EmailSpoof</title>
<style>
  :root {
    --primary: #4f46e5;
    --primary-dark: #4338ca;
    --bg: #f1f5f9;
    --card: #ffffff;
    --text: #0f172a;
    --muted: #64748b;
    --border: #e2e8f0;
    --danger: #dc2626;
    --ok-bg: #dcfce7; --ok-fg: #166534;
    --err-bg: #fee2e2; --err-fg: #991b1b;
  }
  * { box-sizing: border-box; }
  body {
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
    background: linear-gradient(160deg, #eef2ff 0%, #f1f5f9 45%, #faf5ff 100%);
    min-height: 100vh; margin: 0; padding: 2rem 1rem 4rem; color: var(--text);
  }
  .wrap { max-width: 760px; margin: 0 auto; }
  .hero { text-align: center; margin-bottom: 1.5rem; }
  .hero .logo {
    display: inline-flex; align-items: center; justify-content: center;
    width: 56px; height: 56px; border-radius: 16px; margin-bottom: .75rem;
    background: linear-gradient(135deg, var(--primary), #9333ea);
    box-shadow: 0 8px 20px rgba(79, 70, 229, .35);
  }
  .hero h1 { margin: 0; font-size: 1.6rem; }
  .hero p { margin: .3rem 0 0; color: var(--muted); font-size: .95rem; }

  label { display: block; margin: 1rem 0 .35rem; font-weight: 600; font-size: .9rem; }
  label .req { color: var(--danger); }
  input[type="text"], input[type="email"] {
    width: 100%; padding: .65rem .8rem; border: 1px solid var(--border); border-radius: 10px;
    font: inherit; background: #f8fafc; transition: border-color .15s, box-shadow .15s;
  }
  input:focus { outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(79, 70, 229, .15); }
  .row { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
  @media (max-width: 560px) { .row { grid-template-columns: 1fr; } }

  /* Editor */
  .editor-shell { border: 1px solid var(--border); border-radius: 12px; overflow: hidden; background: #fff; }
  .editor-shell:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, .15); }
  .toolbar {
    display: flex; flex-wrap: wrap; gap: 2px; align-items: center;
    padding: .4rem; background: #f8fafc; border-bottom: 1px solid var(--border);
  }
  .toolbar .sep { width: 1px; height: 22px; background: var(--border); margin: 0 4px; }
  .toolbar button, .toolbar select {
    border: 0; background: transparent; border-radius: 6px; cursor: pointer;
    font: inherit; font-size: .85rem; padding: .35rem .5rem; color: var(--text);
    display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 30px;
  }
  .toolbar button:hover, .toolbar select:hover { background: #e2e8f0; }
  .toolbar button.active { background: #e0e7ff; color: var(--primary-dark); }
  .toolbar button svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .toolbar button.lbl { font-weight: 700; }
  .toolbar button.lbl.italic { font-style: italic; }
  .toolbar button.lbl.underline { text-decoration: underline; }
  .toolbar button.lbl.strike { text-decoration: line-through; }
  .editor {
    min-height: 260px; max-height: 480px; overflow-y: auto; padding: .9rem 1rem;
    font: inherit; line-height: 1.55; outline: none;
  }
  .editor:empty::before { content: attr(data-placeholder); color: #94a3b8; pointer-events: none; }
  .editor img { max-width: 100%; }
  .editor blockquote { border-left: 3px solid var(--border); margin: .5rem 0; padding: .2rem 0 .2rem .8rem; color: var(--muted); }
  .editor pre { background: #f1f5f9; padding: .6rem .8rem; border-radius: 8px; overflow-x: auto; }
  .source-ta {
    display: none; width: 100%; min-height: 260px; border: 0; resize: vertical;
    padding: .9rem 1rem; font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
    font-size: .85rem; line-height: 1.5; outline: none;
  }
  .editor-meta { display: flex; justify-content: space-between; padding: .35rem .75rem; border-top: 1px solid var(--border); font-size: .75rem; color: var(--muted); background: #f8fafc; }
  .editor-meta .over { color: var(--danger); font-weight: 600; }

  /* Adjuntos */
  .dropzone {
    margin-top: .35rem; border: 2px dashed var(--border); border-radius: 12px;
    padding: 1.4rem 1rem; text-align: center; color: var(--muted); cursor: pointer;
    transition: border-color .15s, background .15s; background: #f8fafc;
  }
  .dropzone:hover, .dropzone.drag { border-color: var(--primary); background: #eef2ff; }
  .dropzone strong { color: var(--primary); }
  .dropzone .dz-hint { font-size: .78rem; margin-top: .25rem; }
  .att-list { list-style: none; margin: .6rem 0 0; padding: 0; display: flex; flex-direction: column; gap: .4rem; }
  .att-list li {
    display: flex; align-items: center; gap: .6rem; padding: .45rem .7rem;
    border: 1px solid var(--border); border-radius: 10px; background: #fff; font-size: .85rem;
  }
  .att-list .att-icon { color: var(--primary); flex-shrink: 0; display: inline-flex; }
  .att-list .att-icon svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .att-list .att-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .att-list .att-size { color: var(--muted); flex-shrink: 0; }
  .att-list .att-remove {
    border: 0; background: transparent; color: var(--danger); cursor: pointer;
    font-size: 1rem; line-height: 1; padding: .15rem .35rem; border-radius: 6px;
  }
  .att-list .att-remove:hover { background: var(--err-bg); }
  .att-error { color: var(--danger); font-size: .8rem; margin-top: .4rem; display: none; }
  .att-error.show { display: block; }

  button.submit {
    margin-top: 1.5rem; width: 100%; padding: .8rem 1.2rem;
    background: linear-gradient(135deg, var(--primary), #7c3aed); color: #fff;
    border: 0; border-radius: 12px; cursor: pointer; font: inherit; font-weight: 600; font-size: 1rem;
    box-shadow: 0 6px 16px rgba(79, 70, 229, .3); transition: transform .1s, box-shadow .15s;
  }
  button.submit:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(79, 70, 229, .4); }
  button.submit:active { transform: translateY(0); }

  .flash { padding: .8rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: .9rem; }
  .ok { background: var(--ok-bg); color: var(--ok-fg); }
  .err { background: var(--err-bg); color: var(--err-fg); }
  .hp { position: absolute; left: -9999px; } /* honeypot */</style>
</head>
<body>
<div class="wrap">
  <div class="hero">
    <div class="logo">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
    </div>
    <h1>Enviar correo</h1>
    <p>Escribe con formato HTML y adjunta archivos</p>
  </div>

<?php if ($flash): ?>
  <div class="flash <?= $flash['type'] === 'ok' ? 'ok' : 'err' ?>"><?= h($flash['msg']) ?></div>
<?php endif; ?>

<form method="post" action="send.php" enctype="multipart/form-data" autocomplete="off" id="sendForm">
  <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

  <div class="row">
    <div>
      <label for="name">Tu nombre <span class="req">*</span></label>
      <input id="name" name="name" type="text" required maxlength="60" placeholder="Juan Perez">
    </div>
    <div>
      <label for="from">Email remitente (From) <span class="req">*</span></label>
      <input id="from" name="from" type="email" required maxlength="254" placeholder="tu@email.com">
    </div>
  </div>

  <label for="to">Email de tu amigo <span class="req">*</span></label>
  <input id="to" name="to" type="email" required maxlength="254" placeholder="amigo@email.com">

  <label for="subject">Asunto <span class="req">*</span></label>
  <input id="subject" name="subject" type="text" required maxlength="200" placeholder="Asunto del correo">

  <label>Mensaje <span class="req">*</span></label>
  <div class="editor-shell">
    <div class="toolbar" id="toolbar">
      <button type="button" data-cmd="undo" title="Deshacer"><svg viewBox="0 0 24 24"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-15-6.7L3 13"/></svg></button>
      <button type="button" data-cmd="redo" title="Rehacer"><svg viewBox="0 0 24 24"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 15-6.7L21 13"/></svg></button>
      <span class="sep"></span>
      <select id="blockSelect" title="Estilo de parrafo">
        <option value="P">Parrafo</option>
        <option value="H1">Titulo 1</option>
        <option value="H2">Titulo 2</option>
        <option value="H3">Titulo 3</option>
        <option value="BLOCKQUOTE">Cita</option>
        <option value="PRE">Codigo</option>
      </select>
      <span class="sep"></span>
      <button type="button" class="lbl" data-cmd="bold" title="Negrita">B</button>
      <button type="button" class="lbl italic" data-cmd="italic" title="Cursiva">I</button>
      <button type="button" class="lbl underline" data-cmd="underline" title="Subrayado">U</button>
      <button type="button" class="lbl strike" data-cmd="strikeThrough" title="Tachado">S</button>
      <span class="sep"></span>
      <button type="button" data-cmd="insertUnorderedList" title="Lista con vinetas"><svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13"/><circle cx="3.5" cy="6" r="1" fill="currentColor" stroke="none"/><circle cx="3.5" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="3.5" cy="18" r="1" fill="currentColor" stroke="none"/></svg></button>
      <button type="button" data-cmd="insertOrderedList" title="Lista numerada"><svg viewBox="0 0 24 24"><path d="M10 6h11M10 12h11M10 18h11"/><path d="M4 6h1v4M4 10h2M4 14h2a1 1 0 0 1 0 2H4m0 0h2a1 1 0 0 1 0 2H4"/></svg></button>
      <span class="sep"></span>
      <button type="button" data-cmd="justifyLeft" title="Alinear a la izquierda"><svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h12M3 18h15"/></svg></button>
      <button type="button" data-cmd="justifyCenter" title="Centrar"><svg viewBox="0 0 24 24"><path d="M3 6h18M6 12h12M4 18h16"/></svg></button>
      <button type="button" data-cmd="justifyRight" title="Alinear a la derecha"><svg viewBox="0 0 24 24"><path d="M3 6h18M9 12h12M6 18h15"/></svg></button>
      <span class="sep"></span>
      <button type="button" id="btnLink" title="Insertar enlace"><svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg></button>
      <button type="button" id="btnImage" title="Insertar imagen (URL)"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-4-4-9 9"/></svg></button>
      <button type="button" id="btnFore" title="Color del texto"><svg viewBox="0 0 24 24"><path d="M12 3 5 21m2.5-6h9M12 3l7 18"/><path d="M4 21h16" stroke-width="4"/></svg></button>
      <button type="button" id="btnHilite" title="Color de fondo"><svg viewBox="0 0 24 24"><path d="m9 11-6 6v3h9l3-3"/><path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4Z"/></svg></button>
      <button type="button" data-cmd="removeFormat" title="Quitar formato"><svg viewBox="0 0 24 24"><path d="M4 7V5h12v2M9 5v14M6 19h6M15 13v6m0-6 6 6m0-6-6 6"/></svg></button>
      <span class="sep"></span>
      <button type="button" id="btnSource" title="Ver codigo HTML">&lt;&gt;</button>
    </div>
    <div id="editor" class="editor" contenteditable="true" data-placeholder="Escribe tu mensaje... usa la barra para dar formato"></div>
    <textarea id="sourceTa" class="source-ta" spellcheck="false" placeholder="&lt;p&gt;Escribe tu HTML aqui...&lt;/p&gt;"></textarea>
    <div class="editor-meta">
      <span id="charCount">0 / <?= $jsLimits['maxChar'] ?> caracteres</span>
      <span id="modeHint">Editor visual</span>
    </div>
  </div>

  <label>Archivos adjuntos</label>
  <div class="dropzone" id="dropzone">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;margin:0 auto .25rem"><path d="M21.4 11.05 12.25 20.2a6 6 0 0 1-8.49-8.49l8.49-8.48a4 4 0 0 1 5.66 5.65l-8.49 8.49a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
    Arrastra archivos aqui o <strong>haz clic para elegir</strong>
    <div class="dz-hint" id="dzHint">Maximo <?= $jsLimits['maxFiles'] ?> archivos · <?= number_format($jsLimits['maxFileSize'] / 1048576, 1) ?> MB por archivo</div>
    <input type="file" id="attInput" name="attachments[]" multiple hidden>
  </div>
  <ul class="att-list" id="attList"></ul>
  <div class="att-error" id="attError"></div>

  <!-- honeypot anti-bots -->
  <div class="hp"><label>Deja vacio<input name="website" type="text" tabindex="-1" autocomplete="off"></label></div>

  <button type="submit" class="submit">Enviar correo</button>
</form>
</div>

<script>
(function () {
  'use strict';
  var LIMITS = <?= json_encode($jsLimits, JSON_UNESCAPED_UNICODE) ?>;

  var editor = document.getElementById('editor');
  var sourceTa = document.getElementById('sourceTa');
  var blockSelect = document.getElementById('blockSelect');
  var charCount = document.getElementById('charCount');
  var modeHint = document.getElementById('modeHint');
  var form = document.getElementById('sendForm');

  function exec(cmd, val) {
    editor.focus();
    document.execCommand(cmd, false, val || null);
    updateCount();
    syncActiveStates();
  }

  function updateCount() {
    var len = editor.innerText.replace(/\n$/, '').length;
    charCount.textContent = len + ' / ' + LIMITS.maxChar + ' caracteres';
    charCount.className = len > LIMITS.maxChar ? 'over' : '';
  }

  function syncActiveStates() {
    var map = { bold: 1, italic: 1, underline: 1, strikeThrough: 1,
                insertUnorderedList: 1, insertOrderedList: 1,
                justifyLeft: 1, justifyCenter: 1, justifyRight: 1 };
    var buttons = document.querySelectorAll('#toolbar button[data-cmd]');
    for (var i = 0; i < buttons.length; i++) {
      var cmd = buttons[i].getAttribute('data-cmd');
      if (map[cmd]) buttons[i].classList.toggle('active', document.queryCommandState(cmd));
    }
  }

  document.querySelectorAll('#toolbar button[data-cmd]').forEach(function (b) {
    b.addEventListener('click', function () { exec(b.getAttribute('data-cmd')); });
  });

  blockSelect.addEventListener('change', function () {
    exec('formatBlock', '<' + blockSelect.value + '>');
    blockSelect.value = 'P';
  });

  document.getElementById('btnLink').addEventListener('click', function () {
    var url = prompt('URL del enlace:', 'https://');
    if (!url) return;
    if (!/^(https?:|mailto:)/i.test(url)) url = 'https://' + url;
    var sel = window.getSelection();
    if (sel && !sel.isCollapsed) {
      exec('createLink', url);
    } else {
      exec('insertHTML', '<a href="' + url.replace(/"/g, '&quot;') + '">' + url + '</a>');
    }
  });

  document.getElementById('btnImage').addEventListener('click', function () {
    var url = prompt('URL de la imagen:', 'https://');
    if (!url) return;
    if (!/^https?:/i.test(url)) url = 'https://' + url;
    exec('insertHTML', '<img src="' + url.replace(/"/g, '&quot;') + '" alt="imagen" style="max-width:100%">');
  });

  function colorButton(btnId, cmd) {
    var btn = document.getElementById(btnId);
    var picker = document.createElement('input');
    picker.type = 'color';
    picker.style.position = 'absolute';
    picker.style.left = '-9999px';
    document.body.appendChild(picker);
    btn.addEventListener('click', function () { picker.click(); });
    picker.addEventListener('input', function () { exec(cmd, picker.value); });
  }
  colorButton('btnFore', 'foreColor');
  colorButton('btnHilite', 'hiliteColor');

  var sourceMode = false;
  var btnSource = document.getElementById('btnSource');
  btnSource.addEventListener('click', function () {
    sourceMode = !sourceMode;
    btnSource.classList.toggle('active', sourceMode);
    if (sourceMode) {
      sourceTa.value = editor.innerHTML;
      editor.style.display = 'none';
      sourceTa.style.display = 'block';
      modeHint.textContent = 'Codigo HTML';
      sourceTa.focus();
    } else {
      editor.innerHTML = sourceTa.value;
      sourceTa.style.display = 'none';
      editor.style.display = 'block';
      modeHint.textContent = 'Editor visual';
      updateCount();
      editor.focus();
    }
  });

  editor.addEventListener('input', updateCount);
  editor.addEventListener('keyup', syncActiveStates);
  editor.addEventListener('mouseup', syncActiveStates);
  editor.addEventListener('paste', function (e) {
    e.preventDefault();
    var html = (e.clipboardData || window.clipboardData).getData('text/html');
    var text = (e.clipboardData || window.clipboardData).getData('text/plain');
    if (html) {
      document.execCommand('insertHTML', false, html);
    } else {
      document.execCommand('insertText', false, text);
    }
    updateCount();
  });

  form.addEventListener('submit', function (e) {
    var message = sourceMode ? sourceTa.value : editor.innerHTML;
    if (editor.innerText.replace(/\n$/, '').length > LIMITS.maxChar) {
      e.preventDefault();
      alert('El mensaje supera el maximo de ' + LIMITS.maxChar + ' caracteres.');
      return;
    }
    if (message.length > LIMITS.maxChar * 20) {
      if (!confirm('El HTML del mensaje es muy grande y podria ser rechazado. Enviar de todas formas?')) {
        e.preventDefault();
        return;
      }
    }
    var hidden = document.getElementById('messageField');
    if (!hidden) {
      hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.id = 'messageField';
      hidden.name = 'message';
      form.appendChild(hidden);
    }
    hidden.value = message;
  });

  updateCount();

  /* ---- Adjuntos ---- */
  var dropzone = document.getElementById('dropzone');
  var attInput = document.getElementById('attInput');
  var attList = document.getElementById('attList');
  var attError = document.getElementById('attError');
  var files = [];

  function fmtSize(bytes) {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(0) + ' KB';
    return bytes + ' B';
  }

  function showAttError(msg) {
    attError.textContent = msg;
    attError.classList.add('show');
    clearTimeout(showAttError.t);
    showAttError.t = setTimeout(function () { attError.classList.remove('show'); }, 6000);
  }

  function totalSize() {
    return files.reduce(function (s, x) { return s + x.size; }, 0);
  }

  function addFiles(list) {
    for (var i = 0; i < list.length; i++) {
      var f = list[i];
      var dup = files.some(function (x) { return x.name === f.name && x.size === f.size; });
      if (dup) continue;
      if (files.length >= LIMITS.maxFiles) {
        showAttError('Maximo ' + LIMITS.maxFiles + ' archivos adjuntos.');
        break;
      }
      if (f.size > LIMITS.maxFileSize) {
        showAttError('"' + f.name + '" supera el maximo de ' + fmtSize(LIMITS.maxFileSize) + ' por archivo.');
        continue;
      }
      if (totalSize() + f.size > LIMITS.maxTotalSize) {
        showAttError('El total de adjuntos supera ' + fmtSize(LIMITS.maxTotalSize) + '.');
        continue;
      }
      files.push(f);
    }
    renderFiles();
  }

  function renderFiles() {
    attList.innerHTML = '';
    files.forEach(function (f, idx) {
      var li = document.createElement('li');
      var icon = document.createElement('span');
      icon.className = 'att-icon';
      icon.innerHTML = '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>';
      var name = document.createElement('span');
      name.className = 'att-name';
      name.textContent = f.name;
      name.title = f.name;
      var size = document.createElement('span');
      size.className = 'att-size';
      size.textContent = fmtSize(f.size);
      var rm = document.createElement('button');
      rm.type = 'button';
      rm.className = 'att-remove';
      rm.title = 'Quitar';
      rm.innerHTML = '&times;';
      rm.addEventListener('click', function () {
        files.splice(idx, 1);
        renderFiles();
      });
      li.appendChild(icon);
      li.appendChild(name);
      li.appendChild(size);
      li.appendChild(rm);
      attList.appendChild(li);
    });
    syncInput();
  }

  function syncInput() {
    if (typeof DataTransfer === 'undefined') return;
    var dt = new DataTransfer();
    files.forEach(function (f) { dt.items.add(f); });
    attInput.files = dt.files;
  }

  dropzone.addEventListener('click', function () { attInput.click(); });
  attInput.addEventListener('change', function () {
    addFiles(attInput.files);
    syncInput();
  });
  ['dragenter', 'dragover'].forEach(function (ev) {
    dropzone.addEventListener(ev, function (e) {
      e.preventDefault();
      dropzone.classList.add('drag');
    });
  });
  ['dragleave', 'drop'].forEach(function (ev) {
    dropzone.addEventListener(ev, function (e) {
      e.preventDefault();
      dropzone.classList.remove('drag');
    });
  });
  dropzone.addEventListener('drop', function (e) {
    if (e.dataTransfer && e.dataTransfer.files) addFiles(e.dataTransfer.files);
  });
})();
</script>
</body>
</html>
