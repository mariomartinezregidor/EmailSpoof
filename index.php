<?php
require __DIR__ . '/lib/helpers.php';
session_start();
$csrf = csrf_token();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recomienda la web</title>
<style>
  body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; color: #222; }
  h1 { font-size: 1.4rem; }
  label { display: block; margin: .8rem 0 .2rem; font-weight: 600; }
  input, textarea { width: 100%; box-sizing: border-box; padding: .5rem; border: 1px solid #ccc; border-radius: 4px; font: inherit; }
  textarea { min-height: 120px; resize: vertical; }
  button { margin-top: 1rem; padding: .6rem 1.2rem; background: #2563eb; color: #fff; border: 0; border-radius: 4px; cursor: pointer; font: inherit; }
  button:hover { background: #1d4ed8; }
  .flash { padding: .8rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
  .ok { background: #dcfce7; color: #166534; }
  .err { background: #fee2e2; color: #991b1b; }
  .hint { font-size: .85rem; color: #555; }
  .hp { position: absolute; left: -9999px; } /* honeypot */
</style>
</head>
<body>
<h1>Enviar correo</h1>


<?php if ($flash): ?>
  <div class="flash <?= $flash['type'] === 'ok' ? 'ok' : 'err' ?>"><?= h($flash['msg']) ?></div>
<?php endif; ?>

<form method="post" action="send.php" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

  <label for="name">Tu nombre</label>
  <input id="name" name="name" type="text" required maxlength="60" placeholder="Juan Perez">

  <label for="from">Email remitente (From)</label>
  <input id="from" name="from" type="email" required maxlength="254" placeholder="tu@email.com">

  <label for="to">Email de tu amigo</label>
  <input id="to" name="to" type="email" required maxlength="254" placeholder="amigo@email.com">

  <label for="subject">Asunto</label>
  <input id="subject" name="subject" type="text" required maxlength="200" placeholder="Asunto del correo">

  <label for="message">Mensaje (puedes usar HTML, p.ej. &lt;img src="..."&gt;)</label>
  <textarea id="message" name="message" required maxlength="20000" style="min-height:300px" placeholder="Escribe tu mensaje..."></textarea>

  <!-- honeypot anti-bots -->
  <div class="hp"><label>Deja vacio<input name="website" type="text" tabindex="-1" autocomplete="off"></label></div>

  <button type="submit">Enviar recomendacion</button>
</form>
</body>
</html>
