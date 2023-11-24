#!/bin/bash

set -euxo pipefail

volumes_list=$(docker volume ls -q | grep "^${PWD##*/}_${1-}*" || echo "")

if [ ! -z "$volumes_list" ] ; then
  ### remove all/filtered volumes
  docker volume rm $(docker volume ls -q | grep "^${PWD##*/}_${1-}*")
fi
