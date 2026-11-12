<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Form\InventoryAdjustmentType;
use App\Form\ProductType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
final class ProductController extends AbstractController
{
    private const LOW_STOCK_THRESHOLD = 5;

    private const UPLOAD_DIRECTORY = '/public/uploads/products';

    /**
     * Allow only Admin and Staff.
     */
    private function denyUnauthorizedUsers(): void
    {
        if (
            !$this->isGranted('ROLE_ADMIN') &&
            !$this->isGranted('ROLE_STAFF')
        ) {
            throw $this->createAccessDeniedException(
                'You are not authorized to access product management.'
            );
        }
    }

    /**
     * Admin can manage all products.
     * Staff can manage only their own products.
     */
    private function checkProductOwnership(Product $product): void
    {
        $this->denyUnauthorizedUsers();

        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->getUser();

        if (
            !$user instanceof User ||
            $product->getCreatedBy()?->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException(
                'You are not authorized to modify this product.'
            );
        }
    }

    /**
     * Calculate stock counts.
     */
    private function calculateStockCounts(array $products): array
    {
        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach ($products as $product) {
            $stock = $product->getStock() ?? 0;

            if ($stock === 0) {
                $outOfStockCount++;
            } elseif ($stock <= self::LOW_STOCK_THRESHOLD) {
                $lowStockCount++;
            }
        }

        return [
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
        ];
    }

    /**
     * Upload a product image and return its relative path.
     */
    private function uploadProductImage(UploadedFile $image): string
    {
        $projectDirectory = $this->getParameter('kernel.project_dir');

        $uploadDirectory = $projectDirectory . self::UPLOAD_DIRECTORY;

        if (
            !is_dir($uploadDirectory) &&
            !mkdir($uploadDirectory, 0775, true) &&
            !is_dir($uploadDirectory)
        ) {
            throw new \RuntimeException(
                'Unable to create the product image upload directory.'
            );
        }

        $extension = $image->guessExtension();

        if (!$extension) {
            throw new \RuntimeException(
                'Unable to determine the uploaded image format.'
            );
        }

        // Generate a unique filename to avoid overwriting images.
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        $image->move($uploadDirectory, $filename);

        return 'uploads/products/' . $filename;
    }

    /**
     * Delete an uploaded product image.
     * Existing predefined image paths are preserved.
     */
    private function deleteUploadedProductImage(?string $imagePath): void
    {
        if (
            !$imagePath ||
            !str_starts_with($imagePath, 'uploads/products/')
        ) {
            return;
        }

        $projectDirectory = $this->getParameter('kernel.project_dir');

        $fullPath = $projectDirectory . '/public/' . $imagePath;

        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * Display all products to Admin and Staff.
     */
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $this->denyUnauthorizedUsers();

        $products = $productRepository->findAll();

        $stockCounts = $this->calculateStockCounts($products);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAllOrderedByName(),
            'lowStockCount' => $stockCounts['lowStockCount'],
            'outOfStockCount' => $stockCounts['outOfStockCount'],
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    /**
     * Display all products in Inventory Management.
     */
    #[Route('/inventory', name: 'app_inventory_index', methods: ['GET'])]
    public function inventoryIndex(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $this->denyUnauthorizedUsers();

        $products = $productRepository->findAll();

        $stockCounts = $this->calculateStockCounts($products);

        return $this->render('product/inventory_index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAllOrderedByName(),
            'lowStockCount' => $stockCounts['lowStockCount'],
            'outOfStockCount' => $stockCounts['outOfStockCount'],
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    /**
     * Admin and Staff can create products.
     */
    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyUnauthorizedUsers();

        $product = new Product();
        $product->setStock(0);

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();

            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile instanceof UploadedFile) {
                $product->setImage(
                    $this->uploadProductImage($imageFile)
                );
            }

            $product->setCreatedBy($user);

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

    /**
     * Only Admin or product owner can adjust inventory.
     */
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
        $this->checkProductOwnership($product);

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
                $this->addFlash(
                    'danger',
                    'Please select a valid stock action.'
                );
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

    /**
     * Admin and Staff can view any product.
     */
    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        $this->denyUnauthorizedUsers();

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * Only Admin or product owner can edit.
     */
    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {
        $this->checkProductOwnership($product);

        $existingStock = $product->getStock() ?? 0;
        $existingImage = $product->getImage();

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();

            // Replace the image only when a new file is selected.
            if ($imageFile instanceof UploadedFile) {
                $newImagePath = $this->uploadProductImage($imageFile);

                $product->setImage($newImagePath);

                // Remove the previous uploaded image after replacement.
                if ($existingImage !== $newImagePath) {
                    $this->deleteUploadedProductImage($existingImage);
                }
            } else {
                // Preserve the current image when no replacement is selected.
                $product->setImage($existingImage);
            }

            // Editing product details must not change stock or ownership.
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

    /**
     * Only Admin or product owner can delete.
     */
    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {
        $this->checkProductOwnership($product);

        if ($this->isCsrfTokenValid(
            'delete' . $product->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $imagePath = $product->getImage();

            $entityManager->remove($product);
            $entityManager->flush();

            $this->deleteUploadedProductImage($imagePath);

            $this->addFlash('success', 'Product deleted successfully.');
        }

        return $this->redirectToRoute(
            'app_product_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
