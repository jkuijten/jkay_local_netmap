<?php
// includes/scanner.php — Netwerk scanner (ARP + /proc/net/arp)
// Aangepast voor DS1511+: geen ping (raw socket geblokkeerd),
// leest /proc/net/arp direct, detecteert subnet automatisch
// error logging, can be disabled wen in working condition
ini_set('display_errors', 1);
error_reporting(E_ALL);
//
require_once __DIR__ . '/db.php';

class Scanner {

    // ── Ping via TCP socket (geen raw socket nodig) ──────────
    // Vervangt ping -c1 want die vereist CAP_NET_RAW rechten
    public static function pingHost(string $ip): ?int {
        $start = microtime(true);

        // Methode 1: TCP connect op poort 80 (webserver) of 443
        foreach ([80, 443, 22, 445, 8080] as $port) {
            $sock = @fsockopen($ip, $port, $errno, $errstr, 0.5);
            if ($sock) {
                fclose($sock);
                return (int) round((microtime(true) - $start) * 1000);
            }
        }

        // Methode 2: UDP socket trick — stuur leeg pakket, meet tijd
        // Werkt ook als geen poort open is maar host wel bestaat
        $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($sock) {
            socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 0, 'usec' => 500000]);
            @socket_connect($sock, $ip, 7);
            @socket_send($sock, '', 0, 0);
            socket_close($sock);
            $ms = (int) round((microtime(true) - $start) * 1000);
            // UDP geeft geen bevestiging, maar als host bestaat is verbinding snel
            return $ms < 500 ? $ms : null;
        }

        return null;
    }

    // ── Detecteer lokale interfaces ──────────────────────────
    public static function getInterfaces(): array {
        $interfaces = [];

        // Methode 1: ip route show
        $lines = [];
        exec('ip route show 2>/dev/null', $lines);
        foreach ($lines as $line) {
            if (preg_match('/^(\d+\.\d+\.\d+\.\d+\/\d+)\s+dev\s+(\S+)/', $line, $m)) {
                if (strpos($m[1], '169.254') !== 0 && strpos($m[1], '127') !== 0) {
                    $interfaces[] = ['subnet' => $m[1], 'iface' => $m[2]];
                }
            }
        }

        // Methode 2: /proc/net/arp uitlezen (shell-vrij)
        if (empty($interfaces)) {
            $interfaces = self::getInterfacesFromProc();
        }

        // Methode 3: ip addr show
        if (empty($interfaces)) {
            $addrLines = [];
            exec('ip -4 addr show 2>/dev/null', $addrLines);
            $currentIface = '';
            foreach ($addrLines as $line) {
                if (preg_match('/^\d+:\s+(\S+)/', $line, $m)) {
                    $currentIface = $m[1];
                }
                if (preg_match('/inet\s+(\d+\.\d+\.\d+\.\d+\/\d+)/', $line, $m)) {
                    $cidr = $m[1];
                    if (strpos($cidr, '127') !== 0) {
                        $interfaces[] = ['subnet' => $cidr, 'iface' => $currentIface];
                    }
                }
            }
        }

        return $interfaces;
    }

    // ── Interfaces uit /proc/net/fib_trie (puur PHP) ─────────
    private static function getInterfacesFromProc(): array {
        $interfaces = [];
        // Lees /proc/net/if_inet6 of bepaal via /proc/net/arp welk subnet actief is
        if (!is_readable('/proc/net/arp')) return [];

        $seen = [];
        $lines = file('/proc/net/arp', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        array_shift($lines); // header overslaan

        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 6) continue;
            [$ip, , , , , $iface] = $parts;
            // Bepaal /24 subnet uit IP
            $parts_ip = explode('.', $ip);
            if (count($parts_ip) === 4) {
                $subnet = $parts_ip[0].'.'.$parts_ip[1].'.'.$parts_ip[2].'.0/24';
                $key = $subnet . '@' . $iface;
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $interfaces[] = ['subnet' => $subnet, 'iface' => $iface];
                }
            }
        }
        return $interfaces;
    }

    // ── ARP tabel lezen uit /proc/net/arp (puur PHP) ─────────
    // Geen shell nodig, werkt altijd op Linux
    private static function scanViaProcArp(): array {
        $hosts = [];
        if (!is_readable('/proc/net/arp')) return [];

        $lines = file('/proc/net/arp', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        array_shift($lines); // header overslaan: "IP address HW type Flags HW address Mask Device"

        foreach ($lines as $line) {
            // Kolommen: IP, hw_type, flags, mac, mask, device
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 4) continue;

            $ip    = $parts[0];
            $flags = hexdec($parts[2]); // 0x2 = complete, 0x0 = incomplete
            $mac   = strtoupper($parts[3]);

            // Sla incomplete/ongeldige entries over
            if ($flags === 0 || $mac === '00:00:00:00:00:00' || $mac === 'FF:FF:FF:FF:FF:FF') continue;
            if (!filter_var($ip, FILTER_VALIDATE_IP)) continue;

            $hosts[] = [
                'ip'       => $ip,
                'mac'      => $mac,
                'hostname' => null,
                'vendor'   => null,
            ];
        }
        return $hosts;
    }

    // ── ARP tabel via shell (fallback) ───────────────────────
    private static function scanViaARP(): array {
        $hosts = [];
        $lines = [];
        exec('arp -a 2>/dev/null', $lines, $ret);

        if ($ret === 0 && !empty($lines)) {
            foreach ($lines as $line) {
                if (preg_match('/\((\d+\.\d+\.\d+\.\d+)\)\s+at\s+([0-9a-fA-F:]{17})/', $line, $m)) {
                    $hosts[] = ['ip' => $m[1], 'mac' => strtoupper($m[2]), 'hostname' => null, 'vendor' => null];
                }
            }
        }

        // ip neigh als arp -a leeg is
        if (empty($hosts)) {
            $lines = [];
            exec('ip neigh show 2>/dev/null', $lines);
            foreach ($lines as $line) {
                if (preg_match('/^(\d+\.\d+\.\d+\.\d+).*lladdr\s+([0-9a-fA-F:]{17})/', $line, $m)) {
                    $hosts[] = ['ip' => $m[1], 'mac' => strtoupper($m[2]), 'hostname' => null, 'vendor' => null];
                }
            }
        }
        return $hosts;
    }

    // ── Python ARP scan ──────────────────────────────────────
    private static function scanViaPython(string $subnet): array {
        $script = BASE_DIR . '/cron/arp_scan.py';
        if (!file_exists($script)) return [];
        $out = shell_exec(PYTHON_PATH . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($subnet) . ' 2>/dev/null');
        if (!$out) return [];
        $data = json_decode($out, true);
        return is_array($data) ? $data : [];
    }

    // ── nmap scan ────────────────────────────────────────────
    private static function scanViaNmap(string $subnet): array {
        $hosts = [];
        $xml = shell_exec(NMAP_PATH . ' -sn --send-ip -oX - ' . escapeshellarg($subnet) . ' 2>/dev/null');
        if (!$xml) return [];
        $dom = new DOMDocument();
        if (!@$dom->loadXML($xml)) return [];
        foreach ($dom->getElementsByTagName('host') as $host) {
            $status = $host->getElementsByTagName('status')->item(0);
            if (!$status || $status->getAttribute('state') !== 'up') continue;
            $ip = $mac = $vendor = $hostname = null;
            foreach ($host->getElementsByTagName('address') as $addr) {
                if ($addr->getAttribute('addrtype') === 'ipv4') $ip = $addr->getAttribute('addr');
                if ($addr->getAttribute('addrtype') === 'mac') {
                    $mac    = strtoupper($addr->getAttribute('addr'));
                    $vendor = $addr->getAttribute('vendor') ?: null;
                }
            }
            foreach ($host->getElementsByTagName('hostname') as $hn) {
                if ($hn->getAttribute('type') === 'PTR') $hostname = $hn->getAttribute('name');
            }
            if ($ip) $hosts[] = compact('ip', 'mac', 'vendor', 'hostname');
        }
        return $hosts;
    }

    // ── Hoofd scan functie ───────────────────────────────────
    public static function runScan(string $subnet = SCAN_SUBNET): array {
        if (file_exists(LOCK_FILE) && (time() - filemtime(LOCK_FILE)) < 60) {
            return ['error' => 'Scan al bezig'];
        }
        file_put_contents(LOCK_FILE, getmypid());

        try {
            $start    = microtime(true);
            $scanType = 'proc-arp';
            $hosts    = [];

            // Volgorde: /proc/net/arp (snelst, geen shell) → nmap → Python → shell arp
            $hosts = self::scanViaProcArp();
            $scanType = 'proc-arp';

            if (empty($hosts) && SCAN_METHOD === 'nmap' && is_executable(NMAP_PATH)) {
                $hosts    = self::scanViaNmap($subnet);
                $scanType = 'nmap';
            }
            if (empty($hosts)) {
                $hosts    = self::scanViaPython($subnet);
                $scanType = 'python-arp';
            }
            if (empty($hosts)) {
                $hosts    = self::scanViaARP();
                $scanType = 'shell-arp';
            }

            // Filter op subnet als opgegeven
            if ($subnet && $subnet !== '0.0.0.0/0') {
                $hosts = self::filterBySubnet($hosts, $subnet);
            }

            // Vendor lookup + latency meting
            foreach ($hosts as &$host) {
                if (empty($host['vendor']) && !empty($host['mac'])) {
                    $host['vendor'] = DB::lookupVendor($host['mac']);
                }
                if (!empty($host['ip'])) {
                    $host['ping_ms'] = self::pingHost($host['ip']);
                }
            }
            unset($host);

            $duration = (int) round((microtime(true) - $start) * 1000);

            DB::insert(
                'INSERT INTO scan_history (subnet, hosts_found, duration_ms, scan_type) VALUES (?,?,?,?)',
                [$subnet, count($hosts), $duration, $scanType]
            );

            $events = self::updateDevices($hosts);

            return [
                'ok'        => true,
                'hosts'     => count($hosts),
                'events'    => $events,
                'duration'  => $duration,
                'scan_type' => $scanType,
                'subnet'    => $subnet,
            ];
        } finally {
            @unlink(LOCK_FILE);
        }
    }

    // ── Filter hosts op subnet ───────────────────────────────
    private static function filterBySubnet(array $hosts, string $subnet): array {
        // Zet subnet om naar netwerkadres + masker
        if (strpos($subnet, '/') === false) return $hosts;
        [$network, $bits] = explode('/', $subnet);
        $mask    = ~((1 << (32 - (int)$bits)) - 1) & 0xFFFFFFFF;
        $netLong = ip2long($network) & $mask;

        return array_filter($hosts, function($host) use ($netLong, $mask) {
            $ip = ip2long($host['ip'] ?? '');
            return $ip !== false && ($ip & $mask) === $netLong;
        });
    }

    // ── Database update ──────────────────────────────────────
    private static function updateDevices(array $hosts): array {
        $events = [];
        foreach ($hosts as $host) {
            if (empty($host['mac'])) continue;
            $mac = $host['mac'];

            $existing = DB::queryOne('SELECT * FROM devices WHERE mac = ?', [$mac]);

            if (!$existing) {
                $id = DB::insert(
                    'INSERT INTO devices (mac, ip, vendor, hostname, last_seen, first_seen)
                     VALUES (?,?,?,?,NOW(),NOW())',
                    [$mac, $host['ip'] ?? null, $host['vendor'] ?? null, $host['hostname'] ?? null]
                );
                DB::insert(
                    'INSERT INTO device_events (device_id, mac, event_type, new_value) VALUES (?,?,?,?)',
                    [$id, $mac, 'new', $host['ip'] ?? null]
                );
                $events[] = ['type' => 'new', 'mac' => $mac, 'ip' => $host['ip'] ?? null, 'vendor' => $host['vendor'] ?? null];
            } else {
                if (isset($host['ip']) && $existing['ip'] !== $host['ip']) {
                    DB::insert(
                        'INSERT INTO device_events (device_id, mac, event_type, old_value, new_value) VALUES (?,?,?,?,?)',
                        [$existing['id'], $mac, 'ip_changed', $existing['ip'], $host['ip']]
                    );
                    $events[] = ['type' => 'ip_changed', 'mac' => $mac, 'old_ip' => $existing['ip'], 'new_ip' => $host['ip']];
                }
                DB::execute(
                    'UPDATE devices SET ip=?, last_seen=NOW(), updated_at=NOW(), ping_ms=? WHERE mac=?',
                    [$host['ip'] ?? $existing['ip'], $host['ping_ms'] ?? null, $mac]
                );
            }
        }
        $cutoff = date('Y-m-d H:i:s', time() - GONE_AFTER);
        $stale  = DB::query('SELECT mac, name FROM devices WHERE last_seen < ?', [$cutoff]);
        foreach ($stale as $dev) {
            $events[] = ['type' => 'offline', 'mac' => $dev['mac'], 'name' => $dev['name']];
        }
        return $events;
    }

    // ── Haal één apparaat op ─────────────────────────────────
    public static function getDevice(int $id): ?array {
        $row = DB::queryOne('SELECT * FROM devices WHERE id = ?', [$id]);
        if (!$row) return null;
        $row['is_gateway'] = (bool)$row['is_gateway'];
        $row['is_trusted'] = (bool)$row['is_trusted'];
        return $row;
    }

    // ── Haal alle apparaten op ───────────────────────────────
    public static function getDevices(): array {
        $rows = DB::query(
            'SELECT d.*, TIMESTAMPDIFF(SECOND, d.last_seen, NOW()) AS seconds_ago
             FROM devices d ORDER BY d.is_gateway DESC, d.last_seen DESC'
        );
        return array_map(function($d) {
            $secs            = (int)$d['seconds_ago'];
            $d['status']     = $secs < OFFLINE_AFTER ? 'online' : ($secs < GONE_AFTER ? 'idle' : 'offline');
            $d['is_gateway'] = (bool)$d['is_gateway'];
            $d['is_trusted'] = (bool)$d['is_trusted'];
            return $d;
        }, $rows);
    }
}
