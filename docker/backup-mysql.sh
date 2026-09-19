#!/bin/sh

set -eu

backup_dir="${1:-./backups}"
timestamp="$(date +%Y-%m-%d_%H-%M-%S)"
backup_file="${backup_dir}/meri_gaadi_${timestamp}.sql"

mkdir -p "$backup_dir"
docker exec shared-mysql-server mysqldump \
    -uroot -prootsecret \
    --single-transaction --routines --triggers \
    meri_gaadi > "$backup_file"

printf 'Database backup created: %s\n' "$backup_file"