#!/bin/bash

set -euxo pipefail

docker-compose \
  -f docker-compose.yml \
  -f docker-compose.install.yml \
  -f docker-compose.dev.yml \
  -f docker-compose.prod.yml \
  -f docker-compose.test.yml \
  down --remove-orphans