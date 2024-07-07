<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$dataFile = 'data.json';

if (!isset($_SESSION['request_count'])) {
    $_SESSION['request_count'] = 0;
    $_SESSION['last_reset'] = time();
}

$_SESSION['request_count']++;

if (file_exists($dataFile)) {
    $data = json_decode(file_get_contents($dataFile), true);
} else {
    $data = [
        'request_count' => 0,
        'request_ips' => []
    ];
}

$data['request_count']++;
$data['request_ips'][] = $_SERVER['REMOTE_ADDR'];
$data['request_ips'] = array_unique($data['request_ips']);

file_put_contents($dataFile, json_encode($data));

if (isset($_GET['action']) && $_GET['action'] == 'getData') {
    header('Content-Type: application/json');
    echo json_encode($data);
    
    if (time() - $_SESSION['last_reset'] >= 2) {
        $_SESSION['last_reset'] = time();
        $data['request_count'] = 0;
        $data['request_ips'] = [];
        file_put_contents($dataFile, json_encode($data));
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Dstat</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        #requests {
            font-size: 24px;
            margin-bottom: 20px;
        }
        #errors {
            color: red;
            margin-top: 20px;
        }
    </style>
    <script>
        function updateDstat() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '?action=getData', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            document.getElementById('requests-count').innerText = data.request_count;
                            document.getElementById('requests-from').innerText = data.request_ips.join('\n');
                            document.getElementById('errors').innerText = '';
                        } catch (e) {
                            document.getElementById('errors').innerText = 'Error parsing response: ' + e.message + ' Response: ' + xhr.responseText;
                        }
                    } else {
                        document.getElementById('errors').innerText = 'Error: ' + xhr.status + ' ' + xhr.statusText;
                    }
                }
            };
            xhr.send();
        }

        setInterval(updateDstat, 1000);
    </script>
</head>
<body>
    <div id="requests">
        🚀 Requests Count: <span id="requests-count">0</span>
    </div>
    <div>
        🧱 Requests From:
        <pre id="requests-from">No requests yet.</pre>
    </div>
    <div id="errors"></div>
</body>
</html>
