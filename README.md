# SkillSwap Platform

A modern web application where users exchange skills instead of money. Learn anything, teach anything — no money needed!

## Features

| Feature | Status |
| :--- | :---: |
| Authentication (Firebase) | ✅ |
| Profile & Skill Management | ✅ |
| Skill Matching Algorithm | ✅ |
| Real-time Chat | ✅ |
| Notifications & Reviews | ✅ |
| Video Meeting Integration | ✅ |

## Screenshots

| Home | Dashboard | Chat |
|------|-----------|------|
| ![](docs/screenshots/home.png) | ![](docs/screenshots/dashboard.png) | ![](docs/screenshots/chat.png) |

| Matches | Reviews | Profile |
|---------|----------|---------|
| ![](docs/screenshots/matches.png) | ![](docs/screenshots/reviews.png) | ![](docs/screenshots/profile.png) |

| Settings | Login | Register |
|----------|-------|----------|
| ![](docs/screenshots/settings.png) | ![](docs/screenshots/login.png) | ![](docs/screenshots/register.png) |

## Tech Stack

- HTML
- CSS
- Bootstrap
- JavaScript
- PHP
- MySQL
- Firebase Authentication

## Installation

```bash
git clone <repo>
cd skillswap

brew install php
brew install mysql

brew services start mysql

mysql -u root skillswap < config/schema.sql

php -S localhost:8000
```
