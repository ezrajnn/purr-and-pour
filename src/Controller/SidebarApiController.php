<?php

namespace App\Controller;

use App\Entity\CafeSettings;
use App\Repository\CafeSettingsRepository;
use App\Repository\OrdersRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/sidebar')]
#[Route('/sidebarApiController')]
class SidebarApiController extends AbstractController
{
    #[Route('/data', name: 'api_sidebar_data', methods: ['GET'])]
    public function getSidebarData(
        Request $request,
        UsersRepository $usersRepo,
        OrdersRepository $ordersRepo,
        CafeSettingsRepository $settingsRepo
    ): JsonResponse {
        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            return new JsonResponse(['logged_in' => false]);
        }

        $user = $usersRepo->find($userId);
        if (!$user) {
            return new JsonResponse(['logged_in' => false]);
        }

        $isAdmin = ($user->getRole() === 'admin');

        $supportSettings = [
            'email' => 'purr&pour@gmail.com',
            'phone' => '0967 6767 6767',
            'hours' => 'Mon - Sun, 8:00 AM - 9:00 PM',
        ];

        $settings = $settingsRepo->findAll();
        foreach ($settings as $s) {
            if ($s->getSettingKey() === 'support_email') {
                $supportSettings['email'] = $s->getSettingValue();
            }
            if ($s->getSettingKey() === 'support_phone') {
                $supportSettings['phone'] = $s->getSettingValue();
            }
            if ($s->getSettingKey() === 'support_hours') {
                $supportSettings['hours'] = $s->getSettingValue();
            }
        }

        if ($isAdmin) {
            $orders = $ordersRepo->findBy([], ['id' => 'DESC'], 6);
        } else {
            $orders = $ordersRepo->findBy(['user' => $user], ['id' => 'DESC'], 5);
        }

        $ordersList = [];
        $pendingCount = 0;
        foreach ($orders as $o) {
            if ($o->getStatus() === 'Pending') {
                $pendingCount++;
            }

            $itemsSummary = [];
            foreach ($o->getOrderItems() as $oi) {
                $itemsSummary[] = $oi->getProductName() . ' (x' . $oi->getQuantity() . ')';
            }

            $ordersList[] = [
                'id' => $o->getId(),
                'customerName' => $o->getCustomerName(),
                'status' => $o->getStatus(),
                'itemsSummary' => implode(', ', $itemsSummary) ?: 'Café items',
                'totalAmount' => $o->getTotalAmount(),
                'createdAt' => $o->getCreatedAt()->format('M d, Y h:i A'),
            ];
        }

        $accountsList = [];
        if ($isAdmin) {
            $allUsers = $usersRepo->findBy([], ['id' => 'ASC'], 25);
            foreach ($allUsers as $u) {
                $accountsList[] = [
                    'id' => $u->getId(),
                    'name' => $u->getName(),
                    'email' => $u->getEmail(),
                    'role' => $u->getRole(),
                ];
            }
        }

        return new JsonResponse([
            'logged_in' => true,
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'avatar' => $user->getAvatar(),
                'notif_email' => $user->isNotifEmail(),
                'notif_site' => $user->isNotifSite(),
            ],
            'is_admin' => $isAdmin,
            'support' => $supportSettings,
            'orders' => $ordersList,
            'pending_count' => $pendingCount,
            'accounts' => $accountsList,
        ]);
    }

    #[Route('/update-name', name: 'api_sidebar_update_name', methods: ['POST'])]
    public function sidebarUpdateName(
        Request $request,
        UsersRepository $usersRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return new JsonResponse(['success' => false, 'message' => 'Please log in.'], 401);
        }

        $newName = trim((string) $request->request->get('name', ''));
        if (empty($newName)) {
            return new JsonResponse(['success' => false, 'message' => 'Name cannot be empty.'], 400);
        }

        $user = $usersRepo->find($userId);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found.'], 404);
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

    #[Route('/update-support', name: 'api_sidebar_update_support', methods: ['POST'])]
    public function sidebarUpdateSupport(
        Request $request,
        CafeSettingsRepository $settingsRepo,
        EntityManagerInterface $em
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

    #[Route('/update-order-status', name: 'api_sidebar_update_order_status', methods: ['POST'])]
    public function sidebarUpdateOrderStatus(
        Request $request,
        OrdersRepository $ordersRepo,
        EntityManagerInterface $em
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

    #[Route('/edit-user', name: 'api_sidebar_edit_user', methods: ['POST'])]
    public function sidebarEditUser(
        Request $request,
        UsersRepository $usersRepo,
        EntityManagerInterface $em
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
