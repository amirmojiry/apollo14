# Apollo14 Air Quality API - Swagger Documentation

## Overview
This project now includes comprehensive Swagger/OpenAPI documentation for all API endpoints.

## Accessing the Documentation

### Swagger UI
Once your Laravel server is running, you can access the interactive Swagger UI at:
```
http://localhost:8000/api/documentation
```

### API Endpoints Documented

#### Authentication Endpoints
- `POST /api/auth/register` - Register a new user
- `POST /api/auth/login` - Login user
- `POST /api/auth/logout` - Logout user (requires authentication)
- `GET /api/auth/profile` - Get user profile (requires authentication)
- `PUT /api/auth/profile` - Update user profile (requires authentication)

#### Air Quality Endpoints (Public)
- `GET /api/air-quality/current` - Get current air quality for a location
- `GET /api/air-quality/forecast` - Get 7-day air quality forecast
- `GET /api/air-quality/history` - Get historical air quality data
- `GET /api/air-quality/alerts` - Get air quality alerts for a location

#### Submission Endpoints (Protected)
- `POST /api/submissions` - Submit a photo with air quality guess
- `GET /api/submissions` - Get user's submission history
- `GET /api/submissions/{id}` - Get specific submission details

#### Notification Endpoints (Protected)
- `POST /api/notifications/subscribe` - Subscribe to push notifications
- `POST /api/notifications/unsubscribe` - Unsubscribe from push notifications
- `GET /api/notifications/settings` - Get notification settings
- `PUT /api/notifications/settings` - Update notification settings

#### System Endpoints
- `GET /api/health` - Health check endpoint

## Authentication
Most endpoints require authentication using Laravel Sanctum. Include the Bearer token in the Authorization header:
```
Authorization: Bearer your_token_here
```

## Features
- Interactive API testing through Swagger UI
- Complete request/response examples
- Parameter validation details
- Authentication requirements clearly marked
- Error response documentation

## Regenerating Documentation
To regenerate the Swagger documentation after making changes to annotations:
```bash
php artisan l5-swagger:generate
```

## Configuration
The Swagger configuration is located in `config/l5-swagger.php` and can be customized as needed.
