<?php

namespace App\Controller;

use App\Entity\CafeSettings;
use App\Entity\Products;
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

class AdminDashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET', 'POST'])]
    #[Route('/adminDashboardController', name: 'admin_dashboard_controller', methods: ['GET', 'POST'])]
    public function admin(
        Request $request,
        ProductsRepository $productsRepo,
        UsersRepository $usersRepo,
        OrdersRepository $ordersRepo,
        EntityManagerInterface $em
    ): Response {
        $session = $request->getSession();
        if ($session->get('role') !== 'admin') {
            return $this->redirectToRoute('home');
        }

        $allProducts = $productsRepo->findBy([], ['id' => 'DESC']);
        $allOrders = $ordersRepo->findBy([], ['id' => 'DESC']);
        $allUsers = $usersRepo->findBy([], ['id' => 'ASC']);

        $totalSales = 0.0;
        foreach ($allOrders as $o) {
            $totalSales += (float) $o->getTotalAmount();
        }

        return $this->render('admin/dashboard.html.twig', [
            'total_sales' => $totalSales,
            'orders' => $allOrders,
            'products' => $allProducts,
            'users' => $allUsers,
        ]);
    }
}
