#!/bin/bash

set -euxo pipefail

### stop and remove any container

#$(dirname "$(realpath "$0")")/qa-demo.cleanup.sh
source "${BASH_SOURCE%/*}/qa-demo.cleanup.sh"

### get volume list or default to empty string.
### Note: without the default—echo statement—this script will exit since -u option is used
volumes_list=$(docker volume ls -q | grep "^${PWD##*/}_qa-demo*" || echo "")
confirm_reset="N"

if [ -z "$volumes_list" ] ; then
  echo "
  Already clean
  "
else
  echo
  echo "This will remove all qa-demo related docker volumes related to this project folder
  "
  echo "The following volumes will be removed:
  "
  echo "$volumes_list
  "
  read -p "are you sure? [y/N]" -n 1 -r confirm_reset
  echo # new line

  if [ -z "${confirm_reset-}" ]; then confirm_reset="N"; fi
  if [[ $confirm_reset =~ ^[Yy]$ ]]; then
    echo "removing volumes..."
    ### remove all "qa-demo" related volumes
    # $(dirname "$(realpath "$0")")/qa-demo.reset.sh "qa-demo"
    source "${BASH_SOURCE%/*}/qa-demo.reset.sh" "qa-demo"
  else
    echo "canceling..."
    exit 0
  fi
fi

### Pulling the DXPR Builder and populating the dxpr-builder volume.
### Run the dxpr-builder service separately to avoid shutting
### down the stack after the service stops.
echo "Puling the dxpr/dxpr_builder image..."
docker-compose \
  -f docker-compose.yml \
  -f docker-compose.prod.yml pull dxpr-builder && docker-compose \
  -f docker-compose.yml \
  -f docker-compose.prod.yml \
  up dxpr-builder

### Enforce building the grunt images.
docker-compose \
  -f docker-compose.yml \
  -f docker-compose.dev.yml \
  build dxpr-theme-grunt

### Run profiles that needs to run to perform installation and volume population
docker-compose \
  -f docker-compose.yml \
  -f docker-compose.install.yml \
  -f docker-compose.dev.yml \
  --profile qa-demo \
  up
