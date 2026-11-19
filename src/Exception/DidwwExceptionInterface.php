<?php

namespace Didww\Exception;

/**
 * Implemented by the token service and prices exceptions of the SDK
 * (DidwwAuthException, DidwwPricesException), so that they can be caught
 * together. Other exceptions the SDK can throw do not implement it: the
 * Guzzle TransferException of a JSON:API call, \InvalidArgumentException
 * for invalid arguments, and the \RuntimeException and
 * \UnexpectedValueException of price files and lookups.
 */
interface DidwwExceptionInterface extends \Throwable
{
}
