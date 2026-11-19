<?php

namespace Didww\Prices;

use GuzzleHttp\Psr7\Header;
use Psr\Http\Message\ResponseInterface;

/**
 * The price file headers of a HEAD or download answer.
 */
final readonly class PriceFile
{
    public function __construct(
        private int $statusCode,
        private ?\DateTimeImmutable $lastModified,
        private ?int $contentLength,
        private ?string $filename,
        private int $bytesWritten,
    ) {
    }

    /**
     * @internal
     */
    public static function fromResponse(ResponseInterface $response, int $bytesWritten): self
    {
        $contentLength = $response->getHeaderLine('Content-Length');

        return new self(
            $response->getStatusCode(),
            self::parseHttpDate($response->getHeaderLine('Last-Modified')),
            preg_match('/\A[0-9]{1,18}\z/', $contentLength) ? (int) $contentLength : null,
            self::parseFilename($response->getHeaderLine('Content-Disposition')),
            $bytesWritten,
        );
    }

    /**
     * 200, or 304 when the file has not changed since the If-Modified-Since
     * date sent.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * The Last-Modified of the answer, in UTC; null when absent or not an
     * IMF-fixdate.
     */
    public function getLastModified(): ?\DateTimeImmutable
    {
        return $this->lastModified;
    }

    /**
     * The Content-Length of the answer; null when unknown, for example on 304.
     */
    public function getContentLength(): ?int
    {
        return $this->contentLength;
    }

    /**
     * The file name from Content-Disposition, for example "voice_in_prices.csv.gz".
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * The bytes written by a download; 0 for HEAD and 304.
     */
    public function getBytesWritten(): int
    {
        return $this->bytesWritten;
    }

    /**
     * Whether the file has not changed since the If-Modified-Since date sent (304).
     */
    public function isNotModified(): bool
    {
        return 304 === $this->statusCode;
    }

    private static function parseHttpDate(string $value): ?\DateTimeImmutable
    {
        if ('' === $value) {
            return null;
        }
        // An IMF-fixdate such as "Wed, 14 Oct 2026 06:00:00 GMT". Its date is
        // parsed without the weekday ("D" would move the date to that
        // weekday), which must then match.
        $value = trim($value);
        if (1 !== preg_match('/\A(Mon|Tue|Wed|Thu|Fri|Sat|Sun), (.+)\z/', $value, $parts)) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!d M Y H:i:s \G\M\T', $parts[2], new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();
        if (false === $date || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('D') !== $parts[1]) {
            return null;
        }

        return $date;
    }

    // The filename* (RFC 5987) or filename parameter of a Content-Disposition.
    private static function parseFilename(string $value): ?string
    {
        if ('' === $value) {
            return null;
        }
        $params = array_change_key_case(Header::parse($value)[0] ?? [], CASE_LOWER);
        $extended = $params['filename*'] ?? null;
        if (is_string($extended) && preg_match("/\\A[^']*'[^']*'(.+)\\z/s", $extended, $m)) {
            return rawurldecode($m[1]);
        }
        $filename = $params['filename'] ?? null;

        return is_string($filename) && '' !== $filename ? $filename : null;
    }
}
