<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        $categories = $this->categoryRepository->findAllOrderedByName();

        $products = $this->productRepository->findBy(
            [],
            ['id' => 'DESC'],
            8
        );

        return $this->render('storefront/home.html.twig', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }
}
