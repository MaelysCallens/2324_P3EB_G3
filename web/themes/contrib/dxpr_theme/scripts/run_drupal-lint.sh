#!/bin/bash

source scripts/prepare_drupal-lint.sh

EXIT_CODE=0

phpcs --standard=Drupal \
  --extensions=php,module,inc,install,test,profile,theme,info,txt,md,yml \
  --ignore="node_modules,vendor,.github,color.inc" \
  -v \
  .

status=$?
if [ $status -ne 0 ]; then
  EXIT_CODE=$status
fi


phpcs --standard=DrupalPractice \
  --extensions=php,module,inc,install,test,profile,theme,info,txt,md,yml \
  --ignore="node_modules,vendor,.github,color.inc" \
  -v \
  .

status=$?
if [ $status -ne 0 ]; then
  EXIT_CODE=$status
fi

# failed if one of the two checks failed
exit $EXIT_CODE
