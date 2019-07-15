<?php

namespace Apache\Avro\Exception;

use Apache\Avro\Schema\Schema;

/**
 * Exceptions arising from incompatibility between reader and writer schemas.
 */
class BadRequestStatusCodeException extends Exception
{
    public function __construct($sLink, $sCode)
    {
        parent::__construct(
            sprintf('Bad request url: "%s", status_code: "%s".', $sLink, $sCode)
        );
    }
}
