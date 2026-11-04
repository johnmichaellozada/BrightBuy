<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Search products by name.
     *
     * @return Product[]
     */
    public function searchProducts(string $search): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products belonging to a category.
     *
     * @return Product[]
     */
    public function findByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('IDENTITY(p.category) = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products with low stock.
     *
     * @return Product[]
     */
    public function findLowStock(int $threshold = 5): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.stock > 0')
            ->andWhere('p.stock <= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('p.stock', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products that are out of stock.
     *
     * @return Product[]
     */
    public function findOutOfStock(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.stock <= :stock')
            ->setParameter('stock', 0)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count products with low stock.
     */
    public function countLowStock(int $threshold = 5): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.stock > 0')
            ->andWhere('p.stock <= :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count products that are out of stock.
     */
    public function countOutOfStock(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.stock <= :stock')
            ->setParameter('stock', 0)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
