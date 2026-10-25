<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrdersController extends AbstractController
{
    #[Route('/orders', name: 'app_orders', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('orders/index.html.twig');
    }
}

