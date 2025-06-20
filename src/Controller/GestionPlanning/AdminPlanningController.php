<?php
namespace App\Controller\GestionPlanning;

use App\Entity\Planning;
use App\Form\PlanningType;
use App\Repository\PlanningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;  // Import Security

#[Route('/admin/planning')]
class AdminPlanningController extends AbstractController
{
    private Security $security;

    // Inject Security service into the controller
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'admin_planning_index', methods: ['GET'])]
    public function index(PlanningRepository $planningRepository): Response
    {
        return $this->render('admin/GestionPlanning/planning/index.html.twig', [
            'plannings' => $planningRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_planning_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $isAdmin = $this->security->isGranted('ROLE_ADMIN'); // Check if the user has admin role

        $planning = new Planning();
        // Create the form with the 'show_doctor' option passed based on the user's role
        $form = $this->createForm(PlanningType::class, $planning, [
            'show_doctor' => $isAdmin,  // Pass 'true' if admin, 'false' otherwise
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($planning);
            $em->flush();

            return $this->redirectToRoute('admin_planning_index');
        }

        return $this->render('admin/GestionPlanning/planning/new.html.twig', [
            'planning' => $planning,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_planning_show', methods: ['GET'])]
    public function show(Planning $planning): Response
    {
        return $this->render('admin/GestionPlanning/planning/show.html.twig', [
            'planning' => $planning,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_planning_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Planning $planning, EntityManagerInterface $em): Response
    {
        
        // Pass the 'show_doctor' option to the form based on the user's role
        $form = $this->createForm(PlanningType::class, $planning);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('admin_planning_index');
        }

        return $this->render('admin/GestionPlanning/planning/edit.html.twig', [
            'planning' => $planning,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_planning_delete', methods: ['POST'])]
    public function delete(Request $request, Planning $planning, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $planning->getId(), $request->request->get('_token'))) {
            $em->remove($planning);
            $em->flush();
        }

        return $this->redirectToRoute('admin_planning_index');
    }
}
