#!/bin/bash

set -euxo pipefail
  
host="$1"
shift
cmd="$@"

until [[ "$(curl -s -o /dev/null -I -w "%{http_code}\n" -H Host:$host http://nginx)" == "200" ]] ; do

  ## The "|| true" construct protects against possible non-zero exist code executing the curl command
  ## that will propagate and terminate the current script.
  code="$(curl -s -o /dev/null -I -w "%{http_code}\n" -H Host:$host http://nginx || true)"

  ## If installation fails or missing important files
  if [ "$code" = "500" -o "$code" = "404" ] ; then
    echo "Demo site is broke with status code $code, exiting..."
    exit 1
  fi

  >&2 echo "qa-demo is inaccessible..."
  sleep 5
done

>&2 echo "qa-demo is up and running, starting dxpr builder grunt..."
exec $cmd