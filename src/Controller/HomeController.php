<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\FormPosteType;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->create(FormPosteType::class);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'postForm' => $form->createView(), // ✅ Now postForm is always available
        ]);
    }

    #[Route('/about', name: 'about')]
    public function about(FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->create(FormPosteType::class);
        return $this->render('about/about.html.twig', [
            'postForm' => $form->createView(),
        ]);
    }

    #[Route('/contact', name: 'contact')]
    public function contact(FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->create(FormPosteType::class);
        return $this->render('contact/contact.html.twig', [
            'postForm' => $form->createView(),
        ]);
    }

    #[Route('/services', name: 'services')]
    public function services(FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->create(FormPosteType::class);
        return $this->render('services/services.html.twig', [
            'postForm' => $form->createView(),
        ]);
    }
}
