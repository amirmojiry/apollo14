# Apollo14 API Documentation

## Overview

The Apollo14 API provides endpoints for air quality assessment, photo submissions, and data retrieval. The API follows RESTful principles and returns JSON responses.

## Base URL

- **Development**: `http://localhost:8000/api`
- **Production**: `https://api.apollo14.nasa.gov/api`

## Authentication

The API uses Laravel Sanctum for authentication. Include the bearer token in the Authorization header:

```
Authorization: Bearer {your-token}
```

## API Endpoints

### Authentication

#### Register User
```http
POST /api/auth/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "1|abc123..."
  }
}
```

#### Login User
```http
POST /api/auth/login
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "1|abc123..."
  }
}
```

### Photo Submissions

#### Submit Photo Assessment
```http
POST /api/submissions
```

**Request:** Multipart form data
- `photo`: Image file (JPEG, PNG, GIF, max 10MB)
- `user_guess`: Integer (1-5 scale)
- `latitude`: Float (-90 to 90)
- `longitude`: Float (-180 to 180)

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "user_guess": 3,
    "actual_level": 4,
    "accuracy_score": 4,
    "photo_url": "https://api.apollo14.nasa.gov/storage/submissions/photo.jpg",
    "forecast": [
      {
        "date": "2024-01-15",
        "aqi_value": 4,
        "no2_level": 45.2,
        "o3_level": 78.5,
        "pm25_level": 32.1
      }
    ],
    "submitted_at": "2024-01-14T10:30:00Z"
  }
}
```

#### Get User Submissions
```http
GET /api/submissions
```

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "user_guess": 3,
      "actual_level": 4,
      "accuracy_score": 4,
      "location_lat": 40.7128,
      "location_lng": -74.0060,
      "submitted_at": "2024-01-14T10:30:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

#### Get Specific Submission
```http
GET /api/submissions/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "user_guess": 3,
    "actual_level": 4,
    "accuracy_score": 4,
    "photo_url": "https://api.apollo14.nasa.gov/storage/submissions/photo.jpg",
    "location_lat": 40.7128,
    "location_lng": -74.0060,
    "air_quality_data": {
      "aqi": 4,
      "no2": 45.2,
      "o3": 78.5,
      "pm25": 32.1,
      "sources": ["TEMPO", "OpenAQ"]
    },
    "submitted_at": "2024-01-14T10:30:00Z"
  }
}
```

### Air Quality Data

#### Get Current Air Quality
```http
GET /api/air-quality/current
```

**Query Parameters:**
- `lat`: Latitude (-90 to 90)
- `lng`: Longitude (-180 to 180)

**Response:**
```json
{
  "success": true,
  "data": {
    "aqi_value": 3,
    "no2_level": 25.5,
    "o3_level": 65.2,
    "pm25_level": 18.7,
    "timestamp": "2024-01-14T10:30:00Z",
    "data_sources": ["TEMPO", "OpenAQ"],
    "location": {
      "latitude": 40.7128,
      "longitude": -74.0060
    }
  }
}
```

#### Get Air Quality Forecast
```http
GET /api/air-quality/forecast
```

**Query Parameters:**
- `lat`: Latitude (-90 to 90)
- `lng`: Longitude (-180 to 180)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "date": "2024-01-15",
      "aqi_value": 3,
      "no2_level": 28.1,
      "o3_level": 62.3,
      "pm25_level": 19.2
    },
    {
      "date": "2024-01-16",
      "aqi_value": 4,
      "no2_level": 35.7,
      "o3_level": 71.8,
      "pm25_level": 25.4
    }
  ]
}
```

#### Get Historical Data
```http
GET /api/air-quality/history
```

**Query Parameters:**
- `lat`: Latitude (-90 to 90)
- `lng`: Longitude (-180 to 180)
- `days`: Number of days (1-30, default: 7)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "date": "2024-01-07",
      "aqi_value": 2,
      "no2_level": 18.3,
      "o3_level": 55.1,
      "pm25_level": 12.8
    }
  ]
}
```

#### Get Air Quality Alerts
```http
GET /api/air-quality/alerts
```

**Query Parameters:**
- `lat`: Latitude (-90 to 90)
- `lng`: Longitude (-180 to 180)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "type": "air_quality_warning",
      "level": "poor",
      "message": "Air quality is poor. Everyone should limit outdoor activities.",
      "timestamp": "2024-01-14T10:30:00Z"
    }
  ]
}
```

### Notifications

#### Subscribe to Push Notifications
```http
POST /api/notifications/subscribe
```

**Request Body:**
```json
{
  "endpoint": "https://fcm.googleapis.com/fcm/send/...",
  "p256dh_key": "BNJxw...",
  "auth_key": "tBHIt..."
}
```

**Response:**
```json
{
  "success": true,
  "message": "Successfully subscribed to notifications"
}
```

#### Unsubscribe from Notifications
```http
POST /api/notifications/unsubscribe
```

**Response:**
```json
{
  "success": true,
  "message": "Successfully unsubscribed from notifications"
}
```

## Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

## Rate Limiting

- **General API**: 1000 requests per hour per IP
- **Photo Upload**: 10 requests per hour per user
- **Air Quality Data**: 100 requests per hour per IP

## Data Sources

### NASA TEMPO
- **Coverage**: North America
- **Update Frequency**: Hourly
- **Parameters**: NO₂, HCHO, O₃
- **Latency**: 1-2 hours

### OpenAQ
- **Coverage**: Global
- **Update Frequency**: Real-time
- **Parameters**: PM2.5, PM10, NO₂, O₃, SO₂, CO
- **Latency**: < 1 hour

### Weather Data
- **Source**: OpenWeatherMap
- **Coverage**: Global
- **Update Frequency**: Hourly
- **Parameters**: Temperature, Humidity, Wind, Pressure
- **Latency**: < 1 hour

## Air Quality Scale

| AQI Value | Level | Description |
|-----------|-------|-------------|
| 1 | Excellent | Air quality is excellent. Enjoy outdoor activities! |
| 2 | Good | Air quality is good. Most people can enjoy outdoor activities. |
| 3 | Moderate | Air quality is moderate. Sensitive individuals should limit outdoor activities. |
| 4 | Poor | Air quality is poor. Everyone should limit outdoor activities. |
| 5 | Hazardous | Air quality is hazardous. Avoid outdoor activities and stay indoors. |

## Webhooks

### Air Quality Alert Webhook

When air quality conditions exceed user-defined thresholds, a webhook is sent:

```json
{
  "event": "air_quality_alert",
  "user_id": 123,
  "location": {
    "latitude": 40.7128,
    "longitude": -74.0060
  },
  "air_quality": {
    "aqi_value": 4,
    "level": "poor"
  },
  "timestamp": "2024-01-14T10:30:00Z"
}
```

## SDKs and Libraries

### JavaScript/Node.js
```bash
npm install apollo14-api-client
```

### Python
```bash
pip install apollo14-api-client
```

### PHP
```bash
composer require apollo14/api-client
```

## Support

For API support and questions:
- **Email**: api-support@apollo14.nasa.gov
- **Documentation**: https://docs.apollo14.nasa.gov
- **Status Page**: https://status.apollo14.nasa.gov
