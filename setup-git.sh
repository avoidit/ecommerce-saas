#!/bin/bash
set -e

echo "🚀 E-commerce SaaS - GitHub Setup"
echo "=================================="

# Create .gitignore
cat > .gitignore << 'EOF'
.env
.env.backup
.env.production
.idea/
.vscode/
*.sublime-project
*.sublime-workspace
.DS_Store
Thumbs.db
/node_modules
/public/hot
/public/storage
/storage/*.key
/vendor
npm-debug.log
yarn-error.log
/public/build
/public/css
/public/js
*.log
/storage/logs/*.log
/bootstrap/cache/*.php
/storage/framework/cache/*
/storage/framework/sessions/*
/storage/framework/views/*
.phpunit.result.cache
.phpunit.cache
/coverage
composer.phar
composer.lock
package-lock.json
yarn.lock
docker-compose.override.yml
*.sqlite
*.bak
*.backup
*.swp
*~
*.pem
*.key
*.crt
/public/uploads/*
!/public/uploads/.gitkeep
/storage/app/*
!/storage/app/.gitkeep
/storage/framework/cache/*
!/storage/framework/cache/.gitkeep
/storage/framework/sessions/*
!/storage/framework/sessions/.gitkeep
/storage/framework/views/*
!/storage/framework/views/.gitkeep
/storage/logs/*
!/storage/logs/.gitkeep
!.gitkeep
!.htaccess
EOF

echo "✓ .gitignore created"

# Initialize Git
if [ ! -d ".git" ]; then
    git init
    echo "✓ Git initialized"
else
    echo "✓ Git already initialized"
fi

# Create .env.example
if [ ! -f ".env.example" ] && [ -f ".env" ]; then
    cp .env .env.example
    sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=your_secure_password/' .env.example
    sed -i 's/APP_KEY=.*/APP_KEY=/' .env.example
    echo "✓ .env.example created"
fi

# Add files
git add .
echo "✓ Files staged"

# Commit
git commit -m "Initial commit: E-commerce SaaS platform

- Laravel 11 with Jetstream
- PostgreSQL 16 database  
- Redis cache and queue
- Docker containerization
- Vue.js 3 frontend
- Development tools" 2>/dev/null || echo "✓ Already committed"

echo ""
echo "=================================="
echo "✅ Git Setup Complete!"
echo "=================================="
echo ""
echo "NEXT STEPS:"
echo ""
echo "1. CREATE GITHUB REPO:"
echo "   Visit: https://github.com/new"
echo "   Name: ecommerce-saas"
echo "   Private: Yes"
echo "   Don't initialize with anything"
echo ""
echo "2. CONNECT AND PUSH:"
echo "   git branch -M main"
echo "   git remote add origin https://github.com/YOUR_USERNAME/ecommerce-saas.git"
echo "   git push -u origin main"
echo ""
echo "Replace YOUR_USERNAME with your GitHub username!"
echo ""

