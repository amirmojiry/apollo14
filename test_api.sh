#!/bin/bash

# Apollo14 API Test Script
# This script tests all the main API endpoints

BASE_URL="http://localhost:80/api"
TEST_LAT="40.7128"  # New York City
TEST_LNG="-74.0060"

echo "🚀 Testing Apollo14 API Endpoints"
echo "=================================="
echo "Base URL: $BASE_URL"
echo "Test Location: New York City ($TEST_LAT, $TEST_LNG)"
echo ""

# Function to test endpoint
test_endpoint() {
    local name="$1"
    local url="$2"
    local method="${3:-GET}"
    
    echo "Testing: $name"
    echo "URL: $url"
    
    if [ "$method" = "POST" ]; then
        response=$(curl -s -X POST "$url" -H "Content-Type: application/json" -d "$4")
    else
        response=$(curl -s "$url")
    fi
    
    # Check if response contains success or error
    if echo "$response" | grep -q '"success":true'; then
        echo "✅ SUCCESS"
    elif echo "$response" | grep -q '"success":false'; then
        echo "❌ ERROR"
    elif echo "$response" | grep -q '"status":"ok"'; then
        echo "✅ SUCCESS"
    else
        echo "⚠️  UNKNOWN RESPONSE"
    fi
    
    echo "Response: $response"
    echo ""
}

# Test 1: Health Check
test_endpoint "Health Check" "$BASE_URL/health"

# Test 2: Current Air Quality
test_endpoint "Current Air Quality" "$BASE_URL/air-quality/current?lat=$TEST_LAT&lng=$TEST_LNG"

# Test 3: Air Quality Forecast
test_endpoint "Air Quality Forecast" "$BASE_URL/air-quality/forecast?lat=$TEST_LAT&lng=$TEST_LNG"

# Test 4: Air Quality History
test_endpoint "Air Quality History" "$BASE_URL/air-quality/history?lat=$TEST_LAT&lng=$TEST_LNG&days=7"

# Test 5: Air Quality Alerts
test_endpoint "Air Quality Alerts" "$BASE_URL/air-quality/alerts?lat=$TEST_LAT&lng=$TEST_LNG"

# Test 6: User Registration
test_endpoint "User Registration" "$BASE_URL/auth/register" "POST" '{
    "name": "Test User",
    "email": "test'$(date +%s)'@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}'

echo "🎉 API Testing Complete!"
echo ""
echo "📋 Summary:"
echo "- All endpoints tested"
echo "- Check the responses above for any errors"
echo "- If you see ✅ SUCCESS, the endpoint is working"
echo "- If you see ❌ ERROR, check the error message"
echo ""
echo "📚 For more detailed testing, see API_TESTING_GUIDE.md"
echo "📖 For complete API docs, see docs/api.md"
