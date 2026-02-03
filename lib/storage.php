<?php

declare(strict_types=1);

function read_json(string $path, array $default = []): array
{
    if (!file_exists($path)) {
        return $default;
    }

    $fp = fopen($path, 'r');
    if ($fp === false) {
        return $default;
    }

    flock($fp, LOCK_SH);
    $contents = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    $data = json_decode($contents ?: '[]', true);
    return is_array($data) ? $data : $default;
}

function write_json(string $path, array $data): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $fp = fopen($path, 'c+');
    if ($fp === false) {
        throw new RuntimeException('Unable to open file for writing: ' . $path);
    }

    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function now_utc(): string
{
    return gmdate('Y-m-d H:i:s');
}

function now_local(): string
{
    return date('Y-m-d H:i:s');
}

function uuid(): string
{
    return bin2hex(random_bytes(16));
}
