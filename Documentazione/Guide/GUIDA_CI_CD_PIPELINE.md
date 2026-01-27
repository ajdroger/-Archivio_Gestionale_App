# 🔄 GUIDA CI/CD PIPELINE MCAG
## Continuous Integration / Continuous Deployment

**Versione**: 1.0  
**Data**: 27 Gennaio 2026  
**Sistema**: MCAG v8.3.0+  
**Tipo**: Guida Tecnica Deployment

---

## 📋 INDICE

1. [Overview CI/CD](#1-overview-cicd)
2. [GitHub Actions Setup](#2-github-actions-setup)
3. [GitLab CI Configuration](#3-gitlab-ci-configuration)
4. [Jenkins Pipeline](#4-jenkins-pipeline)
5. [Quality Gates](#5-quality-gates)
6. [Environment Management](#6-environment-management)
7. [Deploy Automation](#7-deploy-automation)
8. [Rollback Procedures](#8-rollback-procedures)
9. [Monitoring & Alerts](#9-monitoring--alerts)
10. [Best Practices](#10-best-practices)

---

## 1. OVERVIEW CI/CD

### 1.1 Architettura Pipeline

```
┌─────────────┐      ┌──────────────┐      ┌─────────────┐      ┌──────────────┐
│   GIT PUSH  │─────▶│  BUILD & TEST │─────▶│ QUALITY GATE │─────▶│   DEPLOY     │
│  (develop)  │      │   (PHPStan,   │      │  (Coverage,  │      │  (Staging)   │
└─────────────┘      │    Pest)      │      │   Security)  │      └──────────────┘
                     └──────────────┘      └─────────────┘             │
                                                                        ▼
┌─────────────┐      ┌──────────────┐      ┌─────────────┐      ┌──────────────┐
│   GIT TAG   │─────▶│  BUILD PROD  │─────▶│ MANUAL GATE │─────▶│    DEPLOY    │
│  (v8.x.x)   │      │  (optimize)  │      │  (Approval)  │      │ (Production) │
└─────────────┘      └──────────────┘      └─────────────┘      └──────────────┘
```

### 1.2 Pipeline Stages

**Stage 1 - Build** (2-3 min):
- Composer install
- NPM install
- Asset compilation (Vite)

**Stage 2 - Test** (5-8 min):
- PHPUnit/Pest suite (206 tests)
- PHPStan Level 7 analysis
- Code coverage report

**Stage 3 - Quality Gate** (1-2 min):
- Coverage threshold: ≥90%
- PHPStan errors: 0
- Security scan (Snyk/OWASP)

**Stage 4 - Deploy** (3-5 min):
- Database migrations
- Cache clear
- Health check
- Smoke tests

**Total Pipeline Time**: 11-18 minuti

---

## 2. GITHUB ACTIONS SETUP

### 2.1 Workflow File

**File**: `.github/workflows/ci-cd.yml`

```yaml
name: MCAG CI/CD Pipeline

on:
  push:
    branches: [ develop, main, stable ]
  pull_request:
    branches: [ develop, main ]
  release:
    types: [published]

jobs:
  build-and-test:
    name: Build & Test
    runs-on: ubuntu-latest
    timeout-minutes: 15
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: test_password
          MYSQL_DATABASE: mcag_test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
        ports:
          - 3306:3306
      
      redis:
        image: redis:7-alpine
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
        ports:
          - 6379:6379
    
    steps:
      - name: Checkout Code
        uses: actions/checkout@v3
        with:
          fetch-depth: 0  # Full history for SonarQube
      
      - name: Setup PHP 8.2
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo_mysql, redis, zip, gd
          coverage: xdebug
          tools: composer:v2
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
          cache: 'npm'
      
      - name: Cache Composer Dependencies
        uses: actions/cache@v3
        with:
          path: vendor
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: |
            ${{ runner.os }}-composer-
      
      - name: Install Dependencies
        run: |
          composer install --prefer-dist --no-progress --no-suggest
          npm ci
      
      - name: Copy Environment File
        run: cp .env.testing .env
      
      - name: Generate Application Key
        run: php bin/console key:generate
      
      - name: Run Database Migrations
        run: php bin/console migrate:fresh --seed
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: mcag_test
          DB_USERNAME: root
          DB_PASSWORD: test_password
      
      - name: Compile Assets
        run: npm run build
      
      - name: Run PHPStan Analysis
        run: vendor/bin/phpstan analyse --memory-limit=2G
      
      - name: Run Pest Tests with Coverage
        run: vendor/bin/pest --coverage --min=90
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: mcag_test
          DB_USERNAME: root
          DB_PASSWORD: test_password
          REDIS_HOST: 127.0.0.1
          REDIS_PORT: 6379
      
      - name: Archive Coverage Report
        uses: actions/upload-artifact@v3
        with:
          name: coverage-report
          path: coverage/
          retention-days: 7
      
      - name: Security Audit (Composer)
        run: composer audit
      
      - name: Security Audit (NPM)
        run: npm audit --audit-level=moderate
      
      - name: SonarQube Scan
        uses: sonarsource/sonarqube-scan-action@master
        env:
          SONAR_TOKEN: ${{ secrets.SONAR_TOKEN }}
          SONAR_HOST_URL: ${{ secrets.SONAR_HOST_RL }}

  deploy-staging:
    name: Deploy to Staging
    needs: build-and-test
    if: github.ref == 'refs/heads/develop' && github.event_name == 'push'
    runs-on: ubuntu-latest
    environment:
      name: staging
      url: https://staging.mcag.app
    
    steps:
      - name: Checkout Code
        uses: actions/checkout@v3
      
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v0.1.10
        with:
          host: ${{ secrets.STAGING_HOST }}
          username: ${{ secrets.STAGING_USER }}
          key: ${{ secrets.STAGING_SSH_KEY }}
          script: |
            cd /var/www/mcag-staging
            git pull origin develop
            composer install --no-dev --optimize-autoloader
            npm ci && npm run build
            php bin/console migrate
            php bin/console cache:clear
            php bin/console config:cache
            php bin/console queue:restart
            
      - name: Run Smoke Tests
        run: |
          curl -f https://staging.mcag.app/health || exit 1
          curl -f https://staging.mcag.app/api/status || exit 1
      
      - name: Notify Slack
        uses: 8398a7/action-slack@v3
        with:
          status: ${{ job.status }}
          text: 'Deploy to Staging completed!'
          webhook_url: ${{ secrets.SLACK_WEBHOOK }}

  deploy-production:
    name: Deploy to Production
    needs: build-and-test
    if: github.event_name == 'release'
    runs-on: ubuntu-latest
    environment:
      name: production
      url: https://app.mcag.it
    
    steps:
      - name: Checkout Code
        uses: actions/checkout@v3
        with:
          ref: ${{ github.event.release.tag_name }}
      
      - name: Create Backup
        uses: appleboy/ssh-action@v0.1.10
        with:
          host: ${{ secrets.PROD_HOST }}
          username: ${{ secrets.PROD_USER }}
          key: ${{ secrets.PROD_SSH_KEY }}
          script: |
            cd /var/www/mcag-production
            php bin/console backup:create --tag="pre-deploy-${{ github.event.release.tag_name }}"
      
      - name: Deploy to Production
        uses: appleboy/ssh-action@v0.1.10
        with:
          host: ${{ secrets.PROD_HOST }}
          username: ${{ secrets.PROD_USER }}
          key: ${{ secrets.PROD_SSH_KEY }}
          script: |
            cd /var/www/mcag-production
            git fetch --tags
            git checkout ${{ github.event.release.tag_name }}
            composer install --no-dev --optimize-autoloader
            npm ci && npm run build
            php bin/console down
            php bin/console migrate --force
            php bin/console cache:clear
            php bin/console config:cache
            php bin/console up
            php bin/console queue:restart
      
      - name: Run Production Smoke Tests
        run: |
          sleep 10
          curl -f https://app.mcag.it/health || exit 1
          curl -f https://app.mcag.it/api/status || exit 1
      
      - name: Notify Team
        uses: 8398a7/action-slack@v3
        with:
          status: ${{ job.status }}
          text: 'Production deploy ${{ github.event.release.tag_name }} completed!'
          webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

### 2.2 Branch Protection Rules

**Main Branch**:
- ✅ Require pull request reviews (min 1 approvazione)
- ✅ Require status checks to pass (CI must pass)
- ✅ Require branches to be up to date
- ✅ Require conversation resolution before merging
- ✅ No direct pushes (solo via PR o release tag)

**Develop Branch**:
- ✅ Require status checks to pass
- ✅ Allow force push (solo per maintainer)

---

## 3. GITLAB CI CONFIGURATION

### 3.1 .gitlab-ci.yml

```yaml
image: php:8.2-fpm

variables:
  MYSQL_ROOT_PASSWORD: test_password
  MYSQL_DATABASE: mcag_test
  MYSQL_USER: mcag
  MYSQL_PASSWORD: mcag_pass
  COVERAGE_THRESHOLD: "90"

stages:
  - build
  - test
  - quality
  - deploy

cache:
  key: ${CI_COMMIT_REF_SLUG}
  paths:
    - vendor/
    - node_modules/

before_script:
  - apt-get update -qq
  - apt-get install -y -qq git curl unzip nodejs npm
  - curl -sS https://getcomposer.org/installer | php
  - php composer.phar install --no-progress

build:
  stage: build
  script:
    - composer install --prefer-dist --no-dev --optimize-autoloader
    - npm ci
    - npm run build
  artifacts:
    paths:
      - vendor/
      - node_modules/
      - public/dist/
    expire_in: 1 hour

test:unit:
  stage: test
  services:
    - mysql:8.0
    - redis:7-alpine
  script:
    - cp .env.testing .env
    - php bin/console migrate:fresh
    - vendor/bin/pest --testsuite=Unit --coverage --min=${COVERAGE_THRESHOLD}
  coverage: '/^\s*Lines:\s*\d+.\d+\%/'
  artifacts:
    reports:
      coverage_report:
        coverage_format: cobertura
        path: coverage.xml

test:feature:
  stage: test
  services:
    - mysql:8.0
    - redis:7-alpine
  script:
    - cp .env.testing .env
    - php bin/console migrate:fresh --seed
    - vendor/bin/pest --testsuite=Feature

quality:phpstan:
  stage: quality
  script:
    - vendor/bin/phpstan analyse --memory-limit=2G --error-format=gitlab > phpstan-report.json
  artifacts:
    reports:
      codequality: phpstan-report.json

quality:security:
  stage: quality
  script:
    - composer audit --format=json > composer-audit.json
    - npm audit --json > npm-audit.json
  artifacts:
    paths:
      - composer-audit.json
      - npm-audit.json
  allow_failure: true

deploy:staging:
  stage: deploy
  environment:
    name: staging
    url: https://staging.mcag.app
  only:
    - develop
  script:
    - 'which ssh-agent || ( apt-get install -qq openssh-client )'
    - eval $(ssh-agent -s)
    - echo "$SSH_PRIVATE_KEY" | tr -d '\r' | ssh-add -
    - mkdir -p ~/.ssh
    - chmod 700 ~/.ssh
    - ssh-keyscan $DEPLOY_HOST >> ~/.ssh/known_hosts
    - ssh $DEPLOY_USER@$DEPLOY_HOST "cd /var/www/staging && ./deploy.sh develop"

deploy:production:
  stage: deploy
  environment:
    name: production
    url: https://app.mcag.it
  only:
    - tags
  when: manual
  script:
    - ssh $PROD_USER@$PROD_HOST "cd /var/www/production && ./deploy.sh ${CI_COMMIT_TAG}"
```

---

## 4. JENKINS PIPELINE

### 4.1 Jenkinsfile

```groovy
pipeline {
    agent any
    
    environment {
        PHP_VERSION = '8.2'
        NODE_VERSION = '18'
        COMPOSER_HOME = "${WORKSPACE}/.composer"
    }
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
                sh 'git clean -fdx'
            }
        }
        
        stage('Install Dependencies') {
            parallel {
                stage('Composer') {
                    steps {
                        sh 'composer install --prefer-dist --no-progress'
                    }
                }
                stage('NPM') {
                    steps {
                        sh 'npm ci'
                    }
                }
            }
        }
        
        stage('Build Assets') {
            steps {
                sh 'npm run build'
            }
        }
        
        stage('Static Analysis') {
            steps {
                sh 'vendor/bin/phpstan analyse --error-format=checkstyle > phpstan-report.xml'
                recordIssues(tools: [phpStan(pattern: 'phpstan-report.xml')])
            }
        }
        
        stage('Unit Tests') {
            steps {
                sh 'vendor/bin/pest --testsuite=Unit --log-junit=junit-unit.xml'
                junit 'junit-unit.xml'
            }
        }
        
        stage('Feature Tests') {
            steps {
                sh 'vendor/bin/pest --testsuite=Feature --log-junit=junit-feature.xml'
                junit 'junit-feature.xml'
            }
        }
        
        stage('Code Coverage') {
            steps {
                sh 'vendor/bin/pest --coverage --coverage-clover=coverage.xml --min=90'
                publishCoverage adapters: [coberturaAdapter('coverage.xml')]
            }
        }
        
        stage('Security Audit') {
            steps {
                sh 'composer audit || true'
                sh 'npm audit --audit-level=moderate || true'
            }
        }
        
        stage('Deploy to Staging') {
            when {
                branch 'develop'
            }
            steps {
                sshagent(credentials: ['staging-ssh-key']) {
                    sh '''
                        ssh deploy@staging.mcag.app "cd /var/www/mcag && ./deploy.sh"
                    '''
                }
            }
        }
        
        stage('Deploy to Production') {
            when {
                tag pattern: "v\\d+\\.\\d+\\.\\d+", comparator: "REGEXP"
            }
            steps {
                input message: 'Deploy to production?', ok: 'Deploy'
                
                sshagent(credentials: ['production-ssh-key']) {
                    sh '''
                        ssh deploy@app.mcag.it "cd /var/www/mcag && ./deploy.sh ${TAG_NAME}"
                    '''
                }
            }
        }
    }
    
    post {
        always {
            cleanWs()
        }
        success {
            slackSend(color: 'good', message: "Build #${BUILD_NUMBER} succeeded!")
        }
        failure {
            slackSend(color: 'danger', message: "Build #${BUILD_NUMBER} failed!")
        }
    }
}
```

---

## 5. QUALITY GATES

### 5.1 Coverage Threshold

**Minimum Requirements**:
- **Overall Coverage**: ≥ 90%
- **New Code Coverage**: ≥ 95%
- **Changed Files Coverage**: ≥ 92%

**Enforcement**:
```bash
vendor/bin/pest --coverage --min=90
# Exit code 1 se coverage < 90%
```

### 5.2 PHPStan Rules

**Configuration** (phpstan.neon):
```neon
parameters:
    level: 7
    paths:
        - src
    excludePaths:
        - vendor
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
    reportUnmatchedIgnoredErrors: true
```

**Zero Tolerance**: Pipeline fails con anche 1 solo errore PHPStan

### 5.3 Security Checks

**Composer Audit**:
```bash
composer audit --format=json
# Blocca se vulnerabilità CRITICAL o HIGH
```

**NPM Audit**:
```bash
npm audit --audit-level=high
# Blocca se vulnerabilità HIGH o CRITICAL
```

**OWASP Dependency Check** (opzionale):
```bash
dependency-check --project MCAG --scan . --format ALL
```

---

## 6. ENVIRONMENT MANAGEMENT

### 6.1 Environment Variables

**Development**:
```env
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
DB_HOST=localhost
REDIS_HOST=localhost
```

**Staging**:
```env
APP_ENV=staging
APP_DEBUG=false
LOG_LEVEL=info
DB_HOST=staging-db.mcag.internal
REDIS_HOST=staging-redis.mcag.internal
SENTRY_DSN=https://xxx@sentry.io/yyy
```

**Production**:
```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
DB_HOST=prod-db-cluster.mcag.internal
REDIS_HOST=prod-redis-cluster.mcag.internal
SENTRY_DSN=https://xxx@sentry.io/zzz
```

### 6.2 Secrets Management

**GitHub Secrets**:
- `STAGING_HOST`, `STAGING_USER`, `STAGING_SSH_KEY`
- `PROD_HOST`, `PROD_USER`, `PROD_SSH_KEY`
- `SLACK_WEBHOOK`
- `SONAR_TOKEN`

**Vault Integration** (production):
```bash
# Fetch secrets da HashiCorp Vault
vault kv get secret/mcag/production/env > .env
```

---

## 7. DEPLOY AUTOMATION

### 7.1 Deploy Script

**File**: `deploy.sh`

```bash
#!/bin/bash
set -e

BRANCH=${1:-develop}
BACKUP_TAG="pre-deploy-$(date +%Y%m%d-%H%M%S)"

echo "🚀 Starting deployment for branch: $BRANCH"

# 1. Maintenance mode
echo "📝 Enabling maintenance mode..."
php bin/console down || true

# 2. Backup database
echo "💾 Creating database backup..."
php bin/console backup:create --tag="$BACKUP_TAG"

# 3. Pull latest code
echo "📥 Pulling latest code..."
git fetch --all
git checkout "$BRANCH"
git pull origin "$BRANCH"

# 4. Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 5. Run migrations
echo "🗄️  Running database migrations..."
php bin/console migrate --force

# 6. Clear caches
echo "🧹 Clearing caches..."
php bin/console cache:clear
php bin/console config:cache
php bin/console route:cache

# 7. Restart queue workers
echo "🔄 Restarting queue workers..."
php bin/console queue:restart

# 8. Disable maintenance mode
echo "✅ Disabling maintenance mode..."
php bin/console up

# 9. Health check
echo "🏥 Running health check..."
sleep 5
curl -f http://localhost/health || {
    echo "❌ Health check failed! Rolling back..."
    php bin/console backup:restore --tag="$BACKUP_TAG"
    exit 1
}

echo "✅ Deployment completed successfully!"
```

### 7.2 Zero-Downtime Deploy

**Blue-Green Deployment**:
```bash
# Nginx config: switch upstream
upstream mcag {
    server mcag-blue:9000;   # Current
    # server mcag-green:9000; # New
}

# Deploy to green, test, then switch
./deploy-blue-green.sh green
nginx -s reload  # Switch traffic
```

---

## 8. ROLLBACK PROCEDURES

### 8.1 Git Rollback

```bash
# Rollback a tag precedente
git fetch --tags
git checkout v8.2.0
./deploy.sh v8.2.0
```

### 8.2 Database Rollback

```bash
# Restore da backup
php bin/console backup:list
php bin/console backup:restore --tag="pre-deploy-20260127-1430"
```

### 8.3 Emergency Rollback

```bash
#!/bin/bash
# emergency-rollback.sh

PREVIOUS_TAG=$1

if [ -z "$PREVIOUS_TAG" ]; then
    echo "Usage: ./emergency-rollback.sh <tag>"
    exit 1
fi

echo "⚠️  EMERGENCY ROLLBACK TO $PREVIOUS_TAG"

# Stop accepting traffic
php bin/console down

# Restore code
git checkout "$PREVIOUS_TAG"

# Restore database (assumes backup exists)
php bin/console backup:restore --tag="pre-deploy-$PREVIOUS_TAG" --force

# Restore dependencies
composer install --no-dev
npm ci && npm run build

# Resume traffic
php bin/console up

echo "✅ Rollback completed"
```

---

## 9. MONITORING & ALERTS

### 9.1 Pipeline Metrics

**Track**:
- Build duration trend
- Test pass rate
- Coverage trend
- Deploy frequency
- Deploy success rate
- Mean Time To Recovery (MTTR)

**Dashboard**: Grafana + Prometheus

### 9.2 Deployment Notifications

**Slack Integration**:
```yaml
- name: Slack Notification
  uses: 8398a7/action-slack@v3
  with:
    status: ${{ job.status }}
    fields: repo,message,commit,author,action,eventName,ref,workflow
    webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

**Email Alerts**:
- Deploy success/failure
- Quality gate failures
- Security vulnerabilities detected

---

## 10. BEST PRACTICES

### 10.1 Commit Guidelines

**Conventional Commits**:
```
feat: add Workshift AI optimizer
fix: resolve CSRF token validation
docs: update API reference
test: add integration test for 2FA
refactor: extract email service interface
perf: optimize database queries
```

### 10.2 PR Checklist

- [ ] Tests added/updated
- [ ] PHPStan passes locally
- [ ] Code coverage ≥ 90%
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] No console errors/warnings
- [ ] Peer review completed

### 10.3 Release Process

1. Create release branch: `git checkout -b release/v8.4.0`
2. Update version numbers (composer.json, package.json)
3. Update CHANGELOG.md
4. Create PR to main
5. Merge after approval
6. Create tag: `git tag v8.4.0`
7. Push tag: `git push origin v8.4.0`
8. CI/CD deploys automatically (after manual approval)

---

## CONCLUSIONE

Una pipeline CI/CD robusta è **fondamentale** per mantenere alta qualità e velocità di rilascio. MCAG implementa best practices industry-standard con quality gates rigorosi e deployment automation completa.

**Metriche Target**:
- ✅ Build time < 15 minuti
- ✅ Deploy frequency: 2-5 volte/settimana
- ✅ Change Failure Rate < 5%
- ✅ MTTR < 30 minuti

---

**© 2026 Soobadur Mohammad Ajmeer - All Rights Reserved**  
**MCAG CI/CD Guide**  
**Versione**: 1.0  
**Data**: 27 Gennaio 2026
