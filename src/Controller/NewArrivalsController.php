<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NewArrivalsController extends AbstractController
{
    #[Route('/new-arrivals', name: 'app_new_arrivals', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $products = $productRepository->createQueryBuilder('p')
            ->andWhere('p.stock > 0')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

        return $this->render('storefront/new_arrivals.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAllOrderedByName(),
        ]);
    }
}
