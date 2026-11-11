<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SidebarApiController extends AbstractController
{
    #[Route('/sidebar/api', name: 'app_sidebar_api')]
    public function index(): Response
    {
        return $this->render('sidebar_api/index.html.twig', [
            'controller_name' => 'SidebarApiController',
        ]);
    }
}
