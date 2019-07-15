#!/bin/bash
set -eu

#cd /var/www/html/docs
composer create-project symfony/skeleton $(pwd)
composer require symfony/twig-bundle
composer require sensio/framework-extra-bundle
composer require symfony/http-foundation
composer require symfony/webpack-encore-bundle

yarn install
yarn add bootstrap --dev
yarn add sass-loader@^7.0.1 node-sass --dev
yarn add jquery popper.js

composer config repositories.avro vcs https://github.com/northv/avro-php
composer require apache/avro-php

echo "AVRO_REGISTRY_LINK=http://${1}/api/v1/schemaregistry" >> .env
echo "AVRO_CONFLUENT_LINK=http://${1}/api/v1/confluent"     >> .env

mkdir templates/AvroSerDe
cp vendor/apache/avro-php/AvroBundle/Controller/AppControllerAvroSerDeController.php src/Controller/AvroSerDeController.php
cp vendor/apache/avro-php/AvroBundle/Resources/views/AvroSerDe/index.html.twig       templates/AvroSerDe/index.html.twig

cat vendor/apache/avro-php/AvroBundle/Resources/views/base.html.twig               > templates/base.html.twig
cat vendor/apache/avro-php/AvroBundle/Resources/assets/css/app.css                 > assets/css/app.css
cat vendor/apache/avro-php/AvroBundle/Resources/assets/js/app.js                   > assets/js/app.js

sed -i "s/\/\/.enableSassLoader()/.enableSassLoader()/" webpack.config.js
yarn encore dev
