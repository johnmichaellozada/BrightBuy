<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DealsController extends AbstractController
{
    #[Route('/deals', name: 'app_deals', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $products = $productRepository->createQueryBuilder('p')
            ->andWhere('p.stock > 0')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();

        return $this->render('storefront/deals.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAllOrderedByName(),
        ]);
    }
}
