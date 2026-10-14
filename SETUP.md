# 🛠️ PipraPay Setup Guide

This guide covers two ways to install PipraPay: **Docker** (recommended) and **manual installation** on a standard LAMP stack.

---

## 📋 Requirements

| Component | Requirement |
|---|---|
| PHP | 8.1.x – 8.3.x |
| PHP Extensions | cURL, cURL Multi, PDO, GD, Fileinfo, Imagick, OpenSSL, ZipArchive, Mbstring |
| Database | MySQL 8.0+ (utf8mb4) |
| Web Server | Apache (with `mod_rewrite`) or Nginx |

> The built-in checker at `pp-requirement.php` validates all of these during installation.

---

## 🐳 Option 1: Docker Installation

### 1. Configure your credentials

Real config files are git-ignored, so create your own from the examples:

```bash
cp pp-config.example.php pp-config.php
cp docker-compose.example.yml docker-compose.yml
```

Edit `pp-config.php` with your database credentials:

```php
$db_host = 'db';          // keep as 'db' for Docker
$db_port = '3306';
$db_user = 'piprapay_user';
$db_pass = 'your_db_password';
$db_name = 'piprapay';
$db_prefix = 'pp_';
```

Then edit `docker-compose.yml` and set the **same** credentials under the `db` service:

```yaml
environment:
  MYSQL_ROOT_PASSWORD: your_root_password
  MYSQL_DATABASE: piprapay
  MYSQL_USER: piprapay_user
  MYSQL_PASSWORD: your_db_password
```

### 2. Start the containers

```bash
docker compose up -d --build
```

This starts:

- **Web** — PHP 8.2 + Apache on port **7667** → http://localhost:7667
- **DB** — MySQL 8.0 on port **7668** (data persists in the `piprapay-db-data` volume)

On first start, the MySQL container automatically imports `pp-content/pp-install/db.sql`.

### 3. Open the app

Visit http://localhost:7667 and follow the on-screen installer.

---

## 🖥️ Option 2: Manual Installation

### 1. Upload the files

Copy the project files to your web root (e.g. `/var/www/html` or `public_html`).

### 2. Create the database

Create a MySQL database and user (utf8mb4 recommended):

```sql
CREATE DATABASE piprapay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'piprapay_user'@'localhost' IDENTIFIED BY 'your_db_password';
GRANT ALL PRIVILEGES ON piprapay.* TO 'piprapay_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Import the schema

```bash
mysql -u piprapay_user -p piprapay < pp-content/pp-install/db.sql
```

### 4. Create the config file

```bash
cp pp-config.example.php pp-config.php
```

Edit `pp-config.php` with your real database credentials:

```php
$db_host = 'localhost';
$db_port = '3306';
$db_user = 'piprapay_user';
$db_pass = 'your_db_password';
$db_name = 'piprapay';
$db_prefix = 'pp_';
```

### 5. Set permissions

The web server user needs write access to the project:

```bash
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
```

### 6. Run the requirement check

Open your site in a browser — `pp-requirement.php` will verify that all PHP requirements pass before installation continues.

---

## ⏰ Cron Job Setup

PipraPay uses a cron job for background tasks (auto-verification, cleanup, etc.).

1. Log in to the **admin panel**
2. Go to **System Settings → Cron Job**
3. Copy the generated cron command — it looks like:

```bash
curl -s https://your-domain.com/cron/YOUR_CRON_KEY >/dev/null 2>&1
```

4. Add it to your crontab to run **every minute**:

```bash
crontab -e
```

```cron
* * * * * curl -s https://your-domain.com/cron/YOUR_CRON_KEY >/dev/null 2>&1
```

You can regenerate the cron key anytime from the same page (the old key is invalidated).

---

## 🔒 Security Notes

- **Never commit** `pp-config.php` or `docker-compose.yml` — both contain real credentials and are already listed in `.gitignore`. Use the `.example` files as templates.
- Use strong, unique passwords for MySQL (root and app user).
- Serve the app over **HTTPS** in production.
- Keep the database port (`3306` / `7668` in Docker) **not exposed to the public internet** — restrict it with a firewall or remove the port mapping in `docker-compose.yml`.
- Keep PipraPay up to date from the admin panel.

---

## 📚 More Documentation

- 📖 Docs: https://help.piprapay.com/
- 🔌 API Reference: https://piprapay.readme.io
- 💬 Community: https://www.facebook.com/groups/piprapay
