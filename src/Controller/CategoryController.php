<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/category')]
final class CategoryController extends AbstractController
{
    private const UPLOAD_DIRECTORY = '/public/uploads/categories';

    /**
     * Allow only Admin and Staff to view categories.
     */
    private function denyUnauthorizedUsers(): void
    {
        if (
            !$this->isGranted('ROLE_ADMIN') &&
            !$this->isGranted('ROLE_STAFF')
        ) {
            throw $this->createAccessDeniedException(
                'You are not authorized to access categories.'
            );
        }
    }

    /**
     * Only Admin can create, edit, or delete categories.
     */
    private function denyNonAdminUsers(): void
    {
        $this->denyAccessUnlessGranted(
            'ROLE_ADMIN',
            null,
            'Only administrators can modify categories.'
        );
    }

    /**
     * Upload category image and return its relative public path.
     */
    private function uploadCategoryImage(UploadedFile $image): string
    {
        $uploadDirectory = $this->getParameter('kernel.project_dir')
            . self::UPLOAD_DIRECTORY;

        if (
            !is_dir($uploadDirectory) &&
            !mkdir($uploadDirectory, 0775, true) &&
            !is_dir($uploadDirectory)
        ) {
            throw new \RuntimeException(
                'Unable to create the category image upload directory.'
            );
        }

        $extension = $image->guessExtension() ?? 'bin';
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        $image->move($uploadDirectory, $filename);

        return 'uploads/categories/' . $filename;
    }

    /**
     * Delete only images uploaded through the category upload directory.
     * Existing predefined images are preserved.
     */
    private function deleteUploadedCategoryImage(?string $imagePath): void
    {
        if (
            !$imagePath ||
            !str_starts_with($imagePath, 'uploads/categories/')
        ) {
            return;
        }

        $fullPath = $this->getParameter('kernel.project_dir')
            . '/public/'
            . $imagePath;

        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(
        CategoryRepository $categoryRepository
    ): Response {
        $this->denyUnauthorizedUsers();

        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAllOrderedByName(),
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyNonAdminUsers();

        $category = new Category();

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile instanceof UploadedFile) {
                $category->setImage(
                    $this->uploadCategoryImage($imageFile)
                );
            }

            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Category created successfully.'
            );

            return $this->redirectToRoute(
                'app_category_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        $this->denyUnauthorizedUsers();

        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyNonAdminUsers();

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();

            $oldImage = $category->getImage();

            if ($imageFile instanceof UploadedFile) {
                $newImage = $this->uploadCategoryImage($imageFile);
                $category->setImage($newImage);

                $entityManager->flush();

                if ($oldImage !== $newImage) {
                    $this->deleteUploadedCategoryImage($oldImage);
                }
            } else {
                // Keep the existing image if no replacement was selected.
                $entityManager->flush();
            }

            $this->addFlash(
                'success',
                'Category updated successfully.'
            );

            return $this->redirectToRoute(
                'app_category_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyNonAdminUsers();

        if ($this->isCsrfTokenValid(
            'delete'.$category->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $oldImage = $category->getImage();

            $entityManager->remove($category);
            $entityManager->flush();

            $this->deleteUploadedCategoryImage($oldImage);

            $this->addFlash(
                'success',
                'Category deleted successfully.'
            );
        }

        return $this->redirectToRoute(
            'app_category_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
