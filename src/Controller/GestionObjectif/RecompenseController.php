<?php
namespace App\Controller\GestionObjectif;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Recompense;
use App\Form\GestionObjectif\RecompenseType;
use App\Repository\RecompenseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecompenseController extends AbstractController
{
    #[Route('/backofficeR', name: 'app_backoffice')]
    public function index(RecompenseRepository $recompenseRepository): Response
    {
        // Récupérer toutes les récompenses de la base de données
        $recompenses = $recompenseRepository->findAll();

        return $this->render('admin/gestionObjectif/recompense/index.html.twig', [
            'recompenses' => $recompenses,
        ]);
    }

    #[Route('admin/recompense/new', name: 'app_recompense_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recompense = new Recompense();
        $form = $this->createForm(RecompenseType::class, $recompense);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour l'état de la récompense avant de la persister
            $recompense->updateEtat();

            // Sauvegarder la récompense dans la base de données
            $entityManager->persist($recompense);
            $entityManager->flush();

            // Message de succès
            $this->addFlash('success', 'Récompense ajoutée avec succès!');

            return $this->redirectToRoute('app_backoffice');
        }

        return $this->render('admin/gestionObjectif/recompense/newR.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('admin/recompense/{id}', name: 'app_recompense_show')]
    public function show(Recompense $recompense): Response
    {
        // Mettre à jour l'état de la récompense avant de la rendre
        $recompense->updateEtat();

        return $this->render('admin/gestionObjectif/recompense/show.html.twig', [
            'recompense' => $recompense,
        ]);
    }

    #[Route('admin/recompense/{id}/edit', name: 'app_recompense_edit')]
    public function edit(Request $request, Recompense $recompense, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecompenseType::class, $recompense);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour l'état de la récompense avant de la persister
            $recompense->updateEtat();

            // Sauvegarder les modifications dans la base de données
            $entityManager->flush();

            // Message de succès
            $this->addFlash('success', 'Récompense modifiée avec succès!');

            return $this->redirectToRoute('app_backoffice');
        }

        return $this->render('admin/gestionObjectif/recompense/edit.html.twig', [
            'form' => $form->createView(),
            'recompense' => $recompense,
        ]);
    }

    #[Route('admin/recompense/{id}/delete', name: 'app_recompense_delete')]
    public function delete(EntityManagerInterface $entityManager, Recompense $recompense): Response
    {
        // Suppression de la récompense
        $entityManager->remove($recompense);
        $entityManager->flush();

        // Message de succès
        $this->addFlash('success', 'La récompense a été supprimée avec succès!');

        return $this->redirectToRoute('app_backoffice');
    }


    //affichage de dashboard.html.twig
}
