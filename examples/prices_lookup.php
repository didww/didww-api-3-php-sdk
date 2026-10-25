<?php

// Looks up Voice IN, Voice OUT, SMS OUT and SMS IN prices for one number.
//
// Prices are served by separate hosts (api-vin, api-vout and api-sms); the SDK
// mints a token for each of them on first use. A read-scoped configuration
// (Configuration::configure($credentials, scope: 'read')) is enough for
// prices. The account needs a price plan of the service, otherwise the call
// fails with PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED.
//
// Lookup filters:
//   - Voice IN: number (required), src_country_iso
//   - Voice OUT: number (required)
//   - SMS OUT A2P / P2P / OTP and SMS IN: dst_number, dst_country_iso (at least one)
//
// Usage: DIDWW_API_KEY=your_api_key php examples/prices_lookup.php [number]

require_once 'bootstrap.php';

use Didww\Exception\DidwwPricesException;
use Didww\Exception\PricesErrorKind;
use Didww\Prices;
use Didww\Prices\PricesService;

// Prints every getter of a price row. Prices and fees are in USD; null fields
// are printed as null.
function describe(object $row): string
{
    $fields = [];
    foreach (get_class_methods($row) as $method) {
        if (!str_starts_with($method, 'get')) {
            continue;
        }
        $value = $row->$method();
        $fields[] = lcfirst(substr($method, 3)).'='.match (true) {
            null === $value => 'null',
            is_bool($value) => var_export($value, true),
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format(DATE_ATOM),
            default => (string) $value,
        };
    }

    return implode(', ', $fields);
}

function show(string $title, PricesService $prices, array $filters): void
{
    echo "\n=== $title ===\n";
    try {
        $lookup = $prices->lookup($filters);
    } catch (DidwwPricesException $e) {
        if (in_array($e->getKind(), [PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED, PricesErrorKind::PRICE_NOT_FOUND], true)) {
            echo '  '.$e->getKind()->value.': '.$e->getMessage()."\n";

            return;
        }
        throw $e;
    }

    echo $lookup->getTotalRecords()." matching price(s)\n";
    foreach ($lookup as $row) {
        echo '  '.describe($row)."\n";
    }
}

$number = $argv[1] ?? '18005550123';

show('Voice IN', Prices::voiceIn(), ['number' => $number]);
show('Voice IN, calls from the US', Prices::voiceIn(), ['number' => $number, 'src_country_iso' => 'US']);
show('Voice OUT', Prices::voiceOut(), ['number' => $number]);
show('SMS OUT A2P', Prices::smsOutA2p(), ['dst_number' => $number]);
show('SMS OUT P2P, whole country', Prices::smsOutP2p(), ['dst_country_iso' => 'US']);
show('SMS OUT OTP', Prices::smsOutOtp(), ['dst_number' => $number, 'dst_country_iso' => 'US']);
show('SMS IN', Prices::smsIn(), ['dst_number' => $number]);

// Filters are checked before any request is sent: Voice OUT takes number only.
try {
    Prices::voiceOut()->lookup(['number' => $number, 'src_country_iso' => 'US']);
} catch (InvalidArgumentException $e) {
    echo "\nRejected locally: ".$e->getMessage()."\n";
}
