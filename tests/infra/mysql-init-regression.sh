#!/usr/bin/env bash

set -eu

script="$(dirname "$0")/../../infra/local/mysql-init/01-create-testing-database.sh"
mysql_host_args=()
if [[ -n "${MYSQL_INIT_MYSQL_HOST:-}" ]]; then
    mysql_host_args=(--protocol=tcp --host="$MYSQL_INIT_MYSQL_HOST" --port="${MYSQL_INIT_MYSQL_PORT:-3306}")
fi

test_password='p'"'"'ss;word$\\tail'
ordinary_password='ordinary-password'

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

run_initialization() {
    MYSQL_ROOT_PASSWORD='root' \
    MYSQL_DATABASE='platform' \
    MYSQL_USER='sail' \
    MYSQL_TEST_DATABASE='testing' \
    MYSQL_TEST_USER='sail_test' \
    MYSQL_TEST_PASSWORD="$1" \
    MYSQL_INIT_MYSQL_HOST="${MYSQL_INIT_MYSQL_HOST:-}" \
    MYSQL_INIT_MYSQL_PORT="${MYSQL_INIT_MYSQL_PORT:-3306}" \
    "$script"
}

run_validation 'testing' 'sail_test' "$test_password"

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

run_initialization "$ordinary_password"
MYSQL_PWD="$ordinary_password" mysql "${mysql_host_args[@]}" --user=sail_test --database=testing \
    --batch --skip-column-names --execute='SELECT DATABASE()' | grep -Fxq 'testing'

MYSQL_PWD=root mysql "${mysql_host_args[@]}" --user=root --batch --skip-column-names \
    --execute="DROP USER IF EXISTS 'sail_test'@'%'"

run_initialization "$test_password"
MYSQL_PWD="$test_password" mysql "${mysql_host_args[@]}" --user=sail_test --database=testing \
    --batch --skip-column-names --execute='SELECT DATABASE()' | grep -Fxq 'testing'

if MYSQL_PWD="$test_password" mysql "${mysql_host_args[@]}" --user=sail_test --database=platform \
    --batch --skip-column-names --execute='SELECT 1' >/dev/null 2>&1; then
    printf '%s\n' 'testing user unexpectedly accessed development database.' >&2
    exit 1
fi

printf '%s\n' 'MySQL init validation and SQL execution checks passed.'