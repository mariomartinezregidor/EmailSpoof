# Recomendar web (envio desde tu dominio) — Mailgun

Web PHP para que un visitante recomiende tu web a un amigo. El correo sale de
tu dominio (cuenta `recomendaciones@tudominio.com`) con el nombre del visitante
visible y su email como `Reply-To`. No falsifica el remitente. El envio se hace
por SMTP de Mailgun.

## Estructura

- `index.php` - formulario
- `send.php` - validacion y envio
- `lib/helpers.php` - helpers (sanitizacion, rate limit, captcha, CSRF)
- `vendor/PHPMailer/` - PHPMailer (SMTP)
- `config.php` - credenciales (NO subir a repositorios; copiar de `config.example.php`)
- `data/` - rate limit (creado automaticamente; protegido por .htaccess)

## Configuracion de Mailgun

1. En el panel de Mailgun, anade tu dominio (p.ej. `mg.marietelabs.online`) en la
   region que corresponda (EU o US).
2. Publica en tu DNS los registros que Mailgun te indica:
   - TXT de verificacion del dominio
   - MX (para recepcion, si quieres)
   - TXT SPF: `v=spf1 include:mailgun.org ~all`
   - TXT DKIM: el que te da Mailgun (selector `k1` o similar)
   - DMARC: `v=DMARC1; p=quarantine; rua=mailto:postmaster@tudominio.com`
3. Espera a que Mailgun marque el dominio como "Active/Verified".
4. En "Domain settings → SMTP credentials", crea un usuario SMTP (p.ej.
   `localmail@mg.marietelabs.online`) y anota su contrasena.
5. Copia `config.example.php` a `config.php` y rellena:
   - `smtp.host`: `smtp.eu.mailgun.org` (EU) o `smtp.mailgun.org` (US)
   - `smtp.port`: `587`, `encryption`: `tls`
   - `smtp.username` / `smtp.password`: las credenciales SMTP de Mailgun
   - `sender.email`: un remitente del dominio verificado (p.ej. `localmail@mg.marietelabs.online`)
   - `site.*`: nombre, URL y hosts permitidos de tu web

## Ejecutar en local

Requisitos: PHP 8.x (probado con 8.4).

```bash
# Servidor de desarrollo en http://127.0.0.1:8080
php -S 127.0.0.1:8080
```

O usa el script incluido:

```bash
./run-local.sh
```

Luego abre http://127.0.0.1:8080 en el navegador. Los correos salen por SMTP de
Mailgun hacia destinatarios reales (fuera de tu maquina).

### Exponer la web al exterior (opcional)

Si quieres que otros accedan a tu instancia local, usa el tunel de Cloudflare
(`cloudflared` ya instalado):

```bash
cloudflared tunnel --url http://127.0.0.1:8080
```

Te dara una URL publica temporal. Recuerda que el rate limit es por IP y los
datos se guardan en `data/` (gitignored).

## Seguridad incluida

- Validacion de emails y longitud de campos.
- Sanitizacion de cabeceras (previene inyeccion de headers).
- Token CSRF.
- Honeypot anti-bots.
- Captcha matematico simple.
- Rate limit por IP (5 envios/hora por defecto).
- `.htaccess` bloquea `config.php`, `data/`, `vendor/`, `lib/`.

## Nota sobre "spoofing"

Esta web NO falsifica la direccion `From`. El correo sale de tu dominio y el
nombre del visitante va en el nombre visible, marcado como "(via Recomendaciones)",
con su email en `Reply-To`. Falsificar el `From` haria que Gmail/Outlook
marcara el correo como spam o lo rechazara (SPF/DKIM/DMARC) y podria considerarse
suplantacion de identidad. Si quieres probar la entregabilidad de tu dominio,
esta implementacion es suficiente: el remitente real es tu dominio.
