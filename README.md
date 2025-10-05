# Apollo14 - NASA Space Apps Challenge 2025

## Team Information
- **Team Name**: Apollo14
- **Challenge**: From EarthData to Action: Cloud Computing with Earth Observation Data for Predicting Cleaner, Safer Skies
- **Team Members**:
  - Haleh Adab
  - Amin Sarlak
  - Hanieh Mashayekhi
  - Amir Mojiri

## Project Overview

Apollo14 is a web-based application that forecasts air quality by integrating real-time NASA TEMPO satellite data with ground-based air quality measurements and weather data. The app helps users limit their exposure to unhealthy levels of air pollution by providing local air quality predictions and timely alerts.

### Key Features

1. **Photo-Based Air Quality Assessment**: Users can take photos of their environment and guess the air pollution level
2. **Real-Time Data Integration**: Combines TEMPO satellite data, ground-based measurements, and weather data
3. **AI-Powered Scoring**: Compares user guesses with actual pollution levels and provides accuracy scores
4. **7-Day Forecasts**: Provides air quality predictions for the next week
5. **Historical Tracking**: Users can view their submission history and accuracy trends
6. **Proactive Alerts**: Push notifications for poor air quality conditions
7. **Educational Component**: Helps users learn to visually assess air quality

## Architecture Overview

The application follows a microservices architecture with three main components:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   React Frontend │    │  Laravel Backend │    │ Python Data     │
│                 │◄──►│                 │◄──►│ Processing      │
│  - Photo Upload │    │  - API Gateway   │    │                 │
│  - User Interface│    │  - Data Storage │    │ - TEMPO Data    │
│  - Notifications│    │  - Auth System   │    │ - Weather API   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Project Structure

```
apollo14/
├── README.md                 # This comprehensive guide
├── frontend/                 # React application
│   ├── src/
│   │   ├── components/       # React components
│   │   ├── services/         # API services
│   │   ├── utils/           # Utility functions
│   │   └── App.js           # Main app component
│   ├── public/              # Static assets
│   └── package.json         # Frontend dependencies
├── backend/                  # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/ # API controllers
│   │   ├── Models/          # Database models
│   │   └── Services/       # Business logic
│   ├── database/
│   │   ├── migrations/      # Database schema
│   │   └── seeders/        # Sample data
│   ├── routes/              # API routes
│   └── composer.json        # Backend dependencies
├── python-service/          # Data processing service
│   ├── src/
│   │   ├── tempo_data/      # TEMPO satellite data processing
│   │   ├── weather_api/     # Weather data integration
│   │   └── air_quality/     # Air quality calculations
│   ├── requirements.txt     # Python dependencies
│   └── main.py             # Service entry point
├── docs/                    # Additional documentation
│   ├── api.md              # API documentation
│   ├── deployment.md       # Deployment guide
│   └── data-sources.md     # Data source documentation
└── docker-compose.yml       # Development environment
```

## Application Workflow

### 1. Photo Upload and Assessment
```
User takes photo → Uploads to app → Sets pollution guess (1-5 scale) → 
App sends location + photo to backend → Backend stores submission
```

### 2. Data Processing Pipeline
```
Backend receives location → Sends to Python service → 
Python service queries:
- TEMPO satellite data
- Ground-based air quality stations
- Weather data
→ Calculates actual pollution level → Generates 7-day forecast
```

### 3. Results and Scoring
```
Python service returns data → Backend calculates user score → 
Sends results to frontend → App displays:
- Actual pollution level
- User's guess comparison
- Accuracy score (1-5)
- 7-day forecast
```

### 4. Historical Tracking
```
User requests history → Backend queries database → 
Returns submission history with trends → Frontend displays analytics
```

### 5. Alert System
```
Python service monitors conditions → Detects poor air quality → 
Sends alert to backend → Backend triggers push notification → 
User receives proactive warning
```

## Data Sources Integration

