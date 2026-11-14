<?php

namespace App\Controller;

use App\Entity\OrderItems;
use App\Entity\Orders;
use App\Repository\OrdersRepository;
use App\Repository\ProductsRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/checkout', name: 'checkout')]
    #[Route('/orderController', name: 'order_controller')]
    #[Route('/orderController/checkout', name: 'order_controller_checkout')]
    public function checkout(Request $request, ProductsRepository $productsRepo): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('login');
        }

        $cart = $session->get('cart', []);
        if (empty($cart)) {
            return $this->redirectToRoute('menu');
        }

        $cartItems = [];
        $totalAmount = 0.0;
        foreach ($cart as $pid => $qty) {
            $p = $productsRepo->find($pid);
            if ($p) {
                $subtotal = (float) $p->getPrice() * $qty;
                $totalAmount += $subtotal;
                $cartItems[] = [
                    'product' => $p,
                    'quantity' => $qty,
                    'subtotal' => $subtotal,
                ];
            }
        }

        return $this->render('checkout.html.twig', [
            'cart_items' => $cartItems,
            'total_amount' => $totalAmount,
            'user_name' => $session->get('name', ''),
            'user_email' => $session->get('email', ''),
        ]);
    }

    #[Route('/order/place', name: 'order_place', methods: ['POST'])]
    #[Route('/orderController/place', name: 'order_controller_place', methods: ['POST'])]
    public function orderPlace(
        Request $request,
        UsersRepository $usersRepo,
        ProductsRepository $productsRepo,
        EntityManagerInterface $em
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('login');
        }

        $cart = $session->get('cart', []);
        if (empty($cart)) {
            return $this->redirectToRoute('menu');
        }

        $user = $usersRepo->find($userId);
        $customerName = trim((string) $request->request->get('customer_name', $session->get('name', 'Customer')));
        $email = trim((string) $request->request->get('email', $session->get('email', '')));
        $phone = trim((string) $request->request->get('phone', ''));
        $address = trim((string) $request->request->get('address', ''));
        $paymentMethod = $request->request->get('payment_method', 'Cash on Delivery');
        $referenceNumber = trim((string) $request->request->get('reference_number', ''));

        $order = new Orders();
        $order->setUser($user);
        $order->setCustomerName($customerName);
        $order->setEmail($email);
        $order->setPhone($phone);
        $order->setAddress($address);
        $order->setPaymentMethod($paymentMethod);
        $order->setReferenceNumber($referenceNumber ?: null);
        $order->setStatus('Pending');
        $order->setCreatedAt(new \DateTime());

        $totalAmount = 0.0;
        foreach ($cart as $pid => $qty) {
            $p = $productsRepo->find($pid);
            if ($p) {
                $itemPrice = (float) $p->getPrice();
                $subtotal = $itemPrice * $qty;
                $totalAmount += $subtotal;

                $orderItem = new OrderItems();
                $orderItem->setOrder($order);
                $orderItem->setProduct($p);
                $orderItem->setProductName($p->getName());
                $orderItem->setQuantity($qty);
                $orderItem->setPrice($itemPrice);
                $orderItem->setSubtotal($subtotal);

                $newStock = max(0, $p->getStock() - $qty);
                $p->setStock($newStock);

                $em->persist($orderItem);
            }
        }

        $order->setTotalAmount($totalAmount);
        $em->persist($order);
        $em->flush();

        $session->set('cart', []);

        return $this->redirectToRoute('order_confirmation', ['id' => $order->getId()]);
    }

    #[Route('/orderConfirmation', name: 'order_confirmation')]
    #[Route('/orderController/orderConfirmation', name: 'order_controller_order_confirmation')]
    public function orderConfirmation(Request $request, OrdersRepository $ordersRepo): Response
    {
        $session = $request->getSession();
        $orderId = (int) $request->query->get('id', $request->query->get('order_id'));
        $order = $ordersRepo->find($orderId);

        if (!$order) {
            return $this->redirectToRoute('home');
        }

        return $this->render('orderConfirmation.html.twig', [
            'order' => $order,
        ]);
    }
}
