<?php
// includes/config.php — NetMap configuratie voor Synology DS1511+

// ── Database ─────────────────────────────────────────────────
// Gebruik 'localhost' — verbindt via Unix socket zoals andere apps op deze NAS
// net zoals andere applicaties op deze NAS
define('DB_HOST', 'localhost');
define('DB_NAME',   'netmap');
define('DB_USER',   'netmap_user');
define('DB_PASS',   'NetMap2024!');
define('DB_CHARSET','utf8mb4');
// Synology MariaDB 10 socket pad
define('DB_SOCKET', '/run/mysqld/mysqld10.sock');

// ── Netwerk ───────────────────────────────────────────────────
define('SCAN_SUBNET',   '192.168.2.0/24');
define('SCAN_INTERVAL', 5);
define('OFFLINE_AFTER', 600);
define('GONE_AFTER',    3600);

// ── Paden ─────────────────────────────────────────────────────
define('BASE_DIR',   dirname(__DIR__));
define('EXPORT_DIR', BASE_DIR . '/exports');
define('LOCK_FILE',  '/tmp/netmap_scan.lock');

// ── Scan methode ──────────────────────────────────────────────
define('SCAN_METHOD', 'arp');
define('NMAP_PATH',   '/usr/bin/nmap');
define('PYTHON_PATH', '/usr/local/bin/python3');

// ── Fingbox API sync ──────────────────────────────────────────
// Vul in via het dashboard of direct hier:
define('FINGBOX_URL',     '');   // bijv. http://192.168.2.50:49090
define('FINGBOX_API_KEY', '');   // API key uit Fingbox instellingen
date_default_timezone_set('Europe/Amsterdam');

if (!is_dir(EXPORT_DIR)) {
    mkdir(EXPORT_DIR, 0755, true);
}
