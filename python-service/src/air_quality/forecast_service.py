from typing import Dict, List, Any
from datetime import datetime, timedelta
import logging

logger = logging.getLogger(__name__)

class ForecastService:
    """
    Service for generating air quality forecasts
    """
    
    def __init__(self):
        self.tempo_client = None  # Will be injected
        self.weather_client = None  # Will be injected
        
    async def generate_forecast(self, latitude: float, longitude: float, 
                              current_data: Dict[str, Any] = None) -> List[Dict[str, Any]]:
        """
        Generate 7-day air quality forecast
        """
        try:
            forecast = []
            
            # Get current air quality data if not provided
            if not current_data:
                current_data = await self._get_current_data(latitude, longitude)
            
            # Generate forecast for each day
            for i in range(1, 8):
                day_forecast = await self._generate_day_forecast(
                    latitude, longitude, i, current_data
                )
                forecast.append(day_forecast)
            
            return forecast
            
        except Exception as e:
            logger.error(f"Error generating forecast: {e}")
            return self._get_default_forecast()
    
    async def _get_current_data(self, latitude: float, longitude: float) -> Dict[str, Any]:
        """
        Get current air quality data for forecast base
        """
        # This would typically call the air quality calculator
        # For now, return mock data
        return {
            'aqi': 3,
            'no2': 25,
            'o3': 60,
            'pm25': 20,
            'timestamp': datetime.now().isoformat()
        }
    
    async def _generate_day_forecast(self, latitude: float, longitude: float, 
                                   days_ahead: int, current_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Generate forecast for a specific day
        """
        try:
            # Get weather forecast for the day
            weather_forecast = await self._get_weather_forecast(
                latitude, longitude, days_ahead
            )
            
            # Calculate air quality based on weather and current conditions
            forecast_aqi = self._calculate_forecast_aqi(
                current_data, weather_forecast, days_ahead
            )
            
            # Generate pollutant forecasts
            pollutant_forecasts = self._generate_pollutant_forecasts(
                current_data, weather_forecast, days_ahead
            )
            
            return {
                'date': (datetime.now() + timedelta(days=days_ahead)).strftime('%Y-%m-%d'),
                'aqi_value': forecast_aqi,
                'no2_level': pollutant_forecasts.get('no2'),
                'o3_level': pollutant_forecasts.get('o3'),
                'pm25_level': pollutant_forecasts.get('pm25'),
                'weather': weather_forecast,
                'confidence': self._calculate_confidence(days_ahead),
                'trend': self._calculate_trend(current_data, forecast_aqi)
            }
            
        except Exception as e:
            logger.error(f"Error generating day forecast: {e}")
            return self._get_default_day_forecast(days_ahead)
    
    async def _get_weather_forecast(self, latitude: float, longitude: float, 
                                 days_ahead: int) -> Dict[str, Any]:
        """
        Get weather forecast for a specific day
        """
        # This would typically call the weather client
        # For now, return mock weather data
        return {
            'temperature': 20 + (hash(str(days_ahead)) % 15) - 7,
            'humidity': 50 + (hash(str(days_ahead + 1)) % 30) - 15,
            'wind_speed': 5 + (hash(str(days_ahead + 2)) % 10) - 5,
            'pressure': 1013 + (hash(str(days_ahead + 3)) % 20) - 10,
            'conditions': self._get_weather_conditions(days_ahead)
        }
    
    def _calculate_forecast_aqi(self, current_data: Dict[str, Any], 
                               weather_forecast: Dict[str, Any], 
                               days_ahead: int) -> int:
        """
        Calculate forecast AQI based on current data and weather
        """
        base_aqi = current_data.get('aqi', 3)
        
        # Weather impact
        weather_factor = self._calculate_weather_impact(weather_forecast)
        
        # Seasonal trend (simplified)
        seasonal_factor = self._calculate_seasonal_factor(days_ahead)
        
        # Random variation (simplified model)
        random_variation = (hash(str(days_ahead)) % 3) - 1
        
        forecast_aqi = base_aqi + weather_factor + seasonal_factor + random_variation
        
        return max(1, min(5, forecast_aqi))
    
    def _generate_pollutant_forecasts(self, current_data: Dict[str, Any], 
                                    weather_forecast: Dict[str, Any], 
                                    days_ahead: int) -> Dict[str, float]:
        """
        Generate pollutant level forecasts
        """
        forecasts = {}
        
        # Base pollutant levels
        base_no2 = current_data.get('no2', 25)
        base_o3 = current_data.get('o3', 60)
        base_pm25 = current_data.get('pm25', 20)
        
        # Weather impact on pollutants
        temp_factor = weather_forecast.get('temperature', 20) / 20
        wind_factor = weather_forecast.get('wind_speed', 5) / 5
        humidity_factor = weather_forecast.get('humidity', 50) / 50
        
        # NO2: decreases with wind, increases with temperature
        forecasts['no2'] = base_no2 * (1 + (temp_factor - 1) * 0.2) * (1 - (wind_factor - 1) * 0.1)
        
        # O3: increases with temperature and sunlight
        forecasts['o3'] = base_o3 * (1 + (temp_factor - 1) * 0.3) * (1 + (humidity_factor - 1) * 0.1)
        
        # PM2.5: decreases with wind and rain, increases with humidity
        forecasts['pm25'] = base_pm25 * (1 - (wind_factor - 1) * 0.2) * (1 + (humidity_factor - 1) * 0.1)
        
        return forecasts
    
    def _calculate_weather_impact(self, weather_forecast: Dict[str, Any]) -> int:
        """
        Calculate weather impact on air quality (-2 to +2)
        """
        impact = 0
        
        # Temperature impact
        temp = weather_forecast.get('temperature', 20)
        if temp > 30:
            impact += 1  # High temp worsens air quality
        elif temp < 5:
            impact -= 1  # Low temp improves air quality
        
        # Wind impact
        wind_speed = weather_forecast.get('wind_speed', 5)
        if wind_speed > 10:
            impact -= 1  # High wind improves air quality
        elif wind_speed < 2:
            impact += 1  # Low wind worsens air quality
        
        # Humidity impact
        humidity = weather_forecast.get('humidity', 50)
        if humidity > 80:
            impact += 1  # High humidity worsens air quality
        elif humidity < 30:
            impact -= 1  # Low humidity improves air quality
        
        return max(-2, min(2, impact))
    
    def _calculate_seasonal_factor(self, days_ahead: int) -> int:
        """
        Calculate seasonal trend factor
        """
        # Simple seasonal model
        month = (datetime.now() + timedelta(days=days_ahead)).month
        
        # Summer months (June-August) tend to have higher ozone
        if month in [6, 7, 8]:
            return 1
        # Winter months (Dec-Feb) tend to have higher PM2.5
        elif month in [12, 1, 2]:
            return 1
        # Spring and fall are generally better
        else:
            return -1
    
    def _calculate_confidence(self, days_ahead: int) -> float:
        """
        Calculate forecast confidence (0-1 scale)
        """
        # Confidence decreases with time
        base_confidence = 0.9
        decay_rate = 0.1
        
        confidence = base_confidence - (days_ahead - 1) * decay_rate
        return max(0.3, min(1.0, confidence))
    
    def _calculate_trend(self, current_data: Dict[str, Any], forecast_aqi: int) -> str:
        """
        Calculate air quality trend
        """
        current_aqi = current_data.get('aqi', 3)
        
        if forecast_aqi > current_aqi + 0.5:
            return 'worsening'
        elif forecast_aqi < current_aqi - 0.5:
            return 'improving'
        else:
            return 'stable'
    
    def _get_weather_conditions(self, days_ahead: int) -> str:
        """
        Get weather conditions for a day
        """
        conditions = ['clear', 'partly cloudy', 'cloudy', 'overcast', 'rainy']
        return conditions[hash(str(days_ahead)) % len(conditions)]
    
    def _get_default_forecast(self) -> List[Dict[str, Any]]:
        """
        Return default forecast when generation fails
        """
        forecast = []
        for i in range(1, 8):
            forecast.append(self._get_default_day_forecast(i))
        return forecast
    
    def _get_default_day_forecast(self, days_ahead: int) -> Dict[str, Any]:
        """
        Return default day forecast
        """
        return {
            'date': (datetime.now() + timedelta(days=days_ahead)).strftime('%Y-%m-%d'),
            'aqi_value': 3,
            'no2_level': 25,
            'o3_level': 60,
            'pm25_level': 20,
            'weather': {
                'temperature': 20,
                'humidity': 50,
                'wind_speed': 5,
                'pressure': 1013,
                'conditions': 'partly cloudy'
            },
            'confidence': 0.5,
            'trend': 'stable'
        }
