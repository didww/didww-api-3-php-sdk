<?php

// A raw HTTP server for wire tests: it answers the n-th connection with the
// n-th scripted reply and appends each request head to a log file.
//
// Usage: php scripted_server.php <log file> <replies JSON file>
// A reply is a list of steps: {"send": "<bytes, base64>"} or {"sleep": <seconds>}.
// A sleep ends early when the client closes the connection, so that the next
// connection is answered at once. The connection is closed after the last step. The port is printed on stdout.

[, $log, $repliesFile] = $argv;
$replies = json_decode(file_get_contents($repliesFile), true);

$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
if (false === $server) {
    fwrite(STDERR, $errstr.PHP_EOL);
    exit(1);
}
echo explode(':', stream_socket_get_name($server, false))[1].PHP_EOL;
fflush(STDOUT);

foreach ($replies as $steps) {
    $connection = @stream_socket_accept($server, 30);
    if (false === $connection) {
        exit(0);
    }
    $head = '';
    while (!str_contains($head, "\r\n\r\n") && !feof($connection)) {
        $line = fgets($connection);
        if (false === $line) {
            break;
        }
        $head .= $line;
    }
    file_put_contents($log, json_encode(['head' => $head]).PHP_EOL, FILE_APPEND);
    foreach ($steps as $step) {
        if (isset($step['sleep'])) {
            $until = microtime(true) + $step['sleep'];
            while (($left = $until - microtime(true)) > 0) {
                $read = [$connection];
                $write = $except = null;
                $ready = @stream_select($read, $write, $except, (int) $left, (int) (fmod($left, 1) * 1e6));
                if (false === $ready || ($ready > 0 && '' === (string) @fread($connection, 8192) && feof($connection))) {
                    break;
                }
            }
        } else {
            @fwrite($connection, base64_decode($step['send']));
            @fflush($connection);
        }
    }
    @fclose($connection);
}
