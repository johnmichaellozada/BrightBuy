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
        return (int) $this->createQueryBuilder('u')
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

    /**
     * Count all registered users.
     */
    public function countAllUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find all active users.
     *
     * @return User[]
     */
    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all inactive users.
     *
     * @return User[]
     */
    public function findInactiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = :active')
            ->setParameter('active', false)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search users by name, email, role, and active status.
     *
     * @return User[]
     */
    public function searchUsersByRoleAndStatus(
        string $search = '',
        ?string $role = null,
        ?bool $isActive = null
    ): array {
        $qb = $this->createQueryBuilder('u');

        if (trim($search) !== '') {
            $qb->andWhere(
                'LOWER(u.firstName) LIKE :search
                OR LOWER(u.lastName) LIKE :search
                OR LOWER(u.email) LIKE :search'
            )
            ->setParameter(
                'search',
                '%' . strtolower(trim($search)) . '%'
            );
        }

        if ($role !== null && trim($role) !== '') {
            $qb->andWhere('LOWER(u.role) = :role')
                ->setParameter('role', strtolower(trim($role)));
        }

        if ($isActive !== null) {
            $qb->andWhere('u.isActive = :active')
                ->setParameter('active', $isActive);
        }

        return $qb
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
