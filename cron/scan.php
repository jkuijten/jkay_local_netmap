#!/usr/bin/env php
<?php
// cron/scan.php — Auto-scan script voor crontab
// Crontab instelling: */5 * * * * php /volume1/web/netmap/cron/scan.php >> /tmp/netmap_cron.log 2>&1

define('RUNNING_FROM_CRON', true);
chdir(dirname(__DIR__));

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/scanner.php';

$ts = date('Y-m-d H:i:s');
echo "[$ts] NetMap auto-scan gestart...\n";

$result = Scanner::runScan(SCAN_SUBNET);

if (isset($result['error'])) {
    echo "[$ts] ⚠️  {$result['error']}\n";
    exit(1);
}

echo "[$ts] ✅ Klaar: {$result['hosts']} hosts, {$result['duration']}ms ({$result['scan_type']})\n";

if (!empty($result['events'])) {
    foreach ($result['events'] as $ev) {
        $type = $ev['type'];
        if ($type === 'new') {
            echo "[$ts] 🆕 Nieuw apparaat: {$ev['ip']} ({$ev['mac']}) — {$ev['vendor']}\n";
        } elseif ($type === 'ip_changed') {
            echo "[$ts] 🔄 IP gewijzigd: {$ev['mac']} — {$ev['old_ip']} → {$ev['new_ip']}\n";
        }
    }
}
