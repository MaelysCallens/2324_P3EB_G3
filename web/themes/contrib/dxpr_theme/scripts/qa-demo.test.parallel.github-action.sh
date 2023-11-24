#!/bin/bash

set -euxo pipefail

ENV=${ENV-"prod"}

### Source the .env file in dev environment
if [ "$ENV" = "dev" ] ; then
  source .env
fi

### stop and remove any container
source "${BASH_SOURCE%/*}/qa-demo.cleanup.sh"

### get volume list or default to empty string.
### Note: without the default—echo statement—this script will exit since -u option is used
volumes_list=$(docker volume ls -q | grep "^${PWD##*/}*" || echo "")

if [ -z "$volumes_list" ] ; then
  echo "
  Already clean
  "
else
  echo
  echo "The following volumes will be removed:
  "
  echo "$volumes_list
  "
  echo # new line

  echo "removing volumes..."
  ### This will not remove volumes. removing volumes should be done intentionally and explicitly
  source "${BASH_SOURCE%/*}/qa-demo.reset.sh"
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

### Building the DXPR Theme and populating the dxpr-theme volume.
### Run the dxpr-theme service separately to avoid shutting
### down the stack after the service stops.
echo "Building the dxpr/dxpr_theme image..."
docker-compose \
  -f docker-compose.yml \
  -f docker-compose.prod.yml \
  up --build dxpr-theme

### Starting the build and test profiles and stop the stack on the maven service exit code.
docker-compose \
  -f docker-compose.yml \
  -f docker-compose.install.yml \
  -f docker-compose.prod.yml \
  -f docker-compose.test.yml \
  --profile qa-demo --profile test \
  up --exit-code-from maven \
  --scale chrome="${DRIVERS_POOL_SIZE:-6}"