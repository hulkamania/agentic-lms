#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Load environment variables
if [ -f .env ]; then
    set -a
    source .env
    set +a
fi

echo "========================================"
echo "  Agentic LMS Moodle Setup"
echo "========================================"

# Create GitHub repo if it doesn't exist
if ! gh repo view hulkamania/agentic-lms >/dev/null 2>&1; then
    echo "Creating GitHub repository hulkamania/agentic-lms..."
    gh repo create hulkamania/agentic-lms --public --description "Agentic LMS - Moodle Docker setup with learning path for Swiss LMS market analysis"
else
    echo "GitHub repository already exists."
fi

# Commit any new changes
if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
    echo "Committing local changes..."
    git add -A
    git commit -m "Update configuration and scripts for custom Moodle build"
    git push origin main || true
fi

# Build and start services
echo "Building Moodle image and starting services..."
docker compose build --no-cache
docker compose up -d

# Wait for Moodle to be healthy
echo "Waiting for Moodle to become healthy (this may take 3–5 minutes)..."
for i in {1..90}; do
    if docker compose ps moodle | grep -q healthy; then
        echo ""
        echo "Moodle is healthy!"
        break
    fi
    echo -n "."
    sleep 5
done

if ! docker compose ps moodle | grep -q healthy; then
    echo ""
    echo "ERROR: Moodle did not become healthy in time."
    docker compose logs moodle --tail 100
    exit 1
fi

# Run init script (create student, enable services)
echo "Running Moodle init scripts..."
docker compose exec -T moodle bash /scripts/init-moodle.sh || true

# Create content
echo "Creating learning path content..."
docker compose exec -T moodle bash -c '
    export MOODLE_STUDENT_USER="'"$MOODLE_STUDENT_USER"'"
    export MOODLE_STUDENT_PASSWORD="'"$MOODLE_STUDENT_PASSWORD"'"
    export MOODLE_ADMIN_USER="'"$MOODLE_ADMIN_USER"'"
    export MOODLE_ADMIN_PASSWORD="'"$MOODLE_ADMIN_PASSWORD"'"
    sudo -u www-data php /scripts/create-content.php
' || true

# Save credentials locally
echo "Saving credentials to ~/.moodle.creds..."
cat > ~/.moodle.creds <<CREDS
# Moodle Credentials for Agentic LMS
# Generated: $(date)
ADMIN_USER=${MOODLE_ADMIN_USER:-admin}
ADMIN_PASS=${MOODLE_ADMIN_PASSWORD:-admin}
STUDENT_USER=${MOODLE_STUDENT_USER:-student}
STUDENT_PASS=${MOODLE_STUDENT_PASSWORD:-student}
MOODLE_URL=http://localhost:${MOODLE_PORT:-8080}
COURSE_SHORTNAME=LMSPM2024
CREDS
chmod 600 ~/.moodle.creds

# Commit content creation files
echo "Committing content creation artifacts..."
git add -A
git diff --cached --quiet || git commit -m "Add Moodle content creation scripts and learning path materials"
git push origin main || true

echo "========================================"
echo "  Setup Complete!"
echo "========================================"
echo "Moodle URL: http://localhost:${MOODLE_PORT:-8080}"
echo "Admin:    ${MOODLE_ADMIN_USER:-admin} / [hidden]"
echo "Student:  ${MOODLE_STUDENT_USER:-student} / [hidden]"
echo "Credentials: ~/.moodle.creds"
echo "Course:   LMS Product Management & Market Intelligence"
