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
    gh repo create hulkamania/agentic-lms --public --description "Agentic LMS - Moodle Docker setup with learning path for Swiss LMS market analysis" --confirm
else
    echo "GitHub repository already exists."
fi

# Commit initial files if not already committed
if [ -z "$(git log --oneline -1 2>/dev/null)" ]; then
    echo "Committing initial files..."
    git add .
    git commit -m "Initial commit: Moodle Docker Compose setup with parameterized secrets"
    git branch -M main
    git push -u origin main || true
fi

# Pull images and start services
echo "Pulling images and starting services..."
docker compose pull
docker compose up -d

# Wait for Moodle to be healthy
echo "Waiting for Moodle to become healthy (this may take 2–3 minutes)..."
for i in {1..60}; do
    if docker compose ps moodle | grep -q healthy; then
        echo "Moodle is healthy!"
        break
    fi
    echo -n "."
    sleep 5
done

if ! docker compose ps moodle | grep -q healthy; then
    echo "ERROR: Moodle did not become healthy in time."
    docker compose logs moodle --tail 50
    exit 1
fi

# Create content
echo "Creating learning path content..."
docker compose exec -T moodle bash -c '
    export MOODLE_STUDENT_USER="'"$MOODLE_STUDENT_USER"'"
    export MOODLE_STUDENT_PASSWORD="'"$MOODLE_STUDENT_PASSWORD"'"
    export MOODLE_ADMIN_USER="'"$MOODLE_ADMIN_USER"'"
    export MOODLE_ADMIN_PASSWORD="'"$MOODLE_ADMIN_PASSWORD"'"
    sudo -u daemon php /docker-entrypoint-init.d/create-content.php
'

# Save credentials locally
if [ -f ~/.moodle.creds ]; then
    echo "Credentials already saved at ~/.moodle.creds"
else
    echo "WARNING: Credentials file not found. The content script should have created it."
fi

# Commit content creation files
echo "Committing content creation scripts..."
git add scripts/
git diff --cached --quiet || git commit -m "Add Moodle content creation scripts and learning path"
git push origin main || true

echo "========================================"
echo "  Setup Complete!"
echo "========================================"
echo "Moodle URL: http://localhost:${MOODLE_PORT:-8080}"
echo "Admin:    ${MOODLE_ADMIN_USER:-admin} / ${MOODLE_ADMIN_PASSWORD:-***}"
echo "Student:  ${MOODLE_STUDENT_USER:-student} / ${MOODLE_STUDENT_PASSWORD:-***}"
echo "Credentials: ~/.moodle.creds"
echo "Course:   LMS Product Management & Market Intelligence (shortname: LMSPM2024)"
