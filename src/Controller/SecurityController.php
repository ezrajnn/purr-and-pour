<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    #[Route('/securityController', name: 'security_controller', methods: ['GET', 'POST'])]
    #[Route('/securityController/login', name: 'security_controller_login', methods: ['GET', 'POST'])]
    public function login(Request $request, UsersRepository $usersRepo): Response
    {
        $session = $request->getSession();

        if ($session->get('user_id')) {
            return $this->redirectToRoute('home');
        }

        $error = null;
        $email = '';

        if ($request->query->get('error') === 'login_required') {
            $error = 'Please log in to add items to your cart.';
        }

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email', ''));
            $password = (string) $request->request->get('password', '');

            if (empty($email) || empty($password)) {
                $error = 'Please fill in all fields.';
            } else {
                $user = $usersRepo->findOneBy(['email' => $email]);

                if (!$user) {
                    $error = 'Email not found.';
                } elseif (!password_verify($password, $user->getPassword())) {
                    $error = 'Incorrect password.';
                } else {
                    $session->set('user_id', $user->getId());
                    $session->set('name', $user->getName());
                    $session->set('email', $user->getEmail());
                    $session->set('role', $user->getRole());

                    if ($user->getRole() === 'admin') {
                        return $this->redirectToRoute('admin_dashboard');
                    }

                    return $this->redirectToRoute('home');
                }
            }
        }

        return $this->render('login.html.twig', [
            'error' => $error,
            'email' => $email,
        ]);
    }

    #[Route('/register', name: 'register', methods: ['GET', 'POST'])]
    #[Route('/securityController/register', name: 'security_controller_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UsersRepository $usersRepo,
        EntityManagerInterface $em
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
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long.';
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
    #[Route('/securityController/logout', name: 'security_controller_logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->clear();
        return $this->redirectToRoute('home');
    }
}
