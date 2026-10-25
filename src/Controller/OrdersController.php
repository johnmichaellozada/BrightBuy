<?php

namespace App\Controller;

use App\Repository\OrderRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrdersController extends AbstractController
{
    #[Route('/orders/{id}', name: 'app_order_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, OrderRepository $orderRepository, \App\Repository\OrderItemRepository $orderItemRepository): Response
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        $orderItems = $orderItemRepository->findBy(['order' => $order], ['id' => 'ASC']);

        return $this->render('orders/show.html.twig', [
            'order' => $order,
            'orderItems' => $orderItems,
        ]);
    }

    #[Route('/orders', name: 'app_orders', methods: ['GET'])]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $search = trim($request->query->getString('search'));
        $status = $request->query->getString('status');
        $paymentStatus = $request->query->getString('paymentStatus');

        $allowedStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
        $allowedPaymentStatuses = ['Pending', 'Paid', 'Failed', 'Refunded'];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        if (!in_array($paymentStatus, $allowedPaymentStatuses, true)) {
            $paymentStatus = '';
        }

        $orders = $orderRepository->searchAndFilter(
            $search,
            $status,
            $paymentStatus
        );

        return $this->render('orders/index.html.twig', [
            'orders' => $orders,
            'ordersCount' => count($orders),
            'search' => $search,
            'selectedStatus' => $status,
            'selectedPaymentStatus' => $paymentStatus,
        ]);
    }

    #[Route('/orders/{id}/status', name: 'app_order_update_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateStatus(
        int $id,
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $order = $orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        if (!$this->isCsrfTokenValid(
            'update_order_status_'.$order->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $this->addFlash('danger', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_orders', [], Response::HTTP_SEE_OTHER);
        }

        $status = $request->getPayload()->getString('status');
        $allowedStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

        if (!in_array($status, $allowedStatuses, true)) {
            $this->addFlash('danger', 'Please select a valid order status.');

            return $this->redirectToRoute('app_orders', [], Response::HTTP_SEE_OTHER);
        }

        $order->setStatus($status);
        $entityManager->flush();

        $this->addFlash('success', 'Order status updated successfully.');

        return $this->redirectToRoute('app_orders', [], Response::HTTP_SEE_OTHER);
    }
}
