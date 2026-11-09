#!/bin/bash
# Simple Moodle init - runs inside container via docker compose exec
set -e

MOODLE_ROOT=/var/www/html
PHP="php"
APACHE_USER="www-data"

echo "=== Moodle Init Script Starting ==="

# Create student user if not exists
echo "Creating student user..."
"$PHP" /scripts/create-users.php || true

# Enable web services (REST API) for content management
echo "Enabling web services..."
"$PHP" "$MOODLE_ROOT/admin/cli/cfg.php" --name=enablewebservices --set=1 || true

# Enable REST protocol
echo "Enabling REST protocol..."
"$PHP" "$MOODLE_ROOT/admin/cli/cfg.php" --name=webserviceprotocols --set=rest,soap,xmlrpc || true

# Auto-confirm email for admin (skip email verification)
"$PHP" "$MOODLE_ROOT/admin/cli/cfg.php" --name=emailchangeconfirmation --set=0 || true

echo "=== Moodle Init Script Complete ==="
