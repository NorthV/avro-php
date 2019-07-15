<?php

namespace Apache\Avro\Exception;

use Apache\Avro\Schema\Schema;

/**
 * Exceptions arising from incompatibility between reader and writer schemas.
 */
class NoCachedMetaException extends Exception
{
    public function __construct(Schema $oSchema)
    {
        parent::__construct(
            sprintf('No cached meta for schema: "%s".', $oSchema)
        );
    }
}
