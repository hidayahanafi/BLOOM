<?php

namespace App\Controller\Gestionpharmacie;

use App\Entity\Pharmacie;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\PharmacieType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\PharmacieRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\BarChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use Knp\Snappy\Pdf;

final class PharmacieController extends AbstractController{
    #[Route('/home', name: 'home')]
    public function index(): Response
    {
        
        return $this->render('base.html.twig',[
            'controller_name'=>'PharmacieController',
        ]);
    }
    
    #[Route('/pharmacie', name: 'app_pharmacie')]
    public function listph(EntityManagerInterface $entityManager): Response
    {
        $pharmacies = $entityManager->getRepository(Pharmacie::class)->findAll();
        // Encode the logo for each pharmacy
        foreach ($pharmacies as $pharmacie) {
            if ($pharmacie->getLogo()) {
                $logoData = stream_get_contents($pharmacie->getLogo());
                $pharmacie->logoBase64 = base64_encode($logoData); // Add a new property for the base64-encoded logo
            }
        }
        return $this->render('gestionpharmacie/pharmacie/listfront.html.twig', [
            'pharmacies' => $pharmacies,
        ]);
    }

    #[Route('/pharmacie/list', name: 'listph')]
public function listbackph(
    Request $request,
    PharmacieRepository $pharmacieRepository,
    PaginatorInterface $paginator
): Response {
    // Récupérer le terme de recherche
    $searchTerm = $request->query->get('q');

    // Créer une requête de base
    $queryBuilder = $pharmacieRepository->createQueryBuilder('p');

    // Appliquer la recherche si un terme est fourni
    if ($searchTerm) {
        $queryBuilder
            ->where('p.nom LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%');
    }

    // Paginer les résultats
    $pharmacies = $paginator->paginate(
        $queryBuilder->getQuery(), // Requête
        $request->query->getInt('page', 1), // Numéro de page
        10, // Limite par page
        [
            'defaultSortFieldName' => 'p.nom', // Colonne par défaut pour le tri
            'defaultSortDirection' => 'asc', // Ordre par défaut
        ]
    );

    // Encoder les logos en base64
    foreach ($pharmacies as $pharmacie) {
        if ($pharmacie->getLogo()) {
            $logoData = stream_get_contents($pharmacie->getLogo());
            $pharmacie->logoBase64 = base64_encode($logoData);
        }
    }

    // Si c'est une requête AJAX, retourner uniquement le corps du tableau
    if ($request->isXmlHttpRequest()) {
        return $this->render('admin/gestionpharmacie/pharmacie/_table.html.twig', [
            'pharmacies' => $pharmacies,
        ]);
    }

    // Pour les requêtes normales, retourner la page complète
    return $this->render('admin/gestionpharmacie/pharmacie/list.html.twig', [
        'pharmacies' => $pharmacies,
    ]);
}

    
    #[Route('/pharmacie/add', name: 'addph')]
    public function ajouter(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pharmacie = new Pharmacie();
        $form = $this->createForm(PharmacieType::class, $pharmacie);
    
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $logoFile = $form->get('logo')->getData();
    
            if ($logoFile) {
                // Open file and read binary content
                $logoBinary = file_get_contents($logoFile->getPathname());
    
                // Check if binary data was read
                if ($logoBinary === false) {
                    throw new \Exception('Error reading the uploaded file.');
                }
    
                // Store binary data in the entity
                $pharmacie->setLogo($logoBinary);
            }
    
            $entityManager->persist($pharmacie);
            $entityManager->flush();
    
            $this->addFlash('success', 'La pharmacie a été ajoutée avec succès.');
    
            return $this->redirectToRoute('listph');
        }
    
        return $this->render('admin/gestionpharmacie/pharmacie/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/pharmacie/supprimer/{id}', name: 'supprimer_pharmacie', methods: ['POST'])]
