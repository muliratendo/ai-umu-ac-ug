#!/usr/bin/env bash

set -eu

: "${MYSQL_ROOT_PASSWORD:?MYSQL_ROOT_PASSWORD is required}"
: "${MYSQL_TEST_DATABASE:?MYSQL_TEST_DATABASE is required}"
: "${MYSQL_TEST_USER:?MYSQL_TEST_USER is required}"
: "${MYSQL_TEST_PASSWORD:?MYSQL_TEST_PASSWORD is required}"

identifier_pattern='^[A-Za-z0-9_]+$'

if [[ "$MYSQL_TEST_DATABASE" != 'testing' ]]; then
    printf '%s\n' 'MYSQL_TEST_DATABASE must be exactly testing.' >&2
    exit 1
fi

if [[ "$MYSQL_TEST_USER" != 'sail_test' ]]; then
    printf '%s\n' 'MYSQL_TEST_USER must be exactly sail_test.' >&2
    exit 1
fi

if [[ ! "$MYSQL_TEST_DATABASE" =~ $identifier_pattern || ! "$MYSQL_TEST_USER" =~ $identifier_pattern ]]; then
    printf '%s\n' 'Database and user identifiers may contain only ASCII letters, numbers, and underscores.' >&2
    exit 1
fi

if [[ "${MYSQL_DATABASE:-}" == "$MYSQL_TEST_DATABASE" || "${MYSQL_USER:-}" == "$MYSQL_TEST_USER" ]]; then
    printf '%s\n' 'The testing database and user must not reuse development targets.' >&2
    exit 1
fi

if [[ "${MYSQL_INIT_VALIDATE_ONLY:-0}" == '1' ]]; then
    exit 0
fi

# Encode the password as a MySQL hexadecimal string instead of interpolating it
# into a quoted SQL string. This safely supports quotes, semicolons, and dollars.
password_hex=$(printf '%s' "$MYSQL_TEST_PASSWORD" | od -An -tx1 | tr -d '[:space:]')

MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql --protocol=socket --user=root <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`$MYSQL_TEST_DATABASE\`;
    CREATE USER IF NOT EXISTS '$MYSQL_TEST_USER'@'%' IDENTIFIED BY 0x$password_hex;
    GRANT ALL PRIVILEGES ON \`$MYSQL_TEST_DATABASE\`.* TO '$MYSQL_TEST_USER'@'%';
    FLUSH PRIVILEGES;
EOSQL
