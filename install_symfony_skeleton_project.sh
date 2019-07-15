#!/bin/bash
set -eu

#cd /var/www/html/docs
composer create-project symfony/skeleton $(pwd)
composer require symfony/twig-bundle
composer require sensio/framework-extra-bundle
composer require symfony/http-foundation

composer config repositories.avro vcs https://github.com/northv/avro-php
composer require apache/avro-php

echo "AVRO_REGISTRY_LINK=http://${1}/api/v1/schemaregistry" >> .env
echo "AVRO_CONFLUENT_LINK=http://${1}/api/v1/confluent"     >> .env

mkdir templates/AvroSerDe
cp vendor/apache/avro-php/AvroBundle/Resources/views/AvroSerDe/index.html.twig       templates/AvroSerDe/index.html.twig
cp vendor/apache/avro-php/AvroBundle/Controller/AppControllerAvroSerDeController.php src/Controller/AvroSerDeController.php
