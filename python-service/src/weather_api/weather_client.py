import httpx
import os
from typing import Dict, List, Optional, Any
from datetime import datetime, timedelta
import logging

logger = logging.getLogger(__name__)

class WeatherClient:
    """
    Client for accessing weather data APIs
    """
    
    def __init__(self):
        self.api_key = os.getenv('WEATHER_API_KEY')
        self.base_url = os.getenv('WEATHER_API_BASE_URL', 'https://api.openweathermap.org/data/2.5')
        
    async def health_check(self) -> bool:
        """Check if weather service is accessible"""
        try:
            if not self.api_key:
                return False
                
            async with httpx.AsyncClient() as client:
                response = await client.get(
                    f"{self.base_url}/weather",
                    params={'q': 'London', 'appid': self.api_key},
                    timeout=5
                )
                return response.status_code == 200
        except Exception as e:
            logger.error(f"Weather health check failed: {e}")
            return False
    
    async def get_current_weather(self, latitude: float, longitude: float) -> Dict[str, Any]:
        """
        Get current weather data for a location
        """
        try:
            if self.api_key:
                return await self._get_real_weather_data(latitude, longitude)
            else:
                return self._get_mock_weather_data(latitude, longitude)
                
        except Exception as e:
            logger.error(f"Error fetching weather data: {e}")
            return self._get_mock_weather_data(latitude, longitude)
    
    async def get_weather_forecast(self, latitude: float, longitude: float, days: int = 7) -> List[Dict[str, Any]]:
        """
        Get weather forecast for a location
        """
        try:
            if self.api_key:
                return await self._get_real_forecast(latitude, longitude, days)
            else:
                return self._get_mock_forecast(latitude, longitude, days)
                
        except Exception as e:
            logger.error(f"Error fetching weather forecast: {e}")
            return self._get_mock_forecast(latitude, longitude, days)
    
    async def _get_real_weather_data(self, latitude: float, longitude: float) -> Dict[str, Any]:
        """
        Get real weather data from OpenWeatherMap
        """
        async with httpx.AsyncClient() as client:
            response = await client.get(
                f"{self.base_url}/weather",
                params={
                    'lat': latitude,
                    'lon': longitude,
                    'appid': self.api_key,
                    'units': 'metric'
                },
                timeout=10
            )
            
            if response.status_code == 200:
                data = response.json()
                return self._process_weather_data(data)
            else:
                logger.warning(f"Weather API returned {response.status_code}")
                return self._get_mock_weather_data(latitude, longitude)
    
    async def _get_real_forecast(self, latitude: float, longitude: float, days: int) -> List[Dict[str, Any]]:
        """
        Get real weather forecast from OpenWeatherMap
        """
        async with httpx.AsyncClient() as client:
            response = await client.get(
                f"{self.base_url}/forecast",
                params={
                    'lat': latitude,
                    'lon': longitude,
                    'appid': self.api_key,
                    'units': 'metric'
                },
                timeout=10
            )
            
            if response.status_code == 200:
                data = response.json()
                return self._process_forecast_data(data, days)
            else:
                logger.warning(f"Weather forecast API returned {response.status_code}")
                return self._get_mock_forecast(latitude, longitude, days)
    
    def _get_mock_weather_data(self, latitude: float, longitude: float) -> Dict[str, Any]:
        """
        Generate mock weather data for testing
        """
        # Generate weather based on location and time
        lat_factor = abs(latitude)
        lng_factor = abs(longitude)
        time_factor = datetime.now().hour
        
        # Temperature varies by latitude and time of day
        base_temp = 20 - (lat_factor * 0.5) + (time_factor - 12) * 0.5
        temperature = base_temp + (hash(str(latitude)) % 10) - 5
        
        # Humidity varies by longitude and time
        humidity = 50 + (hash(str(longitude)) % 30) + (time_factor % 10)
        
        # Wind speed varies by location
        wind_speed = 5 + (hash(str(latitude + longitude)) % 15)
        
        return {
            'temperature': round(temperature, 1),
            'humidity': min(100, max(0, humidity)),
            'wind_speed': wind_speed,
            'wind_direction': hash(str(latitude)) % 360,
            'pressure': 1013 + (hash(str(longitude)) % 20) - 10,
            'visibility': 10000 + (hash(str(latitude + longitude)) % 5000),
            'timestamp': datetime.now().isoformat(),
            'source': 'WEATHER_MOCK'
        }
    
    def _get_mock_forecast(self, latitude: float, longitude: float, days: int) -> List[Dict[str, Any]]:
        """
        Generate mock weather forecast
        """
        forecast = []
        base_weather = self._get_mock_weather_data(latitude, longitude)
        
        for i in range(1, days + 1):
            date = datetime.now() + timedelta(days=i)
            
            # Add some variation for each day
            temp_variation = (hash(str(i)) % 10) - 5
            humidity_variation = (hash(str(i + 1)) % 20) - 10
            
            forecast.append({
                'date': date.strftime('%Y-%m-%d'),
                'temperature': base_weather['temperature'] + temp_variation,
                'humidity': max(0, min(100, base_weather['humidity'] + humidity_variation)),
                'wind_speed': base_weather['wind_speed'] + (hash(str(i)) % 5) - 2,
                'pressure': base_weather['pressure'] + (hash(str(i)) % 10) - 5,
                'conditions': self._get_weather_conditions(base_weather['humidity'] + humidity_variation)
            })
        
        return forecast
    
    def _process_weather_data(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Process OpenWeatherMap API response
        """
        main = data.get('main', {})
        wind = data.get('wind', {})
        weather = data.get('weather', [{}])[0]
        
        return {
            'temperature': main.get('temp', 0),
            'humidity': main.get('humidity', 0),
            'pressure': main.get('pressure', 0),
            'wind_speed': wind.get('speed', 0),
            'wind_direction': wind.get('deg', 0),
            'visibility': data.get('visibility', 0),
            'conditions': weather.get('description', 'unknown'),
            'timestamp': datetime.now().isoformat(),
            'source': 'OpenWeatherMap'
        }
    
    def _process_forecast_data(self, data: Dict[str, Any], days: int) -> List[Dict[str, Any]]:
        """
        Process OpenWeatherMap forecast response
        """
        forecast = []
        forecast_list = data.get('list', [])
        
        # Group by day and take daily averages
        daily_data = {}
        for item in forecast_list:
            date = item['dt_txt'].split(' ')[0]
            if date not in daily_data:
                daily_data[date] = []
            daily_data[date].append(item)
        
        # Process each day
        for i, (date, day_items) in enumerate(list(daily_data.items())[:days]):
            if not day_items:
                continue
                
            # Calculate averages
            temps = [item['main']['temp'] for item in day_items]
            humidities = [item['main']['humidity'] for item in day_items]
            pressures = [item['main']['pressure'] for item in day_items]
            wind_speeds = [item['wind']['speed'] for item in day_items]
            
            forecast.append({
                'date': date,
                'temperature': round(sum(temps) / len(temps), 1),
                'humidity': round(sum(humidities) / len(humidities)),
                'pressure': round(sum(pressures) / len(pressures)),
                'wind_speed': round(sum(wind_speeds) / len(wind_speeds), 1),
                'conditions': day_items[0]['weather'][0]['description']
            })
        
        return forecast
    
    def _get_weather_conditions(self, humidity: float) -> str:
        """
        Determine weather conditions based on humidity
        """
        if humidity < 30:
            return 'clear'
        elif humidity < 60:
            return 'partly cloudy'
        elif humidity < 80:
            return 'cloudy'
        else:
            return 'overcast'