public function supprimer(Pharmacie $pharmacie, EntityManagerInterface $entityManager): RedirectResponse
{
    // Check each Medicament to see if it belongs to other Pharmacies
    foreach ($pharmacie->getMedicaments() as $medicament) {
        if ($medicament->getIdPharmacie()->count() === 1) {
            // If the Medicament is only linked to this pharmacy, delete it
            $entityManager->remove($medicament);
        } else {
            // Otherwise, just remove the relationship
            $pharmacie->removeMedicament($medicament);
        }
    }

    // Now, delete the pharmacy
    $entityManager->remove($pharmacie);
    $entityManager->flush();

    $this->addFlash('success', 'La pharmacie et ses médicaments (s\'ils n\'étaient liés à aucune autre pharmacie) ont été supprimés.');

    return $this->redirectToRoute('listph');
}

#[Route('/pharmacie/edit/{id}', name: 'editph')]
public function modifier(Request $request, EntityManagerInterface $entityManager, Pharmacie $pharmacie): Response
{
    // Convertir le logo existant en base64 pour l'affichage dans le template
    $logoBase64 = null;
    if ($pharmacie->getLogo()) {
        $logoBinary = stream_get_contents($pharmacie->getLogo()); // Convertir la ressource en chaîne binaire
        if ($logoBinary !== false) {
            $logoBase64 = base64_encode($logoBinary); // Encoder en base64
        }
    }

    // Créer le formulaire en pré-remplissant les données existantes
    $form = $this->createForm(PharmacieType::class, $pharmacie);

    // Gérer la soumission du formulaire
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Gérer l'upload du nouveau logo (si fourni)
        $logoFile = $form->get('logo')->getData();

        if ($logoFile) {
            // Open file and read binary content
            $logoBinary = file_get_contents($logoFile->getPathname());

            // Check if binary data was read
            if ($logoBinary === false) {
                throw new \Exception('Error reading the uploaded file.');
            }
            $entityManager->persist($pharmacie);
            // Store binary data in the entity
            $pharmacie->setLogo($logoBinary);
        }

        // Enregistrer les modifications en base de données
        $entityManager->flush();

        // Ajouter un message flash de succès
        $this->addFlash('success', 'La pharmacie a été modifiée avec succès.');

        // Rediriger vers la liste des pharmacies
        return $this->redirectToRoute('listph');
    }

    // Afficher le formulaire dans le template
    return $this->render('admin/gestionpharmacie/pharmacie/edit.html.twig', [
        'form' => $form->createView(),
        'pharmacie' => $pharmacie,
        'logoBase64' => $logoBase64, // Passer le logo encodé en base64 au template
    ]);
}
#[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        
        return $this->render('admin/dashboard.html.twig',[
            'controller_name'=>'PharmacieController',
        ]);
    }
    private $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }
        
    #[Route('/pharmacie/statistiques', name: 'pharmacie_statistiques')]
    public function statistiques(PharmacieRepository $pharmacieRepository): Response
    {
        // Récupérer les statistiques
        $statsType = $pharmacieRepository->countByType();
        $statsVille = $pharmacieRepository->countByVille();
    
        return $this->render('admin/gestionpharmacie/pharmacie/statistiques.html.twig', [
            'statsType' => $statsType,
            'statsVille' => $statsVille,
        ]);
    }
    
    #[Route('/pharmacie/pdf', name: 'pharmacie_pdf')]
    public function generatePdf(PharmacieRepository $pharmacieRepository, Pdf $knpSnappyPdf): Response
    {
        // Récupérer toutes les pharmacies
        $pharmacies = $pharmacieRepository->findAll();

        // Rendre le template Twig en HTML
        $html = $this->renderView('admin/gestionpharmacie/pharmacie/pdf_template.html.twig', [
            'pharmacies' => $pharmacies,
        ]);

        // Générer le PDF
        $pdfContent = $knpSnappyPdf->getOutputFromHtml($html);

        // Retourner le PDF en réponse
        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="liste_pharmacies.pdf"',
            ]
        );

    }
}
