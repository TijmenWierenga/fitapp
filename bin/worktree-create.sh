#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 ]]; then
    echo "Usage: bin/worktree-create.sh <branch-name>"
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

echo "==> Creating git worktree at $WORKTREE_DIR (branch: $BRANCH_NAME)"
git -C "$MAIN_REPO" worktree add -b "$BRANCH_NAME" "$WORKTREE_DIR"

echo "==> Creating PostgreSQL database: $DB_NAME"
"$PSQL" -h 127.0.0.1 -p 5432 -U root -d postgres -c "CREATE DATABASE $DB_NAME"

echo "==> Copying .env and updating DB_DATABASE + APP_URL"
cp "$MAIN_REPO/.env" "$WORKTREE_DIR/.env"
sed -i '' "s|^DB_DATABASE=.*|DB_DATABASE=$DB_NAME|" "$WORKTREE_DIR/.env"
sed -i '' "s|^APP_URL=.*|APP_URL=http://$SITE_NAME.test|" "$WORKTREE_DIR/.env"

echo "==> Registering Herd site via symlink"
ln -s "$WORKTREE_DIR" "$HERD_SITES_DIR/$SITE_NAME"

echo "==> Installing Composer dependencies"
composer install --working-dir="$WORKTREE_DIR" --quiet

echo "==> Generating application key"
php "$WORKTREE_DIR/artisan" key:generate --no-interaction

echo "==> Running migrations"
php "$WORKTREE_DIR/artisan" migrate --no-interaction

echo "==> Installing NPM dependencies and building assets"
(cd "$WORKTREE_DIR" && npm install --silent && npm run build)

echo ""
echo "========================================"
echo "  Worktree ready!"
echo "  Path:     $WORKTREE_DIR"
echo "  URL:      http://$SITE_NAME.test"
echo "  Database: $DB_NAME"
echo "========================================"
