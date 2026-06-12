<?php
/**
 * Webhook auto-deploy for WC2026 — runs over the web (443), no SSH needed.
 * Flow: push to GitHub -> GitHub calls this URL -> server git pulls + updates.
 *
 * Security: reads DEPLOY_SECRET from .env (never commit the secret).
 *  - GitHub webhook: verified with the HMAC signature (X-Hub-Signature-256).
 *  - Manual test in a browser: .../deploy.php?token=<DEPLOY_SECRET>
 */

$DIR = '/home/nhsanqih/wc2026.sangtrang.com';

header('Content-Type: text/plain; charset=utf-8');

// --- read DEPLOY_SECRET from .env ---
$secret = null;
$envFile = $DIR . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
        if (strpos(ltrim($line), 'DEPLOY_SECRET=') === 0) {
            $secret = trim(explode('=', $line, 2)[1], " \t\"'");
            break;
        }
    }
}
if (! $secret) {
    http_response_code(500);
    exit("DEPLOY_SECRET is not set in .env\n");
}
if (! function_exists('shell_exec')) {
    http_response_code(500);
    exit("shell_exec is disabled on this host -> use deploy.sh in Terminal instead.\n");
}

// --- authenticate ---
// Manual trigger sends the secret in a header (NOT the URL, which would leak via
// logs/history/Referer):   curl -H "X-Deploy-Token: <secret>" https://.../deploy.php
$authorized = false;
$manualToken = $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '';
if ($manualToken !== '') {
    $authorized = hash_equals($secret, $manualToken);
} else {                                           // GitHub webhook
    $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
    if ($event !== 'push') {
        exit('Ignored event: ' . ($event ?: 'none') . "\n");
    }
    $payload = file_get_contents('php://input');
    $sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $authorized = $sig && hash_equals('sha256=' . hash_hmac('sha256', $payload, $secret), $sig);
    if ($authorized) {                             // only deploy on a normal push to main
        $data = json_decode($payload, true) ?: [];
        if (($data['deleted'] ?? false) === true) {
            exit("Ignored: branch delete\n");
        }
        $ref = $data['ref'] ?? '';
        if ($ref !== 'refs/heads/main') {
            exit("Skipped: $ref (not the main branch)\n");
        }
    }
}
if (! $authorized) {
    http_response_code(403);
    exit("Unauthorized (bad secret or signature).\n");
}

// --- single-flight lock: never let two deploys overlap ---
set_time_limit(0);
$lock = fopen($DIR . '/storage/deploy.lock', 'c');
if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
    http_response_code(409);
    exit("A deploy is already running.\n");
}

// --- run deploy (absolute paths + HOME because the web process lacks env) ---
$composer = '/opt/cpanel/composer/bin/composer';
$php = '/usr/local/bin/php';
$home = dirname($DIR); // /home/nhsanqih
$cmd = 'export HOME=' . escapeshellarg($home) . '; '
     . 'export COMPOSER_HOME=' . escapeshellarg($home . '/.config/composer') . '; '
     . 'cd ' . escapeshellarg($DIR) . ' && '
     . 'git pull origin main 2>&1 && '
     . $composer . ' install --no-dev --optimize-autoloader --no-interaction 2>&1 && '
     . $php . ' artisan migrate --force 2>&1 && '
     . $php . ' artisan view:clear 2>&1 && '
     . $php . ' artisan config:clear 2>&1 && '
     . $php . ' artisan route:clear 2>&1';

echo "===== DEPLOY =====\n";
echo shell_exec($cmd . ' 2>&1');
echo "\n===== DONE =====\n";

flock($lock, LOCK_UN);
fclose($lock);
