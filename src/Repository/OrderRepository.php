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

    /**
     * Search and filter orders.
     *
     * @return Order[]
     */
    public function searchAndFilter(
        ?string $search,
        ?string $status,
        ?string $paymentStatus
    ): array {
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

    /**
     * Count all orders.
     */
    public function countAllOrders(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count orders by status.
     */
    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calculate paid income within a date range.
     *
     * Excludes cancelled and refunded orders.
     */
    public function sumPaidIncomeBetween(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): string {
        $result = $this->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.totalAmount), 0)')
            ->andWhere('o.paymentStatus = :paid')
            ->andWhere('o.status != :cancelled')
            ->andWhere('o.createdAt >= :start')
            ->andWhere('o.createdAt < :end')
            ->setParameter('paid', 'Paid')
            ->setParameter('cancelled', 'Cancelled')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (string) $result;
    }

    /**
     * Get order counts grouped by customer email.
     *
     * @return array<int, array{customerEmail: string, orderCount: int}>
     */
    public function countOrdersGroupedByCustomerEmail(): array
    {
        return $this->createQueryBuilder('o')
            ->select('o.customerEmail AS customerEmail')
            ->addSelect('COUNT(o.id) AS orderCount')
            ->groupBy('o.customerEmail')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Get all orders belonging to a customer using their email.
     *
     * @return Order[]
     */
    public function findByCustomerEmail(string $email): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('LOWER(o.customerEmail) = :email')
            ->setParameter('email', strtolower(trim($email)))
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
