#!/bin/bash
# Moodle init script - runs inside container during first boot
# Creates users, enables services, and sets up the environment

set -e

MOODLE_ROOT=/bitnami/moodle
MOODLEDATA=/bitnami/moodledata
PHP="php"

echo "=== Moodle Init Script Starting ==="

# Ensure proper permissions for the daemon user
chown -R daemon:daemon "$MOODLEDATA"

# Create student user if not exists
echo "Creating student user..."
sudo -u daemon "$PHP" "$MOODLE_ROOT/admin/cli/user_edit.php" \
  --username="${MOODLE_STUDENT_USER}" \
  --password="${MOODLE_STUDENT_PASSWORD}" \
  --firstname=Student \
  --lastname=Learner \
  --email="${MOODLE_STUDENT_EMAIL}" \
  --maildisplay=0 \
  --auth=manual \
  --idnumber=student001 \
  --country=CH \
  --lang=en \
  --create-if-not-exists 2>/dev/null || true

# If user_edit.php doesn't exist, we fall back to a custom PHP script
if [ ! -f "$MOODLE_ROOT/admin/cli/user_edit.php" ]; then
    echo "Using custom user creation script..."
    sudo -u daemon "$PHP" /docker-entrypoint-init.d/create-users.php
fi

# Enable web services (REST API) for content management
echo "Enabling web services..."
sudo -u daemon "$PHP" "$MOODLE_ROOT/admin/cli/cfg.php" --name=enablewebservices --set=1

# Enable REST protocol
sudo -u daemon "$PHP" "$MOODLE_ROOT/admin/cli/cfg.php" --name=webserviceprotocols --set=rest,soap,xmlrpc

# Auto-confirm email for admin (skip email verification)
sudo -u daemon "$PHP" "$MOODLE_ROOT/admin/cli/cfg.php" --name=emailchangeconfirmation --set=0

echo "=== Moodle Init Script Complete ==="
