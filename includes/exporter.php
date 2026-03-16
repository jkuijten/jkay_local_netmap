<?php
// includes/exporter.php — PDF en CSV export (pure PHP, geen dependencies)

require_once __DIR__ . '/db.php';

class Exporter {

    // ── CSV Export ────────────────────────────────────────────
    public static function exportCSV(array $devices): string {
        $filename = 'netmap_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = EXPORT_DIR . '/' . $filename;

        $fp = fopen($filepath, 'w');
        // BOM voor Excel NL
        fwrite($fp, "\xEF\xBB\xBF");

        // Header
        fputcsv($fp, [
            'Naam', 'IP-adres', 'MAC-adres', 'Fabrikant', 'Type',
            'Hostname', 'Status', 'Ping (ms)', 'Vertrouwd',
            'Eerste gezien', 'Laatste gezien', 'Notities'
        ], ';');

        foreach ($devices as $d) {
            fputcsv($fp, [
                $d['name']        ?? '',
                $d['ip']          ?? '',
                $d['mac']         ?? '',
                $d['vendor']      ?? '',
                $d['device_type'] ?? '',
                $d['hostname']    ?? '',
                $d['status']      ?? '',
                $d['ping_ms']     ?? '',
                ($d['is_trusted'] ? 'Ja' : 'Nee'),
                self::fmtDate($d['first_seen'] ?? null),
                self::fmtDate($d['last_seen']  ?? null),
                $d['notes']       ?? '',
            ], ';');
        }
        fclose($fp);
        return $filepath;
    }

