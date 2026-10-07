<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PaymentsController extends AbstractController
{
    #[Route('/admin/payments', name: 'admin_payments')]
    public function index(): Response
    {
        return $this->render('admin/payments/index.html.twig');
    }
}