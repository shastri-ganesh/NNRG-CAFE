<?php
// admin_login_activity.php
// Shows recent admin login/activity logs in a clean table.
// Save this file to your project's admin/ folder.

// ----------------------
// NOTE about logging IPs
// ----------------------
// This page displays the IP that was saved in your admin_status_log table.
// To ensure your log stores the best-possible client IP at the time of login,
// update your login handler to use the getUserIP() function below when inserting
// into admin_status_log. Example (in your login script):
//
// function getUserIP() {
//     if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
//         return $_SERVER['HTTP_CLIENT_IP'];
//     } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
//         // May contain multiple IPs (proxy chain) - the leftmost is the original client
//         $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
//         return trim($ipList[0]);
//     } else {
//         return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
//     }
//}
//
// $ip = getUserIP(); // store this in your log insert
//
// INSERT INTO admin_status_log (changed_by, new_status, ip_address, user_agent, notes, change_time) VALUES (?, ?, ?, ?, ?, NOW());
//
// ----------------------

include('../conn_db.php');
session_start();

// --- Access control: only logged-in admins ---
if (!isset($_SESSION['aid'])) {
    header('Location: admin_login.php');
    exit;
}

// Helper: safe escape for HTML output
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Get client's IP (helper for login scripts — do NOT call this when rendering log rows)
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
}

// Detect if an IP is private/local (helps flag internal/private addresses)
function isPrivateIP($ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // IPv4 private ranges
        $private_ranges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8'
        ];
    } else {
        // IPv6 private ranges (simple check)
        $private_ranges = ['::1/128', 'fc00::/7', 'fe80::/10'];
    }

    foreach ($private_ranges as $range) {
        list($subnet, $bits) = explode('/', $range);
        if (ip_in_range($ip, $subnet, (int)$bits)) return true;
    }
    return false;
}

// Helper for subnet check (works for IPv4 and basic IPv6 patterns)
function ip_in_range($ip, $subnet, $mask) {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask_long = -1 << (32 - $mask);
        $subnet_min = $subnet_long & $mask_long;
        $subnet_max = $subnet_min + (~$mask_long);
        return ($ip_long >= $subnet_min && $ip_long <= $subnet_max);
    }
    // For IPv6 we do a simpler prefix check
    if (strpos($ip, ':') !== false && strpos($subnet, ':') !== false) {
        // convert to binary strings
        $binIP = inet_pton($ip);
        $binSubnet = inet_pton($subnet);
        if ($binIP === false || $binSubnet === false) return false;
        $ipBits = unpack("H*", $binIP)[1];
        $subBits = unpack("H*", $binSubnet)[1];
        // compare prefix bytes
        $bytes = intdiv($mask, 8);
        $remainingBits = $mask % 8;
        if ($bytes > 0) {
            if (substr($ipBits, 0, $bytes*2) !== substr($subBits, 0, $bytes*2)) return false;
        }
        if ($remainingBits > 0) {
            $ipByte = hexdec(substr($ipBits, $bytes*2, 2));
            $subByte = hexdec(substr($subBits, $bytes*2, 2));
            $maskByte = (~(0xFF >> $remainingBits)) & 0xFF;
            return (($ipByte & $maskByte) === ($subByte & $maskByte));
        }
        return true;
    }
    return false;
}

// Get geo location for an IP using ipinfo.io (simple, free endpoint).
// NOTE: This is optional — some hosts disable outbound requests. We cache results in $location_cache.
$location_cache = [];
function getIPLocation($ip) {
    global $location_cache;
    if (!$ip || $ip === '-' || strtolower($ip) === 'unknown') return 'Unknown';
    if (isset($location_cache[$ip])) return $location_cache[$ip];

    // Skip private IPs
    if (isPrivateIP($ip)) {
        $location_cache[$ip] = 'Private / Local Network';
        return $location_cache[$ip];
    }

    // Try ipinfo.io; alternatives: ip-api.com, ipstack, geoip2, etc.
    $url = "https://ipinfo.io/{$ip}/json";

    // Use @ to suppress warnings if allow_url_fopen is disabled; server may also block outbound HTTP
    $response = @file_get_contents($url);
    if ($response === false) {
        $location_cache[$ip] = 'Lookup failed';
        return $location_cache[$ip];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        $location_cache[$ip] = 'Lookup failed';
        return $location_cache[$ip];
    }

    $city = $data['city'] ?? '';
    $region = $data['region'] ?? '';
    $country = $data['country'] ?? '';
    $org = $data['org'] ?? '';

    $parts = array_filter([$city, $region, $country]);
    $loc = $parts ? implode(', ', $parts) : ($org ?: 'Unknown');
    $location_cache[$ip] = $loc;
    return $loc;
}

// Limit how many rows to show (safe integer)
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 200;
if ($limit <= 0) $limit = 200;

// Query logs: most recent first. Using change_time (as in your table structure)
$query = "SELECT id, changed_by, old_status, new_status, ip_address, user_agent, change_time, notes
          FROM admin_status_log
          ORDER BY change_time DESC, id DESC
          LIMIT $limit";

