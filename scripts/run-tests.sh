#!/usr/bin/env bash
set -euo pipefail

echo "Running tests in dev environment..."
APP_ENV=dev ./vendor/bin/phpunit

echo "Running tests in prod environment..."
APP_ENV=prod ./vendor/bin/phpunit


