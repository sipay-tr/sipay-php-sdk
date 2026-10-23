<?php

declare(strict_types=1);

// Reads and writes Sipay::VERSION as text, so it runs without loading the SDK or composer install.
//
// Usage: php scripts/version.php check [<tag>]  Validates VERSION; fails if it is lower than the given release tag.
//        php scripts/version.php set <tag>      Sets VERSION to the numeric part of a release tag (RCs send it too).

function fail(string $message): void
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

$file = __DIR__ . '/../src/Sipay/Sipay.php';
$source = (string) file_get_contents($file);
if (1 !== preg_match_all("/const VERSION = '(\\d{1,3}\\.\\d{1,3}\\.\\d{1,3})';/", $source, $matches)) {
    fail('Sipay::VERSION must be a numeric MAJOR.MINOR.PATCH value.');
}
$version = $matches[1][0];

$command = $argv[1] ?? '';
$tag = $argv[2] ?? '';
if ('' !== $tag && !preg_match('/^v(\d{1,3}\.\d{1,3}\.\d{1,3})(?:-rc\.\d+)?$/D', $tag, $tagMatches)) {
    fail("{$tag} is not a vX.Y.Z or vX.Y.Z-rc.N release tag.");
}

if ('check' === $command) {
    if ('' !== $tag && version_compare($version, $tagMatches[1], '<')) {
        fail("Sipay::VERSION ({$version}) is lower than the released {$tag}.");
    }
} elseif ('set' === $command && '' !== $tag) {
    $version = $tagMatches[1];
    file_put_contents($file, preg_replace("/const VERSION = '[^']*';/", "const VERSION = '{$version}';", $source));
} else {
    fail('Usage: php scripts/version.php check [<tag>] | set <tag>');
}

echo "SDK version: {$version}\n";
