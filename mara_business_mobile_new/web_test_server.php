<?php

/**
 * Router for `php -S 127.0.0.1:8090 -t build/web web_test_server.php`.
 *
 * Serves the Flutter web build for testing in a browser and relays /api/* and
 * /uploads/* to the Laravel server, so the browser only ever talks to one
 * origin. Build the app with --dart-define=API_BASE_URL=http://localhost:8090
 * to point it here.
 *
 * Two problems this solves at once, both artefacts of running a mobile app in a
 * browser rather than faults in the app:
 *
 *   Images. CachedNetworkImage fetches pictures with XHR rather than an <img>
 *   tag, so the browser applies CORS to them. public/uploads is static — the
 *   web server returns those files without ever entering PHP, so Laravel's CORS
 *   package never sees the request and cannot add a header. Fetched
 *   cross-origin, every product photo fails.
 *
 *   Login. Serving the app from Laravel's own origin fixes the images but
 *   breaks authentication: localhost:8000 is a Sanctum stateful domain, so a
 *   browser client there is switched to session authentication and required to
 *   send a CSRF token the app does not have — login returns 419.
 *
 * Relaying server-side avoids both. Laravel receives the call from PHP with no
 * browser Origin and no session cookie, so Sanctum stays on token
 * authentication — the same path a real device takes. Cookies are deliberately
 * not forwarded; the Authorization header is.
 *
 * A test harness only. A real phone calls the API natively, performs no CORS
 * preflight, and needs none of this.
 */

const UPSTREAM = 'http://127.0.0.1:8000';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$docRoot = __DIR__ . '/build/web';

if (str_starts_with($path, '/api/') || str_starts_with($path, '/uploads/')) {
    $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    $url = UPSTREAM . $path . ($query !== null ? '?' . $query : '');

    $forwarded = ['Accept: application/json'];

    foreach (['HTTP_AUTHORIZATION', 'HTTP_CONTENT_TYPE', 'HTTP_CURRENCY', 'HTTP_ACCEPT_LANGUAGE'] as $key) {
        if (!empty($_SERVER[$key])) {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $forwarded[] = $name . ': ' . $_SERVER[$key];
        }
    }

    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        CURLOPT_HTTPHEADER => $forwarded,
        CURLOPT_TIMEOUT => 30,
    ]);

    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        curl_setopt($handle, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
    }

    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $type = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
    curl_close($handle);

    if ($body === false || $status === 0) {
        http_response_code(502);
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Le serveur Laravel ne repond pas sur ' . UPSTREAM]);
        exit;
    }

    http_response_code($status);

    if ($type !== '') {
        header('Content-Type: ' . $type);
    }

    echo $body;
    exit;
}

// Anything present on disk is served by the built-in server itself.
$file = $docRoot . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

// Flutter web routes client-side, so an unknown path is a route, not a 404.
header('Content-Type: text/html; charset=utf-8');
readfile($docRoot . '/index.html');
