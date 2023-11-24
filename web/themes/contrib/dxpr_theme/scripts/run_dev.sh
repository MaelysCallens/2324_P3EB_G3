#!/bin/bash

set -euxo pipefail

rm -rf .build-done || true

[ ! -f "$NPM_INSTALL_STAMP" ] && { npm install; touch "$NPM_INSTALL_STAMP"; }

touch .build-done


if [ "$WATCH" = 'true' ]; then
  npx grunt watch
fi