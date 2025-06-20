<?php

namespace App\Controller\Gestionpharmacie;

use App\Entity\Panier;
use App\Form\PanierType;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Security;
use App\Entity\Medicament;

final class PanierController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/panier', name: 'app_panier')]
    public function index(PanierRepository $panierRepository): Response
    {
        // Get the current user
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Fetch paniers for the current user
        $paniers = $panierRepository->findBy(['id_user' => $user]);

        return $this->render('gestionpharmacie/panier/index.html.twig', [
            'paniers' => $paniers,
        ]);
    }

    #[Route('/panier/add', name: 'add_panier')]
public function addPanier(Request $request, EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $panierData = $request->request->get('panier_data');
    if (!$panierData) {
        return new Response('panier_data is missing.', Response::HTTP_BAD_REQUEST);
    }

    $panierData = json_decode($panierData, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($panierData)) {
        return new Response('Invalid JSON format.', Response::HTTP_BAD_REQUEST);
    }

    // Create a new Panier
    $panier = new Panier();
    $panier->setDateAjout(new \DateTimeImmutable());
    $panier->setIdUser($user);

    $medicamentQuantities = [];

    foreach ($panierData as $item) {
        $medicament = $entityManager->getRepository(Medicament::class)->find($item['id']);
        if ($medicament) {
            $quantity = (int) $item['quantity'];
            if ($medicament->getQuantite() < $quantity) {
                return new Response("Stock insuffisant pour {$medicament->getNom()}", Response::HTTP_BAD_REQUEST);
            }

            // Update stock
            $medicament->setQuantite($medicament->getQuantite() - $quantity);

            // Add medicament to Panier
            $panier->addMedicament($medicament);
            $medicamentQuantities[$medicament->getId()] = $quantity;
        }
    }

    $panier->setMedicamentQuantities($medicamentQuantities);

    $entityManager->persist($panier);
    $entityManager->flush();

    return $this->redirectToRoute('app_panier');
}

    

}