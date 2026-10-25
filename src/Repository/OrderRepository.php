<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function searchAndFilter(?string $search, ?string $status, ?string $paymentStatus): array
    {
        $qb = $this->createQueryBuilder('o');

        if ($search !== null && $search !== '') {
            $qb->andWhere(
                'o.orderNumber LIKE :search
                OR o.customerName LIKE :search
                OR o.customerEmail LIKE :search'
            )
            ->setParameter('search', '%' . $search . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        if ($paymentStatus !== null && $paymentStatus !== '') {
            $qb->andWhere('o.paymentStatus = :paymentStatus')
                ->setParameter('paymentStatus', $paymentStatus);
        }

        return $qb->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}