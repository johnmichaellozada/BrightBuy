<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StorefrontCategoryController extends AbstractController
{
    #[Route('/categories', name: 'app_storefront_categories', methods: ['GET'])]
    public function index(
        CategoryRepository $categoryRepository
    ): Response {
        return $this->render('storefront/categories.html.twig', [
            'categories' => $categoryRepository->findAllOrderedByName(),
        ]);
    }
}
