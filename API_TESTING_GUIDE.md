# Apollo14 API Testing Guide

## 🚀 Quick Start

Your Apollo14 backend is now running with Laravel Sail! Here's how to test the API endpoints.

### **Base URL**
- **Development**: `http://localhost:80/api`
- **Health Check**: `http://localhost:80/api/health`

## 📋 Available Endpoints

### 1. **Health Check** ✅
```bash
curl http://localhost:80/api/health
```
**Response:**
```json
{
  "status": "ok",
  "timestamp": "2025-10-05T08:53:37.242342Z",
  "version": "1.0.0"
}
```

### 2. **Air Quality - Current** 🌬️
```bash
curl "http://localhost:80/api/air-quality/current?lat=40.7128&lng=-74.0060"
```
**Response:**
```json
{
  "success": true,
  "data": {
    "aqi_value": 5,
    "no2_level": 202,
    "o3_level": 302,
    "pm25_level": 147,
    "timestamp": "2025-10-05T08:53:44.022084Z",
    "data_sources": ["mock_data"],
    "location": {
      "latitude": "40.7128",
      "longitude": "-74.0060"
    }
  }
}
```

### 3. **Air Quality - Forecast** 📈
```bash
curl "http://localhost:80/api/air-quality/forecast?lat=40.7128&lng=-74.0060"
```

### 4. **Air Quality - History** 📊
```bash
curl "http://localhost:80/api/air-quality/history?lat=40.7128&lng=-74.0060&days=7"
```

### 5. **Air Quality - Alerts** ⚠️
```bash
curl "http://localhost:80/api/air-quality/alerts?lat=40.7128&lng=-74.0060"
```

## 🔐 Authentication Endpoints

### **Register User**
```bash
curl -X POST http://localhost:80/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### **Login User**
```bash
curl -X POST http://localhost:80/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

## 📸 Photo Submission Endpoints (Requires Authentication)

### **Submit Photo**
```bash
curl -X POST http://localhost:80/api/submissions \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "photo=@/path/to/your/image.jpg" \
  -F "user_guess=3" \
  -F "latitude=40.7128" \
  -F "longitude=-74.0060"
```

### **Get User Submissions**
```bash
curl -X GET http://localhost:80/api/submissions \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 🧪 Testing Tools

### **1. Using curl (Command Line)**
```bash
# Test all air quality endpoints
curl "http://localhost:80/api/air-quality/current?lat=40.7128&lng=-74.0060"
curl "http://localhost:80/api/air-quality/forecast?lat=40.7128&lng=-74.0060"
curl "http://localhost:80/api/air-quality/history?lat=40.7128&lng=-74.0060"
curl "http://localhost:80/api/air-quality/alerts?lat=40.7128&lng=-74.0060"
```

### **2. Using Postman**
1. Import the collection from `tests/postman_collection.json`
2. Set base URL to `http://localhost:80/api`
3. Test endpoints with different coordinates

### **3. Using Browser**
- Health: http://localhost:80/api/health
- Air Quality: http://localhost:80/api/air-quality/current?lat=40.7128&lng=-74.0060

## 📍 Test Coordinates

Use these coordinates for testing:

| Location | Latitude | Longitude |
|----------|----------|-----------|
| New York City | 40.7128 | -74.0060 |
| Los Angeles | 34.0522 | -118.2437 |
| London | 51.5074 | -0.1278 |
| Tokyo | 35.6762 | 139.6503 |
| Sydney | -33.8688 | 151.2093 |

## 🔧 Troubleshooting

### **Backend Not Running**
```bash
cd backend
./vendor/bin/sail up -d
```

### **Check Container Status**
```bash
docker ps
```

### **View Logs**
```bash
cd backend
./vendor/bin/sail logs
```

### **Check Routes**
```bash
cd backend
./vendor/bin/sail artisan route:list --path=api
```

## 📊 Expected Response Format

All API responses follow this format:

### **Success Response**
```json
{
  "success": true,
  "data": { ... }
}
```

### **Error Response**
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

## 🚨 Common Issues

1. **404 Not Found**: Make sure Laravel Sail is running
2. **500 Internal Server Error**: Check if Python service is running on port 5001
3. **Connection Refused**: Verify all Docker containers are up

## 📚 Full API Documentation

See `docs/api.md` for complete API documentation with all endpoints, parameters, and response formats.

## 🎯 Next Steps

1. Test all endpoints with different coordinates
2. Try authentication flow (register → login → protected endpoints)
3. Test photo submission with a real image
4. Check error handling with invalid parameters
5. Verify caching works (same request should be faster on second call)