### NASA TEMPO Data
- **Source**: NASA's Tropospheric Emissions: Monitoring of Pollution mission
- **Data Types**: Nitrogen dioxide (NO₂), formaldehyde (HCHO), ozone (O₃)
- **Update Frequency**: Near real-time (hourly)
- **Coverage**: North America

### Ground-Based Measurements
- **Pandora Network**: High-resolution atmospheric composition
- **OpenAQ**: Global air quality data from various sources
- **TolNet**: Tropospheric ozone lidar network
- **EPA AirNow**: US Environmental Protection Agency data

### Weather Data
- **Source**: National Weather Service APIs
- **Parameters**: Temperature, humidity, wind speed/direction, atmospheric pressure
- **Update Frequency**: Hourly forecasts

## Technology Stack

### Frontend (React)
- **Framework**: React 18+
- **State Management**: Redux Toolkit
- **UI Components**: Material-UI or Ant Design
- **Maps**: Leaflet or Google Maps API
- **Image Processing**: Canvas API for photo analysis
- **Notifications**: Web Push API
- **Charts**: Chart.js or D3.js for data visualization

### Backend (Laravel)
- **Framework**: Laravel 10+
- **Database**: PostgreSQL or MySQL
- **Authentication**: Laravel Sanctum
- **File Storage**: AWS S3 or local storage
- **Queue System**: Redis for background jobs
- **API Documentation**: Swagger/OpenAPI
- **Caching**: Redis for performance optimization

### Python Service
- **Framework**: FastAPI or Flask
- **Data Processing**: Pandas, NumPy
- **Satellite Data**: HDF5, NetCDF libraries
- **Machine Learning**: scikit-learn for forecasting
- **API Client**: Requests, httpx
- **Geospatial**: GeoPandas, Rasterio

## Setup Instructions

### Prerequisites
- Node.js 18+ and npm
- PHP 8.1+ and Composer
- Python 3.9+
- PostgreSQL 13+
- Redis 6+
- Git

### Development Environment Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd apollo14
   ```

2. **Backend Setup (Laravel)**
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   php artisan serve
   ```

3. **Frontend Setup (React)**
   ```bash
   cd frontend
   npm install
   npm start
   ```

4. **Python Service Setup**
   ```bash
   cd python-service
   python -m venv venv
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   pip install -r requirements.txt
   python main.py
   ```

5. **Database Setup**
   ```bash
   # Create PostgreSQL database
   createdb apollo14_air_quality
   
   # Run migrations
   cd backend
   php artisan migrate
   ```

### Environment Configuration

#### Backend (.env)
```env
APP_NAME=Apollo14
APP_ENV=local
APP_KEY=base64:your-app-key
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=apollo14_air_quality
DB_USERNAME=your-username
DB_PASSWORD=your-password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

NASA_TEMPO_API_KEY=your-tempo-api-key
WEATHER_API_KEY=your-weather-api-key
OPENAQ_API_KEY=your-openaq-api-key
```

#### Frontend (.env)
```env
REACT_APP_API_URL=http://localhost:8000/api
REACT_APP_MAPS_API_KEY=your-maps-api-key
REACT_APP_PUSH_VAPID_KEY=your-vapid-key
```

## API Endpoints

### User Submissions
- `POST /api/submissions` - Create new photo submission
- `GET /api/submissions` - Get user's submission history
- `GET /api/submissions/{id}` - Get specific submission details

### Air Quality Data
- `GET /api/air-quality/current` - Get current air quality for location
- `GET /api/air-quality/forecast` - Get 7-day forecast
- `GET /api/air-quality/history` - Get historical data

### Notifications
- `POST /api/notifications/subscribe` - Subscribe to push notifications
- `POST /api/notifications/unsubscribe` - Unsubscribe from notifications

## Database Schema

### Users Table
```sql
- id (primary key)
- name
- email (unique)
- location_preference
- notification_settings
- created_at
- updated_at
```

