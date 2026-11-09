#!/bin/bash
set -e

MOODLE_DIR="/var/www/html"
DATA_DIR="/var/www/moodledata"
APACHE_USER="www-data"

# Ensure data directory exists and is owned by www-data
mkdir -p "$DATA_DIR"
chown -R "$APACHE_USER:$APACHE_USER" "$DATA_DIR"
chown -R "$APACHE_USER:$APACHE_USER" "$MOODLE_DIR"

# Read env vars with defaults
DB_HOST="${MOODLE_DATABASE_HOST:-mariadb}"
DB_PORT="${MOODLE_DATABASE_PORT:-3306}"
DB_NAME="${MOODLE_DB_NAME:-moodle}"
DB_USER="${MOODLE_DB_USER:-moodle}"
DB_PASS="${MOODLE_DB_PASSWORD:-moodlepass}"
ADMIN_USER="${MOODLE_ADMIN_USER:-admin}"
ADMIN_PASS="${MOODLE_ADMIN_PASSWORD:-AdminPass123!}"
ADMIN_EMAIL="${MOODLE_ADMIN_EMAIL:-admin@example.com}"
SITE_NAME="${MOODLE_SITE_NAME:-Moodle Site}"
MOODLE_HOST="${MOODLE_HOST:-localhost}"
MOODLE_PORT="${MOODLE_PORT:-8080}"

WEB_URL="http://${MOODLE_HOST}:${MOODLE_PORT}"

# Wait for database
wait_for_db() {
    echo "Waiting for database at $DB_HOST:$DB_PORT..."
    for i in {1..60}; do
        if php -r "new mysqli('$DB_HOST', '$DB_USER', '$DB_PASS', '$DB_NAME', $DB_PORT);" 2>/dev/null; then
            echo "Database is ready!"
            return 0
        fi
        echo -n "."
        sleep 2
    done
    echo "ERROR: Database not available"
    return 1
}

# Check if Moodle is already installed
is_installed() {
    [ -f "$MOODLE_DIR/config.php" ] && grep -q "CFG->dbpass" "$MOODLE_DIR/config.php"
}

if ! is_installed; then
    wait_for_db

    echo "Installing Moodle for the first time..."

    # Generate config.php
    cp "$MOODLE_DIR/config-dist.php" "$MOODLE_DIR/config.php"

    cat > "$MOODLE_DIR/config.php" <<EOF
<?php  // Moodle configuration file
unset(\$CFG);
global \$CFG;
\$CFG = new stdClass();
\$CFG->dbtype    = 'mariadb';
\$CFG->dblibrary = 'native';
\$CFG->dbhost    = '$DB_HOST';
\$CFG->dbname    = '$DB_NAME';
\$CFG->dbuser    = '$DB_USER';
\$CFG->dbpass    = '$DB_PASS';
\$CFG->prefix    = 'mdl_';
\$CFG->dboptions = array(
    'dbpersist' => false,
    'dbport'    => $DB_PORT,
    'dbsocket'  => '',
    'dbssl'     => 'prefer',
    'dbsslca'   => null,
    'dbsslcert'  => null,
    'dbsslkey'   => null,
    'dbsslverifyservercert' => true,
    'dbsslcaoption' => null,
    'dbsslcapath' => null,
    'dbsslcipher' => null,
);
\$CFG->wwwroot   = '$WEB_URL';
\$CFG->dataroot  = '$DATA_DIR';
\$CFG->admin     = 'admin';
\$CFG->directorypermissions = 02777;
require_once(\$CFG->dirroot . '/lib/setup.php');
EOF

    chown "$APACHE_USER:$APACHE_USER" "$MOODLE_DIR/config.php"

    # Run CLI install
    sudo -u "$APACHE_USER" php "$MOODLE_DIR/admin/cli/install.php" \
        --lang=en \
        --chmod=2777 \
        --wwwroot="$WEB_URL" \
        --dataroot="$DATA_DIR" \
        --dbtype=mariadb \
        --dbhost="$DB_HOST" \
        --dbname="$DB_NAME" \
        --dbuser="$DB_USER" \
        --dbpass="$DB_PASS" \
        --dbport="$DB_PORT" \
        --fullname="$SITE_NAME" \
        --shortname="agentic-lms" \
        --summary="Agentic LMS Docker installation" \
        --adminuser="$ADMIN_USER" \
        --adminpass="$ADMIN_PASS" \
        --adminemail="$ADMIN_EMAIL" \
        --non-interactive \
        --agree-license \
        || true

    # If install failed, check if it's because tables already exist
    if [ ! -f "$DATA_DIR/.moodle-installed" ]; then
        touch "$DATA_DIR/.moodle-installed"
    fi
else
    echo "Moodle already installed. Starting..."
fi

# Run any custom init scripts
if [ -d /scripts ]; then
    for f in /scripts/*.sh; do
        [ -e "$f" ] || continue
        echo "Running init script: $f"
        bash "$f" || true
    done
    for f in /scripts/*.php; do
        [ -e "$f" ] || continue
        echo "Running init PHP: $f"
        sudo -u "$APACHE_USER" php "$f" || true
    done
fi

exec "$@"
