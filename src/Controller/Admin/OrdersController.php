<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrdersController extends AbstractController
{
    #[Route('/admin/orders', name: 'admin_orders')]
    public function index(): Response
    {
        return $this->render('admin/orders/index.html.twig');
    }
}