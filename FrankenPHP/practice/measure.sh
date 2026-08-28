#!/usr/bin/env bash

set -euo pipefail

export LC_ALL=C

project_root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
compose=(docker compose -f "$project_root/compose.yaml")
requests=${REQUESTS:-100}

cleanup() {
  "${compose[@]}" down --volumes --remove-orphans >/dev/null 2>&1 || true
}

wait_for_service() {
  local name=$1
  local host=$2
  local service=$3
  local start_ns elapsed_ns deadline

  start_ns=$(date +%s%N)
  deadline=$((SECONDS + 30))
  until "${compose[@]}" exec --no-TTY benchmark-client \
    curl --silent --fail --output /dev/null "http://$host/"; do
    if (( SECONDS >= deadline )); then
      printf '%s did not respond within 30 seconds. Container logs:\n' "$name" >&2
      "${compose[@]}" logs --tail=50 "$service" >&2
      return 1
    fi
    sleep 0.05
  done
  elapsed_ns=$(( $(date +%s%N) - start_ns ))
  printf '%s cold-start: %.3f s\n' "$name" "$(awk "BEGIN { print $elapsed_ns / 1000000000 }")"
}

measure_latency() {
  local name=$1
  local host=$2
  local index result_file p95_rank

  result_file=$(mktemp)
  p95_rank=$(( (requests * 95 + 99) / 100 ))

  printf '%s latency, %s sequential requests:\n' "$name" "$requests"
  for index in $(seq 1 "$requests"); do
    "${compose[@]}" exec --no-TTY benchmark-client \
      curl --silent --output /dev/null --write-out '%{time_total}\n' "http://$host/" >> "$result_file"
  done

  awk -v name="$name" '
    { sum += $1 }
    NR == 1 || $1 < min { min = $1 }
    $1 > max { max = $1 }
    END { printf "  min: %.6f s; avg: %.6f s; max: %.6f s\n", min, sum / NR, max }
  ' "$result_file"
  sort -n "$result_file" | awk -v rank="$p95_rank" 'NR == rank { printf "  p95: %.6f s\n", $1 }'
  rm -f "$result_file"
}

cleanup
trap cleanup EXIT

printf 'Starting benchmark client...\n'
"${compose[@]}" up --detach benchmark-client
printf 'Starting FrankenPHP and waiting for the first response...\n'
"${compose[@]}" up --detach frankenphp
wait_for_service "FrankenPHP" "frankenphp" "frankenphp"
measure_latency "FrankenPHP" "frankenphp"
"${compose[@]}" stop frankenphp >/dev/null

printf 'Starting nginx + php-fpm and waiting for the first response...\n'
"${compose[@]}" up --detach nginx php-fpm
wait_for_service "nginx + php-fpm" "nginx" "nginx"
measure_latency "nginx + php-fpm" "nginx"
