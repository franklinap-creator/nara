# Deployment and security checklist

## Local XAMPP

1. Put the project in C:\xampp\htdocs\e_com.
2. Start Apache and MySQL in XAMPP.
3. Create the product-card database in phpMyAdmin if it does not exist.
4. Open http://localhost/e_com/.
5. Visit http://localhost/e_com/admin/setup.php once to create the owner account.
6. Delete or keep the protected admin/setup.php disabled after setup.

## Paystack configuration

Set the secret key as a server environment variable named PAYSTACK_SECRET_KEY. Do not paste it into a public PHP file or commit it to Git. The application reads the value through getenv().

For local testing, update PAYSTACK_CALLBACK_URL in config.php to your local URL. For production, use an HTTPS URL such as https://your-domain.com/payment-callback.php and configure the webhook URL as https://your-domain.com/payment-webhook.php in Paystack.

## Production server

- Use PHP 8.1+ with PDO MySQL and cURL enabled.
- Render runs the PHP web service, but you must provide an external MySQL-compatible database. Render's web filesystem is not a permanent database or upload store.
- Create the MySQL database and least-privileged database user; avoid using root.
- Set strong database credentials in the server environment.
- Use HTTPS and redirect HTTP to HTTPS.
- Keep .htaccess enabled and confirm config.php, db.php, SQL files, and SQLite files are not downloadable.
- Delete admin/setup.php after creating the first admin account.
- Rotate any payment key that has ever been committed, pasted into chat, or shared publicly.
- Back up the database and restrict backups to private storage.
- For uploaded product images in production, use object storage or a persistent disk; files written to a normal Render web container can be lost on redeploy.
- Test checkout with Paystack test keys before switching to live keys.

## Render environment variables

Set these in the Render service settings:

DB_HOST=your-mysql-host
DB_PORT=3306
DB_NAME=product-card
DB_USER=your-database-user
DB_PASS=your-database-password
PAYSTACK_SECRET_KEY=sk_test_or_sk_live_key
PAYSTACK_CALLBACK_URL=https://your-render-domain.onrender.com/payment-callback.php

Do not put these values in render.yaml, GitHub, or a committed PHP file.
