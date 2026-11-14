<?php

namespace App\Controller;

use App\Repository\OrdersRepository;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    #[Route('/account', name: 'account')]
    #[Route('/orderHistory', name: 'order_history')]
    #[Route('/accountController', name: 'account_controller')]
    #[Route('/accountController/orderHistory', name: 'account_controller_order_history')]
    public function account(
        Request $request,
        UsersRepository $usersRepo,
        OrdersRepository $ordersRepo
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('login');
        }

        $user = $usersRepo->find($userId);
        if (!$user) {
            $session->clear();
            return $this->redirectToRoute('login');
        }

        $allOrders = $ordersRepo->findBy(['user' => $user], ['id' => 'DESC']);

        $totalOrders = count($allOrders);
        $totalSpent = 0.0;
        $pendingCount = 0;
        $approvedCount = 0;
        $cancelledCount = 0;

        $ordersView = [];
        foreach ($allOrders as $ord) {
            $totalSpent += (float) $ord->getTotalAmount();
            $st = $ord->getStatus();
            if ($st === 'Approved' || $st === 'Confirmed') {
                $approvedCount++;
            } elseif ($st === 'Pending') {
                $pendingCount++;
            } elseif ($st === 'Cancelled') {
                $cancelledCount++;
            }

            $itemsParts = [];
            foreach ($ord->getOrderItems() as $item) {
                $itemsParts[] = $item->getProductName() . ' (x' . $item->getQuantity() . ')';
            }

            $ordersView[] = [
                'id' => $ord->getId(),
                'createdAt' => $ord->getCreatedAt(),
                'itemsSummary' => implode(', ', $itemsParts) ?: 'Order items',
                'paymentMethod' => $ord->getPaymentMethod(),
                'referenceNumber' => $ord->getReferenceNumber(),
                'totalAmount' => $ord->getTotalAmount(),
                'status' => $ord->getStatus(),
            ];
        }

        return $this->render('account.html.twig', [
            'user' => $user,
            'orders' => $ordersView,
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'cancelled_count' => $cancelledCount,
        ]);
    }
}
