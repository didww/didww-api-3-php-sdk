<?php

// Runs the PHP code snippets of the SDK README (README.md in the repository
// root) against the sandbox, one check per snippet; a few long snippets are
// split into several checks. A failing check is reported and the next one
// runs anyway.
//
// What a check creates is deleted in its finally block, and what it changes
// on an existing DID or Sender ID verification is put back; a clean-up step
// that fails also fails the check, and when a restore fails the trunks it
// still uses are kept and that DID is not changed again. The README
// placeholders ('uuid', 'YOUR_API_KEY', '/var/data/...') are replaced with
// real values; its proxy and CA bundle placeholders are left out. The order
// checks buy DIDs and a capacity channel on the sandbox account (an order
// still pending is canceled), and the export checks create exports, which
// cannot be deleted.
//
// Not run: the shell snippets (installation, development) and the snippets
// reported as skipped with the reason, such as the production configuration.
//
// Prints "Results: N passed, M failed" and exits with 1 when a check failed.
//
// Usage: DIDWW_API_KEY=your_sandbox_api_key php examples/test_readme.php

require_once 'bootstrap.php';

use Didww\Auth\AccessToken;
use Didww\Auth\Scope;
use Didww\Auth\TokenStore;
use Didww\Callback\RequestValidator;
use Didww\Configuration;
use Didww\Credentials;
use Didww\Encrypt;
use Didww\Enum\CallbackMethod;
use Didww\Enum\CliFormat;
use Didww\Enum\Codec;
use Didww\Enum\ExportServiceType;
use Didww\Enum\ExportType;
use Didww\Enum\IceMode;
use Didww\Enum\IdentityType;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\NumberType;
use Didww\Enum\OnCliMismatchAction;
use Didww\Enum\Pop;
use Didww\Enum\SenderIdVerificationStatus;
use Didww\Enum\SmsBodyType;
use Didww\Enum\SmsHttpMethod;
use Didww\Enum\SstRefreshMethod;
use Didww\Enum\TransportProtocol;
use Didww\Enum\VoiceOutCodec;
use Didww\Exception\AuthErrorKind;
use Didww\Exception\DidwwAuthException;
use Didww\Exception\DidwwExceptionInterface;
use Didww\Exception\DidwwPricesException;
use Didww\Exception\PricesErrorKind;
use Didww\Item\Address;
use Didww\Item\AddressRequirement;
use Didww\Item\AddressVerification;
use Didww\Item\Area;
use Didww\Item\AuthenticationMethod\CredentialsAndIp;
use Didww\Item\AvailableDid;
use Didww\Item\Balance;
use Didww\Item\CapacityPool;
use Didww\Item\City;
use Didww\Item\Configuration\Base;
use Didww\Item\Configuration\Sip;
use Didww\Item\Country;
use Didww\Item\Did;
use Didww\Item\DidGroup;
use Didww\Item\DidHistory;
use Didww\Item\DidReservation;
use Didww\Item\EmergencyCallingService;
use Didww\Item\EmergencyRequirement;
use Didww\Item\EncryptedFile;
use Didww\Item\Export;
use Didww\Item\Identity;
use Didww\Item\NanpaPrefix;
use Didww\Item\Order;
use Didww\Item\OrderItem\AvailableDid as AvailableDidOrderItem;
use Didww\Item\OrderItem\Capacity as CapacityOrderItem;
use Didww\Item\OrderItem\Did as DidOrderItem;
use Didww\Item\OrderItem\ReservationDid as ReservationDidOrderItem;
use Didww\Item\ProofType;
use Didww\Item\PublicKey;
use Didww\Item\Region;
use Didww\Item\SenderIdVerification;
use Didww\Item\SharedCapacityGroup;
use Didww\Item\SmsTrunk;
use Didww\Item\SmsTrunkConfiguration\HttpIn;
use Didww\Item\SmsTrunkConfiguration\HttpOut;
use Didww\Item\SmsTrunkConfiguration\SmsToEmail;
use Didww\Item\SmsTrunkGroup;
use Didww\Item\VoiceInTrunk;
use Didww\Item\VoiceInTrunkGroup;
use Didww\Item\VoiceOutTrunk;
use Didww\Prices;
use Didww\Prices\PriceFileReader;
use Didww\Prices\VoiceOutPriceCategory;
use Didww\RetryAfter;
use Psr\Log\AbstractLogger;
use Swis\JsonApi\Client\Collection;

if ('sandbox' !== $credentials->getEnv()) {
    echo "test_readme.php runs against the sandbox only: unset DIDWW_ENV or set it to sandbox\n";
    exit(1);
}
// The URL overrides must not lead to production either
$productionHosts = ['api.didww.com', 'auth.didww.com', 'api-vin.didww.com', 'api-vout.didww.com', 'api-sms.didww.com'];
foreach (urlOverrides() as $argument => $url) {
    // a trailing dot names the same host
    if (null !== $url && in_array(rtrim(strtolower((string) parse_url($url, PHP_URL_HOST)), '.'), $productionHosts, true)) {
        echo "test_readme.php runs against the sandbox only: the $argument override points to production\n";
        exit(1);
    }
}

// ─── Helpers ────────────────────────────────────────────────

// Thrown by a check whose snippet cannot run on this account.
final class SkipCheck extends Exception
{
}

final class Readme
{
    public static int $passed = 0;
    public static int $failed = 0;
    public static int $skipped = 0;
    /** @var string[] */
    public static array $failures = [];
    /** @var string[] the clean-up steps of the running check that failed */
    public static array $cleanupErrors = [];
    /** @var string|null a DID an earlier check could not restore */
    public static ?string $unrestoredDid = null;

    public static function section(string $title): void
    {
        echo "\n== $title ==\n";
    }

    // Runs one check: records it as passed, failed (with the error and any
    // failed clean-up step) or skipped, and puts the configuration of
    // bootstrap.php back.
    public static function check(string $name, callable $check): void
    {
        self::$cleanupErrors = [];
        $error = $skipReason = null;
        try {
            $check();
        } catch (SkipCheck $e) {
            $skipReason = $e->getMessage();
        } catch (Throwable $e) {
            $error = get_class($e).': '.$e->getMessage();
        } finally {
            restoreConfiguration();
        }
        if ([] !== self::$cleanupErrors) {
            $error = (null === $error ? '' : "$error; ").'clean-up failed: '.implode('; ', self::$cleanupErrors);
        }
        if (null !== $error) {
            ++self::$failed;
            self::$failures[] = "$name: $error";
            echo "  ✗ $name: $error\n";
        } elseif (null !== $skipReason) {
            self::skip($name, $skipReason);
        } else {
            ++self::$passed;
            echo "  ✓ $name\n";
        }
    }

    public static function skip(string $name, string $reason): void
    {
        ++self::$skipped;
        echo "  - $name: skipped, $reason\n";
    }
}

// Collects the records the SDK logs.
final class MemoryLogger extends AbstractLogger
{
    /** @var string[] */
    public array $records = [];

    public function log($level, $message, array $context = []): void
    {
        $this->records[] = "$level: $message";
    }
}

function note(string $message): void
{
    echo "    $message\n";
}

function ensure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new UnexpectedValueException($message);
    }
}

function errorsOf($document): string
{
    $errors = [];
    foreach ($document->getErrors() as $error) {
        $pointer = $error->getSource()?->getPointer();
        $errors[] = trim($error->getStatus().' '.($error->getDetail() ?? $error->getTitle()).($pointer ? " ($pointer)" : ''));
    }

    return implode('; ', $errors);
}

