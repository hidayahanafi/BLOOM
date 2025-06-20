<?php

namespace App\Controller\GestionEvent;

use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request; // Correct import for Request
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route; // Correct import for Route annotation
use Twilio\Rest\Client;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;

final class EventController extends AbstractController
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    #[Route('event/calendar', name: 'calendar_list', methods: ['GET'])]
    public function listCalendars(EventRepository $eventRepository): Response
    {
        // Assuming getUser() gives access to the logged-in user (doctor or organizer)
        $user = $this->getUser();
        $events = $eventRepository->findAll();  // Fetch events associated with the logged-in user

        return $this->render('admin/GestionEvents/calendar/index.html.twig', [
            'events' => $events, // Pass events to the template
        ]);
    }

    #[Route('/events.json', name: 'events_list', methods: ['GET'])]
    public function eventsList(EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findAll();  // Fetch all events from the repository

        $formattedEvents = [];
        foreach ($events as $event) {
            // Combine date and time for the event


            $formattedEvents[] = [
                'id' => $event->getId(),
                'title' => $event->getTitre(),
                // Assurez-vous d'inclure l'heure de fin
                'extendedProps' => [
                    'description' => $event->getDescription(),
                    'lieu' => $event->getLieu(),
                    'type' => $event->getType(),
                    'img' => $event->getImg(),
                    'budget' => $event->getBudget(),
                ],
            ];
        }

        return new JsonResponse($formattedEvents);
    }

    #[Route('/front_event', name: 'searchh')]
    public function searchh(Request $request, EventRepository $repo): Response
    {
        $query = $request->query->get('q', ''); // Récupérer la valeur de la recherche
        $events = $repo->searchByCriteria($query); // Utilisation d'une méthode custom dans le repository

        return $this->render("GestionEvents/event/front_event.html.twig", [
            "events" => $events, // Changement de "list" à "events"
            "query" => $query,
        ]);
    }


    #[Route('/event/statistiques', name: 'event_statistiques')]
    public function statistiques(EventRepository $eventRepository): Response
    {
        // Récupérer le nombre de participants par événement
        $statsParticipants = $eventRepository->countParticipantsByEvent();

        return $this->render('admin/GestionEvents/event/statistique.html.twig', [
            'statsParticipants' => $statsParticipants,
        ]);
    }


    #[Route('/event/{eventId}/remove/{userId}', name: 'remove_participant', methods: ['POST'])]
    public function removeParticipant(int $eventId, int $userId, EntityManagerInterface $entityManager, Request $request): Response
    {
        $event = $entityManager->getRepository(Event::class)->find($eventId);
        $user = $entityManager->getRepository(User::class)->find($userId);

        if (!$event || !$user) {
            throw $this->createNotFoundException('Événement ou utilisateur non trouvé.');
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('remove' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // Suppression du participant de l'événement
        $event->removeUser($user);
        $entityManager->flush();

        $this->addFlash('success', 'Le participant a été supprimé avec succès.');

        return $this->redirectToRoute('event_participants', ['id' => $eventId]);
    }

    #[Route('/event/{id}/participants', name: 'event_participants')]
    public function listParticipants(Event $event): Response
    {
        return $this->render('admin/GestionEvents/event/participants.html.twig', [
            'event' => $event,
            'participants' => $event->getUsers(),
        ]);
    }



    #[Route('/event/{id}/register', name: 'event_register')]
    public function register(int $id, Client $twilioClient, EntityManagerInterface $entityManager): Response
    {
        // Récupérez l'utilisateur connecté
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour vous inscrire.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérez l'événement correspondant à l'ID
        $event = $entityManager->getRepository(Event::class)->find($id);

        // Vérifiez si l'événement existe
        if (!$event) {
            $this->addFlash('error', 'L\'événement demandé n\'existe pas.');
            return $this->redirectToRoute('front_event');
        }

        // Vérifie si l'utilisateur est déjà inscrit
        if ($event->getUsers()->contains($user)) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cet événement.');
        } else {
            // Inscrire l'utilisateur à l'événement
            $event->addUser($user);
            $entityManager->persist($event);
            $entityManager->flush();

            // Personnalisation du message SMS
            $message = sprintf(
                'Bonjour Ms/Mdm %s, Vous êtes inscrit à l\'événement "%s" qui aura lieu le %s.',
                $user->getName(),
                $event->getTitre(),
                $event->getDate()->format('d/m/Y')
            );

            // Formatage du numéro de téléphone
            $numeroLocal = $user->getPhoneNumber(); // Numéro local (ex: 54072276)
            $codePays = '216'; // Code pays pour la Tunisie
            $to = '+' . $codePays . $numeroLocal;

            // Envoi du SMS via Twilio
            try {
                $twilioClient->messages->create(
                    $to,
                    [
                        'from' => $_ENV['TWILIO_PHONE_NUMBER'],
                        'body' => $message,
                    ]
                );

                $this->addFlash('success', 'Inscription réussie ! Un SMS de confirmation a été envoyé.');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Inscription réussie, mais l\'envoi du SMS a échoué.');
            }
        }

        return $this->redirectToRoute('front_event');
    }

    #[Route('/event/list', name: 'search')]
    public function search(Request $request, EventRepository $repo): Response
    {
        $query = $request->query->get('q', ''); // Récupérer la valeur de la recherche
        $events = $repo->searchByCriteria($query); // Utilisation d'une méthode custom dans le repository

        return $this->render("admin/GestionEvents/event/list.html.twig", [
            "list" => $events,
            "query" => $query,
        ]);
    }

    #[Route('/front_event', name: "front_event")]
    public function listEvent(EventRepository $repo): Response
    {
        $event = $repo->findAll();

        return $this->render("GestionEvents/event/front_event.html.twig", [
            "events" => $event
        ]);
    }
    #[Route('/event/list', name: "list")]
    public function listEvents(EventRepository $repo, Request $request): Response
    {

        $event = $repo->findAll();

        return $this->render("admin/GestionEvents/event/list.html.twig", [
            "list" => $event,

        ]);
    }



    #[Route('/event', name: 'app_event_add')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'evenement ajouté avec succès.');
            return $this->redirectToRoute('list');
        }

        return $this->render('admin/GestionEvents/event/index.html.twig', ['form' => $form->createView()]);
    }
    #[Route('/event/edit/{id}', name: 'edit')]
    public function edit(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Evenement modifié avec succès.');

            return $this->redirectToRoute('list');
        }

        return $this->render('admin/GestionEvents/event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }
    #[Route('/event/delete/{id}', name: 'delete')]
    public function delete(Event $event, EntityManagerInterface $em): Response
    {
        $em->remove($event);
        $em->flush();
        $this->addFlash('success', 'Événement supprimé avec succès.');

        return $this->redirectToRoute('list');
    }
}
