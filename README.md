# Agentic LMS

A Docker Compose-based Moodle LMS installation with a comprehensive learning path for Product Managers evaluating a new Swiss LMS SaaS product using agentic autonomous software factories.

## Quick Start

```bash
# 1. Clone and enter the directory
git clone https://github.com/hulkamania/agentic-lms.git
cd agentic-lms

# 2. Configure secrets
cp .env.example .env
# Edit .env with your preferred passwords (no dollar signs for Docker Compose compat)

# 3. Start everything
./setup.sh
```

## What's Inside

- **Moodle 4.4** (custom Dockerfile from `php:8.2-apache`) with MariaDB
- **Parameterized secrets** via `.env` (never commit `.env`)
- **Admin + Student accounts** pre-configured with editingteacher role
- **Learning Path course** (`LMSPM2024`) covering:
  1. **Module 1:** LMS fundamentals and value chain
  2. **Module 2:** Technical architecture for LMS SaaS (microservices, xAPI/LTI, security)
  3. **Module 3:** Swiss LMS market analysis (size, drivers, procurement)
  4. **Module 4:** Top players in the Swiss LMS market (Moodle, Canvas, SAP, OLAT, etc.)
  5. **Module 5:** Product strategy and cost modeling with agentic software factories
  6. **Module 6:** Decision framework and next steps for go/no-go

## Credentials

After setup, credentials are saved to `~/.moodle.creds`:

| Role     | Username | Password (from `.env`) |
|----------|----------|------------------------|
| Admin    | admin    | `MOODLE_ADMIN_PASSWORD` |
| Student  | student  | `MOODLE_STUDENT_PASSWORD` |

Access Moodle at: http://localhost:8080

## Structure

```
.
├── docker-compose.yml      # Moodle + MariaDB services
├── Dockerfile              # Custom Moodle image (PHP 8.2 + Apache)
├── entrypoint.sh           # Container initialization (db wait, install, custom scripts)
├── .env.example            # Secret template
├── .env                    # Your local secrets (gitignored)
├── scripts/
│   ├── init-moodle.sh      # Enables services, creates student
│   ├── create-users.php    # Bootstrap user creation via Moodle API
│   └── create-content.php  # Full learning path generation
└── setup.sh                # One-command setup, commit, and deploy
```

## GitHub

Repository: https://github.com/hulkamania/agentic-lms

## Notes

- First startup takes 3–5 minutes for database initialization.
- The learning path includes detailed analysis a Product Manager needs for LMS SaaS go/no-go decisions in the Swiss market.

## License

Provided as-is for demonstration and research purposes.
