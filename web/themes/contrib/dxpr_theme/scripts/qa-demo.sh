#!/bin/bash

set -euxo pipefail

### Cleanup before running
### This will not remove volumes. removing volumes should be done intentionally and explicitly
source "${BASH_SOURCE%/*}/qa-demo.cleanup.sh"
source .env

### Enforce building the grunt images.
docker-compose \
  -f docker-compose.yml \
  -f docker-compose.dev.yml \
  build dxpr-theme-grunt

### Run with latest dxpr theme.
docker-compose \
  -f docker-compose.yml \
  -f docker-compose.dev.yml \
  --profile qa-demo \
  up -d

### Follow logs.
docker-compose \
  -f docker-compose.yml \
  -f docker-compose.dev.yml \
  logs -f
