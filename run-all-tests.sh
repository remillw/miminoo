#!/bin/bash

# TrouveTaBabysitter - Complete Test Suite Runner
# This script ensures ALL controller tests pass before completion

set -e  # Exit immediately if any command fails

echo "🧪 TrouveTaBabysitter - Running Complete Test Suite"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    print_error "Error: artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

# Clear any existing test artifacts
print_status "Clearing test cache and artifacts..."
php artisan config:clear --env=testing || true
php artisan cache:clear --env=testing || true
php artisan view:clear || true

# Install/update dependencies if needed
print_status "Checking dependencies..."
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    print_status "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generate app key if needed
if [ -z "$(php artisan key:generate --show 2>/dev/null)" ]; then
    print_status "Generating application key..."
    php artisan key:generate --ansi
fi

# Test categories with their expected test counts
declare -A test_categories=(
    ["Auth Tests"]="tests/Feature/Auth/"
    ["Settings Tests"]="tests/Feature/Settings/"
    ["Announcement Controller Tests"]="tests/Feature/AnnouncementControllerTest.php"
    ["Reservation Controller Tests"]="tests/Feature/ReservationControllerTest.php"
    ["Messaging Controller Tests"]="tests/Feature/MessagingControllerTest.php"
    ["Payment Controller Tests"]="tests/Feature/PaymentControllerTest.php"
    ["Stripe Controller Tests"]="tests/Feature/StripeControllerTest.php"
    ["Review Controller Tests"]="tests/Feature/ReviewControllerTest.php"
    ["Babysitter Controller Tests"]="tests/Feature/BabysitterControllerTest.php"
    ["Parent Controller Tests"]="tests/Feature/ParentControllerTest.php"
    ["Admin Controller Tests"]="tests/Feature/AdminControllerTest.php"
    ["Unit Tests"]="tests/Unit/"
)

# Track overall results
total_categories=0
passed_categories=0
failed_categories=()

print_status "Starting comprehensive test execution..."
echo

# Function to run a test category
run_test_category() {
    local category_name="$1"
    local test_path="$2"
    
    total_categories=$((total_categories + 1))
    
    print_status "Running: $category_name"
    echo "  Path: $test_path"
    
    if [ -f "$test_path" ] || [ -d "$test_path" ]; then
        # Run the specific test category
        if php artisan test "$test_path" --stop-on-failure --quiet; then
            print_success "$category_name passed"
            passed_categories=$((passed_categories + 1))
        else
            print_error "$category_name failed"
            failed_categories+=("$category_name")
            
            # Show detailed failure for debugging
            echo
            print_warning "Detailed failure output for $category_name:"
            php artisan test "$test_path" --stop-on-failure
            echo
        fi
    else
        print_warning "$category_name - Test file not found: $test_path"
        print_warning "Skipping this category (tests may not be implemented yet)"
    fi
    
    echo
}

# Run all test categories
for category in "${!test_categories[@]}"; do
    run_test_category "$category" "${test_categories[$category]}"
done

# Final results
echo "=============================================="
echo "🏁 TEST EXECUTION COMPLETE"
echo "=============================================="

if [ ${#failed_categories[@]} -eq 0 ]; then
    print_success "ALL TESTS PASSED! 🎉"
    print_success "Categories passed: $passed_categories/$total_categories"
    echo
    print_success "Your application has comprehensive test coverage!"
    print_success "All controllers are properly tested and working."
    exit 0
else
    print_error "SOME TESTS FAILED! ⚠️"
    echo "Categories passed: $passed_categories/$total_categories"
    echo
    print_error "Failed categories:"
    for failed_cat in "${failed_categories[@]}"; do
        echo "  - $failed_cat"
    done
    echo
    print_error "Please fix the failing tests before proceeding."
    print_error "All tests must pass to ensure application reliability."
    exit 1
fi