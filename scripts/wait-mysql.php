<?php
/** Poll 127.0.0.1:3306 until reachable (max ~30s). Exit 0 if up, 1 if timeout. */
declare(strict_types=1);
$host = '127.0.0.1'; $port = 3306;
for ($i = 0; $i < 30; $i++) {
    $c = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($c) { fclose($c); echo "MySQL pronto.\n"; exit(0); }
    echo "A aguardar pelo MySQL... ($i)\n";
    sleep(1);
}
fwrite(STDERR, "MySQL não respondeu a tempo.\n");
exit(1);
