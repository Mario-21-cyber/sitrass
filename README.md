# SITRASS — Transportation Reservation and Rental Management System

Isang web-based na reservation at rental management system para sa van transportation services. Ginawa gamit ang **PHP (custom MVC)** at **MySQL/MariaDB**, at tugma sa **XAMPP** para sa lokal na development.

## ✨ Features

- **Customer** — maghanap ng biyahe, mag-book ng trip, mag-reserve ng van rental, bayaran ( kasama ang QR payment), i-track ang booking, mag-iwan ng feedback/ratings, at chat
- **Driver** — dashboard, pamahalaan ang sariling vans, tingnan ang schedules, payments, at scan/track ng bookings
- **Admin** — dashboard, user management, routes, schedules, locations, vans at van images, payment methods, payments, ratings, feedback, audit logs, at system settings
- **Multi-language support** (English / Taglish) via `app/lang`
- **Role-based access** — customer, driver, admin
- **Audit logging** at **email/SMS config** (tingnan ang `config/`)

## 🗂️ Project Structure

```
sitrass/
├── app/
│   ├── controllers/   # MVC controllers
│   ├── models/        # Database models
│   ├── views/         # UI views at templates
│   ├── helpers/       # Helper classes (e.g., Lang.php)
│   └── lang/          # Language files (en.php, ttl.php)
├── config/            # Database, mail, at sms configuration
├── database/          # SQL files (schema, views, seed)
├── public/            # Document root (index.php, css, js, img, uploads)
└── fix-labels.sh
```

## 🚀 Setup (XAMPP)

1. Ilagay ang project folder sa `htdocs` (hal. `C:\xampp\htdocs\sitrass` o `/Applications/XAMPP/xamppfiles/htdocs/sitrass`).
2. Buksan ang **XAMPP Control Panel** at i-start ang **Apache** at **MySQL**.
3. I-import ang database sa pagkakasunod-sunod (via phpMyAdmin o MySQL CLI):
   ```
   database/01_schema.sql
   database/02_views.sql
   database/03_seed.sql
   ```
   > Ang `01_schema.sql` ay awtomatikong gagawa ng `sitrass_db` database.
4. Buksan ang browser at pumunta sa: `http://localhost/sitrass/public/`

### Configuration

- Ang mga default database setting ay nasa `config/database.php` (`localhost`, `sitrass_db`, user `root`, walang password).
- Para sa live hosting (hal. InfinityFree), gumawa ng `config/database.local.php` para i-override ang mga default — hindi kailangang baguhin ang `config/database.php`.
- Ang mail at SMS settings ay nasa `config/mail.php` at `config/sms.php`.
- Ang `config/database.local.php` ay hindi kasama sa git para sa seguridad.

## 🛠️ Tech Stack

- **PHP** (custom MVC framework, walang external dependencies)
- **MySQL / MariaDB** (InnoDB, utf8mb4)
- **HTML / CSS / JavaScript** (nasa `public/`)
- **XAMPP** (Apache + MySQL) para sa lokal na development

## 📄 License

Proyektong pang-edukasyon / pang-tao. Gumamit nang mabuti! 🚐