$result = $mysqli->query($query);
if (!$result) {
    die("Database error: " . esc($mysqli->error));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Recent Admin Login Activity</title>
    <style>
        body{font-family:Arial, Helvetica, sans-serif;background:#f4f6f8;padding:20px}
        .wrap{max-width:1300px;margin:0 auto}
        h1{margin-bottom:12px}
        .controls{display:flex;gap:12px;align-items:center;margin-bottom:12px}
        .controls .info{color:#555;font-size:13px}
        .btn{display:inline-block;padding:8px 12px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px}
        table{width:100%;border-collapse:collapse;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,0.04)}
        th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;font-size:13px}
        th{background:#0b5ed7;color:#fff;position:sticky;top:0;z-index:1}
        tr.success td{background:#e9f7ef}
        tr.failed td{background:#fff5f5}
        .badge{display:inline-block;padding:4px 8px;border-radius:12px;font-size:12px}
        .badge-success{background:#198754;color:#fff}
        .badge-failed{background:#dc3545;color:#fff}
        .small{font-size:12px;color:#666}
        .ip-private{font-weight:600;color:#6c757d}
        .ip-public{font-weight:600;color:#0b5ed7}
        .loc{font-size:12px;color:#444}
    </style>
</head>
<body>
<div class="wrap">
    <h1>Recent Admin Login Activity</h1>

    <div class="controls">
        <a class="btn" href="admin_home.php">Back to Dashboard</a>
        <div class="info">Showing latest <strong><?php echo $limit; ?></strong> entries. <a href="?limit=500">Show 500</a></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Admin ID</th>
                <th>Old Status</th>
                <th>New Status</th>
                <th>IP Address</th>
                <th>IP Type</th>
                <th>Location</th>
                <th>Device / Browser</th>
                <th>Notes</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $count = 1;
        while ($row = $result->fetch_assoc()) {
            $changed_by = ($row['changed_by'] === null || $row['changed_by'] === '') ? 'Unknown' : esc($row['changed_by']);
            $old_status = esc($row['old_status'] ?? '-');
            $new_status_raw = strtoupper($row['new_status'] ?? '');

            // Map status to badge + row class
            if ($new_status_raw === 'SUCCESS' || $new_status_raw === 'OK') {
                $row_class = 'success';
                $new_status_html = '<span class="badge badge-success">SUCCESS</span>';
            } elseif ($new_status_raw === 'FAILED' || $new_status_raw === 'FAIL') {
                $row_class = 'failed';
                $new_status_html = '<span class="badge badge-failed">FAILED</span>';
            } else {
                $row_class = '';
                $new_status_html = '<span class="small">' . esc($row['new_status'] ?? '-') . '</span>';
            }

            $ip_raw = $row['ip_address'] ?? '-';
            $ip_display = esc($ip_raw);

            // IP type and location
            $ip_type = isPrivateIP($ip_raw) ? 'Private / Local' : 'Public';
            $ip_type_class = isPrivateIP($ip_raw) ? 'ip-private' : 'ip-public';
            $location = getIPLocation($ip_raw);

            $ua_full = $row['user_agent'] ?? '';
            if ($ua_full === '') $ua_display = '-';
            else {
                if (function_exists('mb_strlen')) {
                    $ua_display = (mb_strlen($ua_full) > 85) ? esc(mb_substr($ua_full,0,82)) . '...' : esc($ua_full);
                } else {
                    $ua_display = (strlen($ua_full) > 85) ? esc(substr($ua_full,0,82)) . '...' : esc($ua_full);
                }
            }

            $notes = esc($row['notes'] ?? '-');

            // Convert change_time or created_at to 12-hour format with AM/PM
            $raw_time = $row['change_time'] ?? $row['created_at'] ?? '-';
            if ($raw_time && $raw_time !== '-') {
                $formatted_time = date("d-m-Y h:i:s A", strtotime($raw_time));
                $time = esc($formatted_time);
            } else {
                $time = '-';
            }

            echo "<tr class=\"$row_class\">";
            echo "<td>" . $count . "</td>";
            echo "<td>" . $changed_by . "</td>";
            echo "<td>" . $old_status . "</td>";
            echo "<td>" . $new_status_html . "</td>";

            echo "<td>" . $ip_display . "</td>";
            echo "<td><span class=\"" . esc($ip_type_class) . "\">" . esc($ip_type) . "</span></td>";
            echo "<td><span class=\"loc\">" . esc($location) . "</span></td>";

            echo "<td>" . $ua_display . "</td>";
            echo "<td>" . $notes . "</td>";
            echo "<td>" . $time . "</td>";
            echo "</tr>";

            $count++;
        }
        ?>
        </tbody>
    </table>
    <p class="small" style="margin-top:10px;color:#666">
        Tip: To capture the most accurate IPs (including forwarded headers), update your login script to use the getUserIP() helper shown at the top of this file when inserting rows into <code>admin_status_log</code>.
        Also, IP-based location lookups are approximate.
    </p>
</div>
</body>
</html>
