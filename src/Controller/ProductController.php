<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\InventoryAdjustmentType;
use App\Form\ProductType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
final class ProductController extends AbstractController
{
    private const LOW_STOCK_THRESHOLD = 5;

    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $products = $productRepository->findAll();

        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach ($products as $product) {
            $stock = $product->getStock() ?? 0;

            if ($stock <= 0) {
                $outOfStockCount++;
            } elseif ($stock <= self::LOW_STOCK_THRESHOLD) {
                $lowStockCount++;
            }
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    #[Route('/inventory', name: 'app_inventory_index', methods: ['GET'])]
    public function inventoryIndex(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $products = $productRepository->findAll();

        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach ($products as $product) {
            $stock = $product->getStock() ?? 0;

            if ($stock <= 0) {
                $outOfStockCount++;
            } elseif ($stock <= self::LOW_STOCK_THRESHOLD) {
                $lowStockCount++;
            }
        }

        return $this->render('product/inventory_index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $product = new Product();
        $product->setStock(0);

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Product created successfully. Add stock from Inventory Management.'
            );

            return $this->redirectToRoute(
                'app_product_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route(
        '/{id}/inventory',
        name: 'app_product_inventory',
        methods: ['GET', 'POST']
    )]
    public function inventory(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(InventoryAdjustmentType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $action = $data['action'];
            $quantity = (int) $data['quantity'];
            $currentStock = $product->getStock() ?? 0;

            if ($action === 'add' && $quantity > 0) {
                $product->setStock($currentStock + $quantity);
                $entityManager->flush();

                $this->addFlash('success', 'Stock added successfully.');

                return $this->redirectToRoute('app_inventory_index');
            }

            if ($action === 'reduce' && $quantity > 0) {
                if ($quantity > $currentStock) {
                    $this->addFlash(
                        'danger',
                        'Cannot reduce more than the available stock.'
                    );
                } else {
                    $product->setStock($currentStock - $quantity);
                    $entityManager->flush();

                    $this->addFlash('success', 'Stock reduced successfully.');

                    return $this->redirectToRoute('app_inventory_index');
                }
            }

            if ($action === 'set' && $quantity >= 0) {
                $product->setStock($quantity);
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Stock quantity updated successfully.'
                );

                return $this->redirectToRoute('app_inventory_index');
            }

            if (!in_array($action, ['add', 'reduce', 'set'], true)) {
                $this->addFlash('danger', 'Please select a valid stock action.');
            } elseif ($quantity === 0 && $action !== 'set') {
                $this->addFlash(
                    'danger',
                    'For Add Stock or Reduce Stock, quantity must be greater than zero.'
                );
            }
        }

        return $this->render('product/inventory.html.twig', [
            'product' => $product,
            'form' => $form,
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {
        $existingStock = $product->getStock() ?? 0;

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Editing product details must not change its stock.
            $product->setStock($existingStock);

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Product details updated successfully.'
            );

            return $this->redirectToRoute(
                'app_product_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete'.$product->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product deleted successfully.');
        }

        return $this->redirectToRoute(
            'app_product_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
