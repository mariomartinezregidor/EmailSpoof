# EmailSpoof

Formulario web en PHP para enviar correos con asunto y cuerpo libres (HTML permitido) a través del SMTP de Mailgun.

> **Proyecto educativo.** Este código se ha creado con fines de aprendizaje: para entender cómo funciona el envío de correo por SMTP, la validación de formularios, la protección CSRF y el uso de servicios como Mailgun. **No lo uses para hacer daño**: suplantar identidades, engañar a personas, enviar spam o hacer phishing es ilegal y perjudica a otros. Usa esta herramienta solo con direcciones de tu propiedad y con fines legítimos.

> **Aviso sobre el remitente:** el campo "Email remitente (From)" permite escribir cualquier dirección, pero Mailgun y otros proveedores serios **rechazan** los correos cuyo remitente no pertenece a un dominio verificado en tu cuenta. Si quieres que el correo llegue, usa un remitente de tu propio dominio verificado.

## Estructura

- `index.php`: formulario.
- `send.php`: validación y envío con PHPMailer (SMTP).
- `lib/helpers.php`: funciones auxiliares (sanitización, CSRF, rate limit).
- `vendor/PHPMailer/`: librería PHPMailer.
- `config.example.php`: plantilla de configuración con valores de ejemplo.
- `data/`: se crea automáticamente.
- `run-local.sh`: arranca el servidor de desarrollo en local.

## Requisitos

- PHP 8.x con las extensiones `mbstring` y `openssl`.
- Una cuenta de Mailgun con un dominio verificado.

## Configuración de Mailgun

1. En el panel de Mailgun, añade tu dominio (por ejemplo `mg.tudominio.com`) en la región que corresponda (US o EU).
2. Publica en tu DNS los registros que Mailgun te indique:
   - TXT de verificación del dominio.
   - SPF: `v=spf1 include:mailgun.org ~all`.
   - DKIM: el registro TXT que te da Mailgun (selector `k1` o similar).
   - DMARC: `v=DMARC1; p=quarantine; rua=mailto:postmaster@tudominio.com`.
   - MX: solo si quieres recibir correos en ese dominio.
3. Espera a que Mailgun marque el dominio como verificado.
4. En *Domain settings → SMTP credentials*, crea un usuario SMTP y anota su contraseña.

## Configuración de la aplicación

1. Copia la plantilla:

   ```bash
   cp config.example.php config.php
   ```

2. Edita `config.php` y rellena:
   - `smtp.host`: `smtp.eu.mailgun.org` (región EU) o `smtp.mailgun.org` (región US).
   - `smtp.port`: `587`, `smtp.encryption`: `tls`.
   - `smtp.username` y `smtp.password`: credenciales SMTP de Mailgun.
   - `sender.email`: un remitente de tu dominio verificado.
   - `site.name`, `site.url`, `site.allowed_hosts`: datos de tu web.
   - `limits.max_comment`: longitud máxima del mensaje.

## Ejecutar en local

```bash
./run-local.sh
```

Abre http://127.0.0.1:8080 en el navegador. Para acceder desde fuera de tu red, puedes usar un túnel como `cloudflared tunnel --url http://127.0.0.1:8080`.

## Despliegue en hosting

Sube los archivos por FTP/SFTP a la carpeta pública del hosting y crea `config.php` en el servidor con tus datos. Asegúrate de que `data/` sea escribible por el servidor web.

## Seguridad incluida

- Validación de emails y longitud de campos.
- Sanitización de cabeceras (evita inyección de headers).
- Token CSRF.
- Honeypot anti-bots.
- `.htaccess` bloquea el acceso a `config.php`, `data/`, `vendor/` y `lib/`.

El rate limit está desactivado (`rate_limit_ok` devuelve siempre `true`). Si lo publicas para terceros, reactívalo.
