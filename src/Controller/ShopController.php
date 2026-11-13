<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShopController extends AbstractController
{
    #[Route('/shop', name: 'app_shop', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $search = trim($request->query->get('search', ''));
        $categoryId = $request->query->get('category');

        $selectedCategory = null;

        if ($categoryId !== null && ctype_digit((string) $categoryId)) {
            $selectedCategory = (int) $categoryId;
        }

        if ($search !== '' && $selectedCategory !== null) {
            $products = $productRepository->createQueryBuilder('p')
                ->andWhere('p.name LIKE :search')
                ->andWhere('IDENTITY(p.category) = :categoryId')
                ->setParameter('search', '%' . $search . '%')
                ->setParameter('categoryId', $selectedCategory)
                ->orderBy('p.name', 'ASC')
                ->getQuery()
                ->getResult();
        } elseif ($search !== '') {
            $products = $productRepository->searchProducts($search);
        } elseif ($selectedCategory !== null) {
            $products = $productRepository->findByCategory($selectedCategory);
        } else {
            $products = $productRepository->findBy(
                [],
                ['name' => 'ASC']
            );
        }

        return $this->render('storefront/shop.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAllOrderedByName(),
            'search' => $search,
            'selectedCategory' => $selectedCategory,
            'totalProducts' => count($products),
        ]);
    }
}
