#!/bin/bash

FILE=./.build-done

until test -f "$FILE"; do
   sleep 3
   echo "waiting for $FILE"
done

echo "$FILE found!"
