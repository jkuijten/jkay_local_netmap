#!/usr/bin/env python3
# cron/arp_scan.py — ARP scan helper voor NetMap
# Gebruik: python3 arp_scan.py 192.168.1.0/24
# Geeft JSON terug met gevonden hosts

import sys
import json
import socket
import subprocess
import re
import os

def scan_arp_table():
    """Lees de ARP tabel uit."""
    hosts = []
    try:
        out = subprocess.check_output(['ip', 'neigh', 'show'], stderr=subprocess.DEVNULL).decode()
        for line in out.splitlines():
            m = re.match(r'^(\d+\.\d+\.\d+\.\d+).*lladdr\s+([0-9a-fA-F:]{17})', line)
            if m:
                hosts.append({'ip': m.group(1), 'mac': m.group(2).upper(), 'hostname': None})
    except Exception:
        pass

    if not hosts:
        try:
            out = subprocess.check_output(['arp', '-a'], stderr=subprocess.DEVNULL).decode()
            for line in out.splitlines():
                m = re.search(r'\((\d+\.\d+\.\d+\.\d+)\)\s+at\s+([0-9a-fA-F:]{17})', line)
                if m:
                    hosts.append({'ip': m.group(1), 'mac': m.group(2).upper(), 'hostname': None})
        except Exception:
            pass
    return hosts

def ping_sweep(subnet):
    """Stuur pings naar heel subnet om ARP tabel te vullen."""
    base = subnet.rsplit('.', 1)[0] if '/' in subnet else subnet.rsplit('.', 1)[0]
    try:
        # Broadcast ping
        broadcast = base + '.255'
        subprocess.call(['ping', '-b', '-c', '1', '-W', '1', broadcast],
                       stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    except Exception:
        pass

    # Scan eerste 254 adressen parallel via ping
    import threading
    def ping_one(ip):
        subprocess.call(['ping', '-c', '1', '-W', '1', ip],
                       stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

    threads = []
    for i in range(1, 255):
        t = threading.Thread(target=ping_one, args=(f'{base}.{i}',))
        t.daemon = True
        threads.append(t)
        t.start()
        if len(threads) >= 50:  # max 50 parallel
            for t in threads:
                t.join(timeout=2)
            threads = []
    for t in threads:
        t.join(timeout=2)

def resolve_hostname(ip):
    """Probeer hostname op te zoeken."""
    try:
        return socket.gethostbyaddr(ip)[0]
    except Exception:
        return None

def main():
    subnet = sys.argv[1] if len(sys.argv) > 1 else '192.168.1.0/24'

    # Ping sweep om ARP tabel te vullen
    ping_sweep(subnet)

    # Lees ARP tabel
    hosts = scan_arp_table()

    # Deduplicate op MAC
    seen = {}
    for h in hosts:
        if h['mac'] not in seen:
            seen[h['mac']] = h

    # Hostname resolving (optioneel — kan langzaam zijn)
    result = list(seen.values())

    print(json.dumps(result))

if __name__ == '__main__':
    main()
