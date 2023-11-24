#!/bin/sh

# install required libs by drupal
GD_ENABLED=$(php -i | grep 'GD Support' | awk '{ print $4 }')

if [ "$GD_ENABLED" != 'enabled' ]; then
  echo "apk update && \
  apk add libpng libpng-dev libjpeg-turbo-dev libwebp-dev zlib-dev libxpm-dev gd && docker-php-ext-install gd"
  apk update && \
  apk add libpng libpng-dev libjpeg-turbo-dev libwebp-dev zlib-dev libxpm-dev gd && docker-php-ext-install gd
fi

if [ -z "DRUPAL_RECOMMENDED_PROJECT" ]; then
  DRUPAL_RECOMMENDED_PROJECT=8.8.x-dev
fi

# if drupal directory exists, don't re-create
if [ ! -d "/drupal" ]; then
  composer create-project drupal/recommended-project:$DRUPAL_RECOMMENDED_PROJECT drupal --no-interaction --stability=dev
fi

echo "cd drupal"
cd drupal
echo "mkdir -p web/themes/contrib/"
mkdir -p web/themes/contrib/

if [ ! -L "web/themes/contrib/dxpr_theme" ]; then
  echo "ln -s /src web/themes/contrib/dxpr_theme"
  ln -s /src web/themes/contrib/dxpr_theme
fi

echo "composer require mglaman/drupal-check --dev"
composer require mglaman/drupal-check --dev

./vendor/bin/drupal-check --drupal-root . -ad web/themes/contrib/dxpr_theme