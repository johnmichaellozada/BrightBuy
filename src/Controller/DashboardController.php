<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
        ProductRepository $productRepository,
        OrderRepository $orderRepository
    ): Response {
        // Only administrators can access the dashboard.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Date ranges for income calculations.
        $todayStart = new DateTimeImmutable('today');
        $tomorrowStart = $todayStart->modify('+1 day');

        $monthStart = $todayStart->modify('first day of this month');
        $nextMonthStart = $monthStart->modify('+1 month');

        // Customer information.
        $totalCustomers = $userRepository->countByRole('customer');
        $customers = $userRepository->findByRole('customer');

        // Sort customers by newest registration first.
        usort($customers, function ($a, $b): int {
            return ($b->getCreatedAt()?->getTimestamp() ?? 0)
                <=> ($a->getCreatedAt()?->getTimestamp() ?? 0);
        });

        // Display only the five most recently registered customers.
        $recentCustomers = array_slice($customers, 0, 5);

        // Other dashboard totals.
        $totalStaff = $userRepository->countByRole('staff');
        $totalProducts = $productRepository->countAllProducts();
        $totalOrders = $orderRepository->countAllOrders();

        // Order status breakdown.
        $orderStatuses = [
            'Pending' => $orderRepository->countByStatus('Pending'),
            'Processing' => $orderRepository->countByStatus('Processing'),
            'Shipped' => $orderRepository->countByStatus('Shipped'),
            'Delivered' => $orderRepository->countByStatus('Delivered'),
            'Cancelled' => $orderRepository->countByStatus('Cancelled'),
        ];

        // Income from paid orders only.
        $todayIncome = $orderRepository->sumPaidIncomeBetween(
            $todayStart,
            $tomorrowStart
        );

        $monthIncome = $orderRepository->sumPaidIncomeBetween(
            $monthStart,
            $nextMonthStart
        );

        return $this->render('dashboard/index.html.twig', [
            'todayDate' => $todayStart->format('F d, Y'),
            'monthLabel' => $monthStart->format('F Y'),

            'todayIncome' => $todayIncome,
            'monthIncome' => $monthIncome,

            'totalCustomers' => $totalCustomers,
            'totalStaff' => $totalStaff,
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,

            'orderStatuses' => $orderStatuses,
            'recentCustomers' => $recentCustomers,
        ]);
    }
}
