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

class PageController extends AbstractController
{
    #[Route('/', name: 'root')]
    #[Route('/home', name: 'home')]
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/menu', name: 'menu')]
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
    public function catLounge(): Response
    {
        return $this->render('catLounge.html.twig');
    }

    #[Route('/ourStory', name: 'our_story')]
    public function ourStory(): Response
    {
        return $this->render('ourStory.html.twig');
    }

    #[Route('/cart/add', name: 'cart_add', methods: ['POST'])]
    public function cartAdd(Request $request, ProductsRepository $productsRepo): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');

        // If no user is logged in, redirect to login page
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

        // When logged in, adding to cart redirects straight to checkout as requested
        return $this->redirectToRoute('checkout');
    }

    #[Route('/cart/update', name: 'cart_update', methods: ['POST'])]
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

    #[Route('/cart', name: 'cart')]
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

    #[Route('/checkout', name: 'checkout')]
    public function checkout(Request $request, ProductsRepository $productsRepo): Response
    {
        $session = $request->getSession();
        if (!$session->get('user_id')) {
            return $this->redirectToRoute('login');
        }

        $cart = $session->get('cart', []);
        if (empty($cart)) {
            return $this->redirectToRoute('menu');
        }

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

        return $this->render('checkout.html.twig', [
            'cart_items' => $cartItems,
            'total_amount' => $totalAmount,
            'total_items' => $totalItems,
        ]);
    }

    #[Route('/order/place', name: 'order_place', methods: ['POST'])]
    public function orderPlace(
        Request $request,
        ProductsRepository $productsRepo,
        UsersRepository $usersRepo,
        EntityManagerInterface $em,
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('login');
        }

        $user = $usersRepo->find($userId);
        $cart = $session->get('cart', []);
        if (!$user || empty($cart)) {
            return $this->redirectToRoute('menu');
        }

        $customerName = trim($request->request->get('customer_name', $user->getName()));
        $customerEmail = trim($request->request->get('customer_email', $user->getEmail()));
        $address = trim($request->request->get('address', 'Café Pickup'));
        $paymentMethod = trim($request->request->get('payment_method', 'Cash on Pickup'));
        $referenceNumber = trim($request->request->get('reference_number', ''));

        $order = new Orders();
        $order->setUser($user);
        $order->setCustomerName($customerName);
        $order->setCustomerEmail($customerEmail);
        $order->setAddress($address);
        $order->setPaymentMethod($paymentMethod);
        $order->setReferenceNumber($referenceNumber ?: null);
        $order->setStatus('Pending');
        $order->setCreatedAt(new \DateTimeImmutable());

        $totalAmount = 0.0;

        foreach ($cart as $pid => $qty) {
            $product = $productsRepo->find($pid);
            if ($product) {
                $unitPrice = (float) $product->getPrice();
                $actualQty = min($qty, $product->getStock());
                if ($actualQty > 0) {
                    $itemSubtotal = $unitPrice * $actualQty;
                    $totalAmount += $itemSubtotal;

                    // Reduce stock in database
                    $newStock = max(0, $product->getStock() - $actualQty);
                    $product->setStock($newStock);

                    $orderItem = new OrderItems();
                    $orderItem->setOrderRef($order);
                    $orderItem->setProduct($product);
                    $orderItem->setProductName($product->getName());
                    $orderItem->setPrice((string) $unitPrice);
                    $orderItem->setQuantity($actualQty);
                    $orderItem->setSubtotal((string) $itemSubtotal);

                    $em->persist($orderItem);
                }
            }
        }

        $order->setTotalAmount((string) $totalAmount);
        $em->persist($order);
        $em->flush();

        // Clear cart after successful checkout
        $session->set('cart', []);

        return $this->redirectToRoute('order_confirmation', ['id' => $order->getId()]);
    }

    #[Route('/orderConfirmation', name: 'order_confirmation')]
    public function orderConfirmation(Request $request, OrdersRepository $ordersRepo): Response
    {
        $id = $request->query->get('id');
        $order = null;
        if ($id) {
            $order = $ordersRepo->find((int) $id);
        }

        return $this->render('orderConfirmation.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/account', name: 'account')]
    #[Route('/orderHistory', name: 'order_history')]
    public function account(
        Request $request,
        UsersRepository $usersRepo,
        OrdersRepository $ordersRepo,
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

    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(Request $request, UsersRepository $usersRepo): Response
    {
        $session = $request->getSession();
        if ($session->get('user_id')) {
            return $this->redirectToRoute('home');
        }

        $error = null;
        $email = '';

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email', ''));
            $password = (string) $request->request->get('password', '');

            if (empty($email) || empty($password)) {
                $error = 'Please fill in all fields.';
            } else {
                $user = $usersRepo->findOneBy(['email' => $email]);
                if ($user && password_verify($password, $user->getPassword())) {
                    $session->set('user_id', $user->getId());
                    $session->set('name', $user->getName());
                    $session->set('email', $user->getEmail());
                    $session->set('role', $user->getRole());

                    return $this->redirectToRoute('home');
                } else {
                    $error = 'Invalid email or password.';
                }
            }
        }

        return $this->render('login.html.twig', [
            'error' => $error,
            'email' => $email,
        ]);
    }

    #[Route('/register', name: 'register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UsersRepository $usersRepo,
        EntityManagerInterface $em,
    ): Response {
        $session = $request->getSession();
        if ($session->get('user_id')) {
            return $this->redirectToRoute('home');
        }

        $error = null;
        $name = '';
        $email = '';

        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name', ''));
            $email = trim((string) $request->request->get('email', ''));
            $password = (string) $request->request->get('password', '');
            $confirmPassword = (string) $request->request->get('confirm_password', '');

            if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
                $error = 'Please fill in all fields.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } elseif ($usersRepo->findOneBy(['name' => $name])) {
                $error = 'Name is already taken.';
            } elseif ($usersRepo->findOneBy(['email' => $email])) {
                $error = 'Email is already registered.';
            } else {
                $user = new Users();
                $user->setName($name);
                $user->setEmail($email);
                $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
                $user->setRole('customer');
                $user->setNotifEmail(true);
                $user->setNotifSite(true);

                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute('login');
            }
        }

        return $this->render('register.html.twig', [
            'error' => $error,
            'name' => $name,
            'email' => $email,
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(Request $request): Response
    {
        $session = $request->getSession();
        $session->clear();

        return $this->redirectToRoute('login');
    }

    #[Route('/admin', name: 'admin_dashboard', methods: ['GET', 'POST'])]
    public function admin(
        Request $request,
        ProductsRepository $productsRepo,
        OrdersRepository $ordersRepo,
        UsersRepository $usersRepo,
        EntityManagerInterface $em,
    ): Response {
        $session = $request->getSession();
        if ($session->get('role') !== 'admin') {
            return $this->redirectToRoute('account');
        }

        $notice = null;
        $error = null;

        // Handle POST actions for Admin Dashboard
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'adjust_stock') {
                $productId = (int) $request->request->get('product_id');
                $adjustType = $request->request->get('adjust_type', 'add');
                $qty = abs((int) $request->request->get('qty', 1));
                if ($qty <= 0) $qty = 1;

                $product = $productsRepo->find($productId);
                if ($product) {
                    $curStock = (int) $product->getStock();
                    $newStock = ($adjustType === 'deduct') ? max(0, $curStock - $qty) : ($curStock + $qty);
                    $product->setStock($newStock);
                    $em->flush();
                    $notice = ($adjustType === 'deduct')
                        ? "Deducted {$qty} unit(s) from '{$product->getName()}'. Current stock: {$newStock}."
                        : "Added {$qty} unit(s) to '{$product->getName()}'. Current stock: {$newStock}.";
                } else {
                    $error = "Product not found.";
                }
            } elseif ($action === 'create_product') {
                $name = trim((string) $request->request->get('name', ''));
                $category = trim((string) $request->request->get('category', 'Hot Drinks'));
                $price = (float) $request->request->get('price', 0);
                $stock = (int) $request->request->get('stock', 0);
                $image = trim((string) $request->request->get('image', 'drink_1.png'));
                $description = trim((string) $request->request->get('description', ''));

                if (!empty($name) && $price >= 0 && $stock >= 0) {
                    $product = new Products();
                    $product->setName($name);
                    $product->setCategory($category);
                    $product->setPrice((string) $price);
                    $product->setStock($stock);
                    $product->setImage($image);
                    $product->setDescription($description);
                    $product->setCreatedAt(new \DateTimeImmutable());
                    $em->persist($product);
                    $em->flush();
                    $notice = "Product '{$name}' created successfully!";
                } else {
                    $error = "Please fill in all required product fields.";
                }
            } elseif ($action === 'update_product') {
                $productId = (int) $request->request->get('product_id');
                $name = trim((string) $request->request->get('name', ''));
                $category = trim((string) $request->request->get('category', 'Hot Drinks'));
                $price = (float) $request->request->get('price', 0);
                $stock = (int) $request->request->get('stock', 0);
                $image = trim((string) $request->request->get('image', 'drink_1.png'));
                $description = trim((string) $request->request->get('description', ''));

                $product = $productsRepo->find($productId);
                if ($product && !empty($name) && $price >= 0 && $stock >= 0) {
                    $product->setName($name);
                    $product->setCategory($category);
                    $product->setPrice((string) $price);
                    $product->setStock($stock);
                    $product->setImage($image);
                    $product->setDescription($description);
                    $em->flush();
                    $notice = "Product #{$productId} updated successfully!";
                } else {
                    $error = "Failed to update product. Please check your inputs.";
                }
            } elseif ($action === 'update_order_status') {
                $orderId = (int) $request->request->get('order_id');
                $newStatus = trim((string) $request->request->get('status', 'Approved'));
                $allowed = ['Approved', 'Cancelled', 'Completed', 'Pending'];

                $order = $ordersRepo->find($orderId);
                if ($order && in_array($newStatus, $allowed, true)) {
                    $order->setStatus($newStatus);
                    $em->flush();
                    $notice = "Order #{$orderId} status set to '{$newStatus}'.";
                } else {
                    $error = "Invalid order or status.";
                }
            } elseif ($action === 'quick_change_role') {
                $targetUid = (int) $request->request->get('user_id');
                $newRole = trim((string) $request->request->get('new_role', 'customer'));
                $currentAdminId = (int) $session->get('user_id');

                if ($targetUid === $currentAdminId && $newRole !== 'admin') {
                    $error = "You cannot demote your own account from admin.";
                } else {
                    $targetUser = $usersRepo->find($targetUid);
                    if ($targetUser && in_array($newRole, ['admin', 'customer'], true)) {
                        $targetUser->setRole($newRole);
                        $em->flush();
                        $notice = "Account #{$targetUid} role updated to '{$newRole}'.";
                    } else {
                        $error = "User not found or invalid role.";
                    }
                }
            } elseif ($action === 'update_user') {
                $targetUid = (int) $request->request->get('user_id');
                $name = trim((string) $request->request->get('name', ''));
                $email = trim((string) $request->request->get('email', ''));
                $role = trim((string) $request->request->get('role', 'customer'));
                $newPass = (string) $request->request->get('new_password', '');
                $currentAdminId = (int) $session->get('user_id');

                $targetUser = $usersRepo->find($targetUid);
                if ($targetUser && !empty($name) && !empty($email)) {
                    if ($targetUid === $currentAdminId && $role !== 'admin') {
                        $error = "You cannot remove your own admin privileges.";
                    } else {
                        $targetUser->setName($name);
                        $targetUser->setEmail($email);
                        $targetUser->setRole($role);
                        if (!empty($newPass)) {
                            $targetUser->setPassword(password_hash($newPass, PASSWORD_DEFAULT));
                        }
                        $em->flush();
                        if ($targetUid === $currentAdminId) {
                            $session->set('name', $name);
                            $session->set('email', $email);
                            $session->set('role', $role);
                        }
                        $notice = "Account #{$targetUid} updated successfully!";
                    }
                } else {
                    $error = "Failed to update account. Please provide valid name and email.";
                }
            }
        }

        // Handle GET actions (delete product, delete user)
        $delId = $request->query->get('delete');
        if ($delId) {
            $delProduct = $productsRepo->find((int) $delId);
            if ($delProduct) {
                try {
                    $em->remove($delProduct);
                    $em->flush();
                    $notice = "Product #{$delId} deleted.";
                } catch (\Throwable $e) {
                    $error = "Could not delete product (it may be referenced by existing orders).";
                }
            }
        }

        $delUserId = $request->query->get('delete_user');
        if ($delUserId) {
            $currentAdminId = (int) $session->get('user_id');
            if ((int) $delUserId === $currentAdminId) {
                $error = "You cannot delete your own admin account.";
            } else {
                $delUser = $usersRepo->find((int) $delUserId);
                if ($delUser) {
                    try {
                        $em->remove($delUser);
                        $em->flush();
                        $notice = "Account #{$delUserId} deleted successfully.";
                    } catch (\Throwable $e) {
                        $error = "Could not delete user: " . $e->getMessage();
                    }
                }
            }
        }

        // Check if editing a product
        $editProduct = null;
        $editId = $request->query->get('edit');
        if ($editId) {
            $editProduct = $productsRepo->find((int) $editId);
        }

        // Check if editing a user
        $editUser = null;
        $editUserParam = $request->query->get('edit_user');
        if ($editUserParam) {
            $editUser = $usersRepo->find((int) $editUserParam);
        }

        // --- Products / Inventory ---
        $allProducts = $productsRepo->findBy([], ['id' => 'ASC']);

        $totalInventoryItems = count($allProducts);
        $totalStockCount     = 0;
        $totalOutOfStock     = 0;
        foreach ($allProducts as $p) {
            $stk = $p->getStock() ?? 0;
            $totalStockCount += $stk;
            if ($stk <= 0) {
                $totalOutOfStock++;
            }
        }

        // --- Orders ---
        $allOrderEntities = $ordersRepo->findBy([], ['id' => 'DESC']);
        $totalOrders      = count($allOrderEntities);
        $totalSales       = array_reduce($allOrderEntities, fn($carry, $o) => $carry + (float) $o->getTotalAmount(), 0.0);

        // Build a flat array with items_summary string for the template
        $ordersWithSummary = [];
        foreach ($allOrderEntities as $ord) {
            $summaryParts = [];
            foreach ($ord->getOrderItems() as $item) {
                $summaryParts[] = $item->getProductName() . ' (x' . $item->getQuantity() . ')';
            }
            $ordersWithSummary[] = [
                'id'               => $ord->getId(),
                'customer_name'    => $ord->getCustomerName(),
                'customer_email'   => $ord->getCustomerEmail(),
                'address'          => $ord->getAddress(),
                'payment_method'   => $ord->getPaymentMethod(),
                'reference_number' => $ord->getReferenceNumber(),
                'total_amount'     => $ord->getTotalAmount(),
                'status'           => $ord->getStatus(),
                'created_at'       => $ord->getCreatedAt(),
                'items_summary'    => implode(', ', $summaryParts) ?: 'None',
            ];
        }

        $ordersSummary = [
            'total_orders' => $totalOrders,
            'total_sales'  => $totalSales,
        ];

        // --- Users ---
        $allUsers        = $usersRepo->findBy([], ['id' => 'ASC']);
        $totalUsersCount = count($allUsers);
        $adminCount      = 0;
        $customerCount   = 0;
        $currentUserId   = (int) $session->get('user_id');

        $usersWithOrderCount = [];
        foreach ($allUsers as $u) {
            if ($u->getRole() === 'admin') {
                $adminCount++;
            } else {
                $customerCount++;
            }
            $usersWithOrderCount[] = [
                'id'          => $u->getId(),
                'name'        => $u->getName(),
                'email'       => $u->getEmail(),
                'role'        => $u->getRole(),
                'is_self'     => ($u->getId() === $currentUserId),
                'order_count' => $u->getOrders()->count(),
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'all_products'         => $allProducts,
            'all_orders'           => $ordersWithSummary,
            'all_users'            => $usersWithOrderCount,
            'edit_product'         => $editProduct,
            'edit_user'            => $editUser,
            'notice'               => $notice,
            'error'                => $error,
            'total_inventory_items'=> $totalInventoryItems,
            'total_stock_count'    => $totalStockCount,
            'total_out_of_stock'   => $totalOutOfStock,
            'orders_summary'       => $ordersSummary,
            'total_users_count'    => $totalUsersCount,
            'admin_count'          => $adminCount,
            'customer_count'       => $customerCount,
        ]);
    }

    #[Route('/api/sidebar/data', name: 'api_sidebar_data', methods: ['GET'])]
    public function getSidebarData(
        Request $request,
        UsersRepository $usersRepo,
        OrdersRepository $ordersRepo,
        CafeSettingsRepository $settingsRepo,
    ): JsonResponse {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return new JsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $user = $usersRepo->find((int) $userId);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        $isAdmin = ($user->getRole() === 'admin');

        // Fetch settings
        $settings = [];
        foreach ($settingsRepo->findAll() as $s) {
            $settings[$s->getSettingKey()] = $s->getSettingValue();
        }

        $supportEmail = $settings['support_email'] ?? 'purr&pour@gmail.com';
        $supportPhone = $settings['support_phone'] ?? '0967 6767 6767';
        $supportHours = $settings['support_hours'] ?? 'Mon - Sun, 8:00 AM - 9:00 PM';

        // Orders list
        $ordersList = [];
        if ($isAdmin) {
            // For admin, show latest orders to approve or reject
            $orders = $ordersRepo->findBy([], ['id' => 'DESC'], 10);
        } else {
            $orders = $ordersRepo->findBy(['user' => $user], ['id' => 'DESC'], 6);
        }

        foreach ($orders as $o) {
            $parts = [];
            foreach ($o->getOrderItems() as $oi) {
                $parts[] = $oi->getProductName() . ' (x' . $oi->getQuantity() . ')';
            }
            $ordersList[] = [
                'id' => $o->getId(),
                'customerName' => $o->getCustomerName(),
                'status' => $o->getStatus(),
                'totalAmount' => (float) $o->getTotalAmount(),
                'itemsSummary' => implode(', ', $parts) ?: 'Items',
                'createdAt' => $o->getCreatedAt()->format('M d, Y h:i A'),
            ];
        }

        // Users list for admin help & support
        $usersList = [];
        if ($isAdmin) {
            foreach ($usersRepo->findBy([], ['id' => 'ASC']) as $u) {
                $usersList[] = [
                    'id' => $u->getId(),
                    'name' => $u->getName(),
                    'email' => $u->getEmail(),
                    'role' => $u->getRole(),
                ];
            }
        }

        return new JsonResponse([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
            ],
            'support' => [
                'email' => $supportEmail,
                'phone' => $supportPhone,
                'hours' => $supportHours,
            ],
            'orders' => $ordersList,
            'users' => $usersList,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/api/sidebar/update-name', name: 'api_sidebar_update_name', methods: ['POST'])]
    public function sidebarUpdateName(
        Request $request,
        UsersRepository $usersRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return new JsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $user = $usersRepo->find((int) $userId);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        $newName = trim((string) $request->request->get('name', ''));
        if (empty($newName)) {
            return new JsonResponse(['success' => false, 'message' => 'Name cannot be empty'], 400);
        }

        $user->setName($newName);
        $em->flush();
        $session->set('name', $newName);

        return new JsonResponse([
            'success' => true,
            'message' => 'Display name updated successfully!',
            'name' => $newName,
        ]);
    }

    #[Route('/api/sidebar/update-support', name: 'api_sidebar_update_support', methods: ['POST'])]
    public function sidebarUpdateSupport(
        Request $request,
        CafeSettingsRepository $settingsRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $session = $request->getSession();
        if ($session->get('role') !== 'admin') {
            return new JsonResponse(['success' => false, 'message' => 'Only admins can modify support information.'], 403);
        }

        $email = trim((string) $request->request->get('email', ''));
        $phone = trim((string) $request->request->get('phone', ''));
        $hours = trim((string) $request->request->get('hours', ''));

        if (empty($email) || empty($phone)) {
            return new JsonResponse(['success' => false, 'message' => 'Email and phone number are required.'], 400);
        }

        $map = [
            'support_email' => $email,
            'support_phone' => $phone,
            'support_hours' => $hours,
        ];

        foreach ($map as $key => $val) {
            $setting = $settingsRepo->find($key);
            if (!$setting) {
                $setting = new CafeSettings();
                $setting->setSettingKey($key);
                $em->persist($setting);
            }
            $setting->setSettingValue($val);
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Help & Support contact info updated successfully!',
            'support' => [
                'email' => $email,
                'phone' => $phone,
                'hours' => $hours,
            ],
        ]);
    }

    #[Route('/api/sidebar/update-order-status', name: 'api_sidebar_update_order_status', methods: ['POST'])]
    public function sidebarUpdateOrderStatus(
        Request $request,
        OrdersRepository $ordersRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $session = $request->getSession();
        if ($session->get('role') !== 'admin') {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $orderId = (int) $request->request->get('order_id');
        $status = trim((string) $request->request->get('status', 'Approved'));
        $allowed = ['Approved', 'Cancelled', 'Completed', 'Pending'];

        if (!in_array($status, $allowed, true)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid status'], 400);
        }

        $order = $ordersRepo->find($orderId);
        if (!$order) {
            return new JsonResponse(['success' => false, 'message' => 'Order not found'], 404);
        }

        $order->setStatus($status);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => "Order #{$orderId} status set to '{$status}'.",
            'order_id' => $orderId,
            'status' => $status,
        ]);
    }

    #[Route('/api/sidebar/edit-user', name: 'api_sidebar_edit_user', methods: ['POST'])]
    public function sidebarEditUser(
        Request $request,
        UsersRepository $usersRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $session = $request->getSession();
        if ($session->get('role') !== 'admin') {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $targetUid = (int) $request->request->get('user_id');
        $name = trim((string) $request->request->get('name', ''));
        $email = trim((string) $request->request->get('email', ''));
        $role = trim((string) $request->request->get('role', 'customer'));
        $currentAdminId = (int) $session->get('user_id');

        $targetUser = $usersRepo->find($targetUid);
        if (!$targetUser || empty($name) || empty($email)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid user or inputs'], 400);
        }

        if ($targetUid === $currentAdminId && $role !== 'admin') {
            return new JsonResponse(['success' => false, 'message' => 'Cannot demote self'], 400);
        }

        $targetUser->setName($name);
        $targetUser->setEmail($email);
        $targetUser->setRole($role);
        $em->flush();

        if ($targetUid === $currentAdminId) {
            $session->set('name', $name);
            $session->set('email', $email);
            $session->set('role', $role);
        }

        return new JsonResponse([
            'success' => true,
            'message' => "Account #{$targetUid} updated successfully!",
        ]);
    }
}
