# Agentic LMS

A Docker Compose-based Moodle LMS installation with a comprehensive learning path for Product Managers evaluating a new Swiss LMS SaaS product using agentic autonomous software factories.

## Quick Start

```bash
# 1. Clone and enter the directory
cd agentic-lms

# 2. Review and customize secrets
cp .env.example .env
# Edit .env with your preferred passwords

# 3. Start everything
./setup.sh
```

## What's Inside

- **Moodle 4.5** (Bitnami image) with MariaDB 11.4
- **Parameterized secrets** via `.env` (never commit `.env`)
- **Admin + Student accounts** pre-configured
- **Learning Path course** (`LMSPM2024`) covering:
  - LMS fundamentals and value chain
  - Technical architecture for LMS SaaS
  - Swiss LMS market analysis
  - Top players in the Swiss LMS market
  - Product strategy and cost modeling with agentic software factories
  - Decision framework for go/no-go

## Credentials

After setup, credentials are saved to `~/.moodle.creds`:

| Role     | Username | Password |
|----------|----------|----------|
| Admin    | admin    | (from `.env`) |
| Student  | student  | (from `.env`) |

Access Moodle at: http://localhost:8080

## Structure

```
.
├── docker-compose.yml      # Moodle + MariaDB services
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

## License

This setup is provided as-is for demonstration and research purposes.
