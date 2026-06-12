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
$authorized = false;
if (isset($_GET['token'])) {                       // manual browser test
    $authorized = hash_equals($secret, (string) $_GET['token']);
} else {                                           // GitHub webhook
    $payload = file_get_contents('php://input');
    $sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $authorized = $sig && hash_equals('sha256=' . hash_hmac('sha256', $payload, $secret), $sig);
    if ($authorized) {                             // only deploy on push to main
        $ref = json_decode($payload, true)['ref'] ?? '';
        if ($ref !== 'refs/heads/main') {
            exit("Skipped: $ref (not the main branch)\n");
        }
    }
}
if (! $authorized) {
    http_response_code(403);
    exit("Unauthorized (bad secret or signature).\n");
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
