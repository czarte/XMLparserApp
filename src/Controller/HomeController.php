<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route("/")]
    public function load(): Response
    {
        return
            $this->render('project/index.html.twig', [
                'controller_name' => 'XML Parser'
            ]);
    }
}
    