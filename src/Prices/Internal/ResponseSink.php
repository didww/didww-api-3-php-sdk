<?php

namespace Didww\Prices\Internal;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * The Guzzle sink of a prices request or an export download. begin() is
 * called from on_headers with each answer, so a retried request starts
 * afresh. The body of a 200 goes to the destination (or is kept, or dropped
 * without one); any other body is buffered up to a size and a time limit,
 * after which the transfer is aborted.
 *
 * write() never throws: an error of the destination is recorded and the
 * transfer aborted (getWriteError()).
 *
 * @internal
 */
final class ResponseSink implements StreamInterface
{
    private ?ResponseInterface $response = null;
    private string $body = '';
    private string $errorBody = '';
    private int $bytesWritten = 0;
    private ?\Throwable $writeError = null;
    private ?string $writeFailure = null;
    private float $deadline = INF;

    /**
     * @param resource|StreamInterface|null $destination where a 200 body is written
     * @param bool                          $keepBody    keep a 200 body in memory (getBody()) instead
     */
    public function __construct(
        private readonly mixed $destination,
        private readonly bool $keepBody,
        private readonly int $maxErrorBodySize,
        private readonly float $errorBodyTimeout,
    ) {
    }

    public function begin(ResponseInterface $response): void
    {
        $this->response = $response;
        $this->body = '';
        $this->errorBody = '';
        $this->bytesWritten = 0;
        $this->deadline = hrtime(true) / 1e9 + $this->errorBodyTimeout;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getErrorBody(): string
    {
        return $this->errorBody;
    }

    public function getBytesWritten(): int
    {
        return $this->bytesWritten;
    }

    // A failed write of a resource is recorded as a message and its exception
    // made here, after the transfer: one made within it would keep the request
    // with its token in the trace.
    public function getWriteError(): ?\Throwable
    {
        return $this->writeError ?? (null === $this->writeFailure ? null : new \RuntimeException($this->writeFailure));
    }

    // Whether the body of a 200 answer was taken in full, for a handler that
    // ends a body cut short by the server without an error (Guzzle's own
    // handlers report it as a transfer error). As decode_content is off, a
    // numeric Content-Length counts the bytes written. A chunked body cut
    // short cannot be told apart here.
    public function isComplete(ResponseInterface $response): bool
    {
        $length = $response->getHeaderLine('Content-Length');

        return !ctype_digit($length) || (int) $length === $this->bytesWritten;
    }

    public function write(string $string): int
    {
        $length = strlen($string);
        if (200 !== $this->response?->getStatusCode()) {
            return $this->bufferError($string);
        }
        if ($this->keepBody) {
            $this->body .= $string;
        } elseif (null === $this->destination) {
            return $length;
        } else {
            if (null !== $this->writeError || null !== $this->writeFailure) {
                return 0;
            }
            try {
                if (!$this->writeToDestination($string)) {
                    return 0;
                }
            } catch (\Throwable $e) {
                $this->writeError = $e;

                return 0;
            }
        }
        $this->bytesWritten += $length;

        return $length;
    }

    private function writeToDestination(string $string): bool
    {
        error_clear_last();
        if ($this->destination instanceof StreamInterface) {
            $written = $this->destination->write($string);
        } else {
            $written = @fwrite($this->destination, $string);
        }
        if ($written !== strlen($string)) {
            $this->writeFailure = 'Failed to write the price file: '.(error_get_last()['message'] ?? 'short write');

            return false;
        }

        return true;
    }

    private function bufferError(string $string): int
    {
        $room = $this->maxErrorBodySize - strlen($this->errorBody);
        if ($room <= 0 || hrtime(true) / 1e9 > $this->deadline) {
            return 0;
        }
        $this->errorBody .= substr($string, 0, $room);

        return strlen($string) > $room ? 0 : strlen($string);
    }

    public function __toString(): string
    {
        return '';
    }

    public function close(): void
    {
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        return $this->bytesWritten;
    }

    public function eof(): bool
    {
        return true;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    // Seeking to the start is accepted and does nothing, since Guzzle's stream
    // handler rewinds the sink once the body is copied.
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (0 !== $offset || SEEK_SET !== $whence) {
            throw new \RuntimeException('The price file sink is not seekable');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function isReadable(): bool
    {
        return false;
    }

    public function read(int $length): string
    {
        throw new \RuntimeException('The price file sink is not readable');
    }

    public function getContents(): string
    {
        return '';
    }

    public function getMetadata(?string $key = null)
    {
        return null === $key ? [] : null;
    }
}
