<?php

namespace App\Controller;

use App\Entity\CafeSettings;
use App\Entity\OrderItems;
use App\Entity\Orders;
use App\Entity\Products;
use App\Entity\Users;
use App\Repository\CafeSettingsRepository;
use App\Repository\OrdersRepository;
use App\Repository\ProductsRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'root')]
    #[Route('/home', name: 'home')]
    #[Route('/homeController', name: 'home_controller')]
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/menu', name: 'menu')]
    #[Route('/homeController/menu', name: 'home_controller_menu')]
    public function menu(ProductsRepository $productsRepo): Response
    {
        $allProducts = $productsRepo->findBy([], ['id' => 'ASC']);
        $categories = [];
        foreach ($allProducts as $p) {
            $cat = $p->getCategory() ?: 'Specialties';
            $categories[$cat][] = $p;
        }

        return $this->render('menu.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/catLounge', name: 'cat_lounge')]
    #[Route('/homeController/catLounge', name: 'home_controller_cat_lounge')]
    public function catLounge(): Response
    {
        return $this->render('catLounge.html.twig');
    }

    #[Route('/ourStory', name: 'our_story')]
    #[Route('/homeController/ourStory', name: 'home_controller_our_story')]
    public function ourStory(): Response
    {
        return $this->render('ourStory.html.twig');
    }
}
