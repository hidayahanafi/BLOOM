<?php

namespace App\Controller\GestionEvent;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class CalendarController extends AbstractController
{
    private $entityManager;

    // Constructor injection for Doctrine Entity Manager
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/events.json', name: 'get_events')]
    public function getEvents(EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findAll();
        $data = [];
        foreach ($events as $event) {
            $data[] = [
                'id' => $event->getId(),
                'title' => $event->getTitre(),
                'start' => $event->getDate()->format('Y-m-d') . 'T' . $event->getHeure(),
                'extendedProps' => [
                    'description' => $event->getDescription(),
                    'lieu' => $event->getLieu(),
                    'type' => $event->getType(),
                    'date' => $event->getDate(),
                    'img' => $event->getImg(),
                    'budget' => $event->getBudget(),
                ]
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/addEvent', name: 'add_event', methods: ['POST'])]
    public function addEvent(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $event = new Event();
        $event->setTitre($data['titre']);
        $event->setDescription($data['description']);
        $event->setLieu($data['lieu']);
    
        
        $event->setType($data['type']);
        $event->setImg($data['img']);
        $event->setBudget($data['budget']);

        // Persist the event to the database
        $entityManager->persist($event);
        $entityManager->flush();

        return new JsonResponse(['id' => $event->getId()]);
    }

    #[Route('/events/{id}', name: 'update_event', methods: ['PUT'])]
    public function updateEvent(Request $request, EventRepository $eventRepository, $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $event = $eventRepository->find($id);

        if (!$event) {
            return new JsonResponse(['message' => 'Event not found'], 404);
        }

        // Update the event with the new data
        if (isset($data['titre'])) {
            $event->setTitre($data['titre']);
        }
        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }
        if (isset($data['lieu'])) {
            $event->setLieu($data['lieu']);
        }
        
        if (isset($data['type'])) {
            $event->setType($data['type']);
        }
        if (isset($data['img'])) {
            $event->setImg($data['img']);
        }
        if (isset($data['budget'])) {
            $event->setBudget($data['budget']);
        }

        // Persist changes to the database
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Event updated successfully']);
    }

    #[Route('/deleteEvent', name: 'delete_event', methods: ['POST'])]
    public function deleteEvent(Request $request, EventRepository $eventRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $eventId = $data['id'] ?? null;

        if ($eventId) {
            $event = $eventRepository->find($eventId);
            if ($event) {
                $this->entityManager->remove($event);
                $this->entityManager->flush();
                return new JsonResponse(['message' => 'Event deleted successfully'], 200);
            } else {
                return new JsonResponse(['message' => 'Event not found'], 404);
            }
        } else {
            return new JsonResponse(['message' => 'Event ID is missing'], 400);
        }
    }

}