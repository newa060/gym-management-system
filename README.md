# Gym Membership & Class Management System

## System Requirements
- XAMPP with PHP `[PHP_VERSION]`, MySQL, and Apache
- Required PHP extensions: `[PHP_EXTENSIONS]`

## Installation & Setup
1. Start Apache and MySQL from the XAMPP Control Panel.
2. Copy project to `[XAMPP_HTDOCS_PATH]/gym_system`.
3. Open phpMyAdmin and import `database/schema.sql`.
4. Edit `config/config.php` with `[DB_USER]` / `[DB_PASS]`.
5. Set file permissions: `[FILE_PERMISSIONS]`; keep `config/` read-only.
6. First-run check: browse to `http://localhost/gym_system`.

## Default Login Credentials
- **URL**: `http://localhost/gym_system/pages/login.php`
- **Username**: `admin` (or `admin@apexfitness.local`)
- **Password**: `admin123`
