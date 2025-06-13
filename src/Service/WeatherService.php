<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private HttpClientInterface $httpClient;
    private TagAwareCacheInterface $cachePool;
    private string $openWeatherApiKey;

    public function __construct(
        HttpClientInterface $httpClient,
        #[Autowire('%env(OPENWEATHERMAP_API_KEY)%')] // tell Symfony to inject the environment variable
        string $openWeatherApiKey,
        TagAwareCacheInterface $cachePool
    ) {
        $this->httpClient = $httpClient;
        $this->openWeatherApiKey = $openWeatherApiKey;
        $this->cachePool = $cachePool;
    }


    public function getWeatherByCity(string $city): array
    {

        // create a cache key 
        $idCache = 'getWeatherByCity-' . strtolower($city);

        // check if the data is already cached
        $weatherFullService = $this->cachePool->get(
            $idCache,
            function (ItemInterface $item) use ($city) {

                $item->expiresAfter(3600);
                $item->tag('weather_data');

                // 1. get the latitude and longitude from the city name in OpenWeatherMap API 
                $geoResponse = $this->httpClient->request(
                    'GET',
                    'https://api.openweathermap.org/data/2.5/weather',
                    [
                        'query' => [
                            'q' => $city,
                            'appid' => $this->openWeatherApiKey,
                            'limits' => '1'
                        ]
                    ]
                );

                $geoData = $geoResponse->toArray();

                $latitude = $geoData['coord']['lat'];
                $longitude = $geoData['coord']['lon'];

                // 2. get the weather data from OpenWeatherMap API using the latitude and longitude

                $fullResponse = $this->httpClient->request(
                    'GET',
                    'https://api.openweathermap.org/data/2.5/weather',
                    [
                        'query' => [
                            'lat' => $latitude,
                            'lon' => $longitude,
                            'appid' => $this->openWeatherApiKey,
                            'exclude' => 'daily,minutely,hourly,alerts',
                            'lang' => 'fr',
                            'units' => 'metric' // to get the temperature in Celsius
                        ]
                    ]
                );

                $fullData = $fullResponse->toArray();
                return $fullData;

            }
        );

        return $weatherFullService;
        
    }
}
