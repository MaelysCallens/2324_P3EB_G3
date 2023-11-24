#!/bin/bash

source scripts/run_eslint_wait.sh


npx eslint js/dist --fix
