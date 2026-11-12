<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Form\EditUserType;
use App\Form\PasswordResetType;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
final class UserManagementController extends AbstractController
{
    #[Route('', name: 'app_user_management', methods: ['GET'])]
    public function index(
        Request $request,
        UserRepository $userRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = trim($request->query->get('search', ''));
        $role = $request->query->get('role');
        $status = $request->query->get('status');

        $allowedRoles = ['admin', 'staff', 'customer'];

        if (!in_array($role, $allowedRoles, true)) {
            $role = null;
        }

        $isActive = match ($status) {
            'active' => true,
            'disabled' => false,
            default => null,
        };

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
            'activeAdmins' => $userRepository->countActiveAdmins(),
        ]);
    }

    #[Route('/new', name: 'app_user_management_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();

        $form = $this->createForm(AdminUserType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($userRepository->emailExists($data['email'])) {
                $form->get('email')->addError(
                    new FormError(
                        'This email address is already registered.'
                    )
                );
            } else {
                $user->setFirstName($data['firstName']);
                $user->setLastName($data['lastName']);
                $user->setEmail($data['email']);
                $user->setPhone($data['phone'] ?: null);
                $user->setRole($data['role']);
                $user->setCreatedAt(new \DateTime());
                $user->setIsActive(true);

                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );

                $user->setPassword($hashedPassword);

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'The new ' . ucfirst($data['role']) .
                    ' account was created successfully!'
                );

                return $this->redirectToRoute('app_user_management');
            }
        }

        return $this->render('user_management/new.html.twig', [
            'userForm' => $form->createView(),
        ]);
    }

    #[Route(
        '/{id}/edit',
        name: 'app_user_management_edit',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function edit(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($id);

        if (!$user instanceof User) {
            throw $this->createNotFoundException(
                'User account not found.'
            );
        }

        // Customer personal information and role cannot be edited by Admin.
        if ($this->isCustomerAccount($user)) {
            $this->addFlash(
                'danger',
                'Customer accounts cannot be edited through User Management.'
            );

            return $this->redirectToRoute('app_user_management');
        }

        $currentAdmin = $this->getUser();

        $isEditingOwnAccount = $currentAdmin instanceof User
            && $currentAdmin->getId() === $user->getId();

        $form = $this->createForm(EditUserType::class, [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'role' => strtolower($user->getRole() ?? ''),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $currentRole = strtolower(trim($user->getRole() ?? ''));
            $newRole = strtolower(trim($data['role']));

            if (
                $userRepository->emailExistsForAnotherUser(
                    $data['email'],
                    $user->getId()
                )
            ) {
                $form->get('email')->addError(
                    new FormError(
                        'This email address is already registered.'
                    )
                );
            } elseif (
                $isEditingOwnAccount &&
                $newRole !== 'admin'
            ) {
                $form->get('role')->addError(
                    new FormError(
                        'You cannot change your own Administrator role.'
                    )
                );
            } elseif (
                $currentRole === 'admin' &&
                $newRole !== 'admin' &&
                $user->isActive() &&
                $userRepository->countActiveAdmins() <= 1
            ) {
                // Prevent removing the last active Administrator.
                $form->get('role')->addError(
                    new FormError(
                        'The last active Administrator cannot be demoted.'
                    )
                );
            } else {
                $user->setFirstName($data['firstName']);
                $user->setLastName($data['lastName']);
                $user->setEmail($data['email']);
                $user->setPhone($data['phone'] ?: null);
                $user->setRole($newRole);

                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'User account updated successfully!'
                );

                return $this->redirectToRoute('app_user_management');
            }
        }

        return $this->render('user_management/edit.html.twig', [
            'userForm' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route(
        '/{id}/reset-password',
        name: 'app_user_management_reset_password',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function resetPassword(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($id);

        if (!$user instanceof User) {
            throw $this->createNotFoundException(
                'User account not found.'
            );
        }

        // Admin cannot directly reset a Customer's password.
        if ($this->isCustomerAccount($user)) {
            $this->addFlash(
                'danger',
                'Customer passwords cannot be reset through User Management.'
            );

            return $this->redirectToRoute('app_user_management');
        }

        $currentAdmin = $this->getUser();

        if (
            $currentAdmin instanceof User &&
            $currentAdmin->getId() === $user->getId()
        ) {
            $this->addFlash(
                'danger',
                'You cannot reset your own password using User Management.'
            );

            return $this->redirectToRoute('app_user_management');
        }

        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plainPassword
            );

            $user->setPassword($hashedPassword);

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Password for ' . $user->getFirstName() . ' ' .
                $user->getLastName() . ' was reset successfully!'
            );

            return $this->redirectToRoute('app_user_management');
        }

        return $this->render('user_management/reset_password.html.twig', [
            'passwordForm' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route(
        '/{id}/toggle-status',
        name: 'app_user_management_toggle_status',
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

        $user = $userRepository->find($id);

        if (!$user instanceof User) {
            throw $this->createNotFoundException(
                'User account not found.'
            );
        }

        if (!$this->isCsrfTokenValid(
            'toggle_status_' . $user->getId(),
            $request->request->get('_token')
        )) {
            $this->addFlash(
                'danger',
                'Invalid account status request. Please try again.'
            );

            return $this->redirectToRoute('app_user_management');
        }

        $currentAdmin = $this->getUser();

        if (
            $currentAdmin instanceof User &&
            $currentAdmin->getId() === $user->getId()
        ) {
            $this->addFlash(
                'danger',
                'You cannot disable your own Administrator account.'
            );

            return $this->redirectToRoute('app_user_management');
        }

        $isAdmin = strtolower($user->getRole() ?? '') === 'admin';

        if (
            $isAdmin &&
            $user->isActive() &&
            $userRepository->countActiveAdmins() <= 1
        ) {
            $this->addFlash(
                'danger',
                'The last active Administrator cannot be disabled.'
            );

            return $this->redirectToRoute('app_user_management');
        }

        // Admin may enable or disable Admin, Staff, and Customer accounts.
        $newStatus = !$user->isActive();

        $user->setIsActive($newStatus);

        $entityManager->flush();

        $fullName = trim(
            $user->getFirstName() . ' ' . $user->getLastName()
        );

        $this->addFlash(
            'success',
            sprintf(
                'The account for %s has been %s successfully.',
                $fullName,
                $newStatus ? 'enabled' : 'disabled'
            )
        );

        return $this->redirectToRoute('app_user_management');
    }

    #[Route(
        '/{id}/delete',
        name: 'app_user_management_delete',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function delete(
        int $id,
        Request $request,
        UserRepository $userRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($id);

        if (!$user instanceof User) {
            throw $this->createNotFoundException(
                'User account not found.'
            );
        }

        if (!$this->isCsrfTokenValid(
            'delete_user_' . $user->getId(),
            $request->request->get('_token')
        )) {
            $this->addFlash(
                'danger',
                'Invalid deletion request. Please try again.'
            );

            return $this->redirectToRoute('app_user_management');
        }

        // Customer accounts cannot be permanently deleted by Admin here.
        if ($this->isCustomerAccount($user)) {
            $this->addFlash(
                'danger',
                'Customer accounts cannot be deleted through User Management.'
            );

            return $this->redirectToRoute('app_user_management');
        }

        $currentAdmin = $this->getUser();

        if (
            $currentAdmin instanceof User &&
            $currentAdmin->getId() === $user->getId()
        ) {
            $this->addFlash(
                'danger',
                'You cannot delete your own Administrator account.'
            );

            return $this->redirectToRoute('app_user_management');
        }

        $role = strtolower($user->getRole() ?? '');
        $isAdmin = $role === 'admin';
        $isStaff = $role === 'staff';

        if (
            $isAdmin &&
            $user->isActive() &&
            $userRepository->countActiveAdmins() <= 1
        ) {
            $this->addFlash(
                'danger',
                'This account is the last active Administrator and cannot be deleted.'
            );

            return $this->redirectToRoute('app_user_management');
        }

        $fullName = trim(
            $user->getFirstName() . ' ' . $user->getLastName()
        );

        if ($isStaff) {
            $deletedProductCount = $productRepository->deleteByCreator($user);

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash(
                'success',
                sprintf(
                    'Staff account for %s and %d product(s) created by this Staff were permanently deleted. Historical order records were preserved.',
                    $fullName,
                    $deletedProductCount
                )
            );

            return $this->redirectToRoute('app_user_management');
        }

        // Preserve products owned by Admin accounts.
        $ownedProductCount = $productRepository->countByCreator($user);

        if ($ownedProductCount > 0) {
            $this->addFlash(
                'danger',
                sprintf(
                    'This account owns %d product(s). Disable the account instead to preserve product ownership.',
                    $ownedProductCount
                )
            );

            return $this->redirectToRoute('app_user_management');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash(
            'success',
            sprintf(
                'The account for %s was permanently deleted.',
                $fullName
            )
        );

        return $this->redirectToRoute('app_user_management');
    }

    /**
     * Check whether the account belongs to a Customer.
     */
    private function isCustomerAccount(User $user): bool
    {
        return strtolower(trim($user->getRole() ?? '')) === 'customer';
    }
}
