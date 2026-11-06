<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Find a user using their email address.
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.email) = :email')
            ->setParameter('email', strtolower(trim($email)))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Check if an email address is already registered.
     */
    public function emailExists(string $email): bool
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('LOWER(u.email) = :email')
            ->setParameter('email', strtolower(trim($email)))
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Search users by first name, last name, or email.
     *
     * @return User[]
     */
    public function searchUsers(string $search): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere(
                'LOWER(u.firstName) LIKE :search
                OR LOWER(u.lastName) LIKE :search
                OR LOWER(u.email) LIKE :search'
            )
            ->setParameter('search', '%' . strtolower(trim($search)) . '%')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users by their role.
     *
     * @return User[]
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.role) = :role')
            ->setParameter('role', strtolower(trim($role)))
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count users by role.
     */
    public function countByRole(string $role): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('LOWER(u.role) = :role')
            ->setParameter('role', strtolower(trim($role)))
            ->getQuery()
            ->getSingleScalarResult();
    }
}
