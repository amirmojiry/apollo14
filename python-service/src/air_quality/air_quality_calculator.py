from typing import Dict, List, Optional, Any
from datetime import datetime
import logging

logger = logging.getLogger(__name__)

class AirQualityCalculator:
    """
    Calculator for air quality metrics and AQI computation
    """
    
    def __init__(self):
        # AQI breakpoints for different pollutants (EPA standards)
        self.aqi_breakpoints = {
            'pm25': [
                (0, 12, 0, 50),      # Good
                (12.1, 35.4, 51, 100),  # Moderate
                (35.5, 55.4, 101, 150), # Unhealthy for Sensitive Groups
                (55.5, 150.4, 151, 200), # Unhealthy
                (150.5, 250.4, 201, 300), # Very Unhealthy
                (250.5, 500.4, 301, 500)  # Hazardous
            ],
            'pm10': [
                (0, 54, 0, 50),
                (55, 154, 51, 100),
                (155, 254, 101, 150),
                (255, 354, 151, 200),
                (355, 424, 201, 300),
                (425, 604, 301, 500)
            ],
            'no2': [
                (0, 53, 0, 50),
                (54, 100, 51, 100),
                (101, 360, 101, 150),
                (361, 649, 151, 200),
                (650, 1249, 201, 300),
                (1250, 2049, 301, 500)
            ],
            'o3': [
                (0, 54, 0, 50),
                (55, 70, 51, 100),
                (71, 85, 101, 150),
                (86, 105, 151, 200),
                (106, 200, 201, 300),
                (201, 500, 301, 500)
            ]
        }
    
    def calculate_aqi(self, tempo_data: Dict[str, Any], 
                     ground_data: Dict[str, Any], 
                     weather_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Calculate Air Quality Index from multiple data sources
        """
        try:
            # Extract pollutant concentrations
            pollutants = self._extract_pollutants(tempo_data, ground_data)
            
            # Calculate individual AQI values
            aqi_values = {}
            for pollutant, concentration in pollutants.items():
                if concentration is not None and concentration > 0:
                    aqi_values[pollutant] = self._calculate_pollutant_aqi(pollutant, concentration)
            
            # Determine overall AQI (highest individual AQI)
            overall_aqi = max(aqi_values.values()) if aqi_values else 3
            
            # Apply weather corrections
            weather_corrected_aqi = self._apply_weather_corrections(
                overall_aqi, weather_data
            )
            
            # Convert to 1-5 scale for our app
            scaled_aqi = self._scale_aqi_to_5(weather_corrected_aqi)
            
            return {
                'aqi': scaled_aqi,
                'no2': pollutants.get('no2'),
                'o3': pollutants.get('o3'),
                'pm25': pollutants.get('pm25'),
                'pm10': pollutants.get('pm10'),
                'timestamp': datetime.now().isoformat(),
                'sources': self._get_data_sources(tempo_data, ground_data),
                'individual_aqi': aqi_values,
                'weather_factor': self._calculate_weather_factor(weather_data)
            }
            
        except Exception as e:
            logger.error(f"Error calculating AQI: {e}")
            return self._get_default_aqi()
    
    def _extract_pollutants(self, tempo_data: Dict[str, Any], 
                           ground_data: Dict[str, Any]) -> Dict[str, Optional[float]]:
        """
        Extract pollutant concentrations from data sources
        """
        pollutants = {}
        
        # Extract from TEMPO data
        if tempo_data:
            pollutants['no2'] = tempo_data.get('no2')
            pollutants['o3'] = tempo_data.get('o3')
            pollutants['hcho'] = tempo_data.get('hcho')
        
        # Extract from ground data (override TEMPO if available)
        if ground_data:
            pollutants['pm25'] = ground_data.get('pm25')
            pollutants['pm10'] = ground_data.get('pm10')
            
            # Use ground data if TEMPO data is not available
            if not pollutants.get('no2'):
                pollutants['no2'] = ground_data.get('no2')
            if not pollutants.get('o3'):
                pollutants['o3'] = ground_data.get('o3')
        
        return pollutants
    
    def _calculate_pollutant_aqi(self, pollutant: str, concentration: float) -> int:
        """
        Calculate AQI for a specific pollutant
        """
        if pollutant not in self.aqi_breakpoints:
            return 3  # Default moderate AQI
        
        breakpoints = self.aqi_breakpoints[pollutant]
        
        for i, (c_low, c_high, aqi_low, aqi_high) in enumerate(breakpoints):
            if c_low <= concentration <= c_high:
                # Linear interpolation
                aqi = ((aqi_high - aqi_low) / (c_high - c_low)) * (concentration - c_low) + aqi_low
                return int(round(aqi))
        
        # If concentration is above highest breakpoint
        return 500
    
    def _apply_weather_corrections(self, aqi: int, weather_data: Dict[str, Any]) -> int:
        """
        Apply weather-based corrections to AQI
        """
        if not weather_data:
            return aqi
        
        corrected_aqi = aqi
        
        # Wind speed correction (higher wind = better dispersion)
        wind_speed = weather_data.get('wind_speed', 5)
        if wind_speed > 10:
            corrected_aqi -= 10  # Improve air quality
        elif wind_speed < 2:
            corrected_aqi += 15  # Worsen air quality
        
        # Temperature correction (higher temp = more chemical reactions)
        temperature = weather_data.get('temperature', 20)
        if temperature > 30:
            corrected_aqi += 10  # Worsen air quality
        elif temperature < 5:
            corrected_aqi -= 5   # Improve air quality
        
        # Humidity correction (high humidity = particle growth)
        humidity = weather_data.get('humidity', 50)
        if humidity > 80:
            corrected_aqi += 5   # Worsen air quality
        elif humidity < 30:
            corrected_aqi -= 5   # Improve air quality
        
        # Pressure correction (low pressure = poor dispersion)
        pressure = weather_data.get('pressure', 1013)
        if pressure < 1000:
            corrected_aqi += 10  # Worsen air quality
        
        return max(0, min(500, corrected_aqi))
    
    def _scale_aqi_to_5(self, aqi: int) -> int:
        """
        Scale EPA AQI (0-500) to our 1-5 scale
        """
        if aqi <= 50:
            return 1  # Good
        elif aqi <= 100:
            return 2  # Moderate
        elif aqi <= 150:
            return 3  # Unhealthy for Sensitive Groups
        elif aqi <= 200:
            return 4  # Unhealthy
        else:
            return 5  # Very Unhealthy/Hazardous
    
    def _get_data_sources(self, tempo_data: Dict[str, Any], 
                         ground_data: Dict[str, Any]) -> List[str]:
        """
        Get list of data sources used
        """
        sources = []
        
        if tempo_data and tempo_data.get('source'):
            sources.append(tempo_data['source'])
        
        if ground_data and ground_data.get('source'):
            sources.append(ground_data['source'])
        
        return sources if sources else ['mock_data']
    
    def _calculate_weather_factor(self, weather_data: Dict[str, Any]) -> float:
        """
        Calculate weather impact factor (0-1 scale)
        """
        if not weather_data:
            return 0.5
        
        factor = 0.5  # Base factor
        
        # Wind impact
        wind_speed = weather_data.get('wind_speed', 5)
        if wind_speed > 10:
            factor -= 0.2
        elif wind_speed < 2:
            factor += 0.2
        
        # Temperature impact
        temperature = weather_data.get('temperature', 20)
        if temperature > 30:
            factor += 0.1
        elif temperature < 5:
            factor -= 0.1
        
        # Humidity impact
        humidity = weather_data.get('humidity', 50)
        if humidity > 80:
            factor += 0.1
        elif humidity < 30:
            factor -= 0.1
        
        return max(0, min(1, factor))
    
    def _get_default_aqi(self) -> Dict[str, Any]:
        """
        Return default AQI data when calculation fails
        """
        return {
            'aqi': 3,
            'no2': None,
            'o3': None,
            'pm25': None,
            'pm10': None,
            'timestamp': datetime.now().isoformat(),
            'sources': ['default'],
            'individual_aqi': {},
            'weather_factor': 0.5
        }
