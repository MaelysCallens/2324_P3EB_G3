#!/bin/bash

set -euxo pipefail

source scripts/run_eslint_wait.sh

# ESlint scope
PATHS=(js/dist)

# create eslint-report.htlm for easier tracing and fixing
if [ "$REPORT_ENABLED" = 'true' ]; then
  # eslint js -f node_modules/eslint-detailed-reporter/lib/detailed.js -o out/eslint-report.html
  npx eslint "${PATHS[@]}" -f node_modules/eslint-detailed-reporter/lib/detailed.js -o out/eslint-report.html || true
  echo "eslint-report.html created"
fi

# check js only for now https://github.com/dxpr/dxpr_builder/issues/146
# eslint js
# should always display the eslint check on the console
npx eslint "${PATHS[@]}"

# too big to get finished
# eslint dxpr_builder/dxpr_param_types.js --debug --no-ignore
