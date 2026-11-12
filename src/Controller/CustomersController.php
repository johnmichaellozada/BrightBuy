<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomersController extends AbstractController
{
    #[Route('/customers', name: 'app_customers', methods: ['GET'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        OrderRepository $orderRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = trim((string) $request->query->get('search', ''));
        $status = strtolower(trim((string) $request->query->get('status', '')));

        if (!in_array($status, ['', 'active', 'disabled'], true)) {
            $status = '';
        }

        $isActive = match ($status) {
            'active' => true,
            'disabled' => false,
            default => null,
        };

        $customers = $userRepository->searchUsersByRoleAndStatus(
            $search,
            'customer',
            $isActive
        );

        $allCustomers = $userRepository->findByRole('customer');

        $totalCustomers = count($allCustomers);
        $activeCustomers = 0;
        $disabledCustomers = 0;

        foreach ($allCustomers as $customer) {
            if ($customer->isActive()) {
                $activeCustomers++;
            } else {
                $disabledCustomers++;
            }
        }

        $orderCounts = $orderRepository->countOrdersGroupedByCustomerEmail();

        $orderCountsByEmail = [];

        foreach ($orderCounts as $orderCount) {
            $email = strtolower(trim($orderCount['customerEmail']));
            $orderCountsByEmail[$email] = (int) $orderCount['orderCount'];
        }

        $customerOrderCounts = [];

        foreach ($customers as $customer) {
            if (!$customer instanceof User) {
                continue;
            }

            $email = strtolower(trim($customer->getEmail()));

            $customerOrderCounts[$customer->getId()] =
                $orderCountsByEmail[$email] ?? 0;
        }

        return $this->render('customers/index.html.twig', [
            'customers' => $customers,
            'customerOrderCounts' => $customerOrderCounts,
            'totalCustomers' => $totalCustomers,
            'activeCustomers' => $activeCustomers,
            'disabledCustomers' => $disabledCustomers,
            'search' => $search,
            'selectedStatus' => $status,
        ]);
    }

    #[Route(
        '/customers/{id}',
        name: 'app_customer_show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(
        int $id,
        UserRepository $userRepository,
        OrderRepository $orderRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $customer = $userRepository->find($id);

        if (
            !$customer instanceof User ||
            strtolower(trim((string) $customer->getRole())) !== 'customer'
        ) {
            throw $this->createNotFoundException('Customer not found.');
        }

        $orders = $orderRepository->findByCustomerEmail(
            $customer->getEmail()
        );

        return $this->render('customers/show.html.twig', [
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }

    #[Route(
        '/customers/{id}/toggle-status',
        name: 'app_customer_toggle_status',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function toggleStatus(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $customer = $userRepository->find($id);

        if (
            !$customer instanceof User ||
            strtolower(trim((string) $customer->getRole())) !== 'customer'
        ) {
            throw $this->createNotFoundException('Customer not found.');
        }

        if (!$this->isCsrfTokenValid(
            'toggle_customer_status_' . $customer->getId(),
            $request->request->get('_token')
        )) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_customers');
        }

        $customer->setIsActive(!$customer->isActive());

        $entityManager->flush();

        $this->addFlash(
            'success',
            $customer->isActive()
                ? 'Customer account has been enabled.'
                : 'Customer account has been disabled.'
        );

        return $this->redirectToRoute('app_customers');
    }
}
