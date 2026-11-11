<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
final class UserManagementController extends AbstractController
{
    #[Route('', name: 'app_user_management', methods: ['GET'])]
    public function index(
        Request $request,
        UserRepository $userRepository
    ): Response {
        // Only administrators can access user management.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = trim($request->query->get('search', ''));
        $role = $request->query->get('role');
        $status = $request->query->get('status');

        // Normalize role filter.
        $allowedRoles = ['admin', 'staff', 'customer'];

        if (!in_array($role, $allowedRoles, true)) {
            $role = null;
        }

        // Convert status filter into a nullable boolean.
        $isActive = match ($status) {
            'active' => true,
            'disabled' => false,
            default => null,
        };

        // Retrieve filtered users through the repository.
        $users = $userRepository->searchUsersByRoleAndStatus(
            $search,
            $role,
            $isActive
        );

        return $this->render('user_management/index.html.twig', [
            'users' => $users,
            'search' => $search,
            'selectedRole' => $role,
            'selectedStatus' => $status,
            'totalUsers' => $userRepository->countAllUsers(),
            'activeUsers' => count($userRepository->findActiveUsers()),
            'inactiveUsers' => count($userRepository->findInactiveUsers()),
        ]);
    }
}
