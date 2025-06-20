<?php

namespace App\Controller\GestionObjectif;

use App\Entity\Objectif;
use App\Repository\RecompenseRepository;
use App\Repository\ObjectifRepository;
use App\Form\GestionObjectif\ObjectifType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use TCPDF;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;



final class ObjectifController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('base.html.twig', [
            'controller_name' => 'ObjectifController',
        ]);
    }

    #[Route('/service', name: 'app_service')]
    public function service(): Response
    {
        return $this->render('objectif/service.html.twig', [
            'controller_name' => 'ObjectifController',
        ]);
    }

    #[Route('/listobjectif/{id_user}', name: 'app_objectif_page')]
    public function objectifPage(EntityManagerInterface $entityManager, int $id_user): Response
    {
        // Récupérer tous les objectifs pour l'utilisateur passé en paramètre, sans filtrer par statut
        $objectifs = $entityManager->getRepository(Objectif::class)->findBy([
            'user' => $id_user,  // Filtrer uniquement par l'utilisateur
        ]);

        // Passer la variable id_user à la vue
        return $this->render('objectif/listobjectif.html.twig', [
            'objectifs' => $objectifs,
            'id_user' => $id_user,  // Assurez-vous que cette ligne est présente
        ]);
    }






    #[Route('/details/{id}/{id_user}', name: 'app_details')]
    public function details($id, $id_user, ObjectifRepository $objectifRepository): Response
    {
        // Récupérer l'objectif spécifique par son ID
        $objectif = $objectifRepository->find($id);

        if (!$objectif) {
            throw $this->createNotFoundException('Objectif non trouvé');
        }

        // Récupérer les objectifs ayant le statut "En cours" pour un utilisateur spécifique
        $objectifsEnCours = $objectifRepository->findBy([
            'statut' => 'En cours',
            'user' => $id_user,
        ]);

        // Récupérer les objectifs ayant le statut "Terminé" pour un utilisateur spécifique
        $objectifsTermines = $objectifRepository->findBy([
            'statut' => 'Terminé',
            'user' => $id_user,
        ]);

        return $this->render('objectif/details.html.twig', [
            'objectif' => $objectif,  // Passer l'objectif sélectionné
            'objectifsEnCours' => $objectifsEnCours,
            'objectifsTermines' => $objectifsTermines,
            'id_user' => $id_user,  // Passer l'id_user à la vue
        ]);
    }



    // Route pour changer le statut d'un objectif en "En cours"
    #[Route('/commencer/{id}/{id_user}', name: 'app_commencer')]
    public function commencer(Objectif $objectif, int $id_user, EntityManagerInterface $entityManager): Response
    {
        // Changer le statut de l'objectif en "En cours"
        $objectif->setStatut('En cours');

        // Sauvegarder la mise à jour avec l'EntityManager
        $entityManager->flush();

        // Rediriger vers la page de détails de l'objectif en passant l'id_user
        return $this->redirectToRoute('app_details', ['id' => $objectif->getId(), 'id_user' => $id_user]);
    }


    #[Route('/suivi/{id_user}', name: 'app_suivi')]
    public function suivi(ObjectifRepository $objectifRepository, int $id_user): Response
    {
        // Récupérer les objectifs "En cours" pour un utilisateur spécifique
        $objectifsEnCours = $objectifRepository->findBy([
            'statut' => 'En cours',
            'user' => $id_user,
        ]);

        // Récupérer les objectifs "Terminé" pour un utilisateur spécifique
        $objectifsTermines = $objectifRepository->findBy([
            'statut' => 'Terminé',
            'user' => $id_user,
        ]);

        // Passer id_user à la vue
        return $this->render('objectif/suivi.html.twig', [
            'objectifsEnCours' => $objectifsEnCours,
            'objectifsTermines' => $objectifsTermines,
            'id_user' => $id_user,  // Passer la variable id_user à la vue
        ]);
    }


    #[Route('/recompense/{id_user}', name: 'app_recompense')]
    public function recompense(int $id_user, ObjectifRepository $objectifRepository, RecompenseRepository $recompenseRepository, EntityManagerInterface $entityManager): Response
    {
        // Utiliser l'EntityManager injecté
        $user = $entityManager->getRepository(User::class)->find($id_user);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Récupérer les objectifs terminés pour l'utilisateur
        $objectifsTermines = $objectifRepository->findBy(['statut' => 'Terminé', 'user' => $user]);

        // Calculer la somme des points des objectifs terminés
        $totalPoints = array_reduce($objectifsTermines, function ($sum, $objectif) {
            return $sum + $objectif->getNbPts();
        }, 0);

        // Récupérer les récompenses pour l'utilisateur
        $recompenses = $recompenseRepository->findBy(['user' => $user]);

        // Calculer la somme des points des récompenses réclamées
        $pointsRecompensesReclamees = array_reduce($recompenses, function ($sum, $recompense) {
            return $sum + ($recompense->getEtat() === 'réclamé' ? $recompense->getCout() : 0);
        }, 0);

        // Soustraire la somme des points des récompenses réclamées du total des points
        $totalPoints -= $pointsRecompensesReclamees;

        // Passer la somme des points ajustée et les récompenses à la vue
        return $this->render('objectif/recompense.html.twig', [
            'recompenses' => $recompenses,
            'totalPoints' => $totalPoints,
            'id_user' => $id_user  // Ajout de l'ID utilisateur à la vue
        ]);
    }



    #[Route('/objectif/new', name: 'app_objectif_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Crée une nouvelle instance de l'entité Objectif
        $objectif = new Objectif();

        // Crée le formulaire basé sur le type ObjectifType
        $form = $this->createForm(ObjectifType::class, $objectif);
        $form->handleRequest($request);

        // Vérifie si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Persiste l'objectif dans la base de données
            $entityManager->persist($objectif);
            $entityManager->flush();

            // Ajoute un message flash pour indiquer le succès


            // Redirige vers la liste des objectifs ou une autre page
            return $this->redirectToRoute('app_backoffice_page');
        }

        return $this->render('admin/GestionObjectif/objectif/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/backoffice', name: 'app_backoffice_page')]
    public function backPage(EntityManagerInterface $entityManager): Response
    {
        // Récupérer tous les objectifs
        $objectifs = $entityManager->getRepository(Objectif::class)->findAll();

        // Rendu de la vue 'backofficelist.html.twig' avec les objectifs
        return $this->render('admin/gestionObjectif/objectif/backofficelist.html.twig', [
            'objectifs' => $objectifs,
        ]);
    }


    // Edit an objectif
    #[Route('/objectif/edit/{id}', name: 'app_objectif_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'objectif par son id
        $objectif = $entityManager->getRepository(Objectif::class)->find($id);

        // Si l'objectif n'existe pas, afficher une erreur
        if (!$objectif) {
            throw $this->createNotFoundException('Objectif non trouvé');
        }

        // Crée le formulaire pour cet objectif
        $form = $this->createForm(ObjectifType::class, $objectif);
        $form->handleRequest($request);

        // Vérifier si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour l'objectif dans la base de données
            $entityManager->flush();

            // Ajouter un message flash de succès


            // Rediriger vers la liste des objectifs ou une autre page
            return $this->redirectToRoute('app_backoffice_page');
        }

        // Rendre le formulaire dans la vue
        return $this->render('admin/gestionObjectif/objectif/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }



    // Delete an objectif
    #[Route('/objectif/delete/{id}', name: 'app_objectif_delete')]
    public function delete(int $id, EntityManagerInterface $entityManager): Response
    {
        $objectif = $entityManager->getRepository(Objectif::class)->find($id);

        if (!$objectif) {
            throw $this->createNotFoundException('Objectif non trouvé');
        }

        // Remove the objectif
        $entityManager->remove($objectif);
        $entityManager->flush();

        $this->addFlash('success', 'Objectif supprimé avec succès!');
        return $this->redirectToRoute('app_backoffice_page');
    }

    // //affichage de dashboard.html.twig
    // #[Route('/dashboard', name: 'app_dashboard')]
    // public function dashboard(): Response
    // {
    //     return $this->render('admin/dashboard.html.twig');
    // }

    // Route pour annuler un objectif
    #[Route('/suivi/annuler/{id}/{id_user}', name: 'app_annuler', methods: ['POST'])]
    public function annuler(Objectif $objectif, EntityManagerInterface $entityManager, int $id_user): Response
    {
        // Changer le statut de l'objectif en "Non commencé"
        $objectif->setStatut('Non commencé');

        // Sauvegarder la mise à jour avec l'EntityManager
        $entityManager->flush();

        // Rediriger vers la page de suivi de l'utilisateur avec l'ID utilisateur
        return $this->redirectToRoute('app_suivi', ['id_user' => $id_user]);
    }


    // Route pour terminer un objectif
    #[Route('/suivi/terminer/{id}/{id_user}', name: 'app_terminer', methods: ['POST'])]
    public function terminer(Objectif $objectif, EntityManagerInterface $entityManager, int $id_user): Response
    {
        // Changer le statut de l'objectif en "Terminé"
        $objectif->setStatut('Terminé');

        // Sauvegarder la mise à jour avec l'EntityManager
        $entityManager->flush();

        // Rediriger vers la page de suivi de l'utilisateur avec l'ID utilisateur
        return $this->redirectToRoute('app_suivi', ['id_user' => $id_user]);
    }
    #[Route('/recompense/echanger/{id}/{id_user}', name: 'app_echanger_recompense')]
    public function echanger(int $id, int $id_user, ObjectifRepository $objectifRepository, RecompenseRepository $recompenseRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer la récompense
        $recompense = $recompenseRepository->find($id);
        if (!$recompense) {
            throw $this->createNotFoundException('Récompense non trouvée.');
        }

        // Récupérer l'utilisateur
        $user = $entityManager->getRepository(User::class)->find($id_user);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        // Calculer le total des points disponibles
        $objectifsTermines = $objectifRepository->findBy(['statut' => 'Terminé', 'user' => $user]);
        $totalPoints = array_reduce($objectifsTermines, fn($sum, $objectif) => $sum + $objectif->getNbPts(), 0);

        // Récupérer les récompenses déjà réclamées
        $recompenses = $recompenseRepository->findBy(['user' => $user]);
        $pointsRecompensesReclamees = array_reduce($recompenses, fn($sum, $rec) => $sum + ($rec->getEtat() === 'réclamé' ? $rec->getCout() : 0), 0);
        $totalPoints -= $pointsRecompensesReclamees;

        // Vérifier si l'utilisateur a assez de points
        if ($totalPoints >= $recompense->getCout() && $recompense->getEtat() !== 'réclamé') {
            // Mettre à jour l'état de la récompense
            $recompense->setEtat('réclamé');
            $entityManager->persist($recompense);
            $entityManager->flush();

            $this->addFlash('success', 'Récompense échangée avec succès !');

            // Rediriger vers la page de félicitations avec la récompense gagnée
            return $this->redirectToRoute('app_felicitations', [
                'id' => $recompense->getId(),
                'id_user' => $id_user
            ]);
        } else {
            $this->addFlash('error', 'Points insuffisants ou récompense déjà réclamée.');
            return $this->redirectToRoute('app_recompense', ['id_user' => $id_user]);
        }
    }

    #[Route('/felicitations/{id}/{id_user}', name: 'app_felicitations')]
    public function felicitations(int $id, int $id_user, RecompenseRepository $recompenseRepository): Response
    {
        // Récupérer la récompense
        $recompense = $recompenseRepository->find($id);
        if (!$recompense) {
            throw $this->createNotFoundException('Récompense non trouvée.');
        }

        // Générer le PDF si demandé
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1') {
            return $this->generatePdf($recompense, $id_user);
        }

        // Afficher la vue de félicitations normalement
        return $this->render('objectif/felicitation.html.twig', [
            'recompense' => $recompense,
            'id_user' => $id_user,
        ]);
    }

    // Fonction pour générer le PDF
    private function generatePdf($recompense, $id_user): Response
    {
        // Créer une instance de TCPDF
        $pdf = new TCPDF();

        // Définir les propriétés du PDF
        $pdf->SetTitle('Félicitations');
        $pdf->AddPage();

        // Définir la police
        $pdf->SetFont('Helvetica', '', 12);

        // Ajouter le contenu
        $html = $this->renderView('objectif/felicitation.html.twig', [
            'recompense' => $recompense,
            'id_user' => $id_user
        ]);

        // Convertir le HTML en PDF
        $pdf->writeHTML($html);

        // Retourner le PDF en téléchargement
        return new Response(
            $pdf->Output('felicitation.pdf', 'D'),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="felicitation.pdf"',
            ]
        );
    }


}
