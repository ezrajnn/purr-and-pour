<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function menu(): Response
    {
        return $this->render('menu.html.twig');
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

    #[Route('/cart', name: 'cart')]
    public function cart(): Response
    {
        return $this->render('cart.html.twig');
    }

    #[Route('/checkout', name: 'checkout')]
    public function checkout(): Response
    {
        return $this->render('checkout.html.twig');
    }

    #[Route('/orderConfirmation', name: 'order_confirmation')]
    public function orderConfirmation(): Response
    {
        return $this->render('orderConfirmation.html.twig');
    }

    #[Route('/account', name: 'account')]
    #[Route('/orderHistory', name: 'order_history')]
    public function account(): Response
    {
        return $this->render('account.html.twig');
    }

    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        return $this->render('login.html.twig');
    }

    #[Route('/register', name: 'register')]
    public function register(): Response
    {
        return $this->render('register.html.twig');
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function admin(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }
}
