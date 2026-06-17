<?php
/**
 * Tool để lấy IP thực của server
 */

function getServerIP() {
    $methods = [];
    
    // Method 1: $_SERVER variables
    $serverVars = ['SERVER_ADDR', 'LOCAL_ADDR', 'HTTP_HOST'];
    foreach ($serverVars as $var) {
        if (!empty($_SERVER[$var])) {
            $methods["SERVER[$var]"] = $_SERVER[$var];
        }
    }
    
    // Method 2: Socket method
    try {
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($sock) {
            socket_connect($sock, "8.8.8.8", 53);
            socket_getsockname($sock, $name);
            socket_close($sock);
            if ($name) {
                $methods['Socket Method'] = $name;
            }
        }
    } catch (Exception $e) {
        $methods['Socket Method'] = 'Error: ' . $e->getMessage();
    }
    
    // Method 3: Command line (Windows)
    if (PHP_OS_FAMILY === 'Windows') {
        $output = shell_exec('ipconfig | findstr /i "IPv4"');
        if ($output) {
            preg_match_all('/(\d+\.\d+\.\d+\.\d+)/', $output, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $ip) {
                    if ($ip !== '127.0.0.1') {
                        $methods['ipconfig'] = $ip;
                        break;
                    }
                }
            }
        }
    }
    
    return $methods;
}

// If accessed directly, show IP information
if (basename($_SERVER['PHP_SELF']) === 'get_server_ip.php') {
    header('Content-Type: application/json');
    echo json_encode([
        'detected_ips' => getServerIP(),
        'current_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], JSON_PRETTY_PRINT);
}
?>
