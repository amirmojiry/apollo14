#!/bin/bash

# Apollo14 Setup Script
# This script helps you set up the Apollo14 project with Laravel Sail

echo "🚀 Setting up Apollo14 with Laravel Sail..."

# Check if .env file exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    echo "⚠️  Please edit .env file with your actual API keys and configuration"
fi

# Check if backend .env file exists
if [ ! -f backend/.env ]; then
    echo "📝 Creating backend/.env file from backend/.env.example..."
    cp backend/.env.example backend/.env
    echo "⚠️  Please edit backend/.env file with your actual configuration"
fi

echo "🐳 Starting Laravel Sail backend..."
cd backend
./vendor/bin/sail up -d

echo "⏳ Waiting for backend services to be ready..."
sleep 10

echo "🔧 Running Laravel migrations..."
./vendor/bin/sail artisan migrate

echo "🌐 Starting frontend and other services..."
cd ..
docker-compose up -d

echo "✅ Setup complete!"
echo ""
echo "🌍 Services are now running:"
echo "   - Frontend: http://localhost:3000"
echo "   - Backend (Laravel Sail): http://localhost:80"
echo "   - Python Service: http://localhost:5000"
echo "   - Redis: localhost:6379"
echo ""
echo "📋 Useful commands:"
echo "   - Stop all services: docker-compose down && cd backend && ./vendor/bin/sail down"
echo "   - View logs: docker-compose logs -f [service-name]"
echo "   - Laravel Sail commands: cd backend && ./vendor/bin/sail [command]"
echo ""
echo "🔧 Don't forget to:"
echo "   1. Add your API keys to .env and backend/.env files"
echo "   2. Run 'cd backend && ./vendor/bin/sail artisan key:generate' to generate APP_KEY"
echo "   3. Run 'cd backend && ./vendor/bin/sail artisan migrate' if needed"
