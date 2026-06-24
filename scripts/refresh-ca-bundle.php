<?php
/**
 * Builds storage/cacert.pem: the public Mozilla CA bundle (from curl.se, the
 * canonical source curl itself recommends) PLUS, on Windows, any locally
 * installed "HTTPS-scanning" root certificates from antivirus/endpoint
 * security software (Avast, AVG, Kaspersky, ESET, Bitdefender, Sophos,
 * McAfee, Norton, Fortinet, Zscaler, Cisco Umbrella, etc).
 *
 * Why this is needed: that class of software transparently intercepts
 * outbound HTTPS and re-signs it with its own locally-generated root cert so
 * it can scan the decrypted traffic for malware. Browsers work fine because
 * they trust the OS certificate store (where that root is installed); a
 * hand-picked Mozilla-only bundle does not include it, so curl/PHP requests
 * fail TLS verification on those machines even though the connection itself
 * is fine. Adding the OS-trusted root here is not a security downgrade — it
 * mirrors exactly what the browser already trusts on this machine.
 *
 * Safe to re-run any time (idempotent); run automatically by setup.bat/sh.
 */
declare(strict_types=1);

require __DIR__ . '/../app/config.php';

$dest = WW_ROOT . '/storage/cacert.pem';
$bundle = '';

// 1) Fetch the current public Mozilla bundle.
$ch = curl_init('https://curl.se/ca/cacert.pem');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
// Bootstrap-only: this is a public, static, non-secret file from curl's own
// maintainers — verification can't be done yet since we have no bundle.
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$fetched = curl_exec($ch);
curl_close($ch);

if (is_string($fetched) && strlen($fetched) > 50000 && str_contains($fetched, 'BEGIN CERTIFICATE')) {
    $bundle = $fetched;
    echo "Fetched current Mozilla CA bundle (" . strlen($fetched) . " bytes).\n";
} elseif (is_file($dest)) {
    $bundle = (string) file_get_contents($dest);
    echo "Could not fetch a fresh bundle (offline?) - keeping the existing one.\n";
} else {
    echo "Could not fetch a CA bundle and none exists yet. Verified HTTPS calls\n";
    echo "(news/FX/crypto) will fall back to cached/seed data until this is run\n";
    echo "again with network access.\n";
}

// 2) On Windows, append any locally-trusted "TLS scanning" root certificates.
if (stripos(PHP_OS, 'WIN') === 0 && $bundle !== '') {
    $needles = 'Avast|AVG|Kaspersky|ESET|Bitdefender|Sophos|McAfee|Norton|Fortinet|'
             . 'Zscaler|Umbrella|TLS.?Scan|SSL.?Scan|Web.?Shield|Mail.?Shield|Proxy.?CA';
    $ps = "Get-ChildItem Cert:\\LocalMachine\\Root | "
        . "Where-Object { \$_.Subject -match '{$needles}' } | "
        . "ForEach-Object {\n"
        . "  '-----BEGIN CERTIFICATE-----'\n"
        . "  [Convert]::ToBase64String(\$_.RawData, [System.Base64FormattingOptions]::InsertLineBreaks)\n"
        . "  '-----END CERTIFICATE-----'\n"
        . "}\n";
    // Writing to a temp .ps1 avoids Windows command-line quoting pitfalls
    // that break escapeshellarg() with complex one-liners.
    $psFile = sys_get_temp_dir() . '/ww_ca_' . uniqid() . '.ps1';
    file_put_contents($psFile, $ps);
    $cmd = 'powershell -NoProfile -ExecutionPolicy Bypass -File ' . escapeshellarg($psFile);
    $extra = @shell_exec($cmd);
    @unlink($psFile);

    if (is_string($extra) && str_contains($extra, 'BEGIN CERTIFICATE')) {
        $bundle .= "\n## Locally-trusted TLS-scanning roots (added by refresh-ca-bundle.php)\n" . $extra;
        echo "Found and appended a local HTTPS-scanning root certificate.\n";
    } else {
        echo "No HTTPS-scanning antivirus root certificate detected - nothing to add.\n";
    }
}

if ($bundle !== '') {
    file_put_contents($dest, $bundle);
    echo "Wrote {$dest} (" . strlen($bundle) . " bytes).\n";
} else {
    echo "Nothing written - no bundle available.\n";
}
