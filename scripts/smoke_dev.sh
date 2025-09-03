#!/usr/bin/env bash
set -euo pipefail

COMPOSE="docker compose -f docker-compose.dev.yml"

echo "[smoke] Building and starting dev stack..."
$COMPOSE up -d --build

echo "[smoke] Waiting for /health..."
for i in {1..30}; do
  if curl -fsS "http://127.0.0.1:8080/health" >/dev/null 2>&1; then
    break
  fi
  sleep 1
  if [[ $i -eq 30 ]]; then
    echo "[smoke] /health did not become ready in time" >&2
    exit 1
  fi
done

echo "[smoke] Running PHPUnit Smoke tests..."
$COMPOSE exec app ./vendor/bin/phpunit -c /local/phpunit.xml --testsuite Smoke

echo "[smoke] SUCCESS"