function ensureNoErrors($document, string $what): void
{
    if ($document->hasErrors()) {
        throw new UnexpectedValueException("$what: ".errorsOf($document));
    }
}

// The data of $document; throws with the API errors when it has any.
function dataOf($document, string $what)
{
    ensureNoErrors($document, $what);

    return $document->getData();
}

// Runs a clean-up step and returns whether it succeeded. A failure is
// recorded, not thrown, so that the check reports the error of the snippet
// first and then fails for the clean-up step too.
function cleanup(string $what, callable $step): bool
{
    try {
        $document = $step();
        if (is_object($document) && method_exists($document, 'hasErrors') && $document->hasErrors()) {
            Readme::$cleanupErrors[] = "$what: ".errorsOf($document);

            return false;
        }

        return true;
    } catch (Throwable $e) {
        Readme::$cleanupErrors[] = "$what: ".$e->getMessage();

        return false;
    }
}

// Leaves in place what an item that could not be restored still uses.
function keep(string $what, $item): void
{
    if (null !== $item) {
        note("kept $what ".$item->getId().', still in use after the failed restore');
    }
}

// The URL overrides of bootstrap.php, as named arguments of configure().
function urlOverrides(): array
{
    return [
        'baseUrl' => exampleEnv('DIDWW_API_URL'),
        'authUrl' => exampleEnv('DIDWW_AUTH_URL'),
        'voiceInPricesUrl' => exampleEnv('DIDWW_API_VIN_URL'),
        'voiceOutPricesUrl' => exampleEnv('DIDWW_API_VOUT_URL'),
        'smsPricesUrl' => exampleEnv('DIDWW_API_SMS_URL'),
    ];
}

function restoreConfiguration(): void
{
    global $credentials, $httpClientConfig;

    Configuration::configure($credentials, $httpClientConfig, ...urlOverrides());
}

function apiKey(): string
{
    global $apiKey;

    return $apiKey;
}

function suffix(): string
{
    return bin2hex(random_bytes(4));
}

function usCountryId(): string
{
    static $id = null;
    if (null === $id) {
        $countries = dataOf(Country::all(['filter' => ['iso' => 'US']]), 'country US');
        ensure($countries->isNotEmpty(), 'country US not found');
        $id = $countries[0]->getId();
    }

    return $id;
}

// The first DID of the account with its routing included; skips the check
// when the account has none, or when the check would change a DID that an
// earlier check could not restore.
function firstDid(bool $toChange = false): Did
{
    if ($toChange && null !== Readme::$unrestoredDid) {
        throw new SkipCheck('an earlier check could not restore DID '.Readme::$unrestoredDid);
    }
    $dids = dataOf(Did::all([
        'include' => 'voice_in_trunk,voice_in_trunk_group,sms_trunk,sms_trunk_group',
        'page' => ['size' => 1, 'number' => 1],
    ]), 'listing DIDs');
    if ($dids->isEmpty()) {
        throw new SkipCheck('the account has no DID');
    }

    return $dids[0];
}

// Puts back the description, capacity limit, voice routing and SMS routing of
// $original, a DID read with firstDid(), and returns whether that succeeded.
function restoreDid(Did $original): bool
{
    $restored = cleanup('restoring DID '.$original->getNumber(), function () use ($original) {
        $did = Did::build($original->getId(), [
            'description' => $original->getDescription(),
            'capacity_limit' => $original->getAttribute('capacity_limit'),
        ]);
        $trunk = $original->voiceInTrunk()->getIncluded();
        $group = $original->voiceInTrunkGroup()->getIncluded();
        if ($trunk) {
            $did->setVoiceInTrunk(VoiceInTrunk::build($trunk->getId()));
        } elseif ($group) {
            $did->setVoiceInTrunkGroup(VoiceInTrunkGroup::build($group->getId()));
        } else {
            $did->voiceInTrunk()->dissociate();
            $did->voiceInTrunkGroup()->dissociate();
        }
        $smsTrunk = $original->smsTrunk()->getIncluded();
        $smsGroup = $original->smsTrunkGroup()->getIncluded();
        if ($smsTrunk) {
            $did->setSmsTrunk(SmsTrunk::build($smsTrunk->getId()));
        } elseif ($smsGroup) {
            $did->setSmsTrunkGroup(SmsTrunkGroup::build($smsGroup->getId()));
        } else {
            $did->setSmsTrunk(null);
            $did->setSmsTrunkGroup(null);
        }

        return $did->save();
    });
    if (!$restored) {
        Readme::$unrestoredDid = $original->getNumber();
    }

    return $restored;
}

function firstSkuId(): string
{
    $didGroups = dataOf(DidGroup::all([
        'include' => 'stock_keeping_units',
        'page' => ['size' => 10, 'number' => 1],
    ]), 'listing DID groups');
    foreach ($didGroups as $didGroup) {
        $skus = $didGroup->stockKeepingUnits()->getIncluded();
        if ($skus->isNotEmpty()) {
            return $skus->first()->getId();
        }
    }
    throw new UnexpectedValueException('no DID group with stock keeping units');
}

// The first available DID with its DID group and SKUs included.
function firstAvailableDid(): AvailableDid
{
    $availableDids = dataOf(AvailableDid::all([
        'include' => 'did_group.stock_keeping_units',
        'page' => ['size' => 1, 'number' => 1],
    ]), 'listing available DIDs');
    if ($availableDids->isEmpty()) {
        throw new SkipCheck('no available DID');
    }

    return $availableDids[0];
}

function skuIdOf(AvailableDid $availableDid): string
{
    $skus = $availableDid->didGroup()->getIncluded()?->stockKeepingUnits()->getIncluded();
    ensure(null !== $skus && $skus->isNotEmpty(), 'no SKU for available DID '.$availableDid->getNumber());

    return $skus->first()->getId();
}

function newSipTrunk(string $name): VoiceInTrunk
{
    $trunk = new VoiceInTrunk();
    $trunk->setName($name);
    $trunk->setConfiguration(new Sip(['host' => 'sip.example.com', 'port' => 5060]));

    return dataOf($trunk->save(), 'creating a voice in trunk');
}

function newSmsTrunk(string $name, $configuration): SmsTrunk
{
    $trunk = new SmsTrunk();
    $trunk->setName($name);
    $trunk->setConfiguration($configuration);

    return dataOf($trunk->save(), 'creating an SMS trunk');
}

function smsToEmail(): SmsToEmail
{
    $configuration = new SmsToEmail();
    $configuration->setSendTo('sms@example.com');
    $configuration->setSubject('Inbound SMS');
    $configuration->setMessage('You have a new message.');

    return $configuration;
}

function httpIn(): HttpIn
{
    $configuration = new HttpIn();
    $configuration->setUrl('https://example.com/sms');
    $configuration->setHttpMethod(SmsHttpMethod::GET);

    return $configuration;
}

function httpOut(): HttpOut
{
    $configuration = new HttpOut();
    $configuration->setCallbackUrl('https://example.com/sms/status');
    $configuration->setAllowedIps(['203.0.113.10']);

    return $configuration;
}

// Cancels an order the check placed while it is still pending.
function cancelOrder(?Order $order): void
{
    if (null === $order) {
        return;
    }
    if ($order->isPending()) {
        cleanup('canceling order '.$order->getReference(), fn () => $order->delete());
    } else {
        note('order '.$order->getReference().' is '.$order->getStatus()->value.', not canceled');
    }
}

