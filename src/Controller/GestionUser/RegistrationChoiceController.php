<?php

namespace App\Controller\GestionUser;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationChoiceController extends AbstractController
{
    #[Route('/register', name: 'registration_choice')]
    public function choice(): Response
    {
        return $this->render('gestion_user/registration/registration_choice.html.twig');
    }
}
