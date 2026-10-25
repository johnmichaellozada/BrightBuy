<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PaymentsController extends AbstractController
{
    #[Route('/payments', name: 'app_payments', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('payments/index.html.twig');
    }
}

