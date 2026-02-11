#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 ]]; then
    echo "Usage: bin/worktree-delete.sh <branch-name>"
    exit 1
fi

BRANCH_NAME="$1"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
MAIN_REPO="$(dirname "$SCRIPT_DIR")"
DB_SAFE_NAME=$(echo "$BRANCH_NAME" | sed 's/[\/\-]/_/g')
WORKTREE_DIR="$(dirname "$MAIN_REPO")/fitapp-$DB_SAFE_NAME"
DB_NAME="fitapp_$DB_SAFE_NAME"
SITE_NAME="fitapp-$DB_SAFE_NAME"
HERD_SITES_DIR="$HOME/Library/Application Support/Herd/config/valet/Sites"
PSQL="$HOME/Library/Application Support/Herd/bin/psql"

echo "==> Removing Herd site symlink"
rm -f "$HERD_SITES_DIR/$SITE_NAME"

echo "==> Dropping PostgreSQL database: $DB_NAME"
"$PSQL" -h 127.0.0.1 -p 5432 -U root -d postgres -c "DROP DATABASE IF EXISTS $DB_NAME"

echo "==> Removing git worktree at $WORKTREE_DIR"
git -C "$MAIN_REPO" worktree remove --force "$WORKTREE_DIR"

read -rp "==> Delete local branch '$BRANCH_NAME'? [y/N] " answer
if [[ "$answer" =~ ^[Yy]$ ]]; then
    git -C "$MAIN_REPO" branch -D "$BRANCH_NAME"
    echo "    Branch deleted."
else
    echo "    Branch kept."
fi

echo ""
echo "========================================"
echo "  Worktree cleaned up!"
echo "  Removed: $WORKTREE_DIR"
echo "  Dropped: $DB_NAME"
echo "========================================"
