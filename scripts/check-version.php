<?php

declare(strict_types=1);

require __DIR__ . '/../src/Sipay/Sipay.php';

use Sipay\Sipay;

$version = Sipay::VERSION;
if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}$/D', $version)) {
    fwrite(STDERR, "SDK version must be a numeric MAJOR.MINOR.PATCH value.\n");
    exit(1);
}

// RC releases send the numeric version, just like the plugins.
if (isset($argv[1])) {
    $tag = $argv[1];
    if (!preg_match('/^v(\d{1,3}\.\d{1,3}\.\d{1,3})(?:-rc\.\d+)?$/D', $tag, $matches)
        || $matches[1] !== $version) {
        fwrite(STDERR, "Release tag {$tag} does not match Sipay::VERSION ({$version}).\n");
        exit(1);
    }
}

echo "SDK version: {$version}\n";
