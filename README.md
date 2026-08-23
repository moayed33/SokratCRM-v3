# SOKRAT CRM V2

**SOKRAT CRM V2** is a modern, high-performance Customer Relationship Management system built with PHP 8.3 and Laravel 13. Designed specifically for deployment on **Ubuntu 24.04.4 LTS**, it provides comprehensive tools for managing leads, tracking pipeline stages, generating quotations, conducting campaigns, and analyzing team performance.

---

## ⚡ One-Line Commands

### One-Line Install Command (Ubuntu 24.04.4)
```bash
curl -sSL https://raw.githubusercontent.com/ahmdosamasokrat-svg/sokrat-crm-v2/main/install.sh | sudo bash
```

### One-Line Uninstall Command (Ubuntu 24.04.4)
```bash
curl -sSL https://raw.githubusercontent.com/ahmdosamasokrat-svg/sokrat-crm-v2/main/uninstall.sh | sudo bash -s -- -y
```

---

## Key Features

- **Lead Management**: Complete lifecycle tracking, creation, editing, status history, and detailed customer profiles.
- **Kanban Board & Pipeline Stages**: Interactive pipeline visualization with custom status tracking.
- **Quotation Generator**: Built-in quotation builder module with custom layout and pricing calculations.
- **Follow-ups & Task Scheduling**: Real-time tracking of upcoming meetings, calls, daily tasks, and VIP clients.
- **Data Import & Export**: Import leads from CSV/Excel templates with preview validation; export selected leads cleanly.
- **Users, Groups & Permissions**: Database-backed login, multi-group RBAC, active-account enforcement, and a protected permission matrix under Settings.
- **Automated Deployment**: Production-ready installer (`install.sh`) and clean uninstaller (`uninstall.sh`) tailored for **Ubuntu 24.04.4 LTS**.
- **Localhost Accessibility**: Out-of-the-box Apache web server configuration serving the application directly on `http://localhost/`.

---

## System Requirements

- **Operating System**: Ubuntu 24.04.4 LTS (Noble Numbat)
- **Web Server**: Apache 2.4 (`mod_rewrite` enabled)
- **PHP Version**: PHP 8.3 (with `cli`, `mysql`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `intl`, `gd`)
- **Database**: MySQL 8.0+ / MariaDB
- **Build Tools**: Composer 2.x, Node.js & npm (for Vite assets)

---

## Quick Start / Installation

### Method 1: One-Line Install (Recommended)
Run this command on your Ubuntu 24.04.4 server terminal:

```bash
curl -sSL https://raw.githubusercontent.com/ahmdosamasokrat-svg/sokrat-crm-v2/main/install.sh | sudo bash
```

### Method 2: Manual Clone & Install
If you have cloned the repository locally:

```bash
git clone https://github.com/ahmdosamasokrat-svg/sokrat-crm-v2.git
cd sokrat-crm-v2
chmod +x install.sh
sudo ./install.sh
```

### What `install.sh` Does:
1. Installs Apache 2.4, MySQL 8.0, PHP 8.3 (with required extensions), Composer, and Node.js/npm.
2. Clones/places application files in `/var/www/html/crm-v2`.
3. Installs PHP dependencies (`composer install`) and builds frontend assets (`npm install && npm run build`).
4. Creates an isolated MySQL database (`sokrat_crm_v2`) and user (`sokrat_crm_v2_app`) with secure generated credentials.
5. Generates `.env` and application key (`php artisan key:generate`).
6. Executes database migrations, seeds the CRM pipeline and permissions, and provisions `admin` as the default Super Admin.
7. Configures Apache VirtualHost serving `/var/www/html/crm-v2/public` on `http://localhost/`.
8. Secures directory storage/cache permissions for `www-data`.
9. Saves the CRM and database credentials to `/root/sokrat-crm-v2-credentials.txt`.

Once installed, open your browser and navigate to:
👉 **[http://localhost/](http://localhost/)**

Default CRM account:

- **Username:** `admin`
- **Password:** `Admin@123`

Change this password from **Settings → Users** after the first login.

---

## Uninstallation

### Method 1: One-Line Uninstall (Recommended)
```bash
curl -sSL https://raw.githubusercontent.com/ahmdosamasokrat-svg/sokrat-crm-v2/main/uninstall.sh | sudo bash -s -- -y
```

### Method 2: Manual Uninstall
```bash
cd sokrat-crm-v2 # or /var/www/html/crm-v2
chmod +x uninstall.sh
sudo ./uninstall.sh
```

### What `uninstall.sh` Does:
1. Disables and removes the Apache virtual host site (`sokrat-crm-v2.conf`).
2. Re-enables default Apache site (`000-default.conf`).
3. Drops the MySQL database (`sokrat_crm_v2`) and database user (`sokrat_crm_v2_app`).
4. Purges the application directory `/var/www/html/crm-v2`.
5. Removes `/root/sokrat-crm-v2-credentials.txt`.

---

## Repository Structure

```text
sokrat-crm-v2/
├── app/
│   ├── Http/Controllers/    # Dashboard, Lead, Followup, Quotation, Task controllers
│   ├── Models/              # Lead, LeadFollowup, LeadStatus, PipelineStage, Quotation, User
│   └── Providers/           # App Service Provider
├── bootstrap/               # Application bootstrap & provider registration
├── config/                  # App, database, auth, session, logging configuration files
├── database/
│   ├── factories/           # User factories
│   ├── migrations/          # Schema migrations for leads, pipelines, quotations
│   └── seeders/             # Pipeline & initial status seeders
├── public/
│   ├── quotation-generator/ # Quotation builder assets & frontend logic
│   ├── crm-sidebar-shared.css
│   ├── index.php            # Entry point for Apache/Nginx
│   └── robots.txt
├── resources/
│   ├── css/                 # Tailwind CSS styles
│   ├── js/                  # App JS bundle
│   └── views/               # Blade layouts, leads, quotations, tasks, partials
├── routes/
│   ├── web.php              # All web routes & authentication endpoints
│   └── console.php          # CLI commands
├── storage/                 # Logs, framework cache, compiled views
├── tests/                   # Unit & feature tests
├── .env.example             # Example environment file
├── composer.json            # PHP dependencies
├── package.json             # JS/Node dependencies & Vite config
├── install.sh               # Ubuntu 24.04.4 automated installer
├── uninstall.sh             # Ubuntu 24.04.4 automated uninstaller
└── README.md                # Documentation
```

---

## License & Support

Developed for Sokrat CRM systems. Licensed under the MIT License.
