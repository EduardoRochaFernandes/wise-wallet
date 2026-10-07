<?php
/** Poll 127.0.0.1:3306 until reachable (max ~30s). Exit 0 if up, 1 if timeout. */
declare(strict_types=1);
$host = '127.0.0.1'; $port = 3306;
for ($i = 0; $i < 30; $i++) {
    $c = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($c) { fclose($c); echo "MySQL is ready.\n"; exit(0); }
    echo "Waiting for MySQL... ($i)\n";
    sleep(1);
}
fwrite(STDERR, "MySQL did not respond in time.\n");
exit(1);
