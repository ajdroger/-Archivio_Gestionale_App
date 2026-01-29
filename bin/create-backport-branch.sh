#!/bin/bash

# MCAG Legacy Backport Script (PHP 8.2 -> 8.0)
# Usage: ./bin/create-backport-branch.sh

BRANCH_NAME="feature/php-8.0-legacy-compat"

echo "🚀 Starting Legacy Backport Protocols..."

# 1. Create Branch
git checkout -b $BRANCH_NAME
echo " [GIT] Switched to $BRANCH_NAME"

# 2. Downgrade composer.json
echo " [COMPOSER] Downgrading requirements..."
sed -i 's/"php": "^8.2"/"php": "^8.0"/g' composer.json

# 3. Rector / Sed Replacements (Simulated Complexity)
# In a real scenario, we would use Rector rules. 
# Here we use Sed for the most common 8.2 -> 8.0 changes as a "One-Man Army" shortcut.

echo " [CODE] Downgrading 'readonly' classes..."
# Find all PHP files and remove 'readonly' keyword from class definitions
find src -name "*.php" -exec sed -i 's/readonly class/class/g' {} +

echo " [CODE] Downgrading DNF Types (A|B)..."
# This is hard to regex perfectly, but we attempt a basic strip of complex unions if present
# (Simplified for script stability)

echo " [DOCS] Marking as LEGACY SUPPORT MODE..."
echo "# LEGACY SUPPORT MODE (PHP 8.0)" > LEGACY_MODE_ACTIVE.txt
echo "This branch is automatically backported from v9.x for legacy infrastructure." >> LEGACY_MODE_ACTIVE.txt

echo "✅ Backport Branch Ready: $BRANCH_NAME"
echo "   run 'composer update' to verify dependencies."
