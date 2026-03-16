<?php
// oui_import.php — Volledige IEEE OUI database importeren
// Open via browser: http://jouw-nas-ip/netmap/oui_import.php
// Of via SSH: php oui_import.php
// VERWIJDER dit bestand na gebruik!

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$isCLI = php_sapi_name() === 'cli';
if (!$isCLI) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><title>OUI Import</title>';
    echo '<style>body{font-family:monospace;background:#0a0e1a;color:#e2eaf8;padding:40px;max-width:700px;margin:0 auto}';
    echo 'h1{color:#00e5ff}.ok{color:#22d3a0}.err{color:#f87171}.inf{color:#00e5ff}.warn{color:#fbbf24}';
    echo 'pre{background:#121722;padding:16px;border-radius:8px;overflow-x:auto;font-size:12px}';
    echo '.btn{display:inline-block;margin-top:20px;padding:12px 24px;background:#00e5ff;color:#0a0e1a;text-decoration:none;border-radius:8px;font-weight:700}</style></head><body>';
    echo '<h1>NetMap — OUI Database Import</h1>';
    ob_implicit_flush(true);
    ob_end_flush();
}

function out($msg, $class = '') {
    global $isCLI;
    if ($isCLI) {
        echo $msg . "\n";
    } else {
        echo '<div class="' . $class . '">' . htmlspecialchars($msg) . '</div>';
        flush();
    }
}

// ── Download IEEE OUI bestand ─────────────────────────────────
$ouiUrl  = 'https://standards-oui.ieee.org/oui/oui.txt';
$tmpFile = sys_get_temp_dir() . '/oui_ieee.txt';

out('Stap 1: IEEE OUI bestand downloaden...', 'inf');
out('  URL: ' . $ouiUrl, '');

// Probeer file_get_contents, anders curl, anders wget
$downloaded = false;
$rawData = null;

if (ini_get('allow_url_fopen')) {
    $ctx = stream_context_create(array('http' => array('timeout' => 30)));
    $rawData = @file_get_contents($ouiUrl, false, $ctx);
    if ($rawData !== false) { $downloaded = true; out('  ✅ Gedownload via file_get_contents', 'ok'); }
}

if (!$downloaded) {
    $ret = -1;
    exec('curl -s --max-time 30 -o ' . escapeshellarg($tmpFile) . ' ' . escapeshellarg($ouiUrl) . ' 2>&1', $out, $ret);
    if ($ret === 0 && file_exists($tmpFile)) {
        $rawData = file_get_contents($tmpFile);
        $downloaded = true;
        out('  ✅ Gedownload via curl', 'ok');
    }
}

if (!$downloaded) {
    $ret = -1;
    exec('wget -q --timeout=30 -O ' . escapeshellarg($tmpFile) . ' ' . escapeshellarg($ouiUrl) . ' 2>&1', $out, $ret);
    if ($ret === 0 && file_exists($tmpFile)) {
        $rawData = file_get_contents($tmpFile);
        $downloaded = true;
        out('  ✅ Gedownload via wget', 'ok');
    }
}

if (!$downloaded || !$rawData) {
    out('❌ Download mislukt. Controleer internetverbinding op de NAS.', 'err');
    out('   Je kunt oui.txt ook handmatig downloaden en uploaden naar /tmp/oui_ieee.txt', 'warn');

    // Probeer lokaal bestand als fallback
    if (file_exists($tmpFile)) {
        $rawData = file_get_contents($tmpFile);
        out('  ↩ Gevonden: lokaal bestand ' . $tmpFile, 'warn');
    } else {
        if (!$isCLI) echo '<a class="btn" href="index.php">← Terug</a></body></html>';
        exit(1);
    }
}

// ── Parse OUI bestand ─────────────────────────────────────────
out('Stap 2: OUI bestand parsen...', 'inf');

$entries = array();
$lines   = explode("\n", $rawData);
$total   = count($lines);

// IEEE oui.txt formaat:
// 00-00-0C   (hex)		Cisco Systems, Inc
// 00000C     (base 16)		Cisco Systems, Inc
//            <registrant details>
//

for ($i = 0; $i < $total; $i++) {
    $line = trim($lines[$i]);
    // Zoek "(hex)" regels: "00-00-0C   (hex)   Cisco Systems, Inc"
    if (strpos($line, '(hex)') !== false) {
        // Formaat: "XX-XX-XX   (hex)   Vendor Naam"
        if (preg_match('/^([0-9A-F]{2}-[0-9A-F]{2}-[0-9A-F]{2})\s+\(hex\)\s+(.+)$/i', $line, $m)) {
            $oui    = str_replace('-', ':', strtoupper($m[1]));
            $vendor = trim($m[2]);
            if ($vendor && $vendor !== '') {
                $entries[$oui] = $vendor;
            }
        }
    }
}

$count = count($entries);
out('  ✅ ' . number_format($count) . ' OUI entries geparsed', 'ok');

if ($count < 1000) {
    out('⚠️  Minder dan 1000 entries — bestand mogelijk onvolledig', 'warn');
}

// ── Database import ───────────────────────────────────────────
out('Stap 3: Database importeren...', 'inf');

try {
    $db = new mysqli(null, DB_USER, DB_PASS, DB_NAME, null, DB_SOCKET);
    if ($db->connect_error) throw new Exception($db->connect_error);
    $db->set_charset('utf8mb4');

    // Tabel leegmaken voor frisse import
    $db->query('TRUNCATE TABLE oui_vendors');
    out('  Bestaande OUI data gewist', '');

    // Batch insert — 500 per keer voor performance
    $batch = array();
    $inserted = 0;
    $batchSize = 500;

    foreach ($entries as $oui => $vendor) {
        $batch[] = '(' . $db->real_escape_string($oui) . ',' .
                   '"' . $db->real_escape_string(substr($vendor, 0, 255)) . '")';

        if (count($batch) >= $batchSize) {
            $sql = 'INSERT IGNORE INTO oui_vendors (oui, vendor) VALUES (' . implode('),(', $batch) . ')';
            // Verwijder de extra haakjes die we al toevoegden
            $sql = 'INSERT IGNORE INTO oui_vendors (oui, vendor) VALUES ' . implode(',', $batch);
            $db->query($sql);
            $inserted += $db->affected_rows;
            $batch = array();
            if ($inserted % 5000 === 0) {
                out('  ' . number_format($inserted) . ' ingevoerd...', '');
                flush();
            }
        }
    }

    // Resterende batch
    if (!empty($batch)) {
        $sql = 'INSERT IGNORE INTO oui_vendors (oui, vendor) VALUES ' . implode(',', $batch);
        $db->query($sql);
        $inserted += $db->affected_rows;
    }

    $db->close();
    out('  ✅ ' . number_format($inserted) . ' OUI vendors ingevoerd in database!', 'ok');

    // Opruimen
    @unlink($tmpFile);

    out('', '');
    out('✅ Import compleet! ' . number_format($inserted) . ' fabrikanten beschikbaar.', 'ok');

} catch (Exception $e) {
    out('❌ Database fout: ' . $e->getMessage(), 'err');
}

if (!$isCLI) {
    echo '<br><a class="btn" href="index.php">→ Open NetMap</a>';
    echo '<br><br><small style="color:var(--muted)">⚠️ Verwijder dit bestand na gebruik: oui_import.php</small>';
    echo '</body></html>';
}
