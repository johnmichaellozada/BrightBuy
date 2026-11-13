<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TrackOrderController extends AbstractController
{
    #[Route('/track-order', name: 'app_track_order', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        OrderRepository $orderRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $order = null;
        $notFound = false;

        if ($request->isMethod('POST')) {
            $orderNumber = trim((string) $request->request->get('orderNumber', ''));
            $customerEmail = strtolower(trim((string) $request->request->get('customerEmail', '')));

            if ($orderNumber !== '' && $customerEmail !== '') {
                $foundOrder = $orderRepository->findOneBy([
                    'orderNumber' => $orderNumber,
                ]);

                if (
                    $foundOrder !== null
                    && strtolower(trim($foundOrder->getCustomerEmail())) === $customerEmail
                ) {
                    $order = $foundOrder;
                } else {
                    $notFound = true;
                }
            } else {
                $notFound = true;
            }
        }

        return $this->render('storefront/track_order.html.twig', [
            'categories' => $categoryRepository->findAllOrderedByName(),
            'order' => $order,
            'notFound' => $notFound,
        ]);
    }
}
