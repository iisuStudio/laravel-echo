#!/bin/bash
# composer script
composer install --prefer-dist --no-interaction --no-suggest --no-progress --no-dev
composer archive --dir=storage/deploy --file=laravel-echo-$(git describe --tags)_update --format=zip
