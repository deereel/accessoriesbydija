#!/bin/bash

# Dija Accessories Deployment Script
# This script handles deployment to production server

set -e  # Exit on any error

echo "🚀 Starting Dija Accessories deployment..."

# Configuration
REMOTE_HOST="accessoriesbydija.uk"
REMOTE_USER="deploy"
REMOTE_PATH="/var/www/accessoriesbydija"
BACKUP_PATH="/var/www/backups"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Pre-deployment checks
check_requirements() {
    log_info "Checking deployment requirements..."

    # Check if .env.live exists
    if [ ! -f ".env.live" ]; then
        log_error ".env.live file not found. Please create it with production configuration."
        exit 1
    fi

    # Check if required tools are installed
    command -v rsync >/dev/null 2>&1 || { log_error "rsync is required but not installed."; exit 1; }
    command -v ssh >/dev/null 2>&1 || { log_error "ssh is required but not installed."; exit 1; }

    log_info "Requirements check passed."
}

# Create backup on remote server
create_backup() {
    log_info "Creating backup on remote server..."

    ssh $REMOTE_USER@$REMOTE_HOST << EOF
        mkdir -p $BACKUP_PATH
        TIMESTAMP=\$(date +%Y%m%d_%H%M%S)
        tar -czf $BACKUP_PATH/backup_\$TIMESTAMP.tar.gz -C $REMOTE_PATH .
        echo "Backup created: backup_\$TIMESTAMP.tar.gz"

        # Keep only last 5 backups
        cd $BACKUP_PATH
        ls -t backup_*.tar.gz | tail -n +6 | xargs -r rm
EOF
}

# Deploy files
deploy_files() {
    log_info "Deploying files to production server..."

    # Exclude files that shouldn't be deployed
    RSYNC_EXCLUDE=(
        --exclude='.git/'
        --exclude='.env*'
        --exclude='deploy.sh'
        --exclude='*.log'
        --exclude='debug.log'
        --exclude='node_modules/'
        --exclude='.qodo/'
        --exclude='*.sql'
        --exclude='run_migration.php'
        --exclude='check_db.php'
        --exclude='scripts/'
        --exclude='admin/assets/images/products/'
    )

    rsync -avz "${RSYNC_EXCLUDE[@]}" ./ $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

    log_info "Files deployed successfully."
}

# Deploy environment file
deploy_env() {
    log_info "Deploying production environment file..."

    scp .env.live $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/.env

    log_info "Environment file deployed."
}

# Run post-deployment tasks
post_deploy() {
    log_info "Running post-deployment tasks..."

    ssh $REMOTE_USER@$REMOTE_HOST << EOF
        cd $REMOTE_PATH

        # Install/update PHP dependencies
        if command -v composer >/dev/null 2>&1; then
            composer install --no-dev --optimize-autoloader
            echo "Composer dependencies installed."
        fi

        # Set proper permissions
        find . -type f -name "*.php" -exec chmod 644 {} \;
        find . -type d -exec chmod 755 {} \;
        chmod 755 *.php

        # Special permissions for assets
        chmod -R 755 assets/
        chmod -R 777 assets/images/  # Allow image uploads

        # Create necessary directories
        mkdir -p logs cache
        chmod 755 logs cache

        # Clear any PHP caches if using OPcache
        if command -v php >/dev/null 2>&1; then
            php -r 'if (function_exists("opcache_reset")) { opcache_reset(); echo "OPcache cleared\n"; }'
        fi

        # Clear application cache
        if [ -d "cache" ]; then
            rm -rf cache/*.cache
            echo "Application cache cleared."
        fi

        echo "Post-deployment tasks completed."
EOF
}

# Health check
health_check() {
    log_info "Performing health check..."

    # Wait a moment for services to restart
    sleep 5

    # Check if site is accessible
    if curl -s --head --fail "https://$REMOTE_HOST" > /dev/null; then
        log_info "✅ Site is accessible at https://$REMOTE_HOST"
    else
        log_error "❌ Site is not accessible. Please check server configuration."
        exit 1
    fi

    # Check if admin panel is accessible
    if curl -s --head --fail "https://$REMOTE_HOST/admin/" > /dev/null; then
        log_info "✅ Admin panel is accessible"
    else
        log_warn "⚠️  Admin panel may not be accessible. Check authentication."
    fi
}

# Rollback function
rollback() {
    log_warn "Starting rollback to previous version..."

    ssh $REMOTE_USER@$REMOTE_HOST << EOF
        cd $BACKUP_PATH
        LATEST_BACKUP=\$(ls -t backup_*.tar.gz | head -1)

        if [ -n "\$LATEST_BACKUP" ]; then
            echo "Rolling back to \$LATEST_BACKUP"
            tar -xzf \$LATEST_BACKUP -C $REMOTE_PATH
            echo "Rollback completed."
        else
            echo "No backup found for rollback!"
            exit 1
        fi
EOF

    log_info "Rollback completed. Please verify site functionality."
}

# Main deployment process
main() {
    echo "🔧 Dija Accessories Deployment Script"
    echo "===================================="

    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --rollback)
                ROLLBACK=true
                shift
                ;;
            --skip-backup)
                SKIP_BACKUP=true
                shift
                ;;
            *)
                log_error "Unknown option: $1"
                echo "Usage: $0 [--rollback] [--skip-backup]"
                exit 1
                ;;
        esac
    done

    if [ "$ROLLBACK" = true ]; then
        rollback
        exit 0
    fi

    check_requirements

    if [ "$SKIP_BACKUP" != true ]; then
        create_backup
    else
        log_warn "Skipping backup as requested."
    fi

    deploy_files
    deploy_env
    post_deploy
    health_check

    log_info "🎉 Deployment completed successfully!"
    log_info "📝 Next steps:"
    log_info "   1. Test payment gateways with small amounts"
    log_info "   2. Verify email notifications are working"
    log_info "   3. Check admin panel functionality"
    log_info "   4. Monitor error logs for any issues"
    log_info ""
    log_info "🚨 In case of issues, run: $0 --rollback"
}

# Run main function
main "$@"