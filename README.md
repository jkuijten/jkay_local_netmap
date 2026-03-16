# NetMap v2.1 — PHP versie (Synology DS1511+)

Lokale netwerkmapper speciaal gebouwd voor de Synology DS1511+ (32-bit Intel Atom,
DSM 6.x). Gebruikt PHP + MariaDB + Web Station — geen Node.js, geen Bun, geen npm.

## Installatie op DS1511+

### Stap 1 — Voorbereiding in DSM
1. Open **Package Center** → installeer **MariaDB 5** (of 10 als beschikbaar)
2. Open **Package Center** → installeer **Web Station**
3. Open **Package Center** → installeer **PHP 7.4** (of hoger)
4. Open **Web Station** → stel de standaard PHP versie in

### Stap 2 — Bestanden uploaden
```
Upload de hele netmap-php map naar:
/volume1/web/netmap/
```
Via **File Station** of via SSH/SFTP.

### Stap 3 — Database aanmaken via SSH
```bash
# SSH in je NAS
ssh admin@jouw-nas-ip

# MariaDB gebruiker aanmaken
mysql -u root -p
  CREATE DATABASE netmap CHARACTER SET utf8mb4;
  CREATE USER 'netmap_user'@'localhost' IDENTIFIED BY 'jouw_wachtwoord';
  GRANT ALL ON netmap.* TO 'netmap_user'@'localhost';
  EXIT;
```

### Stap 4 — Configuratie aanpassen
Bewerk `/volume1/web/netmap/includes/config.php`:
```php
define('DB_PASS',   'jouw_wachtwoord');     // zelfde als stap 3
define('SCAN_SUBNET', '192.168.1.0/24');   // jouw subnet
```

### Stap 5 — Schema aanmaken
Open in browser:
```
http://jouw-nas-ip/netmap/setup.php
```
Klik daarna op **Open NetMap**.

### Stap 6 — Auto-scan instellen (crontab)
```bash
# SSH in NAS, open crontab
vi /etc/crontab

# Voeg toe (elke 5 minuten):
*/5 * * * *  root  php /volume1/web/netmap/cron/scan.php >> /tmp/netmap.log 2>&1
```
###
Auto scan kan ook worden aangemaakt via taken in de scheduling app van synology zelf;
##########

## Gebruik
- **Browser**: `http://jouw-nas-ip/netmap/`
- **Export CSV**: knop in dashboard of `/netmap/api/?action=export_csv`
- **Export PDF**: knop in dashboard → opent HTML → `Ctrl+P` → Opslaan als PDF
- **Handmatige scan**: knop ▶ Scan Nu in dashboard

## Bestandsstructuur
```
netmap-php/
├── index.php              # Dashboard frontend
├── setup.php              # Eenmalige database setup
├── includes/
│   ├── config.php         # Configuratie (aanpassen!)
│   ├── db.php             # MariaDB PDO helper
│   ├── scanner.php        # ARP/nmap/Python scanner
│   └── exporter.php       # CSV + HTML/PDF export
├── api/
│   └── index.php          # REST API (JSON)
├── cron/
│   ├── scan.php           # Auto-scan script
│   └── arp_scan.py        # Python ARP helper
└── exports/               # Gegenereerde exports
```

## API endpoints
Alle calls via `api/?action=NAAM`:

| Action       | Methode | Omschrijving              |
|--------------|---------|---------------------------|
| devices      | GET     | Alle apparaten            |
| devices      | POST    | Toevoegen / bijwerken     |
| devices&id=N | DELETE  | Verwijderen               |
| scan         | POST    | Scan starten              |
| poll         | GET     | Live status + events      |
| history      | GET     | Scan geschiedenis         |
| events       | GET     | Apparaat events           |
| interfaces   | GET     | Netwerk interfaces        |
| export_csv   | GET     | CSV download              |
| export_html  | GET     | HTML rapport (print→PDF)  |
| status       | GET     | Server status             |
