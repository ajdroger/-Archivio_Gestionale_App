<?php
// Proxy di Backend Sicuro per WorldView
// Risolve problemi CORS e nasconde le credenziali API dal client
header("Access-Control-Allow-Origin: *");

$target = $_GET['target'] ?? '';

if ($target === 'opensky') {
    header("Content-Type: application/json");
    $url = 'https://opensky-network.org/api/states/all';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // Le credenziali 'ajdroger' restituiscono 401 Unauthorized.
    // Torno ad accesso anonimo, ma il rate-limit (429) sarà gestito via Bounding Box nel client.

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            "error" => "OpenSky proxy fetch failed",
            "http_code" => $httpCode,
            "response" => $result,
            "curl_error" => curl_error($ch)
        ]);
    } else {
        echo $result;
    }
    exit;
} elseif ($target === 'opensky_bbox') {
    header("Content-Type: application/json");

    // Costruisci URL Bounding Box
    $lamin = $_GET['lamin'] ?? '30.2';
    $lomin = $_GET['lomin'] ?? '-98.0';
    $lamax = $_GET['lamax'] ?? '40.0';
    $lomax = $_GET['lomax'] ?? '-73.0';

    $url = "https://opensky-network.org/api/states/all?lamin={$lamin}&lomin={$lomin}&lamax={$lamax}&lomax={$lomax}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode(["error" => "OpenSky BBOX proxy fetch failed", "http_code" => $httpCode]);
    } else {
        echo $result;
    }
    exit;
} elseif ($target === 'adsb_multihub') {
    header("Content-Type: application/json");

    // Hubs strategici mondiali ad altissimo traffico (Lat, Lon)
    $hubs = [
        ['lat' => 40.6413, 'lon' => -73.7781], // JFK (New York)
        ['lat' => 51.4700, 'lon' => -0.4543],  // LHR (Londra)
        ['lat' => 35.5494, 'lon' => 139.7798], // HND (Tokyo)
        ['lat' => 25.2532, 'lon' => 55.3657],  // DXB (Dubai)
        ['lat' => 50.0333, 'lon' => 8.5706],   // FRA (Francoforte)
        ['lat' => 33.9416, 'lon' => -118.4085],// LAX (Los Angeles)
        ['lat' => -33.9399, 'lon' => 151.1753],// SYD (Sydney)
        ['lat' => 41.9742, 'lon' => -87.9073], // ORD (Chicago)
        ['lat' => 22.3080, 'lon' => 113.9185], // HKG (Hong Kong)
        ['lat' => -23.4356, 'lon' => -46.4731] // GRU (San Paolo)
    ];

    $mh = curl_multi_init();
    $ch_list = [];

    foreach ($hubs as $i => $hub) {
        $ch = curl_init();
        // Raggio 250 Nautic Miles è il massimo consentito da adsb.lol /v2/point/
        $url = "https://api.adsb.lol/v2/point/{$hub['lat']}/{$hub['lon']}/250";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_multi_add_handle($mh, $ch);
        $ch_list[$i] = $ch;
    }

    $active = null;
    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);

    while ($active && $mrc == CURLM_OK) {
        if (curl_multi_select($mh) != -1) {
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
    }

    $aggregated_ac = [];
    $seen_hex = [];

    foreach ($ch_list as $i => $ch) {
        $result = curl_multi_getcontent($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode === 200) {
            $data = json_decode($result, true);
            if (isset($data['ac']) && is_array($data['ac'])) {
                foreach ($data['ac'] as $aircraft) {
                    if (isset($aircraft['hex']) && !isset($seen_hex[$aircraft['hex']])) {
                        // Verifica che l'aereo abbia coordinate valide e navighi
                        if (isset($aircraft['lat']) && isset($aircraft['lon'])) {
                            $aggregated_ac[] = $aircraft;
                            $seen_hex[$aircraft['hex']] = true;
                        }
                    }
                }
            }
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);

    // Formatta il JSON risultante come lo vuole the `useOpenSky` hook
    echo json_encode(["ac" => $aggregated_ac]);
    exit;
} elseif ($target === 'celestrak') {
    header("Content-Type: text/plain");
    // Fetch JSON TLE from SatNOGS instead of CelesTrak to solve IP blocks & timeouts
    $url = 'https://db.satnogs.org/api/tle/';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo "SatNOGS TLE proxy fetch failed";
    } else {
        $data = json_decode($result, true);
        $textOutput = "";
        if (is_array($data)) {
            foreach ($data as $sat) {
                if (isset($sat['tle1']) && isset($sat['tle2'])) {
                    $name = isset($sat['tle0']) ? $sat['tle0'] : "UNKNOWN";
                    if (strpos($name, '0 ') === 0) {
                        $name = substr($name, 2);
                    }
                    $textOutput .= trim($name) . "\n" . trim($sat['tle1']) . "\n" . trim($sat['tle2']) . "\n";
                }
            }
        }
        echo $textOutput;
    }
    exit;
}

http_response_code(400);
echo "Invalid target specified.";
