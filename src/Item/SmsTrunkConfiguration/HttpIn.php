<?php

namespace Didww\Item\SmsTrunkConfiguration;

use Didww\Enum\SmsBodyType;
use Didww\Enum\SmsHttpMethod;

/**
 * Inbound messages are delivered as an HTTP request. Query parameters,
 * headers and body may use the placeholders {SMS_TEXT},
 * {SMS_TEXT_BASE64_ENCODED}, {SMS_SRC_ADDR}, {SMS_DST_ADDR} and {SMS_TIME}.
 *
 * body_type and body are required for `post` and `put` and refused for
 * `get`: set both to null when switching to `get`.
 */
class HttpIn extends Base
{
    protected const OBJECT_ATTRIBUTES = ['query_parameters', 'headers', 'body'];

    protected array $sensitiveAttributes = ['headers'];

    public function getType(): string
    {
        return 'http_in';
    }

    public function getUrl(): ?string
    {
        return $this->attribute('url');
    }

    public function setUrl(string $url)
    {
        $this->attributes['url'] = $url;
    }

    public function getHttpMethod(): SmsHttpMethod|string|null
    {
        return $this->enumAttribute('http_method', SmsHttpMethod::class);
    }

    public function setHttpMethod(SmsHttpMethod|string $httpMethod)
    {
        $this->setEnumAttribute('http_method', $httpMethod);
    }

    public function getQueryParameters(): ?array
    {
        return $this->objectAttribute('query_parameters');
    }

    /**
     * @param array<string, mixed>|null $queryParameters sent as a JSON object; null clears them
     */
    public function setQueryParameters(?array $queryParameters)
    {
        $this->setObjectAttribute('query_parameters', $queryParameters);
    }

    public function getHeaders(): ?array
    {
        return $this->objectAttribute('headers');
    }

    /**
     * @param array<string, mixed>|null $headers sent as a JSON object; null clears them
     */
    public function setHeaders(?array $headers)
    {
        $this->setObjectAttribute('headers', $headers);
    }

    public function getBodyType(): SmsBodyType|string|null
    {
        return $this->enumAttribute('body_type', SmsBodyType::class);
    }

    public function setBodyType(SmsBodyType|string|null $bodyType)
    {
        $this->setEnumAttribute('body_type', $bodyType);
    }

    /**
     * A JSON string for `json`, an array (JSON object) for the form
     * encodings, a raw string for `raw`.
     */
    public function getBody(): string|array|null
    {
        return self::toPlainArray($this->attribute('body'));
    }

    /**
     * @param string|array<string, mixed>|null $body an array is sent as a JSON object
     */
    public function setBody(string|array|null $body)
    {
        $this->attributes['body'] = is_array($body) ? (object) $body : $body;
    }

    public function getFollowRedirect(): ?bool
    {
        return $this->attribute('follow_redirect');
    }

    public function setFollowRedirect(bool $followRedirect)
    {
        $this->attributes['follow_redirect'] = $followRedirect;
    }

    public function getValidateHttpsCertificate(): ?bool
    {
        return $this->attribute('validate_https_certificate');
    }

    public function setValidateHttpsCertificate(bool $validateHttpsCertificate)
    {
        $this->attributes['validate_https_certificate'] = $validateHttpsCertificate;
    }
}
