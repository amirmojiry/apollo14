from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List, Optional, Dict, Any
import uvicorn
import os
from dotenv import load_dotenv

from src.tempo_data.tempo_client import TEMPOClient
from src.weather_api.weather_client import WeatherClient
from src.air_quality.air_quality_calculator import AirQualityCalculator
from src.air_quality.forecast_service import ForecastService

# Load environment variables
load_dotenv()

app = FastAPI(
    title="Apollo14 Air Quality Service",
    description="Python service for processing NASA TEMPO data and air quality calculations",
    version="1.0.0"
)

# Configure CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:3000", "http://localhost:8000"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize services
tempo_client = TEMPOClient()
weather_client = WeatherClient()
air_quality_calculator = AirQualityCalculator()
forecast_service = ForecastService()

# Pydantic models
class LocationRequest(BaseModel):
    latitude: float
    longitude: float

class AirQualityResponse(BaseModel):
    current_aqi: int
    no2_level: Optional[float] = None
    o3_level: Optional[float] = None
    pm25_level: Optional[float] = None
    timestamp: str
    data_sources: List[str]
    forecast: List[Dict[str, Any]]

class ForecastRequest(BaseModel):
    latitude: float
    longitude: float

class HistoryRequest(BaseModel):
    latitude: float
    longitude: float
    days: int = 7

@app.get("/")
async def root():
    return {
        "message": "Apollo14 Air Quality Service",
        "version": "1.0.0",
        "status": "running"
    }

@app.get("/health")
async def health_check():
    return {
        "status": "healthy",
        "services": {
            "tempo": await tempo_client.health_check(),
            "weather": await weather_client.health_check(),
            "air_quality": True
        }
    }

@app.post("/air-quality", response_model=AirQualityResponse)
async def get_air_quality(location: LocationRequest):
    """
    Get current air quality data for a location
    """
    try:
        # Get TEMPO satellite data
        tempo_data = await tempo_client.get_tempo_data(
            location.latitude, 
            location.longitude
        )
        
        # Get ground-based measurements
        ground_data = await tempo_client.get_ground_measurements(
            location.latitude, 
            location.longitude
        )
        
        # Get weather data
        weather_data = await weather_client.get_current_weather(
            location.latitude, 
            location.longitude
        )
        
        # Calculate air quality metrics
        air_quality_data = air_quality_calculator.calculate_aqi(
            tempo_data=tempo_data,
            ground_data=ground_data,
            weather_data=weather_data
        )
        
        # Generate forecast
        forecast = await forecast_service.generate_forecast(
            location.latitude,
            location.longitude,
            air_quality_data
        )
        
        return AirQualityResponse(
            current_aqi=air_quality_data['aqi'],
            no2_level=air_quality_data.get('no2'),
            o3_level=air_quality_data.get('o3'),
            pm25_level=air_quality_data.get('pm25'),
            timestamp=air_quality_data['timestamp'],
            data_sources=air_quality_data['sources'],
            forecast=forecast
        )
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/forecast")
async def get_forecast(request: ForecastRequest):
    """
    Get 7-day air quality forecast
    """
    try:
        forecast = await forecast_service.generate_forecast(
            request.latitude,
            request.longitude
        )
        
        return {"forecast": forecast}
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/history")
async def get_history(request: HistoryRequest):
    """
    Get historical air quality data
    """
    try:
        history = await tempo_client.get_historical_data(
            request.latitude,
            request.longitude,
            request.days
        )
        
        return {"history": history}
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/tempo/status")
async def tempo_status():
    """
    Check TEMPO data availability and status
    """
    try:
        status = await tempo_client.get_status()
        return status
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/data-sources")
async def get_data_sources():
    """
    Get information about available data sources
    """
    return {
        "tempo": {
            "name": "NASA TEMPO",
            "description": "Tropospheric Emissions: Monitoring of Pollution",
            "coverage": "North America",
            "update_frequency": "Hourly",
            "parameters": ["NO2", "HCHO", "O3"]
        },
        "openaq": {
            "name": "OpenAQ",
            "description": "Global air quality data from various sources",
            "coverage": "Global",
            "update_frequency": "Real-time",
            "parameters": ["PM2.5", "PM10", "NO2", "O3", "SO2", "CO"]
        },
        "weather": {
            "name": "OpenWeatherMap",
            "description": "Weather data for air quality modeling",
            "coverage": "Global",
            "update_frequency": "Hourly",
            "parameters": ["Temperature", "Humidity", "Wind", "Pressure"]
        }
    }

if __name__ == "__main__":
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=5000,
        reload=True
    )
