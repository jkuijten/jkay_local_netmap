<?php
// includes/fingbox.php — Fingbox API sync

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

class Fingbox {

    // ── Haal apparaten op via Fingbox API ────────────────────
    public static function fetchDevices($apiUrl, $apiKey) {
        // Normaliseer URL
        $apiUrl = rtrim($apiUrl, '/');

        // Probeer standaard Fingbox API endpoint
        $endpoints = array(
            $apiUrl . '/devices',
            $apiUrl . '/api/devices',
            $apiUrl . '/api/v1/devices',
            $apiUrl . '/v1/devices',
        );

        $lastError = 'Geen endpoint bereikbaar';
        foreach ($endpoints as $endpoint) {
            $result = self::httpGet($endpoint, $apiKey);
            if ($result['ok']) return $result;
            $lastError = $result['error'];
        }
        return array('ok' => false, 'error' => $lastError);
    }

    // ── HTTP GET helper ──────────────────────────────────────
    private static function httpGet($url, $apiKey) {
        $opts = array(
            'http' => array(
                'method'  => 'GET',
                'header'  => 'Authorization: Bearer ' . $apiKey . "\r\n" .
                             'X-API-Key: ' . $apiKey . "\r\n" .
                             'Accept: application/json' . "\r\n",
                'timeout' => 10,
                'ignore_errors' => true,
            ),
            'ssl' => array(
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ),
        );
        $ctx  = stream_context_create($opts);
        $body = @file_get_contents($url, false, $ctx);

        if ($body === false) {
            return array('ok' => false, 'error' => 'Verbinding mislukt: ' . $url);
        }

        $http_status = 0;
        if (isset($http_response_header)) {
            preg_match('{HTTP/\S*\s(\d{3})}', $http_response_header[0], $m);
            $http_status = isset($m[1]) ? (int)$m[1] : 0;
        }

        if ($http_status >= 400) {
            return array('ok' => false, 'error' => 'HTTP ' . $http_status . ' van ' . $url);
        }

        $data = json_decode($body, true);
        if ($data === null) {
            return array('ok' => false, 'error' => 'Ongeldige JSON response van ' . $url);
        }

        return array('ok' => true, 'data' => $data, 'url' => $url);
    }

    // ── Normaliseer Fingbox apparaat naar NetMap formaat ─────
    public static function normalizeDevice($raw) {
        // Fingbox API kan verschillende veldnamen gebruiken
        $mac = strtoupper(trim(
            $raw['mac_address'] ?? $raw['mac'] ?? $raw['hwAddress'] ?? $raw['macAddress'] ?? ''
        ));
        $mac = preg_replace('/[^0-9A-F]/', '', $mac);
        if (strlen($mac) === 12) {
            $mac = implode(':', str_split($mac, 2));
        }
        if (strlen($mac) !== 17) return null;

        $ip = trim(
            $raw['ip_address'] ?? $raw['ip'] ?? $raw['ipv4'] ?? $raw['lastIp'] ?? ''
        );

        $name = trim(
            $raw['name'] ?? $raw['alias'] ?? $raw['deviceName'] ?? $raw['hostname'] ?? ''
        );

        $vendor = trim(
            $raw['manufacturer'] ?? $raw['vendor'] ?? $raw['make'] ?? ''
        );

        $type = trim(
            $raw['type'] ?? $raw['deviceType'] ?? $raw['category'] ?? ''
        );

        $trusted = !empty($raw['trusted']) || !empty($raw['is_trusted']) ||
                   (isset($raw['state']) && strtolower($raw['state']) === 'trusted');

        return array(
            'mac'         => $mac,
            'ip'          => $ip ?: null,
            'name'        => $name ?: null,
            'vendor'      => $vendor ?: null,
            'device_type' => $type ?: null,
            'is_trusted'  => $trusted ? 1 : 0,
        );
    }

    // ── Sync Fingbox apparaten naar NetMap database ──────────
    public static function sync($apiUrl, $apiKey) {
        $result = self::fetchDevices($apiUrl, $apiKey);
        if (!$result['ok']) return $result;

        $data = $result['data'];

        // Fingbox kan apparaten op verschillende plekken in de response zetten
        $rawDevices = array();
        if (isset($data['devices']) && is_array($data['devices'])) {
            $rawDevices = $data['devices'];
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $rawDevices = $data['data'];
        } elseif (is_array($data) && !empty($data)) {
            // Soms is de root al een array van devices
            if (isset($data[0]) && is_array($data[0])) {
                $rawDevices = $data;
            }
        }

        if (empty($rawDevices)) {
            return array('ok' => false, 'error' => 'Geen apparaten gevonden in API response. Controleer het API endpoint.');
        }

        $imported = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = array();

        foreach ($rawDevices as $raw) {
            $device = self::normalizeDevice($raw);
            if (!$device || !$device['mac']) { $skipped++; continue; }

            try {
                DB::execute(
                    'INSERT INTO devices (mac, name, ip, vendor, device_type, is_trusted, last_seen, first_seen)
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE
                       name        = COALESCE(NULLIF(VALUES(name), ""), name),
                       ip          = COALESCE(NULLIF(VALUES(ip), ""), ip),
                       vendor      = COALESCE(NULLIF(VALUES(vendor), ""), vendor),
                       device_type = COALESCE(NULLIF(VALUES(device_type), ""), device_type),
                       is_trusted  = VALUES(is_trusted),
                       updated_at  = NOW()',
                    array(
                        $device['mac'],
                        $device['name'],
                        $device['ip'],
                        $device['vendor'],
                        $device['device_type'],
                        $device['is_trusted'],
                    )
                );
                $imported++;
            } catch (Exception $e) {
                $errors[] = $device['mac'] . ': ' . $e->getMessage();
                $skipped++;
            }
        }

        return array(
            'ok'       => true,
            'imported' => $imported,
            'skipped'  => $skipped,
            'total'    => count($rawDevices),
            'endpoint' => $result['url'],
            'errors'   => $errors,
        );
    }
}
