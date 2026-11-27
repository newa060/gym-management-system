# Gym Membership & Class Management System

## System Requirements
- XAMPP with PHP 8.x, MySQL, and Apache
- Required PHP extensions: pdo_mysql, mbstring

## Installation & Setup
1. Start Apache and MySQL from the XAMPP Control Panel.
2. Copy project to your XAMPP web root (`htdocs/gym_system`).
3. Open phpMyAdmin and import `database/schema.sql` (creates and populates `gym_db`).
4. `config/config.php` is pre-configured with `DB_NAME` set to `gym_db`.
5. First-run check: browse to `http://localhost/gym_system`.

## Default Login Credentials
- **URL**: `http://localhost/gym_system/pages/login.php`
- **Username**: `admin` (or `admin@apexfitness.local`)
- **Password**: `admin123`
