<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HelpCenterController extends AbstractController
{
    #[Route('/help', name: 'app_help_center', methods: ['GET'])]
    public function index(
        CategoryRepository $categoryRepository
    ): Response {
        return $this->render('storefront/help.html.twig', [
            'categories' => $categoryRepository->findAllOrderedByName(),
        ]);
    }
}