### Submissions Table
```sql
- id (primary key)
- user_id (foreign key)
- photo_path
- user_guess (1-5 scale)
- actual_level
- accuracy_score
- location_lat
- location_lng
- submitted_at
```

### AirQualityData Table
```sql
- id (primary key)
- location_lat
- location_lng
- no2_level
- o3_level
- pm25_level
- aqi_value
- data_source
- timestamp
```

## Deployment Considerations

### Cloud Infrastructure
- **Frontend**: Vercel, Netlify, or AWS S3 + CloudFront
- **Backend**: AWS EC2, DigitalOcean Droplet, or Heroku
- **Database**: AWS RDS PostgreSQL or managed database service
- **Python Service**: AWS Lambda, Google Cloud Functions, or containerized deployment
- **File Storage**: AWS S3 for photo storage
- **CDN**: CloudFlare for global content delivery

### Scalability Features
- **Horizontal Scaling**: Load balancers for backend services
- **Caching**: Redis for API response caching
- **Database Optimization**: Indexing and query optimization
- **CDN**: Static asset delivery optimization
- **Background Jobs**: Queue system for data processing

## Testing Strategy

### Frontend Testing
- **Unit Tests**: Jest + React Testing Library
- **Integration Tests**: Cypress for E2E testing
- **Component Tests**: Storybook for component documentation

### Backend Testing
- **Unit Tests**: PHPUnit for Laravel
- **API Tests**: Postman or automated API testing
- **Database Tests**: Factory and seeder testing

### Python Service Testing
- **Unit Tests**: pytest
- **Integration Tests**: Test data processing pipelines
- **Data Validation**: Test satellite data parsing

## Security Considerations

### Data Protection
- **Photo Privacy**: Secure storage and processing
- **Location Data**: Anonymization options
- **API Security**: Rate limiting and authentication
- **HTTPS**: SSL/TLS encryption for all communications

### User Privacy
- **GDPR Compliance**: Data deletion and export options
- **Consent Management**: Clear privacy policy
- **Data Minimization**: Only collect necessary data

## Future Enhancements

### Advanced Features
- **Machine Learning**: Computer vision for automatic pollution detection
- **Social Features**: Community challenges and leaderboards
- **Health Integration**: Connect with health apps and wearables
- **AR Visualization**: Augmented reality air quality overlay
- **IoT Integration**: Connect with personal air quality sensors

### Data Expansion
- **Global Coverage**: Expand beyond North America
- **Additional Pollutants**: Include more air quality parameters
- **Historical Analysis**: Long-term trend analysis
- **Predictive Modeling**: Advanced forecasting algorithms

## Contributing Guidelines

### Development Workflow
1. Fork the repository
2. Create a feature branch
3. Make changes with proper testing
4. Submit a pull request
5. Code review and merge

### Code Standards
- **Frontend**: ESLint + Prettier
- **Backend**: PSR-12 PHP standards
- **Python**: PEP 8 style guide
- **Documentation**: Clear comments and docstrings

## Resources and References

### NASA Data Sources
- [TEMPO Mission](https://tempo.si.edu/)
- [NASA Earthdata](https://earthdata.nasa.gov/)
- [GES DISC](https://disc.gsfc.nasa.gov/)

### Air Quality Networks
- [OpenAQ](https://openaq.org/)
- [Pandora Network](https://pandonia-global-network.org/)
- [EPA AirNow](https://www.airnow.gov/)

### Development Resources
- [React Documentation](https://reactjs.org/docs/)
- [Laravel Documentation](https://laravel.com/docs)
- [NASA API Documentation](https://api.nasa.gov/)

## License

This project is developed for the NASA Space Apps Challenge 2025. Please refer to NASA's data usage policies and terms of service for satellite data usage.

## Contact Information

For questions about this project, please contact the Apollo14 team through the NASA Space Apps Challenge platform or via the project repository.

---

*This project is part of the NASA Space Apps Challenge 2025: "From EarthData to Action: Cloud Computing with Earth Observation Data for Predicting Cleaner, Safer Skies"*
