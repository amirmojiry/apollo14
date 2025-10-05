#!/bin/bash

# Quick deployment script for Apollo14
echo "🚀 Apollo14 Hetzner Deployment Quick Start"
echo "=========================================="
echo ""
echo "This script will help you deploy Apollo14 to your Hetzner server."
echo ""

# Check if we're in the right directory
if [ ! -f "backend/deploy.sh" ]; then
    echo "❌ Error: Please run this script from the project root directory"
    exit 1
fi

echo "📋 Prerequisites Checklist:"
echo "1. ✅ Hetzner server running Ubuntu 22.04"
echo "2. ✅ Domain name pointing to your server"
echo "3. ✅ SSH access to your server"
echo "4. ✅ GitHub repository with your code"
echo ""
read -p "Have you completed all prerequisites? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Please complete the prerequisites first. See DEPLOYMENT_GUIDE.md for details."
    exit 1
fi

echo ""
echo "🔧 Configuration Required:"
echo "1. Update GitHub repository URL in backend/deploy.sh"
echo "2. Update domain name in backend/deploy.sh"
echo "3. Configure GitHub Secrets (HETZNER_HOST, HETZNER_USERNAME, HETZNER_SSH_KEY)"
echo "4. Update database credentials in backend/.env.production"
echo ""
read -p "Have you updated all configurations? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Please update configurations first. See DEPLOYMENT_GUIDE.md for details."
    exit 1
fi

echo ""
echo "📤 Uploading files to server..."
echo "Please provide your server details:"
read -p "Server IP: " SERVER_IP
read -p "Username: " USERNAME

echo ""
echo "🚀 Starting deployment..."
echo "Run these commands on your server:"
echo ""
echo "1. Upload deployment script:"
echo "   scp backend/deploy.sh $USERNAME@$SERVER_IP:~/"
echo ""
echo "2. Upload scripts:"
echo "   scp -r backend/scripts $USERNAME@$SERVER_IP:~/"
echo ""
echo "3. SSH into server and run:"
echo "   ssh $USERNAME@$SERVER_IP"
echo "   chmod +x deploy.sh scripts/*.sh"
echo "   ./deploy.sh"
echo ""
echo "4. After deployment, run:"
echo "   ./scripts/setup-database.sh"
echo "   cp backend/.env.production backend/.env"
echo "   nano backend/.env  # Update with your settings"
echo "   ./scripts/setup-ssl.sh"
echo ""
echo "📚 For detailed instructions, see DEPLOYMENT_GUIDE.md"
echo "🎉 Your API will be available at: https://your-domain.com/api/"
echo "📖 Swagger docs at: https://your-domain.com/api/documentation"
