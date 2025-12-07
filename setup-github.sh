#!/bin/bash

# E-commerce SaaS - GitHub Setup Script
# This script will initialize Git and prepare the project for GitHub

set -e  # Exit on any error

echo "🚀 E-commerce SaaS - GitHub Setup"
echo "=================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: This doesn't appear to be a Laravel project directory.${NC}"
    echo "Please run this script from your ecommerce-saas directory."
    exit 1
fi

echo -e "${GREEN}✓${NC} Found Laravel project"

# Step 1: Initialize Git (if not already initialized)
if [ ! -d ".git" ]; then
    echo ""
    echo "📦 Initializing Git repository..."
    git init
    echo -e "${GREEN}✓${NC} Git repository initialized"
else
    echo ""
    echo -e "${YELLOW}!${NC} Git repository already initialized"
fi

# Step 2: Copy .gitignore if it doesn't exist
if [ ! -f ".gitignore" ]; then
    echo ""
    echo "📝 Creating .gitignore file..."
    # The .gitignore content will be added here
    cat > .gitignore << 'EOF'
# Laravel .gitignore

# Environment files
.env
.env.backup
.env.production
.env.*.php
.env.php

# IDE and Editor files
.idea/
.vscode/
*.sublime-project
*.sublime-workspace
.DS_Store
Thumbs.db

# Laravel specific
/node_modules
/public/hot
/public/storage
/storage/*.key
/vendor
npm-debug.log
yarn-error.log

# Build files
/public/build
/public/css
/public/js
/public/mix-manifest.json
mix-manifest.json

# Laravel logs
*.log
/storage/logs/*.log

# Laravel cache
/bootstrap/cache/*.php
/storage/framework/cache/*
/storage/framework/sessions/*
/storage/framework/views/*

# Testing
.phpunit.result.cache
.phpunit.cache
/coverage
/_ide_helper.php
/_ide_helper_models.php
.phpstorm.meta.php

# Composer
composer.phar
composer.lock
/vendor/

# Node
node_modules/
npm-debug.log
yarn-error.log
package-lock.json
yarn.lock

# Docker (keep docker-compose.yml but ignore runtime data)
docker-compose.override.yml

# Database
*.sqlite
*.sqlite-journal

# Backup files
*.bak
*.backup
*.swp
*.swo
*~

# OS files
.DS_Store
.DS_Store?
._*
.Spotlight-V100
.Trashes
ehthumbs.db
Thumbs.db

# Security
/config/secrets.php
*.pem
*.key
*.crt

# User uploaded files
/public/uploads/*
!/public/uploads/.gitkeep

# Storage directories
/storage/app/*
!/storage/app/.gitkeep
!/storage/app/public/
/storage/app/public/*
!/storage/app/public/.gitkeep
/storage/framework/cache/*
!/storage/framework/cache/.gitkeep
/storage/framework/sessions/*
!/storage/framework/sessions/.gitkeep
/storage/framework/testing/*
!/storage/framework/testing/.gitkeep
/storage/framework/views/*
!/storage/framework/views/.gitkeep
/storage/logs/*
!/storage/logs/.gitkeep

# Keep important placeholder files
!.gitkeep
!.htaccess
EOF
    echo -e "${GREEN}✓${NC} .gitignore created"
else
    echo ""
    echo -e "${YELLOW}!${NC} .gitignore already exists"
fi

# Step 3: Create .env.example if it doesn't exist
if [ ! -f ".env.example" ]; then
    if [ -f ".env" ]; then
        echo ""
        echo "📝 Creating .env.example from .env (removing sensitive data)..."
        cp .env .env.example
        
        # Replace sensitive values with placeholders
        sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=your_secure_password/' .env.example
        sed -i 's/REDIS_PASSWORD=.*/REDIS_PASSWORD=your_redis_password/' .env.example
        sed -i 's/APP_KEY=.*/APP_KEY=/' .env.example
        
        echo -e "${GREEN}✓${NC} .env.example created"
    else
        echo -e "${YELLOW}!${NC} No .env file found, skipping .env.example creation"
    fi
else
    echo ""
    echo -e "${YELLOW}!${NC} .env.example already exists"
fi

# Step 4: Add all files to Git
echo ""
echo "📦 Adding files to Git..."
git add .
echo -e "${GREEN}✓${NC} Files added to staging area"

# Step 5: Create initial commit
echo ""
echo "💾 Creating initial commit..."
if git diff-index --quiet HEAD -- 2>/dev/null; then
    echo -e "${YELLOW}!${NC} No changes to commit"
else
    git commit -m "Initial commit: E-commerce SaaS platform setup

- Laravel 11 with Jetstream and Inertia.js
- PostgreSQL 16 database
- Redis cache and queue system
- Docker containerization
- Vue.js 3 frontend with Tailwind CSS
- Development tools: Horizon, Telescope
- Multi-tenant architecture ready"
    echo -e "${GREEN}✓${NC} Initial commit created"
fi

# Step 6: Display next steps
echo ""
echo "=================================="
echo -e "${GREEN}✅ Git repository is ready!${NC}"
echo "=================================="
echo ""
echo "Next steps:"
echo ""
echo "1. Create a new repository on GitHub:"
echo "   Visit: https://github.com/new"
echo "   Repository name: ecommerce-saas"
echo "   Description: E-commerce Management SaaS Platform"
echo "   Keep it Private (recommended for now)"
echo "   Do NOT initialize with README, .gitignore, or license"
echo ""
echo "2. After creating the GitHub repository, run these commands:"
echo ""
echo "   # Set your default branch to main"
echo "   git branch -M main"
echo ""
echo "   # Add your GitHub repository as remote"
echo "   git remote add origin https://github.com/YOUR_USERNAME/ecommerce-saas.git"
echo ""
echo "   # Push your code to GitHub"
echo "   git push -u origin main"
echo ""
echo "3. Alternative: Use SSH (if you have SSH keys set up):"
echo "   git remote add origin git@github.com:YOUR_USERNAME/ecommerce-saas.git"
echo "   git push -u origin main"
echo ""
echo -e "${YELLOW}Important:${NC}"
echo "- Make sure to replace YOUR_USERNAME with your actual GitHub username"
echo "- Your .env file is NOT included in the repository (it's in .gitignore)"
echo "- Database credentials and secrets are safe"
echo ""
echo -e "${GREEN}Happy coding! 🎉${NC}"
echo ""