<?php

namespace App\Controller\GestionEvent;

use App\Entity\Cour;
use App\Form\CourType;
use App\Repository\CourRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CourController extends AbstractController
{
    #[Route('/cour_front/{id}', name: "cour_front")]
    public function listCours(CourRepository $repo, int $id): Response
    {
        $cours = $repo->findBy(['Event' => $id]);
    
        return $this->render("gestionEvents/cour/cour_front.html.twig", [
            "cours" => $cours
        ]);
    }
    
    #[Route('/admin/cour/list', name: "app_cour_list")]
    public function listCour(CourRepository $repo): Response
    {
        $cours = $repo->findAll();

        return $this->render("admin/GestionEvents/cour/list.html.twig", [
            "list" => $cours
        ]);
    }
    #[Route('/admin/cour', name: 'app_cour_add')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $cour = new Cour();
        $form = $this->createForm(CourType::class, $cour);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($cour);
            $em->flush();
            $this->addFlash('success', 'Cours ajouté avec succès.');
    
            return $this->redirectToRoute('app_cour_list');
        }
    
        return $this->render('admin/GestionEvents/cour/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
     
    #[Route('/admin/cour/edit/{id}', name: 'app_cour_edit')]
    public function edit(Request $request, Cour $cour, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CourType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Cour modifié avec succès.');

            return $this->redirectToRoute('app_cour_list');
        }

        return $this->render('admin/GestionEvents/cour/edit.html.twig', [
            'form' => $form->createView(),
            'cour' => $cour,
        ]);
    }
    #[Route('/admin/cour/delete/{id}', name: 'app_cour_delete')]
    public function delete(Cour $cour, EntityManagerInterface $em): Response
    {
        $em->remove($cour);
        $em->flush();
        $this->addFlash('success', 'cour supprimé avec succès.');
    
        return $this->redirectToRoute('app_cour_list');
    }
}
