<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StaffDashboardController extends AbstractController
{
    #[Route('/staff/dashboard', name: 'app_staff_dashboard', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        OrderRepository $orderRepository
    ): Response {
        // Staff accounts only.
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException(
                'You are not authorized to access this dashboard.'
            );
        }

        // Retrieve only products created by the logged-in staff member.
        $myProducts = $productRepository->findByCreator($user);

        $myProductsCount = count($myProducts);
        $myTotalStock = 0;
        $myLowStock = 0;
        $myOutOfStock = 0;

        foreach ($myProducts as $product) {
            $stock = $product->getStock() ?? 0;

            $myTotalStock += $stock;

            if ($stock === 0) {
                $myOutOfStock++;
            } elseif ($stock <= 5) {
                $myLowStock++;
            }
        }

        // Display the five most recently created products.
        $recentProducts = array_slice(
            array_reverse($myProducts),
            0,
            5
        );

        // Orders are visible to staff but remain read-only.
        $totalOrders = $orderRepository->countAllOrders();

        return $this->render('staff/dashboard.html.twig', [
            'staffName' => $user->getFirstName(),

            'myProductsCount' => $myProductsCount,
            'myTotalStock' => $myTotalStock,
            'myLowStock' => $myLowStock,
            'myOutOfStock' => $myOutOfStock,

            'totalOrders' => $totalOrders,
            'recentProducts' => $recentProducts,
        ]);
    }
}
