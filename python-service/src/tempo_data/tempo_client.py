import httpx
import os
from typing import Dict, List, Optional, Any
from datetime import datetime, timedelta
import logging

logger = logging.getLogger(__name__)

class TEMPOClient:
    """
    Client for accessing NASA TEMPO satellite data
    """
    
    def __init__(self):
        self.base_url = os.getenv('NASA_TEMPO_BASE_URL', 'https://tempo.si.edu/api')
        self.api_key = os.getenv('NASA_TEMPO_API_KEY')
        self.openaq_api_key = os.getenv('OPENAQ_API_KEY')
        
    async def health_check(self) -> bool:
        """Check if TEMPO service is accessible"""
        try:
            async with httpx.AsyncClient() as client:
                response = await client.get(f"{self.base_url}/status", timeout=5)
                return response.status_code == 200
        except Exception as e:
            logger.error(f"TEMPO health check failed: {e}")
            return False
    
    async def get_tempo_data(self, latitude: float, longitude: float) -> Dict[str, Any]:
        """
        Get TEMPO satellite data for a location
        """
        try:
            # In a real implementation, this would query NASA's TEMPO API
            # For now, we'll return mock data based on location
            return self._get_mock_tempo_data(latitude, longitude)
            
        except Exception as e:
            logger.error(f"Error fetching TEMPO data: {e}")
            return self._get_mock_tempo_data(latitude, longitude)
    
    async def get_ground_measurements(self, latitude: float, longitude: float) -> Dict[str, Any]:
        """
        Get ground-based air quality measurements
        """
        try:
            # Query OpenAQ for ground-based measurements
            async with httpx.AsyncClient() as client:
                response = await client.get(
                    'https://api.openaq.org/v2/latest',
                    params={
                        'coordinates': f"{latitude},{longitude}",
                        'radius': 10000,  # 10km radius
                        'limit': 10
                    },
                    timeout=10
                )
                
                if response.status_code == 200:
                    data = response.json()
                    return self._process_openaq_data(data)
                else:
                    logger.warning(f"OpenAQ API returned {response.status_code}")
                    return self._get_mock_ground_data(latitude, longitude)
                    
        except Exception as e:
            logger.error(f"Error fetching ground measurements: {e}")
            return self._get_mock_ground_data(latitude, longitude)
    
    async def get_historical_data(self, latitude: float, longitude: float, days: int) -> List[Dict[str, Any]]:
        """
        Get historical air quality data
        """
        try:
            # Generate historical data based on current patterns
            history = []
            base_data = await self.get_tempo_data(latitude, longitude)
            
            for i in range(days, 0, -1):
                date = datetime.now() - timedelta(days=i)
                
                # Add some variation to the base data
                variation = self._generate_historical_variation(i)
                
                history.append({
                    'date': date.strftime('%Y-%m-%d'),
                    'aqi_value': max(1, min(5, base_data['aqi'] + variation)),
                    'no2_level': base_data.get('no2', 0) + variation * 5,
                    'o3_level': base_data.get('o3', 0) + variation * 3,
                    'pm25_level': base_data.get('pm25', 0) + variation * 2,
                })
            
            return history
            
        except Exception as e:
            logger.error(f"Error fetching historical data: {e}")
            return []
    
    async def get_status(self) -> Dict[str, Any]:
        """
        Get TEMPO service status and data availability
        """
        return {
            'service': 'TEMPO',
            'status': 'operational',
            'last_update': datetime.now().isoformat(),
            'coverage': 'North America',
            'data_latency': '1-2 hours',
            'parameters': ['NO2', 'HCHO', 'O3']
        }
    
    def _get_mock_tempo_data(self, latitude: float, longitude: float) -> Dict[str, Any]:
        """
        Generate mock TEMPO data for testing
        """
        # Simple algorithm based on location
        lat_factor = abs(latitude)
        lng_factor = abs(longitude)
        
        # Urban areas tend to have higher pollution
        urban_factor = (lat_factor + lng_factor / 100) % 3
        
        base_aqi = 2 + int(urban_factor)
        
        return {
            'aqi': base_aqi,
            'no2': 20 + urban_factor * 10 + (hash(str(latitude)) % 10),
            'o3': 50 + urban_factor * 15 + (hash(str(longitude)) % 15),
            'hcho': 5 + urban_factor * 2,
            'timestamp': datetime.now().isoformat(),
            'source': 'TEMPO_MOCK'
        }
    
    def _get_mock_ground_data(self, latitude: float, longitude: float) -> Dict[str, Any]:
        """
        Generate mock ground-based data
        """
        return {
            'pm25': 15 + (hash(str(latitude)) % 20),
            'pm10': 25 + (hash(str(longitude)) % 30),
            'no2': 18 + (hash(str(latitude + longitude)) % 15),
            'o3': 45 + (hash(str(latitude * longitude)) % 25),
            'timestamp': datetime.now().isoformat(),
            'source': 'GROUND_MOCK'
        }
    
    def _process_openaq_data(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Process OpenAQ API response
        """
        measurements = {}
        
        if 'results' in data and data['results']:
            for result in data['results']:
                if 'measurements' in result:
                    for measurement in result['measurements']:
                        parameter = measurement['parameter']
                        value = measurement['value']
                        
                        if parameter not in measurements:
                            measurements[parameter] = []
                        measurements[parameter].append(value)
        
        # Calculate averages
        processed = {}
        for param, values in measurements.items():
            processed[param] = sum(values) / len(values) if values else 0
        
        processed['timestamp'] = datetime.now().isoformat()
        processed['source'] = 'OpenAQ'
        
        return processed
    
    def _generate_historical_variation(self, days_ago: int) -> int:
        """
        Generate realistic historical variation
        """
        # More variation for older data
        variation_range = min(2, days_ago // 3 + 1)
        return (hash(str(days_ago)) % (variation_range * 2 + 1)) - variation_range
