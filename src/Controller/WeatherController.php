<?php

namespace App\Controller;

use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WeatherController extends AbstractController
{
    /**
     * Get the weather for a given city or the user's city if none is provided.
     * @param string|null $ville
     * @param Security $security
     * @param WeatherService $weatherFullService
     * @return JsonResponse
     */

    #[Route('/meteo/{ville?}', name: 'meteo', methods: ['GET'])] // {ville?} makes the parameter optional
    public function getWeather(
        ?string $ville,
        Security $security,
        WeatherService $weatherFullService
    ): JsonResponse {

        $formattedVille = ucfirst(strtolower($ville));

        if (!$formattedVille) {
            $user = $security->getUser();
            $ville = $user?->getCity(); // Default to Paris if no city is provided

        }

        try {
            $weatherData = $weatherFullService->getWeatherByCity($formattedVille);

            if(!isset($weatherData['main']['temp'])) {
                return new JsonResponse([
                    "error" => "La ville $formattedVille n'a pas été trouvée.",
                ], Response::HTTP_NOT_FOUND);
            }
            
            $temperature = round($weatherData['main']['temp']). '°C'; // Convert Kelvin to Celsius

            return new JsonResponse([
                "La température de $formattedVille est de $temperature.",
                Response::HTTP_OK,
                [],
                true
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                "error" => "Une erreur s'est produite lors de la récupération de météo.",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
