<?php

namespace App\Controller\Gestionpharmacie;

use App\Entity\Medicament;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MedicamentRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Form\MedicamentType;
use App\Entity\Pharmacie;

final class MedicamentController extends AbstractController{
    #[Route('/medicament', name: 'app_medicament')]
    public function index(): Response
    {
        return $this->render('medicament/listmed.html.twig', [
            'medicaments' => 'MedicamentController',
        ]);
    }
    #[Route('/listmed/{pharmacieId}', name: 'listmed')]
    public function listmed(int $pharmacieId, EntityManagerInterface $entityManager): Response
    {
        // Récupérer la pharmacie par son ID
        $pharmacie = $entityManager->getRepository(Pharmacie::class)->find($pharmacieId);
    
        if (!$pharmacie) {
            throw $this->createNotFoundException('Pharmacie non trouvée');
        }
    
        // Récupérer les médicaments associés à cette pharmacie (via la relation ManyToMany)
        $medicaments = $pharmacie->getMedicaments();
    
        // Traitement de l'image (si nécessaire)
        foreach ($medicaments as $medicament) {
            if ($medicament->getImageurl()) {
                // Si imageurl est une ressource (BLOB), convertir en base64
                if (is_resource($medicament->getImageurl())) {
                    $medicament->base64Image = 'data:image/jpeg;base64,' . base64_encode(stream_get_contents($medicament->getImageurl()));
                } else {
                    // Si c'est déjà une chaîne (chemin ou URL)
                    $medicament->base64Image = $medicament->getImageurl();
                }
            }
        }
    
        return $this->render('gestionpharmacie/medicament/listmed.html.twig', [
            'medicaments' => $medicaments,
            'pharmacie' => $pharmacie, // Optionnel : passer la pharmacie au template
        ]);
    }
    #[Route('/medicament/addmed', name: 'addmed')]
    public function ajouter(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Retrieve pharmacieId from the query parameters
        $pharmacieId = $request->query->get('pharmacieId');
    
        if (empty($pharmacieId)) {
            throw new \Exception('Pharmacie ID is missing.');
        }
    
        // Fetch the pharmacie entity
        $pharmacie = $entityManager->getRepository(Pharmacie::class)->find($pharmacieId);
    
        if (!$pharmacie) {
            throw new \Exception('Pharmacie not found.');
        }
    
        // Create a new Medicament
        $medicament = new Medicament();
    
        // Add the pharmacie to the medicament's collection
        $medicament->addIdPharmacie($pharmacie);
    
        $form = $this->createForm(MedicamentType::class, $medicament);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle the image upload if a file was provided
            $file = $form->get('imageurl')->getData();
            if ($file) {
                // Get the file content and store it as a BLOB in the database
                $imageData = file_get_contents($file->getPathname());
                $medicament->setImageurl($imageData);
            }
    
            // Persist the Medicament entity in the database
            $entityManager->persist($medicament);
            $entityManager->flush();
    
            $this->addFlash('success', 'Le médicament a été ajouté avec succès.');
    
            // Redirect to the listmedback route with the required pharmacieId
            return $this->redirectToRoute('listmedback', [
                'pharmacieId' => $pharmacieId,
            ]);
        }
    
        return $this->render('admin/gestionpharmacie/medicament/add.html.twig', [
            'formmed' => $form->createView(),
            'pharmacieId' => $pharmacieId, // Pass pharmacieId to the template

        ]);
    }
#[Route('/listmedback/{pharmacieId}', name: 'listmedback')]
public function listmedback(int $pharmacieId, EntityManagerInterface $entityManager): Response
{
    // Récupérer la pharmacie par son ID
    $pharmacie = $entityManager->getRepository(Pharmacie::class)->find($pharmacieId);

    if (!$pharmacie) {
        throw $this->createNotFoundException('Pharmacie non trouvée');
    }

    // Récupérer les médicaments associés à cette pharmacie
    $medicaments = $pharmacie->getMedicaments();

    // Traitement de l'image (si nécessaire)
    foreach ($medicaments as $medicament) {
        if ($medicament->getImageurl()) {
            // Si imageurl est une ressource (BLOB), convertir en base64
            if (is_resource($medicament->getImageurl())) {
                $medicament->base64Image = 'data:image/jpeg;base64,' . base64_encode(stream_get_contents($medicament->getImageurl()));
            } else {
                // Si c'est déjà une chaîne (chemin ou URL)
                $medicament->base64Image = $medicament->getImageurl();
            }
        }
    }

    return $this->render('admin/gestionpharmacie/medicament/list.html.twig', [
        'medicaments' => $medicaments,
        'pharmacie' => $pharmacie, // Optionnel : passer la pharmacie au template
    ]);
}#[Route('/medicament/edit/{id}', name: 'editmed')]
public function edit(Request $request, EntityManagerInterface $entityManager, Medicament $medicament): Response
{
    $form = $this->createForm(MedicamentType::class, $medicament);
    $form->handleRequest($request);

    // Convert existing image to base64 for display
    $imageBase64 = null;
    if ($medicament->getImageurl()) {
        $imageBase64 = base64_encode(stream_get_contents($medicament->getImageurl()));
    }

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('imageurl')->getData();

        if ($imageFile) {
            $medicament->setImageurl(file_get_contents($imageFile->getPathname()));
        }

        $entityManager->flush();
        $this->addFlash('success', 'Le médicament a été mis à jour.');

        return $this->redirectToRoute('listmedback', ['pharmacieId' => $medicament->getIdPharmacie()->first()->getId()]);
    }

    return $this->render('admin/gestionpharmacie/medicament/edit.html.twig', [
        'form' => $form->createView(),
        'medicament' => $medicament,
        'imageBase64' => $imageBase64,
    ]);
}

#[Route('/medicament/delete/{id}', name: 'deletemed', methods: ['POST', 'GET'])]
public function delete(EntityManagerInterface $entityManager, Medicament $medicament): Response
{
    $pharmacieId = $medicament->getIdPharmacie()->first()->getId(); // Get associated pharmacy ID

    $entityManager->remove($medicament);
    $entityManager->flush();

    $this->addFlash('success', 'Le médicament a été supprimé avec succès.');

    return $this->redirectToRoute('listmedback', ['pharmacieId' => $pharmacieId]);
}

}
