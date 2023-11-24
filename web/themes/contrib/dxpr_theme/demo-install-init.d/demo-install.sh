#!/bin/bash

set -eo pipefail

# DXPR access token
if [ -z "$DXPR_ACCESS_TOKEN" ]
then
      echo "DXPR_ACCESS_TOKEN is empty"
      exit 1;
fi

# Configure the dxpr access token
composer config -g bearer.packages.dxpr.com $DXPR_ACCESS_TOKEN

# Creating a new project
composer create-project dxpr/lightning-dxpr-project:2.x-dev .

# Updating packages if using PHP ^8.0
if [[ "$PHP_TAG" =~ .*"8.0".* ]]; then
  composer update
fi

# Install the enterprise package
composer require dxpr/dxpr_builder_e

# Create the settings.php file
chmod 755 docroot/sites/default/
cp docroot/sites/default/default.settings.php docroot/sites/default/settings.php && chmod 777 docroot/sites/default/settings.php
mkdir -p docroot/sites/default/files && chmod -R 777 docroot/sites/default/files

echo "Removing the dxpr builder module..."
rm -rf docroot/modules/contrib/dxpr_builder

echo "Linking to the dxpr builder module..."
ln -s $DXPR_BUILDER_CONTAINER docroot/modules/contrib/dxpr_builder

echo "Removing the dxpr theme module..."
rm -rf docroot/themes/contrib/dxpr_theme

echo "Linking to the dxpr theme module..."
ln -s $DXPR_THEME_CONTAINER docroot/themes/contrib/dxpr_theme

if [ -z ${NPM_INSTALL_STAMP+x} ]
then
      NPM_INSTALL_STAMP=".npm.installed"
fi
NPM_INSTALL_STAMP="$DXPR_BUILDER_CONTAINER/$NPM_INSTALL_STAMP"
echo "removing npm modules at $NPM_INSTALL_STAMP"
rm -rf "$NPM_INSTALL_STAMP" || true

# Installing DXPR QA demo website
drush site-install lightning_dxpr lightning_dxpr_demo_select.demo_select=$DXPR_DEMO --db-url=mysql://$DB_USER:$DB_PASSWORD@mariadb:3306/$DB_NAME --account-pass=$DXPR_ADMIN_PASSWORD -y -v

# Allow accessing website assets
chmod -R 777 docroot/sites/default/files

drush -y config-set --input-format=yaml dxpr_builder.settings json_web_token eyJhbGciOiJQUzI1NiIsImtpZCI6IjR6RGRXS1pGNGRfbXprcVVMc2tYb3ItcE96bGRITFN0WGI1Q1pUX3d4UnMifQ.eyJpc3MiOiJodHRwczpcL1wvZHhwci5jb20iLCJzdWIiOiIyMDYzNiIsImF1ZCI6Imh0dHBzOlwvXC9wYWNrYWdlcy5keHByLmNvbSIsInNjb3BlIjoiZHhwclwvZHhwcl9idWlsZGVyIiwiZHhwcl90aWVyIjoiZW50ZXJwcmlzZSIsImp0aSI6IjgxYjZlNGNkMWFiMDIxZjRlZmExZDcwZDJiM2MyNWUzYjJlMjZiMTc5NjRlODRjMWM5OTY1M2UxN2FmNzVjNzQifQ.XnigBuLA24dbACFUhYGUSxFWYnt2ukiWM23T0b-Ig_mZKZQEBJeBbcFMJ2DjtH5G2Ape6fXS4fw8xDjBCTkaghEos-S8r_LcoZA27RiqTZz61w2235vvGNqtkR5uBY1awED4BLV5Zq26FRVa6A6ifgQd_coAxGZeuWG7KaVe8S9_7QECAtmBOr28KGxvZe4BHJDrd60XcljyPRHe1uLPfdSgmVeeLyWV6Oc2PQw0eHWuMg764s9hApmDkrwgKY4IA4u5yJn0cTF9Lel_rHTseXEHl-tnJZFngareR6W9hCbwlqNemvXoi7KGASpm56B4mimWhjzKLfEHH9uDsdxMbPyYGbq_5SO05qMnjNOTlqPF9a8qBBKMtWKzaEcSxDnJN0He6Rjm0JT3tMwtcd09r3hbAeDn3fTc42VO9Ykf6bo8ViH8QR85sHp0TZuGWn5NMQiT9p1YFGPay1k_Wn7YdD0NxnOydkVgG27DBN8DPK0BvKvuze1KHySNJSzcu0t7pdG8gnBML6QUfwo7L7POE6ZyqkpaKgz4qD2I8zo-gJn1omXGhOH2vuvPxS53CVErWwjsQPhAVhT6C6yzFkfIpJRGR73_-hr35eSKBzIS19_OpWUW-G56wu1Oi85FPaeFYBx87pgULarF34rnYGQXxlm1xi-ifGnCx_xWHoxy3bI

### Enable the DXPR analytics
if [ "$DXPR_RECORD_ANALYTICS" = true ] ; then
  echo "Enabling DXPR analytics..."
  drush -y config-set --input-format=yaml dxpr_builder.settings record_analytics true
else
  echo "Disabling DXPR analytics..."
  drush -y config-set --input-format=yaml dxpr_builder.settings record_analytics false
fi

### Disable the DXPR notifications
if [ "$DXPR_NOTIFICATIONS" = true ] ; then
  echo "Enabling DXPR notifications..."
  drush -y config-set --input-format=yaml dxpr_builder.settings notifications true
else
  echo "Disabling DXPR notifications..."
  drush -y config-set --input-format=yaml dxpr_builder.settings notifications false
fi

# Load editor assets from local (minified) files.
drush -y config-set --input-format=yaml dxpr_builder.settings editor_assets_source 1
drush cr

# Remove the DXPR access token from the container composer config for security
composer config -g --unset bearer.packages.dxpr.com
