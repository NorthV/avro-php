<?php

namespace Apache\Avro\Protocol;

use Apache\Avro\Schema\Name;
use Apache\Avro\Schema\PrimitiveSchema;
use Apache\Avro\Schema\RecordSchema;
use Apache\Avro\Schema\Schema;

class ProtocolMessage
{
    public $name;
    public $request;
    public $response;

    public function __construct(string $name, array $avro, Protocol $protocol)
    {
        $this->name = $name;

        $this->request = new RecordSchema(
            new Name($name, null, $protocol->namespace),
            null,
            $avro['request'],
            $protocol->schemata,
            Schema::REQUEST_SCHEMA
        );

        if (array_key_exists('response', $avro)) {
            $this->response = $protocol->schemata->schemaByName(
                new Name($avro['response'], $protocol->namespace, $protocol->namespace)
            );

            if (null === $this->response) {
                $this->response = new PrimitiveSchema($avro['response']);
            }
        }
    }
}