// Skips the check when the account has no price plan for the service.
function withPricePlan(callable $lookup): void
{
    try {
        $lookup();
    } catch (DidwwPricesException $e) {
        if (PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED === $e->getKind()) {
            throw new SkipCheck('the account has no price plan for this service');
        }
        throw $e;
    }
}

function tempDir(): string
{
    $dir = sys_get_temp_dir().'/didww-readme-'.suffix();
    ensure(mkdir($dir, 0700), "cannot create $dir");

    return $dir;
}

function removeDir(string $dir): void
{
    foreach (glob("$dir/{,.}*", GLOB_BRACE) ?: [] as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
    @rmdir($dir);
}

// The signature DIDWW sends with a callback, computed as RequestValidator
// checks it: HMAC-SHA1 over the URL with its port, then every key and value
// of the payload sorted by key.
function signCallback(string $secret, string $url, array $payload): string
{
    $parts = parse_url($url);
    $port = $parts['port'] ?? ('https' === $parts['scheme'] ? 443 : 80);
    $data = $parts['scheme'].'://'.$parts['host'].':'.$port.($parts['path'] ?? '');
    if (isset($parts['query'])) {
        $data .= '?'.$parts['query'];
    }
    ksort($payload);
    foreach ($payload as $key => $value) {
        $data .= $key.$value;
    }

    return hash_hmac('sha1', $data, $secret);
}

// ─── Usage ──────────────────────────────────────────────────

Readme::section('Usage');

Readme::check('Usage: balance and DID groups', function () {
    $credentials = new Credentials(apiKey(), 'sandbox');
    Configuration::configure($credentials, [], ...urlOverrides());

    $balance = dataOf(Balance::find(), 'reading the balance');
    note('Balance: '.$balance->getTotalBalance());

    $didGroups = dataOf(DidGroup::all([
        'include' => 'stock_keeping_units',
        'filter' => ['area_name' => 'Acapulco'],
    ]), 'listing DID groups');
    note(count($didGroups).' DID groups in Acapulco');
});

// ─── Configuration ──────────────────────────────────────────

Readme::section('Configuration');

Readme::skip('Configuration: production', 'production only; this script runs against the sandbox');

Readme::check('Configuration: sandbox, read-only tokens and a PSR-3 logger', function () {
    $logger = new MemoryLogger();
    $credentials = new Credentials(apiKey(), 'sandbox');
    // The README also passes a proxy; its placeholder host is left out.
    Configuration::configure($credentials, [], ...urlOverrides(), scope: Scope::READ, logger: $logger);
    ensure(Scope::READ === Configuration::getCredentials()->getScope(), 'the scope is not read');
    dataOf(Balance::find(), 'reading the balance with a read token');
    note(count($logger->records).' log records: '.implode(', ', $logger->records));
});

Readme::check('HTTP client: Guzzle options', function () {
    // The README also passes a proxy and a CA bundle; their placeholders are left out.
    Configuration::configure(new Credentials(apiKey(), 'sandbox'), ['connect_timeout' => 5], ...urlOverrides());
    ensure(Configuration::getHttpClient() instanceof GuzzleHttp\ClientInterface, 'no Guzzle client');
    dataOf(Balance::find(), 'reading the balance');
});

// ─── Authentication ─────────────────────────────────────────

Readme::section('Authentication');

Readme::check('Scope: read, full and the default', function () {
    // The README builds them for 'production'; no request is sent with them.
    $read = new Credentials(apiKey(), 'sandbox', scope: Scope::READ);
    $full = new Credentials(apiKey(), 'sandbox', scope: Scope::FULL);
    $default = new Credentials(apiKey(), 'sandbox');
    ensure(Scope::READ === $read->getScope() && Scope::FULL === $full->getScope() && null === $default->getScope(), 'unexpected scopes');

    Configuration::configure($default, [], ...urlOverrides(), scope: Scope::READ);
    dataOf(Balance::find(), 'reading the balance with a read token');
});

Readme::skip('Token cache: Psr16TokenStore over Redis', 'needs a Redis server and symfony/cache, which the SDK does not depend on');

Readme::check('Token cache: a TokenStore of your own', function () {
    $store = new class implements TokenStore {
        /** @var AccessToken[] */
        public array $tokens = [];
        public int $writes = 0;

        public function get(string $key): ?AccessToken
        {
            return $this->tokens[$key] ?? null;
        }

        public function set(string $key, AccessToken $token, int $ttl): void
        {
            $this->tokens[$key] = $token;
            ++$this->writes;
        }

        public function delete(string $key): void
        {
            unset($this->tokens[$key]);
        }
    };
    Configuration::configure(new Credentials(apiKey(), 'sandbox'), [], ...urlOverrides(), tokenStore: $store);
    dataOf(Balance::find(), 'reading the balance');
    ensure(1 === $store->writes, 'expected one token written to the store, got '.$store->writes);

    $token = array_values($store->tokens)[0];
    $copy = AccessToken::fromArray($token->toArray());
    ensure($copy->getAccessToken() === $token->getAccessToken(), 'toArray() and fromArray() lose the access token');
    ensure(!str_contains(print_r($token, true), $token->getAccessToken()), 'print_r() shows the access token');
    note('token for '.implode(', ', $token->getAudience()).', scope '.$token->getScope().', expires in '.$token->getExpiresIn().' s');
});

// ─── Read-Only Resources ────────────────────────────────────

Readme::section('Read-Only Resources');

Readme::check('Read-only resources: countries', function () {
    $countries = dataOf(Country::all(), 'listing countries');
    ensure($countries->isNotEmpty(), 'no countries');
    $country = dataOf(Country::find($countries[0]->getId()), 'finding a country');
    ensure($country->getId() === $countries[0]->getId(), 'find() returned another country');
    note(count($countries).' countries, found '.$country->getName());
});

Readme::check('Read-only resources: regions, cities, areas and NANPA prefixes', function () {
    $classes = ['regions' => Region::class, 'cities' => City::class, 'areas' => Area::class, 'NANPA prefixes' => NanpaPrefix::class];
    foreach ($classes as $what => $class) {
        note(count(dataOf($class::all(), "listing $what"))." $what");
    }
});

Readme::check('Read-only resources: toll-free DID groups with SKUs', function () {
    $groups = dataOf(DidGroup::all([
        'include' => 'stock_keeping_units',
        'filter' => ['number_type' => NumberType::TOLL_FREE->value],
    ]), 'listing DID groups');
    foreach ($groups as $group) {
        ensure(NumberType::TOLL_FREE === $group->getNumberType(), 'DID group '.$group->getId().' is not toll-free');
    }
    note(count($groups).' toll-free DID groups');
});

Readme::check('Read-only resources: available DIDs with nested includes', function () {
    $available = dataOf(AvailableDid::all(['include' => 'did_group.stock_keeping_units']), 'listing available DIDs');
    if ($available->isNotEmpty()) {
        $didGroup = $available[0]->didGroup()->getIncluded();
        ensure(null !== $didGroup, 'did_group is not included');
        note($available[0]->getNumber().': '.count($didGroup->stockKeepingUnits()->getIncluded()).' SKUs');
    }
});

Readme::check('Read-only resources: proof types, public keys, address requirements and balance', function () {
    note(count(dataOf(ProofType::all(), 'listing proof types')).' proof types');
    ensure(count(dataOf(PublicKey::all(), 'listing public keys')) >= 2, 'expected two public keys');
    $requirements = dataOf(AddressRequirement::all(), 'listing address requirements');
    foreach ($requirements as $requirement) {
        // arrays, empty when no field is mandatory
        $requirement->getPersonalMandatoryFields();
        $requirement->getBusinessMandatoryFields();
    }
    note(count($requirements).' address requirements');
    dataOf(Balance::find(), 'reading the balance');
});

// ─── DIDs ───────────────────────────────────────────────────

Readme::section('DIDs');

Readme::check('DIDs: list, then update the description, capacity limit and trunk', function () {
    note(count(dataOf(Did::all(), 'listing DIDs')).' DIDs');
    $original = firstDid(true);
    $trunk = null;
    $changed = false;
    try {
        $trunk = newSipTrunk('README DID trunk '.suffix());

        $did = dataOf(Did::find($original->getId()), 'finding the DID');
        $did->setDescription('Updated');
        $did->setCapacityLimit(20);
        $did->setVoiceInTrunk(VoiceInTrunk::build($trunk->getId()));
        $changed = true;
        $updated = dataOf($did->save(['include' => 'voice_in_trunk']), 'updating the DID');
        ensure('Updated' === $updated->getDescription() && 20 === $updated->getCapacityLimit(), 'the DID was not updated');
        ensure($trunk->getId() === $updated->voiceInTrunk()->getIncluded()?->getId(), 'the trunk was not assigned');
    } finally {
        if ($changed && !restoreDid($original)) {
            keep('trunk', $trunk);
        } elseif (null !== $trunk) {
            cleanup('deleting the trunk', fn () => $trunk->delete());
        }
    }
});

Readme::check('DIDs: SMS routing to a trunk, to a trunk group and unrouted', function () {
    $original = firstDid(true);
    $suffix = suffix();
    $trunk = $groupTrunk = $group = null;
    $changed = false;
    try {
        $trunk = newSmsTrunk("README SMS routing $suffix", smsToEmail());
        $groupTrunk = newSmsTrunk("README SMS group member $suffix", httpIn());
        $newGroup = new SmsTrunkGroup();
        $newGroup->setName("README SMS routing group $suffix");
        $newGroup->setSmsTrunks(new Collection([SmsTrunk::build($groupTrunk->getId())]));
        $group = dataOf($newGroup->save(), 'creating the SMS trunk group');

        // sends sms_trunk and sms_trunk_group: null
        $did = Did::build($original->getId());
        $did->setSmsTrunk(SmsTrunk::build($trunk->getId()));
        $changed = true;
        ensureNoErrors($did->save(), 'routing to the SMS trunk');

        $dids = dataOf(Did::all([
            'filter' => ['sms_trunk.id' => $trunk->getId()],
            'include' => 'sms_trunk,sms_trunk_group',
        ]), 'listing the DIDs of the SMS trunk');
        ensure(1 === count($dids) && $trunk->getId() === $dids[0]->smsTrunk()->getIncluded()?->getId(), 'the DID is not routed to the SMS trunk');

        // sends sms_trunk_group and sms_trunk: null
        $did = Did::build($original->getId());
        $did->setSmsTrunkGroup(SmsTrunkGroup::build($group->getId()));
        $routed = dataOf($did->save(['include' => 'sms_trunk,sms_trunk_group']), 'routing to the SMS trunk group');
        ensure(null === $routed->smsTrunk()->getIncluded() && $group->getId() === $routed->smsTrunkGroup()->getIncluded()?->getId(), 'the DID is not routed to the group only');

        // unroute: sends sms_trunk: null and sms_trunk_group: null
        $did = Did::build($original->getId());
        $did->setSmsTrunk(null);
        $did->setSmsTrunkGroup(null);
        $unrouted = dataOf($did->save(['include' => 'sms_trunk,sms_trunk_group']), 'unrouting');
        ensure(null === $unrouted->smsTrunk()->getIncluded() && null === $unrouted->smsTrunkGroup()->getIncluded(), 'the DID is still routed');
    } finally {
        if ($changed && !restoreDid($original)) {
            keep('SMS trunk', $trunk);
            keep('SMS trunk group', $group);
            keep('SMS trunk', $groupTrunk);
        } else {
            // Deleting a group also deletes the trunks in it.
            if (null !== $group) {
                cleanup('deleting the SMS trunk group', fn () => $group->delete());
            } elseif (null !== $groupTrunk) {
                cleanup('deleting the SMS trunk', fn () => $groupTrunk->delete());
            }
            if (null !== $trunk) {
                cleanup('deleting the SMS trunk', fn () => $trunk->delete());
            }
        }
    }
});

Readme::check('DIDs: dirty-only PATCH serialization', function () {
    $original = firstDid(true);
    $id = $original->getId();
    $suffix = suffix();
    $trunk = null;
    $changed = false;
    try {
        // Load a DID from the API - only dirty fields are sent
        $did = dataOf(Did::find($id), 'finding the DID');
        $did->setDescription('New description');
        $body = $did->toJsonApiPatchArray();
        ensure(['type' => 'dids', 'id' => $id, 'attributes' => ['description' => 'New description']] === $body, 'PATCH body '.json_encode($body));
        $changed = true;
        $did = dataOf($did->save(), 'saving the description');

        // Build a resource by ID and set a single field
        $did = Did::build($id);
        $did->setCapacityLimit(10);
        $body = $did->toJsonApiPatchArray();
        ensure(['type' => 'dids', 'id' => $id, 'attributes' => ['capacity_limit' => 10]] === $body, 'PATCH body '.json_encode($body));
        $did = dataOf($did->save(), 'saving the capacity limit');

        // Explicitly clear a field by setting it to null
        $did = Did::build($id);
        $did->setDescription(null);
        $body = $did->toJsonApiPatchArray();
        ensure(['type' => 'dids', 'id' => $id, 'attributes' => ['description' => null]] === $body, 'PATCH body '.json_encode($body));
        $did = dataOf($did->save(), 'clearing the description');

        // Included resources also start with clean dirty state; the DID is
        // routed to a new trunk so that renaming it changes nothing else.
        $trunk = newSipTrunk("README dirty PATCH trunk $suffix");
        $route = Did::build($id);
        $route->setVoiceInTrunk(VoiceInTrunk::build($trunk->getId()));
        ensureNoErrors($route->save(), 'routing the DID to the trunk');

        $didDocument = Did::find($id, ['include' => 'voice_in_trunk']);
        $included = dataOf($didDocument, 'finding the DID')->voiceInTrunk()->getIncluded();
        ensure(null !== $included, 'voice_in_trunk is not included');
        $included->setName("Renamed trunk $suffix");
        $body = $included->toJsonApiPatchArray();
        ensure(['type' => 'voice_in_trunks', 'id' => $trunk->getId(), 'attributes' => ['name' => "Renamed trunk $suffix"]] === $body, 'PATCH body '.json_encode($body));
        $renamed = dataOf($included->save(), 'renaming the trunk');
        ensure("Renamed trunk $suffix" === $renamed->getName(), 'the trunk was not renamed');
    } finally {
        if ($changed && !restoreDid($original)) {
            keep('trunk', $trunk);
        } elseif (null !== $trunk) {
            cleanup('deleting the trunk', fn () => $trunk->delete());
        }
    }
});

// ─── Voice In Trunks ────────────────────────────────────────

Readme::section('Voice In Trunks');

Readme::check('Voice IN trunks: create, update and delete a SIP trunk', function () {
    $sip = new Sip([
        'host' => 'sip.example.com',
        'port' => 5060,
        'codecs' => [Codec::PCMA, Codec::PCMU, Codec::TELEPHONE_EVENT],
        'ice_mode' => IceMode::ACCEPT,
        'transport_protocol_id' => TransportProtocol::UDP,
        'sst_refresh_method_id' => SstRefreshMethod::INVITE,
        'media_encryption_mode' => MediaEncryptionMode::DISABLED,
        'rerouting_disconnect_code_ids' => Base::getDefaultReroutingDisconnectCodeIds(),
    ]);

    $trunk = new VoiceInTrunk();
    $trunk->setName('My SIP Trunk '.suffix());
    $trunk->setCliFormat(CliFormat::E164);
    $trunk->setRingingTimeout(30);
    $trunk->setConfiguration($sip);

    $trunkDocument = $trunk->save();
    $trunk = dataOf($trunkDocument, 'creating the trunk');
    $deleted = false;
    try {
        ensure([Codec::PCMA, Codec::PCMU, Codec::TELEPHONE_EVENT] === $trunk->getConfiguration()->getCodecs(), 'unexpected codecs');

        // Update trunk
        $trunk->setDescription('Updated');
        $updated = dataOf($trunk->save(), 'updating the trunk');
        ensure('Updated' === $updated->getDescription(), 'the description was not updated');

        // Delete trunk
        ensureNoErrors($trunk->delete(), 'deleting the trunk');
        $deleted = true;
    } finally {
        if (!$deleted) {
            cleanup('deleting the trunk', fn () => $trunk->delete());
        }
    }
});

Readme::check('Voice IN trunks: set the point of presence', function () {
    $created = newSipTrunk('README POP trunk '.suffix());
    try {
        $trunk = dataOf(VoiceInTrunk::find($created->getId()), 'finding the trunk');
        $trunk->setPop(Pop::AMS);
        $updated = dataOf($trunk->save(), 'setting the POP');
        ensure(Pop::AMS === $updated->getPop(), 'the POP was not set');
    } finally {
        cleanup('deleting the trunk', fn () => $created->delete());
    }
});

Readme::check('Voice IN trunks: SIP registration, enabled then disabled', function () {
    $sip = new Sip([
        'enabled_sip_registration' => true,
        'use_did_in_ruri' => true,
        'cnam_lookup' => true,
    ]);
    $trunk = new VoiceInTrunk(['name' => 'Office (registered) '.suffix(), 'configuration' => $sip]);
    $created = dataOf($trunk->save(), 'creating the trunk');
    try {
        $config = $created->getConfiguration();
        ensure(null !== $config->getIncomingAuthUsername() && null !== $config->getIncomingAuthPassword(), 'no server-generated credentials');

        $disable = new Sip();
        $disable->setHost('sip.example.com');
        $update = new VoiceInTrunk(['configuration' => $disable]);
        $update->setId($created->getId());
        $updated = dataOf($update->save(), 'disabling SIP registration');
        ensure(false === $updated->getConfiguration()->getEnabledSipRegistration(), 'SIP registration is still enabled');
    } finally {
        cleanup('deleting the trunk', fn () => $created->delete());
    }
});

// ─── Voice In Trunk Groups ──────────────────────────────────

Readme::section('Voice In Trunk Groups');

Readme::check('Voice IN trunk groups: create', function () {
    $groupDocument = null;
    try {
        $group = new VoiceInTrunkGroup();
        $group->name = 'Primary Group '.suffix();
        $group->capacity_limit = 50;
        $groupDocument = $group->save();
        ensureNoErrors($groupDocument, 'creating the trunk group');
    } finally {
        if (null !== $groupDocument && !$groupDocument->hasErrors()) {
            cleanup('deleting the trunk group', fn () => $groupDocument->getData()->delete());
        }
    }
});

// ─── Voice Out Trunks ───────────────────────────────────────

Readme::section('Voice Out Trunks');

Readme::check('Voice OUT trunks: create with credentials_and_ip', function () {
    $did = firstDid();
    $trunk = new VoiceOutTrunk();
    $trunk->setName('My Outbound Trunk '.suffix());
    $trunk->setAuthenticationMethod(new CredentialsAndIp([
        'allowed_sip_ips' => ['203.0.113.0/24'],
    ]));
    $trunk->setOnCliMismatchAction(OnCliMismatchAction::REPLACE_CLI);
    $trunk->setCodecs([VoiceOutCodec::OPUS, VoiceOutCodec::PCMA, VoiceOutCodec::TELEPHONE_EVENT]);
    $trunk->setDefaultDid(Did::build($did->getId()));
    $trunkDocument = $trunk->save();
    // Without that configuration the API refuses replace_cli or its default_did
    if ($trunkDocument->hasErrors() && preg_match('/on_cli_mismatch_action|default_did/', errorsOf($trunkDocument))) {
        throw new SkipCheck('REPLACE_CLI needs additional account configuration by DIDWW support: '.errorsOf($trunkDocument));
    }
    $created = dataOf($trunkDocument, 'creating the Voice OUT trunk');
    try {
        $auth = $created->getAuthenticationMethod();
        ensure($auth instanceof CredentialsAndIp && null !== $auth->getUsername() && null !== $auth->getPassword(), 'no server-generated credentials');
    } finally {
        cleanup('deleting the Voice OUT trunk', fn () => $created->delete());
    }
});

// ─── SMS Trunks ─────────────────────────────────────────────

Readme::section('SMS Trunks');

Readme::check('SMS trunks: create, update and list an HTTP IN trunk', function () {
    $configuration = new HttpIn();
    $configuration->setUrl('https://example.com/sms');
    $configuration->setHttpMethod(SmsHttpMethod::POST);
    $configuration->setBodyType(SmsBodyType::JSON);
    $configuration->setBody('{"text":"{SMS_TEXT}","from":"{SMS_SRC_ADDR}","to":"{SMS_DST_ADDR}"}');

    $trunk = new SmsTrunk();
    $trunk->setName('Inbound webhook '.suffix());
    $trunk->setConfiguration($configuration);
    $trunk = dataOf($trunk->save(), 'creating the SMS trunk');
    $created = $trunk;
    try {
        // A setter given null sends an explicit null, e.g. when switching to GET
        $update = new HttpIn();
        $update->setHttpMethod(SmsHttpMethod::GET);
        $update->setBodyType(null);
        $update->setBody(null);
        $trunk->setConfiguration($update);
        $trunk = dataOf($trunk->save(), 'switching the trunk to GET');
        ensure(SmsHttpMethod::GET === $trunk->getConfiguration()->getHttpMethod(), 'the HTTP method was not changed');

        // List by configuration type, with the group
        $trunks = dataOf(SmsTrunk::all([
            'filter' => ['configuration.type' => 'http_in,sms_to_email'],
            'include' => 'sms_trunk_group',
        ]), 'listing SMS trunks');
        note(count($trunks).' http_in and sms_to_email trunks on the first page');
    } finally {
        cleanup('deleting the SMS trunk', fn () => $created->delete());
    }
});

Readme::check('SMS trunks: regenerate the credentials of an HTTP OUT trunk', function () {
    $created = newSmsTrunk('README HTTP OUT '.suffix(), httpOut());
    try {
        $trunk = SmsTrunk::build($created->getId());
        ensureNoErrors($trunk->regenerateCredentials(), 'regenerating the credentials');

        $trunk = dataOf(SmsTrunk::find($created->getId()), 'reading the trunk');
        $configuration = $trunk->getConfiguration();
        ensure($configuration instanceof HttpOut, 'not an http_out configuration');
        ensure(null !== $configuration->getAuthName() && null !== $configuration->getAuthPassword(), 'no generated credentials');
    } finally {
        cleanup('deleting the SMS trunk', fn () => $created->delete());
    }
});

// ─── SMS Trunk Groups ───────────────────────────────────────

Readme::section('SMS Trunk Groups');

Readme::check('SMS trunk groups: create with a trunk', function () {
    $suffix = suffix();
    $member = newSmsTrunk("README group member $suffix", httpIn());
    $group = null;
    try {
        $newGroup = new SmsTrunkGroup();
        $newGroup->setName("Inbound SMS $suffix");
        $newGroup->setSmsTrunks(new Collection([SmsTrunk::build($member->getId())]));
        $group = dataOf($newGroup->save(), 'creating the SMS trunk group');
        ensure(1 === $group->getSmsTrunksCount(), 'expected 1 trunk in the group, got '.var_export($group->getSmsTrunksCount(), true));
    } finally {
        // Deleting a group also deletes the trunks in it.
        if (null !== $group) {
            cleanup('deleting the SMS trunk group', fn () => $group->delete());
        } else {
            cleanup('deleting the SMS trunk', fn () => $member->delete());
        }
    }
});

// ─── Sender ID Verifications ────────────────────────────────

Readme::section('Sender ID Verifications');

function activeSenderIdVerifications()
{
    return dataOf(SenderIdVerification::all([
        'filter' => ['status' => SenderIdVerificationStatus::ACTIVE->value],
        'include' => 'sms_trunk',
    ]), 'listing Sender ID verifications');
}

Readme::check('Sender ID verifications: list the active ones', function () {
    $verifications = activeSenderIdVerifications();
    note(count($verifications).' active Sender ID verifications');
    if ($verifications->isNotEmpty()) {
        $meta = $verifications[0]->getMeta();
        note('monthly price: '.($meta['monthly_price'] ?? 'none'));
    }
});

Readme::check('Sender ID verifications: route through an SMS trunk, then unroute', function () {
    $verifications = activeSenderIdVerifications();
    if ($verifications->isEmpty()) {
        throw new SkipCheck('no active Sender ID verification on the account');
    }
    $original = $verifications[0];
    $originalTrunk = $original->smsTrunk()->getIncluded();
    $trunk = null;
    $changed = false;
    try {
        $trunk = newSmsTrunk('README Sender ID '.suffix(), httpOut());

        $verification = SenderIdVerification::build($original->getId());
        $verification->setSmsTrunk(SmsTrunk::build($trunk->getId()));
        $changed = true;
        ensureNoErrors($verification->save(), 'routing through the SMS trunk');

        $verification = SenderIdVerification::build($original->getId());
        $verification->setSmsTrunk(null);
        ensureNoErrors($verification->save(), 'unrouting');
    } finally {
        $restored = !$changed || cleanup('restoring the Sender ID verification', function () use ($original, $originalTrunk) {
            $restore = SenderIdVerification::build($original->getId());
            $restore->setSmsTrunk($originalTrunk ? SmsTrunk::build($originalTrunk->getId()) : null);

            return $restore->save();
        });
        if (!$restored) {
            keep('SMS trunk', $trunk);
        } elseif (null !== $trunk) {
            cleanup('deleting the SMS trunk', fn () => $trunk->delete());
        }
    }
});

// ─── Orders ─────────────────────────────────────────────────

Readme::section('Orders');

Readme::check('Orders: by SKU, with a callback', function () {
    $created = null;
    try {
        $order = new Order([
            'allow_back_ordering' => true,
            'items' => [
                new DidOrderItem(['sku_id' => firstSkuId(), 'qty' => 2]),
            ],
        ]);
        $order->setCallbackUrl('https://example.com/callback');
        $order->setCallbackMethod(CallbackMethod::POST);
        $orderDocument = $order->save();
        $created = dataOf($orderDocument, 'ordering by SKU');
        note('order '.$created->getReference().': '.$created->getStatus()->value);
        ensure(CallbackMethod::POST === $created->getCallbackMethod(), 'the callback method was not saved');
    } finally {
        cancelOrder($created);
    }
});

Readme::check('Orders: an available DID', function () {
    $availableDid = firstAvailableDid();
    $created = null;
    try {
        $order = new Order([
            'items' => [
                new AvailableDidOrderItem([
                    'sku_id' => skuIdOf($availableDid),
                    'available_did_id' => $availableDid->getId(),
                ]),
            ],
        ]);
        $created = dataOf($order->save(), 'ordering the available DID');
        note('order '.$created->getReference().': '.$created->getStatus()->value);
    } finally {
        cancelOrder($created);
    }
});

Readme::check('Orders: a reserved DID', function () {
    $availableDid = firstAvailableDid();
    $skuId = skuIdOf($availableDid);
    $reservation = new DidReservation();
    $reservation->setAvailableDid(AvailableDid::build($availableDid->getId()));
    $reservation = dataOf($reservation->save(), 'reserving the DID');
    $created = null;
    try {
        $order = new Order([
            'items' => [
                new ReservationDidOrderItem([
                    'sku_id' => $skuId,
                    'did_reservation_id' => $reservation->getId(),
                ]),
            ],
        ]);
        $created = dataOf($order->save(), 'ordering the reserved DID');
        note('order '.$created->getReference().': '.$created->getStatus()->value);
    } finally {
        if (null === $created) {
            cleanup('deleting the reservation', fn () => $reservation->delete());
        }
        cancelOrder($created);
    }
});

Readme::check('Orders: capacity', function () {
    $pools = dataOf(CapacityPool::all(), 'listing capacity pools');
    ensure($pools->isNotEmpty(), 'no capacity pools');
    $pool = $pools->first(fn (CapacityPool $candidate) => $candidate->getMinimumQtyPerOrder() <= 1) ?? $pools->first();
    $created = null;
    try {
        $order = new Order([
            'items' => [
                new CapacityOrderItem([
                    'capacity_pool_id' => $pool->getId(),
                    'qty' => max(1, $pool->getMinimumQtyPerOrder()),
                ]),
            ],
        ]);
        $created = dataOf($order->save(), 'ordering capacity');
        note('order '.$created->getReference().' in '.$pool->getName().': '.$created->getStatus()->value);
    } finally {
        cancelOrder($created);
    }
});

// ─── DID Reservations ───────────────────────────────────────

Readme::section('DID Reservations');

Readme::check('DID reservations: create, list and delete', function () {
    $availableDid = firstAvailableDid();
    $reservationDocument = null;
    $deleted = false;
    try {
        $reservation = new DidReservation();
        $reservation->setDescription('Reserved for client');
        $reservation->setAvailableDid(AvailableDid::build($availableDid->getId()));
        $reservationDocument = $reservation->save();
        ensureNoErrors($reservationDocument, 'reserving the DID');
        ensure($reservationDocument->getData()->getExpiresAt() instanceof DateTime, 'no expiry');

        // The reservations that expire first
        $reservations = dataOf(DidReservation::all(['sort' => 'expires_at']), 'listing reservations');
        note(count($reservations).' reservations');

        // Delete reservation
        ensureNoErrors($reservationDocument->getData()->delete(), 'deleting the reservation');
        $deleted = true;
    } finally {
        if (!$deleted && null !== $reservationDocument && !$reservationDocument->hasErrors()) {
            cleanup('deleting the reservation', fn () => $reservationDocument->getData()->delete());
        }
    }
});

// ─── Shared Capacity Groups ─────────────────────────────────

Readme::section('Shared Capacity Groups');

Readme::check('Shared capacity groups: create', function () {
    $pool = dataOf(CapacityPool::all(), 'listing capacity pools')->first(
        fn (CapacityPool $candidate) => $candidate->getTotalChannelsCount() > $candidate->getAssignedChannelsCount()
    );
    if (null === $pool) {
        throw new SkipCheck('no capacity pool has an unassigned channel');
    }
    $group = new SharedCapacityGroup();
    $group->setName('Shared Group '.suffix());
    // 20 in the README; 1 here, so that a sandbox pool has enough channels
    $group->setSharedChannelsCount(1);
    $group->setCapacityPool(CapacityPool::build($pool->getId()));
    $groupDocument = $group->save();
    $created = dataOf($groupDocument, 'creating the shared capacity group');
    cleanup('deleting the shared capacity group', fn () => $created->delete());
});

// ─── Identities and Addresses ───────────────────────────────

Readme::section('Identities and Addresses');

// The address check deletes the identity the identity check creates.
$identityId = null;
Readme::check('Identities: create a business identity', function () use (&$identityId) {
    $identity = new Identity([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => '12125551234',
        'identity_type' => IdentityType::BUSINESS,
        'company_name' => 'Example Ltd',
    ]);
    $identity->setCompanyWebsite('https://example.com');
    $identity->setCountry(Country::build(usCountryId()));
    $identityDocument = $identity->save();
    $identityId = dataOf($identityDocument, 'creating the identity')->getId();
});

Readme::check('Addresses: create', function () use ($identityId) {
    ensure(null !== $identityId, 'needs the identity of the previous check');
    try {
        $address = new Address();
        $address->setCityName('New York');
        $address->setPostalCode('10001');
        $address->setAddress('123 Main St');
        $address->setIdentity(Identity::build($identityId));
        $address->setCountry(Country::build(usCountryId()));
        $addressDocument = $address->save();
        $created = dataOf($addressDocument, 'creating the address');
        cleanup('deleting the address', fn () => $created->delete());
    } finally {
        cleanup('deleting the identity', fn () => Identity::build($identityId)->delete());
    }
});

// ─── Exports ────────────────────────────────────────────────

Readme::section('Exports');

Readme::check('Exports: CDR export', function () {
    $export = new Export();
    $export->setExportType(ExportType::CDR_IN);
    $export->setFilterFrom('2026-10-01 00:00:00'); // inclusive, UTC
    $export->setFilterTo('2026-10-02 00:00:00');   // exclusive, UTC
    $created = dataOf($export->save(), 'creating the CDR export');
    note('export '.$created->getId().': '.$created->getStatus()->value);
});

Readme::check('Exports: SMS export, read again and downloaded when completed', function () {
    $dir = tempDir();
    $smsTrunk = null;
    try {
        $smsTrunk = newSmsTrunk('README export filter '.suffix(), smsToEmail());
        $export = new Export();
        $export->setExportType(ExportType::SMS_OUT);
        $export->setFilterFrom('2026-10-01 00:00:00');
        $export->setFilterTo('2026-10-02 00:00:00');
        $export->setFilterServiceType(ExportServiceType::A2P);
        $export->setFilterSmsTrunkId([$smsTrunk->getId()]); // sent as "sms_trunk.id"
        $export = dataOf($export->save(), 'creating the SMS export');

        // The export is pending until its file is generated; read it again later
        $export = dataOf(Export::find($export->getId()), 'reading the export');
        if ($export->isCompleted()) {
            $result = $export->download("$dir/sms_out.csv.gz"); // gzipped CSV, as served
            ensure(true === $result, "download failed: $result");
            $result = $export->downloadAndDecompress("$dir/sms_out.csv"); // plain CSV
            ensure(true === $result, "download failed: $result");
            note('downloaded '.filesize("$dir/sms_out.csv").' bytes of CSV');
        } else {
            note('export '.$export->getStatus()->value.', not downloaded');
        }
    } finally {
        if (null !== $smsTrunk) {
            cleanup('deleting the SMS trunk', fn () => $smsTrunk->delete());
        }
        removeDir($dir);
    }
});

// ─── Address Verifications ──────────────────────────────────

Readme::section('Address Verifications');

Readme::check('Address verifications: list', function () {
    note(count(dataOf(AddressVerification::all(), 'listing address verifications')).' address verifications');
});

Readme::skip('Address verifications: create', 'needs a DID whose DID group requires an address and an address that meets its requirements, and a verification cannot be deleted');

// ─── Emergency Services ─────────────────────────────────────

Readme::section('Emergency Services');

Readme::check('Emergency services: requirements and calling services', function () {
    note(count(dataOf(EmergencyRequirement::all(), 'listing emergency requirements')).' emergency requirements');
    note(count(dataOf(EmergencyCallingService::all(), 'listing emergency calling services')).' emergency calling services');
});

Readme::skip('Emergency services: create an emergency verification', 'needs a DID with the emergency feature and an address in its country, and a verification cannot be deleted; emergency_scenario.php runs this flow');

// ─── DID History ────────────────────────────────────────────

Readme::section('DID History');

Readme::check('DID history: list', function () {
    $history = dataOf(DidHistory::all(), 'listing DID history');
    foreach (array_slice($history->all(), 0, 3) as $entry) {
        note($entry->getAction().' '.$entry->getCreatedAt()->format('Y-m-d'));
    }
    note(count($history).' DID history entries');
});

// ─── Prices ─────────────────────────────────────────────────

Readme::section('Prices');

Readme::check('Prices: Voice IN lookup', fn () => withPricePlan(function () {
    $result = Prices::voiceIn()->lookup(['number' => '18005550123', 'src_country_iso' => 'US']);
    note('total records: '.$result->getTotalRecords());
    foreach ($result as $price) {
        if (null !== $price->getRate()) {
            note(sprintf('%s %s: %s USD/min', $price->getDestination(), $price->getSourceType(), $price->getRate()));
        }
    }
}));

Readme::check('Prices: Voice OUT lookup', fn () => withPricePlan(function () {
    $voiceOut = Prices::voiceOut()->lookup(['number' => '493012345678']);
    $category = $voiceOut->getRows()[0]->getCategory();
    note('category: '.($category instanceof VoiceOutPriceCategory ? $category->value : $category));
}));

Readme::check('Prices: SMS OUT A2P lookup', fn () => withPricePlan(function () {
    note(count(Prices::smsOutA2p()->lookup(['dst_number' => '4915123456789'])).' rows');
}));

Readme::check('Prices: SMS IN lookup', fn () => withPricePlan(function () {
    note(count(Prices::smsIn()->lookup(['dst_country_iso' => 'US'])).' rows');
}));

Readme::check('Prices: Voice OUT price file', fn () => withPricePlan(function () {
    $dir = tempDir();
    try {
        $prices = Prices::voiceOut();
        $path = "$dir/voice_out_prices.csv.gz";

        // Download unless the copy at the path is current
        $file = $prices->fetch($path);
        if ($file->isNotModified()) {
            note('answered 304, the file was kept');
        } else {
            note(sprintf('downloaded %d bytes, last modified %s', $file->getBytesWritten(), $file->getLastModified()?->format(DATE_ATOM)));
        }

        // Read the rows
        $rows = 0;
        foreach (PriceFileReader::rows($path) as $row) {
            note($row['Prefix'].': '.$row['Rate']);
            if (++$rows >= 3) {
                break;
            }
        }
        ensure($rows > 0, 'the price file has no rows');

        // Lower-level calls
        $status = $prices->head($file->getLastModified());
        note('HEAD: not modified '.var_export($status->isNotModified(), true).', filename '.var_export($status->getFilename(), true));
        $prices->download($path);
        $stream = fopen('php://temp', 'w+b');
        try {
            note('downloaded '.$prices->download($stream)->getBytesWritten().' bytes into a stream');
        } finally {
            fclose($stream);
        }
    } finally {
        removeDir($dir);
    }
}));

// ─── Filtering, Sorting, and Pagination ─────────────────────

Readme::section('Filtering, Sorting, and Pagination');

Readme::check('Regions with filter, include, sort and page', function () {
    $regionsDocument = Region::all([
        'filter' => ['country.id' => usCountryId(), 'name' => 'Arizona'],
        'include' => 'country',
        'sort' => 'name',
        'page' => ['size' => 25, 'number' => 1],
    ]);
    $regions = dataOf($regionsDocument, 'listing regions');
    ensure($regions->isNotEmpty(), 'Arizona not found');
    note($regions[0]->getName().', '.$regions[0]->country()->getIncluded()?->getName());
});

// ─── Error Handling ─────────────────────────────────────────

Readme::section('Error Handling');

Readme::check('Error handling: validation errors', function () {
    $order = new Order(['items' => []]);
    $document = $order->save();
    ensure($document->hasErrors(), 'an order without items was accepted');
    foreach ($document->getErrors() as $error) {
        note($error->getDetail());
    }
});

Readme::check('Error handling: not found', function () {
    $document = Did::find('non-existent-uuid');
    ensure($document->hasErrors(), 'no error for an unknown DID');
    foreach ($document->getErrors() as $error) {
        note($error->getTitle().': '.$error->getDetail());
    }
});

Readme::check('Error handling: Retry-After of an error answer (a 429 is not provoked)', function () {
    $document = Did::find('non-existent-uuid');
    ensure($document->hasErrors(), 'no error for an unknown DID');
    $status = $document->getErrors()->first()->getStatus();
    $retryAfter = RetryAfter::seconds($document);
    note("status $status, Retry-After ".var_export($retryAfter, true));
    ensure('429' === $status || null === $retryAfter, 'a Retry-After without a 429');
});

Readme::check('Error handling: token service and prices exceptions', function () {
    try {
        $result = Prices::smsOutA2p()->lookup(['dst_number' => '4915123456789']);
        note(count($result).' A2P prices');
    } catch (DidwwAuthException $e) {
        if (AuthErrorKind::RATE_LIMITED === $e->getKind()) {
            note('Token service rate limit, retry after '.$e->getRetryAfter());
        } elseif (AuthErrorKind::INVALID_CLIENT === $e->getKind()) {
            note('API key refused: '.$e->getMessage());
        } else {
            note('No access token: '.$e->getMessage());
        }
    } catch (DidwwPricesException $e) {
        note(match ($e->getKind()) {
            PricesErrorKind::PRICE_NOT_FOUND => 'No price for this number',
            PricesErrorKind::TOO_MANY_REQUESTS => 'Prices rate limit, retry after '.$e->getRetryAfter(),
            default => 'Prices error: '.$e->getMessage(),
        });
    }

    // Or catch the token service and prices exceptions together
    try {
        $result = Prices::voiceIn()->lookup(['number' => '18005550123']);
        note(count($result).' Voice IN prices');
    } catch (DidwwExceptionInterface $e) {
        note($e->getMessage());
    }
});

// ─── Date and Datetime Fields ───────────────────────────────

Readme::section('Date and Datetime Fields');

Readme::check('Date and datetime getters', function () {
    $did = dataOf(Did::find(firstDid()->getId()), 'finding the DID');
    note('DID created '.$did->getCreatedAt()->format('Y-m-d H:i:s').', expires '.($did->getExpiresAt()?->format('Y-m-d') ?? 'never'));

    foreach (dataOf(Identity::all(), 'listing identities') as $listed) {
        if (null !== $listed->getBirthDate()) {
            $identity = dataOf(Identity::find($listed->getId()), 'finding the identity');
            ensure(1 === preg_match('/^\d{4}-\d{2}-\d{2}$/', $identity->getBirthDate()->format('Y-m-d')), 'unexpected birth date format');
            note('birth date of an identity read as a date');

            return;
        }
    }
    note('no identity with a birth date on the first page');
});

// ─── Enums ──────────────────────────────────────────────────

Readme::section('Enums');

Readme::check('Enums: setters take enum cases and raw values', function () {
    $order = new Order();
    $order->setCallbackMethod(CallbackMethod::POST);
    ensure(CallbackMethod::POST === $order->getCallbackMethod(), 'callback method');

    $identity = new Identity();
    $identity->setIdentityType(IdentityType::BUSINESS);
    ensure(IdentityType::BUSINESS === $identity->getIdentityType(), 'identity type');

    // Both are equivalent
    $trunk = new VoiceOutTrunk();
    $trunk->setOnCliMismatchAction(OnCliMismatchAction::REPLACE_CLI);
    $byCase = $trunk->getOnCliMismatchAction();
    $trunk->setOnCliMismatchAction('replace_cli');
    ensure(OnCliMismatchAction::REPLACE_CLI === $byCase && $byCase === $trunk->getOnCliMismatchAction(), 'on_cli_mismatch_action');
});

// ─── File Encryption ────────────────────────────────────────

Readme::section('File Encryption');

Readme::check('File encryption: encrypt and upload a file', function () {
    $fileContents = file_get_contents(__DIR__.'/sample.pdf'); // the README reads /path/to/document.pdf
    $enc = new Encrypt();
    $fingerprint = $enc->getFingerprint();
    $encData = $enc->encrypt($fileContents);
    $uploadResult = EncryptedFile::upload($fingerprint, $encData, 'file description');
    if ($uploadResult->success()) {
        note('uploaded '.$uploadResult->getId());
        cleanup('deleting the encrypted file', fn () => EncryptedFile::build($uploadResult->getId())->delete());
    } elseif (429 === $uploadResult->getResponseCode()) {
        throw new UnexpectedValueException('Retry after '.$uploadResult->getRetryAfter().' s');
    } else {
        throw new UnexpectedValueException('upload failed with HTTP '.$uploadResult->getResponseCode().': '.json_encode($uploadResult->getErrors()));
    }
});

// ─── Webhook Signature Validation ───────────────────────────

Readme::section('Webhook Signature Validation');

Readme::check('RequestValidator: a signed callback is valid, a tampered one is not', function () {
    $validator = new RequestValidator('YOUR_CALLBACK_SECRET');
    $requestUrl = 'https://example.com/callbacks/orders?source=didww';
    $payloadParams = ['id' => '2989925f-7df3-4dc9-9944-d1fa0f4e8e86', 'type' => 'orders', 'status' => 'completed'];
    $signature = signCallback('YOUR_CALLBACK_SECRET', $requestUrl, $payloadParams);

    $valid = $validator->validate(
        $requestUrl,      // full original URL
        $payloadParams,   // array of payload key-value pairs
        $signature         // value of X-DIDWW-Signature header
    );
    ensure(true === $valid, 'the signed callback is not valid');
    ensure(false === $validator->validate($requestUrl, ['status' => 'canceled'] + $payloadParams, $signature), 'a tampered callback is valid');
});

// ─── Summary ────────────────────────────────────────────────

echo "\n".str_repeat('=', 50)."\n";
echo 'Results: '.Readme::$passed.' passed, '.Readme::$failed.' failed'.(Readme::$skipped > 0 ? ', '.Readme::$skipped.' skipped' : '')."\n";
if (Readme::$failures) {
    echo "\nFailures:\n";
    foreach (Readme::$failures as $failure) {
        echo "  - $failure\n";
    }
}
echo str_repeat('=', 50)."\n";

exit(Readme::$failed > 0 ? 1 : 0);
