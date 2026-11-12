#!/usr/bin/env bash

set -eu

script="$(dirname "$0")/../../infra/local/mysql-init/01-create-testing-database.sh"

run_validation() {
    MYSQL_ROOT_PASSWORD='root' \
    MYSQL_DATABASE='platform' \
    MYSQL_USER='sail' \
    MYSQL_TEST_DATABASE="$1" \
    MYSQL_TEST_USER="$2" \
    MYSQL_TEST_PASSWORD="$3" \
    MYSQL_INIT_VALIDATE_ONLY=1 \
    "$script" >/dev/null
}

run_validation 'testing' 'sail_test' 'p'"'"'ss;word$'

if run_validation 'testing-name' 'sail_test' 'password'; then
    printf '%s\n' 'invalid identifier was accepted' >&2
    exit 1
fi

if run_validation 'platform' 'sail_test' 'password'; then
    printf '%s\n' 'development database was accepted' >&2
    exit 1
fi

if run_validation 'testing' 'sail' 'password'; then
    printf '%s\n' 'development user was accepted' >&2
    exit 1
fi

printf '%s\n' 'MySQL init validation checks passed.'
