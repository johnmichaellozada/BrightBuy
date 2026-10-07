<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductsController extends AbstractController
{
    #[Route('/admin/products', name: 'admin_products')]
    public function index(): Response
    {
        return $this->render('admin/products/index.html.twig');
    }
}