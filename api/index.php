<?php
// api/index.php — JSON REST API voor NetMap (PHP 7.4 compatibel)
// Logging error can be disabled when in working condition
ini_set('display_errors', 1);
error_reporting(E_ALL);
//
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/scanner.php';
require_once __DIR__ . '/../includes/exporter.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$id     = (int)($_GET['id'] ?? $body['id'] ?? 0);

function ok($data = []) {
    echo json_encode(array_merge(['ok' => true], (array)$data));
}

function err($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}

try {

    // ── Apparaten ────────────────────────────────────────────
    if ($action === 'devices') {
        if ($method === 'GET') {
            if ($id) {
                $d = DB::queryOne('SELECT * FROM devices WHERE id = ?', [$id]);
                $d ? ok(['device' => $d]) : err('Niet gevonden', 404);
            } else {
                ok(['devices' => Scanner::getDevices()]);
            }

        } elseif ($method === 'POST' || $method === 'PUT') {
            $mac = strtoupper(trim($body['mac'] ?? ''));
            if (!$mac) { err('MAC verplicht'); return; }
            DB::execute(
                'INSERT INTO devices (mac, name, ip, vendor, device_type, hostname, is_gateway, notes, is_trusted)
                 VALUES (?,?,?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                   name        = COALESCE(NULLIF(VALUES(name),""), name),
                   ip          = COALESCE(NULLIF(VALUES(ip),""), ip),
                   device_type = COALESCE(NULLIF(VALUES(device_type),""), device_type),
                   notes       = COALESCE(NULLIF(VALUES(notes),""), notes),
                   is_trusted  = VALUES(is_trusted),
                   updated_at  = NOW()',
                [
                    $mac,
                    $body['name']        ?? null,
                    $body['ip']          ?? null,
                    $body['vendor']      ?? DB::lookupVendor($mac),
                    $body['device_type'] ?? null,
                    $body['hostname']    ?? null,
                    !empty($body['is_gateway']) ? 1 : 0,
                    $body['notes']       ?? null,
                    !empty($body['is_trusted'])  ? 1 : 0,
                ]
            );
            $device = DB::queryOne('SELECT * FROM devices WHERE mac = ?', [$mac]);
            ok(['device' => $device]);

        } elseif ($method === 'DELETE') {
            if (!$id) { err('ID verplicht'); return; }
            DB::execute('DELETE FROM devices WHERE id = ?', [$id]);
            ok();
        }

    // ── Scan ─────────────────────────────────────────────────
    } elseif ($action === 'scan') {
        if (file_exists(LOCK_FILE) && (time() - filemtime(LOCK_FILE)) < 60) {
            err('Scan al bezig'); return;
        }
        $subnet = $body['subnet'] ?? SCAN_SUBNET;
        $result = Scanner::runScan($subnet);
        ok($result);

    // ── Scan history ─────────────────────────────────────────
    } elseif ($action === 'history') {
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        ok(['history' => DB::query(
            'SELECT * FROM scan_history ORDER BY scanned_at DESC LIMIT ?', [$limit]
        )]);

    // ── Events ───────────────────────────────────────────────
    } elseif ($action === 'events') {
        $limit = min((int)($_GET['limit'] ?? 50), 200);
        ok(['events' => DB::query(
            'SELECT e.*, d.name AS device_name FROM device_events e
             LEFT JOIN devices d ON e.device_id = d.id
             ORDER BY e.occurred_at DESC LIMIT ?', [$limit]
        )]);

    // ── Interfaces ───────────────────────────────────────────
    } elseif ($action === 'interfaces') {
        ok(['interfaces' => Scanner::getInterfaces()]);

    // ── Poll ─────────────────────────────────────────────────
    } elseif ($action === 'poll') {
        $since    = $_GET['since'] ?? date('Y-m-d H:i:s', time() - 30);
        $devices  = Scanner::getDevices();
        $events   = DB::query(
            'SELECT e.*, d.name AS device_name FROM device_events e
             LEFT JOIN devices d ON e.device_id = d.id
             WHERE e.occurred_at > ? ORDER BY e.occurred_at DESC LIMIT 20',
            [$since]
        );
        $lastScan = DB::queryOne('SELECT * FROM scan_history ORDER BY scanned_at DESC LIMIT 1');
        $scanning = file_exists(LOCK_FILE) && (time() - filemtime(LOCK_FILE)) < 60;
        ok(compact('devices', 'events', 'lastScan', 'scanning'));

    // ── Export CSV ───────────────────────────────────────────
    } elseif ($action === 'export_csv') {
        $devices  = Scanner::getDevices();
        $filepath = Exporter::exportCSV($devices);
        $filename = basename($filepath);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;

    // ── Export HTML/PDF ──────────────────────────────────────
    } elseif ($action === 'export_html') {
        $devices  = Scanner::getDevices();
        $history  = DB::query('SELECT * FROM scan_history ORDER BY scanned_at DESC LIMIT 10');
        $filepath = Exporter::exportHTML($devices, $history);
        header('Content-Type: text/html; charset=utf-8');
        readfile($filepath);
        exit;

    // ── Status ───────────────────────────────────────────────
    } elseif ($action === 'status') {
        ok([
            'version'  => '2.1.0-php',
            'runtime'  => 'PHP ' . PHP_VERSION,
            'scanning' => file_exists(LOCK_FILE) && (time() - filemtime(LOCK_FILE)) < 60,
            'subnet'   => SCAN_SUBNET,
            'uptime'   => time(),
        ]);

    } else {
        err('Onbekende actie: ' . htmlspecialchars($action), 404);
    }

} catch (Throwable $e) {
    err('Server fout: ' . $e->getMessage(), 500);
}
