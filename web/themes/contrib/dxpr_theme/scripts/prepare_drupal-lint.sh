#!/bin/bash

echo "\$COMPOSER_HOME: $COMPOSER_HOME"

composer global require drupal/coder


export PATH="$PATH:$COMPOSER_HOME/vendor/bin"

composer global require dealerdirect/phpcodesniffer-composer-installer

composer global show -P
phpcs -i


phpcs --config-set colors 1
phpcs --config-set ignore_warnings_on_exit 1
phpcs --config-set drupal_core_version 8

phpcs --config-show
