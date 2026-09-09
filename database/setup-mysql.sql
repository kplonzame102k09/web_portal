CREATE DATABASE IF NOT EXISTS digitech_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'digitech_app'@'127.0.0.1' IDENTIFIED BY 'DigitechLocal2026!';
CREATE USER IF NOT EXISTS 'digitech_app'@'localhost' IDENTIFIED BY 'DigitechLocal2026!';
GRANT ALL PRIVILEGES ON digitech_portal.* TO 'digitech_app'@'127.0.0.1';
GRANT ALL PRIVILEGES ON digitech_portal.* TO 'digitech_app'@'localhost';
FLUSH PRIVILEGES;