<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $name = 'Alexis';

        return $this->render('main/index.html.twig', [
            'name' => $name,
        ]);
    }
}
