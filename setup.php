<?php
// setup.php — Database schema aanmaken via mysqli
// Open via browser: http://jouw-nas-ip/netmap/setup.php
// Of via SSH: php setup.php

require_once __DIR__ . '/includes/config.php';

$errors = array();
$done   = array();
$isCLI  = php_sapi_name() === 'cli';

try {
    // Verbind zonder database (om hem aan te maken als hij niet bestaat)
    $db = new mysqli(null, DB_USER, DB_PASS, null, null, DB_SOCKET);
    if ($db->connect_error) {
        throw new Exception('Verbinding mislukt: ' . $db->connect_error);
    }
    $db->set_charset('utf8mb4');
    $done[] = 'Verbinding met MariaDB geslaagd';

    // Database aanmaken
    $db->query('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $db->select_db(DB_NAME);
    $done[] = 'Database `' . DB_NAME . '` aangemaakt of al aanwezig';

    // ── Tabel: devices ──
    $db->query('
        CREATE TABLE IF NOT EXISTS devices (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            mac         VARCHAR(17) UNIQUE NOT NULL,
            name        VARCHAR(255),
            ip          VARCHAR(15),
            vendor      VARCHAR(255),
            device_type VARCHAR(100),
            hostname    VARCHAR(255),
            is_gateway  TINYINT(1) DEFAULT 0,
            notes       TEXT,
            is_trusted  TINYINT(1) DEFAULT 0,
            ping_ms     INT,
            first_seen  DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_seen   DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');
    if ($db->error) throw new Exception('devices tabel: ' . $db->error);
    $done[] = 'Tabel devices aangemaakt';

    // ── Tabel: scan_history ──
    $db->query('
        CREATE TABLE IF NOT EXISTS scan_history (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            scanned_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            subnet      VARCHAR(20),
            hosts_found INT DEFAULT 0,
            duration_ms INT DEFAULT 0,
            scan_type   VARCHAR(50) DEFAULT \'arp\'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');
    if ($db->error) throw new Exception('scan_history tabel: ' . $db->error);
    $done[] = 'Tabel scan_history aangemaakt';

    // ── Tabel: device_events ──
    $db->query('
        CREATE TABLE IF NOT EXISTS device_events (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            device_id   INT,
            mac         VARCHAR(17),
            event_type  VARCHAR(50),
            old_value   VARCHAR(255),
            new_value   VARCHAR(255),
            occurred_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');
    if ($db->error) throw new Exception('device_events tabel: ' . $db->error);
    $done[] = 'Tabel device_events aangemaakt';

    // ── Tabel: oui_vendors ──
    $db->query('
        CREATE TABLE IF NOT EXISTS oui_vendors (
            oui    VARCHAR(8) NOT NULL PRIMARY KEY,
            vendor VARCHAR(255)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');
    if ($db->error) throw new Exception('oui_vendors tabel: ' . $db->error);
    $done[] = 'Tabel oui_vendors aangemaakt';

    // ── Indexes ──
    foreach (array(
        'CREATE INDEX IF NOT EXISTS idx_devices_mac   ON devices(mac)',
        'CREATE INDEX IF NOT EXISTS idx_events_device ON device_events(device_id)',
        'CREATE INDEX IF NOT EXISTS idx_events_time   ON device_events(occurred_at)',
    ) as $idx) {
        $db->query($idx);
    }
    $done[] = 'Indexes aangemaakt';

    // ── OUI vendor seed ──
    $vendors = array(
        array('00:00:0C','Cisco Systems'),    array('00:1A:A0','Dell Inc.'),
        array('00:1B:21','Intel Corp.'),      array('00:1D:60','Apple Inc.'),
        array('00:1E:C9','Apple Inc.'),       array('00:21:6A','Apple Inc.'),
        array('00:25:00','Apple Inc.'),       array('00:26:BB','Apple Inc.'),
        array('00:50:F2','Microsoft Corp.'),  array('00:15:5D','Microsoft Corp.'),
        array('00:1C:14','VMware Inc.'),      array('00:0C:29','VMware Inc.'),
        array('00:50:56','VMware Inc.'),      array('B8:27:EB','Raspberry Pi Foundation'),
        array('DC:A6:32','Raspberry Pi Foundation'), array('E4:5F:01','Raspberry Pi Foundation'),
        array('18:FE:34','Espressif Inc.'),   array('24:6F:28','Espressif Inc.'),
        array('A4:CF:12','Espressif Inc.'),   array('3C:07:54','Apple Inc.'),
        array('70:CD:60','Apple Inc.'),       array('A8:51:AB','Apple Inc.'),
        array('F0:99:BF','Apple Inc.'),       array('44:D8:84','Ubiquiti Networks'),
        array('DC:9F:DB','Ubiquiti Networks'),array('FC:EC:DA','Ubiquiti Networks'),
        array('00:13:D3','TP-Link Technologies'), array('54:C8:0F','TP-Link Technologies'),
        array('B0:4E:26','TP-Link Technologies'), array('30:B5:C2','Netgear'),
        array('C4:04:15','Netgear'),          array('A0:21:B7','Netgear'),
        array('CC:40:D0','Philips Hue'),      array('00:17:88','Philips Hue'),
        array('EC:B5:FA','Sonos Inc.'),       array('B8:E9:37','Sonos Inc.'),
        array('94:9F:3E','Samsung Electronics'), array('F8:04:2E','Samsung Electronics'),
        array('78:4F:43','LG Electronics'),   array('00:1C:62','ASUS'),
        array('50:46:5D','ASUS'),             array('AC:22:0B','ASUS'),
        array('E8:40:40','Linksys'),          array('00:17:F2','Apple Inc.'),
    );

    $stmt = $db->prepare('INSERT IGNORE INTO oui_vendors (oui, vendor) VALUES (?, ?)');
    foreach ($vendors as $v) {
        $stmt->bind_param('ss', $v[0], $v[1]);
        $stmt->execute();
    }
    $stmt->close();
    $done[] = count($vendors) . ' OUI vendor records ingevoerd';

    $db->close();

} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

// ── Output ────────────────────────────────────────────────────
if ($isCLI) {
    foreach ($done   as $d) echo "✅ $d\n";
    foreach ($errors as $e) echo "❌ $e\n";
    echo empty($errors) ? "\n✅ Setup compleet!\n" : "\n⚠️  Setup met fouten\n";
} else {
?><!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>NetMap Setup</title>
<style>
body{font-family:monospace;background:#0a0e1a;color:#e2eaf8;padding:40px;max-width:600px;margin:0 auto}
h1{color:#00e5ff}
.ok{color:#22d3a0}
.err{color:#f87171}
.btn{display:inline-block;margin-top:20px;padding:12px 24px;background:#00e5ff;color:#0a0e1a;text-decoration:none;border-radius:8px;font-weight:700}
</style>
</head>
<body>
<h1>NetMap — Database Setup</h1>
<?php foreach ($done   as $d): ?><div class="ok">✅ <?php echo htmlspecialchars($d); ?></div><?php endforeach; ?>
<?php foreach ($errors as $e): ?><div class="err">❌ <?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>
<?php if (empty($errors)): ?>
    <br><div class="ok">✅ Setup compleet!</div>
    <a class="btn" href="index.php">→ Open NetMap</a>
<?php else: ?>
    <br><div class="err">⚠️ Controleer DB_USER, DB_PASS en DB_HOST in includes/config.php</div>
<?php endif; ?>
</body>
</html>
<?php } ?>
