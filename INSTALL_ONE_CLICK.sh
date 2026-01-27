#!/bin/bash
# MCAG System - One-Click Universal Installer
# Compatible with Ubuntu 22.04/24.04, Debian 11/12
# Usage: sudo ./INSTALL_ONE_CLICK.sh

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}==================================================${NC}"
echo -e "${BLUE}   MCAG System v8.3 - Universal Installer        ${NC}"
echo -e "${BLUE}==================================================${NC}"
echo -e "${YELLOW}Starting One-Click Setup...${NC}"

# 1. System Update
echo -e "\n${BLUE}[1/5] Updating System Packages...${NC}"
apt-get update -y && apt-get upgrade -y
apt-get install -y curl git unzip

# 2. Docker Check & Install
if ! command -v docker &> /dev/null; then
    echo -e "\n${BLUE}[2/5] Installing Docker Engine...${NC}"
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    rm get-docker.sh
    echo -e "${GREEN}Docker Installed.${NC}"
else
    echo -e "\n${GREEN}[2/5] Docker already installed.${NC}"
fi

# 3. Deployment Setup
echo -e "\n${BLUE}[3/5] configuring Environment...${NC}"
APP_DIR="/var/www/mcag"
CURRENT_DIR=$(pwd)

# If running from inside the repo
if [ -f "docker/production/docker-compose.universal.yml" ]; then
    echo "Running from repository root."
    COMPOSE_FILE="docker/production/docker-compose.universal.yml"
else
    echo -e "${RED}Error: detailed Install Script must be run from project root.${NC}"
    exit 1
fi

# Create .env if missing
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}Creating default .env file...${NC}"
    cp .env.example .env || echo "DB_PASS=$(openssl rand -base64 12)" > .env
fi

# 4. Launch Containers
echo -e "\n${BLUE}[4/5] Launching Containers...${NC}"
docker compose -f "$COMPOSE_FILE" up -d --build

# 5. Finalize
echo -e "\n${BLUE}[5/5] Setting Permissions & Cleanup...${NC}"
# docker compose exec app chown -R www-data:www-data /var/www/html/storage
# docker compose exec app chmod -R 775 /var/www/html/storage

PUBLIC_IP=$(curl -s ifconfig.me)

echo -e "\n${GREEN}==================================================${NC}"
echo -e "${GREEN}   INSTALLATION COMPLETE!                         ${NC}"
echo -e "${GREEN}==================================================${NC}"
echo -e "Access MCAG System at:  http://$PUBLIC_IP"
echo -e "Database Admin:         http://$PUBLIC_IP:8080"
echo -e "--------------------------------------------------"
echo -e "${YELLOW}Note: For production, configure SSL/HTTPS via Nginx Proxy.${NC}"
