#!/bin/bash

# E-commerce SaaS Local Development Setup Script
# Simplified version for local development without conflicts

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    if ! command -v docker >/dev/null 2>&1; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    if ! command -v docker-compose >/dev/null 2>&1; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    if ! docker info >/dev/null 2>&1; then
        print_error "Docker daemon is not running. Please start Docker first."
        exit 1
    fi
    
    print_success "All prerequisites are met!"
}

# Check for port conflicts
check_port_conflicts() {
    print_status "Checking for port conflicts..."
    
    PORTS_TO_CHECK=(8090 8443 5433 6380 8082 8083 8025 9000 9001)
    CONFLICTS=()
    
    for port in "${PORTS_TO_CHECK[@]}"; do
        if ss -tuln | grep -q ":$port "; then
            CONFLICTS+=($port)
        fi
    done
    
    if [ ${#CONFLICTS[@]} -ne 0 ]; then
        print_warning "The following ports are already in use: ${CONFLICTS[*]}"
        print_warning "This may cause conflicts. Consider stopping services using these ports."
        echo ""
        read -p "Continue anyway? (y/N): " confirm
        if [[ $confirm != [yY] ]]; then
            print_error "Setup cancelled due to port conflicts"
            exit 1
        fi
    else
        print_success "No port conflicts detected!"
    fi
}

# Create project structure
create_project_structure() {
    print_status "Creating project directory structure..."
    
    mkdir -p {docker/{php,nginx,postgres,redis},storage/logs/nginx,bootstrap/cache}
    mkdir -p storage/{app,framework,logs}
    mkdir -p storage/framework/{cache,sessions,views}
    mkdir -p storage/app/{public,uploads}
    
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
    
    print_success "Project structure created!"
}

# Setup environment file
setup_environment() {
    print_status "Setting up environment file..."
    
    if [ ! -f .env ]; then
        if [ -f .env.example ]; then
            cp .env.example .env
            print_success "Environment file created from .env.example"
        else
            print_warning ".env.example file not found, creating a basic one..."
            cat > .env.example << 'EOF'
# E-commerce SaaS Application Environment Configuration

# Application Settings
APP_NAME="E-commerce SaaS"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8090
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

# Multi-tenant Configuration
MULTI_TENANT_ENABLED=true
TENANT_CACHE_TTL=3600
TENANT_SUBDOMAIN_ENABLED=true

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=ecommerce_saas
DB_USERNAME=ecommerce_user
DB_PASSWORD=secure_password_123
DB_CHARSET=utf8
DB_COLLATION=utf8_unicode_ci

# Test Database
DB_TEST_HOST=db
DB_TEST_PORT=5432
DB_TEST_DATABASE=ecommerce_saas_test
DB_TEST_USERNAME=ecommerce_user
DB_TEST_PASSWORD=secure_password_123

# Cache Configuration
CACHE_STORE=redis
CACHE_PREFIX=ecommerce_saas

# Redis Configuration
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
REDIS_QUEUE_DB=3

# Session Configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# Queue Configuration
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=database

# Broadcasting Configuration
BROADCAST_CONNECTION=redis

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@ecommerce-saas.local"
MAIL_FROM_NAME="${APP_NAME}"

# Logging Configuration
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# File Storage Configuration
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin123
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=ecommerce-saas
AWS_URL=http://localhost:9000
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

# Telescope Configuration
TELESCOPE_ENABLED=true
TELESCOPE_PATH=telescope
TELESCOPE_DRIVER=database

# Horizon Configuration
HORIZON_ENABLED=true
HORIZON_PATH=horizon
HORIZON_PREFIX=horizon:

# API Configuration
API_RATE_LIMIT=1000
API_RATE_LIMIT_WINDOW=60
API_PREFIX=api
API_VERSION=v1

# Development Tools
DEBUGBAR_ENABLED=true
CLOCKWORK_ENABLED=true

# Testing Configuration
TESTING_DATABASE=ecommerce_saas_test
TESTING_CACHE_STORE=array
TESTING_SESSION_DRIVER=array
TESTING_QUEUE_DRIVER=sync
EOF
            cp .env.example .env
            print_success "Basic environment file created and copied to .env"
        fi
    else
        print_warning "Environment file already exists, skipping..."
    fi
}

# Generate application key
generate_app_key() {
    print_status "Generating application key..."
    
    if [ -f .env ]; then
        if ! grep -q "APP_KEY=base64:" .env; then
            APP_KEY=$(openssl rand -base64 32)
            sed -i "s/APP_KEY=/APP_KEY=base64:$APP_KEY/" .env
            print_success "Application key generated!"
        else
            print_warning "Application key already exists"
        fi
    fi
}

# Build and start services
start_services() {
    print_status "Building and starting Docker services..."
    
    # Build images
    docker-compose build --no-cache
    
    # Start services
    docker-compose up -d
    
    # Wait for services to be ready
    print_status "Waiting for services to be ready..."
    sleep 30
    
    # Check if database is ready
    for i in {1..30}; do
        if docker-compose exec -T db pg_isready -h localhost -p 5432 -U ecommerce_user >/dev/null 2>&1; then
            print_success "Database is ready!"
            break
        fi
        if [ $i -eq 30 ]; then
            print_error "Database failed to start within expected time"
            exit 1
        fi
        sleep 2
    done
    
    print_success "Docker services started successfully!"
}

# Setup Laravel application
setup_laravel() {
    print_status "Setting up Laravel application..."
    
    # Check if Laravel is already installed
    if [ -f "artisan" ]; then
        print_warning "Laravel project already exists, skipping creation..."
    else
        print_status "Creating new Laravel project..."
        docker-compose exec app composer create-project laravel/laravel:^11.0 /tmp/laravel --no-interaction
        docker-compose exec app bash -c "cp -r /tmp/laravel/* /var/www/html/ && cp /tmp/laravel/.* /var/www/html/ 2>/dev/null || true"
        docker-compose exec app rm -rf /tmp/laravel
        print_success "Laravel project created!"
    fi
    
    # Install required packages
    print_status "Installing Laravel packages..."
    docker-compose exec app composer require \
        laravel/jetstream \
        laravel/sanctum \
        laravel/horizon \
        laravel/telescope \
        predis/predis \
        --no-interaction --quiet
    
    # Install development packages
    docker-compose exec app composer require --dev \
        laravel/pint \
        pestphp/pest \
        pestphp/pest-plugin-laravel \
        barryvdh/laravel-debugbar \
        --no-interaction --quiet
    
    print_success "Laravel packages installed!"
}

# Configure Laravel
configure_laravel() {
    print_status "Configuring Laravel application..."
    
    # Generate key if needed
    docker-compose exec app php artisan key:generate --force
    
    # Install Jetstream
    print_status "Installing Jetstream..."
    docker-compose exec app php artisan jetstream:install inertia --teams --dark --quiet
    
    # Install and build frontend
    print_status "Installing frontend dependencies..."
    docker-compose exec app npm install --silent
    docker-compose exec app npm run build
    
    # Install Telescope
    docker-compose exec app php artisan telescope:install --quiet
    
    # Install Horizon
    docker-compose exec app php artisan horizon:install --quiet
    
    print_success "Laravel configured!"
}

# Setup database
setup_database() {
    print_status "Setting up database..."
    
    # Run migrations
    docker-compose exec app php artisan migrate:fresh --seed --force
    
    # Create storage link
    docker-compose exec app php artisan storage:link
    
    print_success "Database setup completed!"
}

# Fix permissions
fix_permissions() {
    print_status "Setting up proper permissions..."
    
    docker-compose exec --user root app chown -R www-data:www-data storage bootstrap/cache
    docker-compose exec --user root app chmod -R 775 storage bootstrap/cache
    
    print_success "Permissions configured!"
}

# Display final information
display_final_info() {
    echo ""
    echo "======================================"
    print_success "E-commerce SaaS Local Development Setup Complete!"
    echo "======================================"
    echo ""
    echo "🌐 Application URLs:"
    echo "   Main App:          http://localhost:8090"
    echo "   HTTPS:             https://localhost:8443"
    echo "   Horizon:           http://localhost:8090/horizon"
    echo "   Telescope:         http://localhost:8090/telescope"
    echo ""
    echo "🔧 Development Tools:"
    echo "   pgAdmin:           http://localhost:8082"
    echo "   Redis Commander:   http://localhost:8083"
    echo "   Mailhog:           http://localhost:8025"
    echo "   MinIO:             http://localhost:9001"
    echo ""
    echo "🗄️  Database Connection (External):"
    echo "   Host:              localhost"
    echo "   Port:              5433"
    echo "   Database:          ecommerce_saas"
    echo "   Username:          ecommerce_user"
    echo "   Password:          secure_password_123"
    echo ""
    echo "🚀 Management Commands:"
    echo "   Start services:    docker-compose up -d"
    echo "   Stop services:     docker-compose down"
    echo "   View logs:         docker-compose logs -f"
    echo "   Laravel shell:     docker-compose exec app bash"
    echo "   Artisan commands:  docker-compose exec app php artisan [command]"
    echo ""
    echo "📝 Notes:"
    echo "   - All ports are configured to avoid conflicts with your existing setup"
    echo "   - Database runs on port 5433 (not 5432)"
    echo "   - Redis runs on port 6380 (not 6379)"
    echo "   - Web server runs on port 8090 (not 80)"
    echo "   - All container names are prefixed with 'ecommerce_'"
    echo ""
    print_success "Your e-commerce SaaS development environment is ready!"
    echo ""
}

# Main execution
main() {
    echo "======================================"
    echo "E-commerce SaaS Local Development Setup"
    echo "======================================"
    echo ""
    
    check_prerequisites
    check_port_conflicts
    create_project_structure
    setup_environment
    generate_app_key
    start_services
    setup_laravel
    configure_laravel
    setup_database
    fix_permissions
    
    display_final_info
}

# Handle script interruption
trap 'print_error "Setup interrupted. Run docker-compose down to clean up."; exit 1' INT

# Run main function
main "$@"