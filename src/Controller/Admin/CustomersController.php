<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomersController extends AbstractController
{
    #[Route('/admin/customers', name: 'admin_customers')]
    public function index(): Response
    {
        return $this->render('admin/customers/index.html.twig');
    }
}