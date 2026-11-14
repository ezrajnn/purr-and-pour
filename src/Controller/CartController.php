<?php

namespace App\Controller;

use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'cart')]
    #[Route('/cartController', name: 'cart_controller')]
    #[Route('/cartController/cart', name: 'cart_controller_cart')]
    public function cart(Request $request, ProductsRepository $productsRepo): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);

        $cartItems = [];
        $totalAmount = 0.0;
        $totalItems = 0;

        foreach ($cart as $pid => $qty) {
            $p = $productsRepo->find($pid);
            if ($p) {
                $subtotal = (float) $p->getPrice() * $qty;
                $totalAmount += $subtotal;
                $totalItems += $qty;
                $cartItems[] = [
                    'product' => $p,
                    'quantity' => $qty,
                    'subtotal' => $subtotal,
                ];
            }
        }

        return $this->render('cart.html.twig', [
            'cart_items' => $cartItems,
            'total_amount' => $totalAmount,
            'total_items' => $totalItems,
        ]);
    }

    #[Route('/cart/add', name: 'cart_add', methods: ['POST'])]
    #[Route('/cartController/add', name: 'cart_controller_add', methods: ['POST'])]
    public function cartAdd(Request $request, ProductsRepository $productsRepo): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('login');
        }

        $productId = (int) $request->request->get('product_id');
        $quantity = max(1, (int) $request->request->get('quantity', 1));

        $product = $productsRepo->find($productId);
        if ($product && $product->getStock() > 0) {
            $cart = $session->get('cart', []);
            $currentQty = $cart[$productId] ?? 0;
            $newQty = min($product->getStock(), $currentQty + $quantity);
            $cart[$productId] = $newQty;
            $session->set('cart', $cart);
        }

        return $this->redirectToRoute('checkout');
    }

    #[Route('/cart/update', name: 'cart_update', methods: ['POST'])]
    #[Route('/cartController/update', name: 'cart_controller_update', methods: ['POST'])]
    public function cartUpdate(Request $request, ProductsRepository $productsRepo): Response
    {
        $session = $request->getSession();
        $productId = (int) $request->request->get('product_id');
        $action = $request->request->get('action');

        $cart = $session->get('cart', []);
        if (isset($cart[$productId])) {
            $product = $productsRepo->find($productId);
            if ($action === 'increase') {
                $maxStock = $product ? $product->getStock() : 99;
                if ($cart[$productId] < $maxStock) {
                    $cart[$productId]++;
                }
            } elseif ($action === 'decrease') {
                $cart[$productId]--;
                if ($cart[$productId] <= 0) {
                    unset($cart[$productId]);
                }
            }
            $session->set('cart', $cart);
        }

        return $this->redirectToRoute('cart');
    }

    #[Route('/cart/remove', name: 'cart_remove', methods: ['POST'])]
    #[Route('/cartController/remove', name: 'cart_controller_remove', methods: ['POST'])]
    public function cartRemove(Request $request): Response
    {
        $session = $request->getSession();
        $productId = (int) $request->request->get('product_id');

        $cart = $session->get('cart', []);
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $session->set('cart', $cart);
        }

        return $this->redirectToRoute('cart');
    }
}
