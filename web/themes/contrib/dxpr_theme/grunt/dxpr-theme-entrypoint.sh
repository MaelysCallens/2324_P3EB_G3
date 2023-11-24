#!/bin/bash

set -euxo pipefail

echo "Running dxpr theme grunt..."

/qa-demo.wait.sh $DEMO_HOST [ ! -f "$NPM_INSTALL_STAMP" ] && { npm install && npm rebuild node-sass && touch "$NPM_INSTALL_STAMP"; } \
; { npx grunt babel; npx grunt terser; npx grunt sass; npx grunt postcss; npx grunt ; }