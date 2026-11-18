#!/usr/bin/env bash
set -euo pipefail

cd -- "$(git rev-parse --show-toplevel)"

if [[ "${GITHUB_ACTIONS:-}" != true ||
      "${MYSQL_INIT_MYSQL_HOST:-}" != 127.0.0.1 ||
      "${MYSQL_INIT_MYSQL_PORT:-}" != 3306 ]]; then
    printf '%s\n' 'DB_TARGET_UNSAFE: use the dedicated CI MySQL service on 127.0.0.1:3306' >&2
    exit 2
fi

files=(
    infra/local/mysql-init/01-create-testing-database.sh
    tests/infra/mysql-init-regression.sh
)
original_modes=()
changed=()

restore_modes() {
    local rc=$? cleanup_failed=0 i actual
    trap - EXIT
    trap '' INT TERM

    for i in "${changed[@]}"; do
        if ! chmod -- "${original_modes[i]}" "${files[i]}" ||
           ! actual="$(stat -c '%a' -- "${files[i]}")" ||
           [[ "$actual" != "${original_modes[i]}" ]]; then
            printf 'MYSQL_MODE_RESTORE_FAILED path=%s\n' "${files[i]}" >&2
            cleanup_failed=1
        else
            printf 'MYSQL_MODE_RESTORED path=%s mode=%s\n' "${files[i]}" "$actual"
        fi
    done

    if (( cleanup_failed )); then
        printf 'MYSQL_MODE_CLEANUP_FAILED original_exit=%s\n' "$rc" >&2
        exit 3
    fi
    exit "$rc"
}

trap restore_modes EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

# Inspect both files before changing either one.
for i in "${!files[@]}"; do
    file="${files[i]}"
    if [[ ! -f "$file" || -L "$file" ]]; then
        printf 'MYSQL_SCRIPT_INVALID path=%s\n' "$file" >&2
        exit 2
    fi

    entry="$(git ls-tree --format='%(objectmode)' HEAD -- "$file")"
    case "$entry" in
        100644|100755) ;;
        *)
            printf 'MYSQL_SCRIPT_NOT_TRACKED_REGULAR path=%s\n' "$file" >&2
            exit 2
            ;;
    esac

    original_modes[i]="$(stat -c '%a' -- "$file")"
    printf 'MYSQL_MODE_BEFORE path=%s committed=%s worktree=%s\n' \
        "$file" "$entry" "${original_modes[i]}"
done

for i in "${!files[@]}"; do
    file="${files[i]}"
    if [[ ! -x "$file" ]]; then
        changed+=("$i")
        chmod u+x -- "$file"
        if [[ ! -x "$file" ]]; then
            printf 'MYSQL_MODE_ENABLE_FAILED path=%s\n' "$file" >&2
            exit 2
        fi
        printf 'MYSQL_MODE_ENABLED path=%s mode=%s\n' \
            "$file" "$(stat -c '%a' -- "$file")"
    else
        printf 'MYSQL_MODE_UNCHANGED path=%s\n' "$file"
    fi
done

"${files[1]}"