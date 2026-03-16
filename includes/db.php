<?php
// includes/db.php — MariaDB via mysqli (identiek aan werkende apps op deze NAS)

require_once __DIR__ . '/config.php';

class DB {
    private static $conn = null;

    public static function connect() {
        if (self::$conn === null) {
            // Geef socketpad expliciet mee — Synology MariaDB 10 gebruikt
            // /run/mysqld/mysqld10.sock in plaats van het standaard pad
            self::$conn = new mysqli(null, DB_USER, DB_PASS, DB_NAME, null, DB_SOCKET);

            if (self::$conn->connect_error) {
                http_response_code(503);
                die(json_encode(array(
                    'ok'    => false,
                    'error' => 'DB verbinding mislukt: ' . self::$conn->connect_error
                )));
            }
            self::$conn->set_charset(DB_CHARSET);
        }
        return self::$conn;
    }

    // SELECT — geeft array van rijen terug
    public static function query($sql, $params = array()) {
        if (empty($params)) {
            $result = self::connect()->query($sql);
            if ($result === false) {
                throw new Exception('Query fout: ' . self::connect()->error . ' | SQL: ' . $sql);
            }
            $rows = array();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
            return $rows;
        }
        return self::prepared($sql, $params);
    }

    // SELECT één rij
    public static function queryOne($sql, $params = array()) {
        $rows = self::query($sql, $params);
        return isset($rows[0]) ? $rows[0] : null;
    }

    // INSERT / UPDATE / DELETE
    public static function execute($sql, $params = array()) {
        if (empty($params)) {
            self::connect()->query($sql);
            return self::connect()->affected_rows;
        }
        self::prepared($sql, $params);
        return self::connect()->affected_rows;
    }

    // INSERT — geeft lastInsertId terug
    public static function insert($sql, $params = array()) {
        self::execute($sql, $params);
        return (int) self::connect()->insert_id;
    }

    // Prepared statement helper
    private static function prepared($sql, $params) {
        $db   = self::connect();
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare fout: ' . $db->error . ' | SQL: ' . $sql);
        }

        // Bouw type string: s=string, i=integer, d=double
        $types = '';
        foreach ($params as $p) {
            if (is_int($p))    $types .= 'i';
            elseif (is_float($p)) $types .= 'd';
            else               $types .= 's';
        }

        // bind_param verwacht referenties
        $bindParams = array($types);
        foreach ($params as $key => $val) {
            $bindParams[] = &$params[$key];
        }
        call_user_func_array(array($stmt, 'bind_param'), $bindParams);

        $stmt->execute();

        // Haal resultaat op als het een SELECT is
        $result = $stmt->get_result();
        $rows   = array();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
        }
        $stmt->close();
        return $rows;
    }

    // Vendor lookup via OUI
    public static function lookupVendor($mac) {
        if (empty($mac)) return 'Onbekend';
        $oui = strtoupper(substr($mac, 0, 8));
        $row = self::queryOne(
            'SELECT vendor FROM oui_vendors WHERE oui = ?',
            array($oui)
        );
        return isset($row['vendor']) ? $row['vendor'] : 'Onbekende fabrikant';
    }
}