    // ── PDF Export (pure PHP — geen mPDF/TCPDF nodig) ────────
    // Genereert een nette HTML pagina die de browser kan afdrukken als PDF
    public static function exportHTML(array $devices, array $history): string {
        $filename = 'netmap_' . date('Y-m-d_H-i-s') . '.html';
        $filepath = EXPORT_DIR . '/' . $filename;

        $total   = count($devices);
        $online  = count(array_filter($devices, fn($d) => $d['status'] === 'online'));
        $idle    = count(array_filter($devices, fn($d) => $d['status'] === 'idle'));
        $offline = count(array_filter($devices, fn($d) => $d['status'] === 'offline'));
        $unknown = count(array_filter($devices, fn($d) => empty($d['name'])));
        $genDate = date('d-m-Y H:i:s');

        $rows = '';
        foreach ($devices as $d) {
            $status = $d['status'] ?? '';
            if ($status === 'online') {
                $statusClass = 'status-online';
            } elseif ($status === 'idle') {
                $statusClass = 'status-idle';
            } else {
                $statusClass = 'status-offline';
            }
            $ping = isset($d['ping_ms']) ? $d['ping_ms'] . 'ms' : '—';
            $rows .= sprintf(
                '<tr><td>%s</td><td class="mono">%s</td><td class="mono">%s</td>
                     <td>%s</td><td>%s</td><td class="mono">%s</td>
                     <td><span class="%s">%s</span></td></tr>',
                htmlspecialchars($d['name']    ?? '(onbekend)'),
                htmlspecialchars($d['ip']      ?? '—'),
                htmlspecialchars($d['mac']     ?? '—'),
                htmlspecialchars($d['vendor']  ?? '—'),
                htmlspecialchars($d['device_type'] ?? '—'),
                $ping,
                $statusClass,
                htmlspecialchars($d['status']  ?? '?')
            );
        }

        $scanRows = '';
        foreach (array_slice($history, 0, 10) as $s) {
            $scanRows .= sprintf(
                '<tr><td>%s</td><td class="mono">%s</td><td>%d hosts</td><td class="mono">%dms</td><td>%s</td></tr>',
                self::fmtDate($s['scanned_at']),
                htmlspecialchars($s['subnet'] ?? ''),
                $s['hosts_found'],
                $s['duration_ms'],
                htmlspecialchars($s['scan_type'] ?? '')
            );
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>NetMap Rapport — {$genDate}</title>
<style>
  @media print { .no-print { display:none; } body { margin:0; } }
  body { font-family: 'Segoe UI', Arial, sans-serif; color:#1e293b; margin:0; padding:20px; background:#f8fafc; }
  .header { background:#0a0e1a; color:#00e5ff; padding:24px 32px; border-radius:10px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; }
  .header h1 { margin:0; font-size:28px; } .header span { color:#94a3b8; font-size:13px; }
  .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:24px; }
  .stat { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; text-align:center; }
  .stat-n { font-size:32px; font-weight:900; } .stat-l { font-size:11px; color:#64748b; margin-top:4px; }
  .c-blue{color:#0284c7} .c-green{color:#059669} .c-red{color:#dc2626} .c-yellow{color:#d97706}
  h2 { font-size:16px; color:#0f172a; border-bottom:2px solid #e2e8f0; padding-bottom:8px; margin:24px 0 12px; }
  table { width:100%; border-collapse:collapse; font-size:12px; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.06); }
  th { background:#0a0e1a; color:#00e5ff; padding:9px 12px; text-align:left; font-size:11px; letter-spacing:.5px; }
  td { padding:8px 12px; border-bottom:1px solid #f1f5f9; } tr:last-child td { border:none; }
  tr:nth-child(even) td { background:#f8fafc; }
  .mono { font-family:'Courier New',monospace; font-size:11px; }
  .status-online  { background:#dcfce7; color:#16a34a; padding:2px 8px; border-radius:4px; font-weight:700; font-size:10px; }
  .status-idle    { background:#fef9c3; color:#ca8a04; padding:2px 8px; border-radius:4px; font-weight:700; font-size:10px; }
  .status-offline { background:#fee2e2; color:#dc2626; padding:2px 8px; border-radius:4px; font-weight:700; font-size:10px; }
  .btn { background:#0284c7; color:#fff; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; margin-right:8px; }
  .btn:hover{background:#0369a1} .btn-sec{background:#64748b}
  footer { text-align:center; color:#94a3b8; font-size:11px; margin-top:32px; padding-top:16px; border-top:1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="header">
  <div><h1>NetMap</h1><div style="color:#94a3b8;font-size:13px;margin-top:4px">Thuis Netwerk Rapport</div></div>
  <span>Gegenereerd: {$genDate}</span>
</div>
<div class="no-print" style="margin-bottom:20px">
  <button class="btn" onclick="window.print()">🖨️ Afdrukken / Opslaan als PDF</button>
  <button class="btn btn-sec" onclick="window.close()">✕ Sluiten</button>
</div>
<div class="stats">
  <div class="stat"><div class="stat-n c-blue">{$total}</div><div class="stat-l">Totaal</div></div>
  <div class="stat"><div class="stat-n c-green">{$online}</div><div class="stat-l">Online</div></div>
  <div class="stat"><div class="stat-n c-yellow">{$idle}</div><div class="stat-l">Idle</div></div>
  <div class="stat"><div class="stat-n c-red">{$offline}</div><div class="stat-l">Offline</div></div>
</div>
<h2>Apparaten ({$total})</h2>
<table>
<thead><tr><th>Naam</th><th>IP-adres</th><th>MAC-adres</th><th>Fabrikant</th><th>Type</th><th>Ping</th><th>Status</th></tr></thead>
<tbody>{$rows}</tbody>
</table>
<h2>Recente Scans</h2>
<table>
<thead><tr><th>Tijdstip</th><th>Subnet</th><th>Hosts</th><th>Duur</th><th>Methode</th></tr></thead>
<tbody>{$scanRows}</tbody>
</table>
<footer>NetMap v2.1 PHP — DS1511 — Vertrouwelijk netwerkoverzicht</footer>
</body></html>
HTML;
        file_put_contents($filepath, $html);
        return $filepath;
    }

    private static function fmtDate(?string $dt): string {
        if (!$dt) return '—';
        return date('d-m-Y H:i', strtotime($dt));
    }
}
