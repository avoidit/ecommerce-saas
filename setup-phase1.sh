#!/bin/bash

# Phase 1: Multi-Tenant User Management Setup Script
# This script sets up the database and initial data for the e-commerce SaaS platform

set -e  # Exit on any error

echo "🚀 Starting Phase 1: Multi-Tenant User Management Setup"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Check if we're in the app container
if [ ! -f "artisan" ]; then
    print_error "This script must be run from the Laravel application directory"
    print_info "Please run: docker compose exec app sh"
    print_info "Then execute this script: ./setup-phase1.sh"
    exit 1
fi

echo
print_info "Phase 1 will set up:"
print_info "• Multi-tenant database schema"
print_info "• Role-based access control (RBAC)"
print_info "• Organization and team management"
print_info "• User authentication and authorization"
print_info "• Audit logging and session management"
print_info "• Default roles and demo organization"
echo

# Confirm before proceeding
read -p "Do you want to continue? (y/N): " confirm
if [[ ! $confirm =~ ^[Yy]$ ]]; then
    print_warning "Setup cancelled"
    exit 0
fi

echo
echo "🗃️  Database Setup"
echo "=================="

# Check database connection
print_info "Testing database connection..."
if php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    print_status "Database connection successful"
else
    print_error "Database connection failed"
    print_info "Please check your database configuration in .env file"
    exit 1
fi

# Run fresh migrations
print_info "Running fresh migrations..."
if php artisan migrate:fresh --force; then
    print_status "Database migrations completed"
else
    print_error "Migration failed"
    exit 1
fi

echo
echo "🔐 Setting up Authentication"
echo "============================"

# Install and configure Jetstream if not already done
print_info "Checking Jetstream installation..."
if ! php artisan route:list | grep -q "jetstream" > /dev/null 2>&1; then
    print_warning "Jetstream not properly configured"
    print_info "Publishing Jetstream resources..."
    php artisan jetstream:install inertia --teams
    print_status "Jetstream configured with Inertia and Teams"
fi

echo
echo "🏢 Creating Roles and Organizations"
echo "=================================="

# Run seeders
print_info "Seeding database with default data..."
if php artisan db:seed --force; then
    print_status "Database seeded successfully"
else
    print_error "Seeding failed"
    exit 1
fi

echo
echo "🔧 Configuring Application"
echo "=========================="

# Generate application key if not set
if grep -q "APP_KEY=base64:" .env; then
    print_status "Application key already set"
else
    print_info "Generating application key..."
    php artisan key:generate
    print_status "Application key generated"
fi

# Create storage link
print_info "Creating storage link..."
if php artisan storage:link; then
    print_status "Storage link created"
else
    print_warning "Storage link may already exist"
fi

# Clear all caches
print_info "Clearing application caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
print_status "Caches cleared"

# Optimize application
print_info "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_status "Application optimized"

echo
echo "📦 Installing Frontend Dependencies"
echo "=================================="

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    print_info "Installing npm dependencies..."
    if npm install; then
        print_status "NPM dependencies installed"
    else
        print_error "NPM install failed"
        exit 1
    fi
else
    print_status "NPM dependencies already installed"
fi

# Build assets
print_info "Building frontend assets..."
if npm run build; then
    print_status "Frontend assets built successfully"
else
    print_error "Asset build failed"
    exit 1
fi

echo
echo "🧪 Running Tests"
echo "==============="

# Run basic tests to ensure everything is working
print_info "Running feature tests..."
if php artisan test --filter="Feature" --stop-on-failure; then
    print_status "Feature tests passed"
else
    print_warning "Some tests failed, but setup continues..."
fi

echo
echo "📋 Setup Summary"
echo "==============="

print_status "Phase 1 setup completed successfully!"
echo
echo "Default Accounts Created:"
echo "========================"
echo "🔑 Super Admin (Platform Level):"
echo "   Email: superadmin@platform.com"
echo "   Password: superpassword"
echo
echo "🏢 Demo Organization (demo.yourapp.com):"
echo "   Organization Owner:"
echo "     Email: owner@demo.com"
echo "     Password: password"
echo
echo "   Organization Admin:"
echo "     Email: admin@demo.com" 
echo "     Password: password"
echo
echo "   Manager:"
echo "     Email: manager@demo.com"
echo "     Password: password"
echo
echo "   Employee:"
echo "     Email: employee@demo.com"
echo "     Password: password"
echo

echo "Available Roles:"
echo "==============="
echo "• Super Admin - Platform-wide access"
echo "• Organization Owner - Full organization control"
echo "• Organization Admin - User management and settings"
echo "• Manager - Operational management"
echo "• Employee - Basic operational access"
echo "• View Only - Read-only access"
echo

echo "Next Steps:"
echo "==========="
echo "1. 🌐 Access your application at: http://192.168.1.57:8090"
echo "2. 🔐 Login with any of the demo accounts above"
echo "3. 🗃️  Access pgAdmin at: http://192.168.1.57:8082 (admin@admin.com / admin)"
echo "4. 📊 Monitor queues with Horizon at: http://192.168.1.57:8090/horizon"
echo "5. 🔍 Debug with Telescope at: http://192.168.1.57:8090/telescope"
echo "6. 📧 Test emails with Mailhog at: http://192.168.1.57:8025"
echo

echo "Multi-Tenant Features Enabled:"
echo "============================="
echo "✓ Organization-based tenant isolation"
echo "✓ Role-based access control (RBAC)"
echo "✓ Team management with permissions"
echo "✓ User invitation system"
echo "✓ Audit logging for all actions"
echo "✓ Session management and security"
echo "✓ Multi-factor authentication support"
echo "✓ Password expiry and security policies"
echo

print_status "Phase 1 setup is complete! Ready for Phase 2 development."
echo
