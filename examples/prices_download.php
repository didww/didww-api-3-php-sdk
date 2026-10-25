<?php

// Downloads the Voice IN price file, checks it again with If-Modified-Since,
// and reads a few rows.
//
// The file is written exactly as served, still gzipped, and dated with the
// response's Last-Modified. fetch($path) sends the modification time of the
// file at $path as If-Modified-Since, so while the price file is unchanged the
// second call, and any later run against the same file, is answered 304 and
// keeps it.
//
// The file goes to the directory given as argument, or to didww-prices in the
// user cache directory; a missing directory is created for the current user
// only (mode 0700).
//
// Usage: DIDWW_API_KEY=your_api_key php examples/prices_download.php [dir]

require_once 'bootstrap.php';

use Didww\Exception\DidwwPricesException;
use Didww\Exception\PricesErrorKind;
use Didww\Prices;
use Didww\Prices\PriceFileReader;
use Didww\Prices\PricesService;

const FIRST_ROWS = 5;

// The user cache directory: XDG_CACHE_HOME or ~/.cache, ~/Library/Caches on
// macOS, %LOCALAPPDATA% on Windows.
function userCacheDir(): string
{
    if ('Windows' === PHP_OS_FAMILY) {
        $dir = getenv('LOCALAPPDATA');
    } elseif ('Darwin' === PHP_OS_FAMILY) {
        $dir = false === getenv('HOME') ? false : getenv('HOME').'/Library/Caches';
    } else {
        $dir = getenv('XDG_CACHE_HOME') ?: (false === getenv('HOME') ? false : getenv('HOME').'/.cache');
    }
    if (false === $dir || '' === $dir) {
        exit("No user cache directory; pass a directory: php examples/prices_download.php <dir>\n");
    }

    return $dir;
}

function priceDir(?string $dir): string
{
    $dir ??= userCacheDir().DIRECTORY_SEPARATOR.'didww-prices';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        exit("Cannot create $dir\n");
    }

    return $dir;
}

function fetchFile(PricesService $prices, string $path): void
{
    $file = $prices->fetch($path);
    echo $file->isNotModified() ? "Not modified, file kept\n" : 'Downloaded '.$file->getBytesWritten()." bytes\n";
}

$dir = priceDir($argv[1] ?? null);
$path = $dir.DIRECTORY_SEPARATOR.'voice_in_prices.csv.gz';
echo "Price files go to $dir\n";

$prices = Prices::voiceIn();
try {
    echo "=== HEAD ===\n";
    $head = $prices->head();
    echo 'Last-Modified: '.($head->getLastModified()?->format('D, d M Y H:i:s \\G\\M\\T') ?? 'unknown')
        .', '.($head->getContentLength() ?? 'unknown').' bytes, '.($head->getFilename() ?? 'unnamed')."\n";

    echo "\n=== Fetch $path ===\n";
    fetchFile($prices, $path);

    echo "\n=== Fetch again ===\n";
    fetchFile($prices, $path);

    echo "\n=== First rows ===\n";
    $n = 0;
    foreach (PriceFileReader::rows($path) as $row) {
        echo '  '.($row['Country'] ?? '').' '.($row['Destination'] ?? '').' '.($row['Source type'] ?? '').': '.($row['Rate (USD, excl. VAT)'] ?? '')."\n";
        if (FIRST_ROWS === ++$n) {
            break;
        }
    }
} catch (DidwwPricesException $e) {
    if (PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED === $e->getKind()) {
        echo 'No Voice IN price plan on this account: '.$e->getMessage()."\n";
    } elseif (PricesErrorKind::SERVICE_UNAVAILABLE === $e->getKind()) {
        echo 'The price file is not available yet, retry in '.($e->getRetryAfter() ?? '?').' s: '.$e->getMessage()."\n";
    } else {
        throw $e;
    }
}
