<?php

namespace Didww\Enum;

enum SmsBodyType: string
{
    case JSON = 'json';
    case HTML_MULTIPART = 'html_multipart';
    case HTML_URL_ENCODED = 'html_url_encoded';
    case RAW = 'raw';
}
