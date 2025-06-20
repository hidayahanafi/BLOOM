<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CalendarService
{
    private $client;
    private $apiUrl = 'https://v1.nocodeapi.com/hod/calendar/eybITVVOilSvDrTw';

    public function __construct()
    {
        // Créer un client HTTP avec Guzzle
        $this->client = new Client();
    }

    /**
     * Ajouter un événement au calendrier
     * @param string $summary
     * @param string $description
     * @param string $startDate
     * @param string $endDate
     * @param string $timeZone
     * @return array
     */
    public function addEvent($summary, $description, $startDate, $endDate, $timeZone = 'UTC')
    {
        try {
            // Appel à l'API Google Calendar via NoCodeAPI pour créer un événement
            $response = $this->client->post($this->apiUrl, [
                'json' => [
                    'summary' => $summary,
                    'description' => $description,
                    'start' => [
                        'dateTime' => $startDate,
                        'timeZone' => $timeZone,
                    ],
                    'end' => [
                        'dateTime' => $endDate,
                        'timeZone' => $timeZone,
                    ]
                ]
            ]);
            
            // Récupérer la réponse de l'API
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            // En cas d'erreur, retourner une réponse avec un message d'erreur
            return ['error' => 'Une erreur est survenue lors de l\'ajout de l\'événement.'];
        }
    }

    /**
     * Obtenir tous les événements du calendrier
     * @return array
     */
    public function getEvents()
    {
        try {
            // Appel GET à l'API pour récupérer les événements
            $response = $this->client->get($this->apiUrl);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return ['error' => 'Une erreur est survenue lors de la récupération des événements.'];
        }
    }
}
