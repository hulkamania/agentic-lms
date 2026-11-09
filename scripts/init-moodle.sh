#!/bin/bash
# Simple Moodle init - runs inside container via docker compose exec
set -e

MOODLE_ROOT=/bitnami/moodle
PHP="php"

echo "=== Moodle Init Script Starting ==="

# Create student user if not exists
echo "Creating student user..."
sudo -u daemon "$PHP" /scripts/create-users.php || true

# Enable web services (REST API) for content management
echo "Enabling web services..."
sudo -u daemon "$PHP" "$MOODLE_ROOT/admin/cli/cfg.php" --name=enablewebservices --set=1 || true

# Enable REST protocol
echo "Enabling REST protocol..."
sudo -u daemon "$PHP" "$MOODLE_ROOT/admin/cli/cfg.php" --name=webserviceprotocols --set=rest,soap,xmlrpc || true

# Auto-confirm email for admin (skip email verification)
sudo -u daemon "$PHP" "$MOODLE_ROOT/admin/cli/cfg.php" --name=emailchangeconfirmation --set=0 || true

echo "=== Moodle Init Script Complete ==="
