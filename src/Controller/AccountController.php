<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/account')]
final class AccountController extends AbstractController
{
    /**
     * VIEW OWN PROFILE
     */
    #[Route('/profile', name: 'app_account_profile', methods: ['GET'])]
    public function profile(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException(
                'You are not authorized to view this profile.'
            );
        }

        return $this->render('account/profile.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * CHANGE OWN PASSWORD
     */
    #[Route(
        '/change-password',
        name: 'app_account_change_password',
        methods: ['GET', 'POST']
    )]
    public function changePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException(
                'You are not authorized to change this password.'
            );
        }

        $form = $this->createForm(ChangePasswordType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $currentPassword = $form
                ->get('currentPassword')
                ->getData();

            if (!$passwordHasher->isPasswordValid(
                $user,
                $currentPassword
            )) {

                $form->get('currentPassword')->addError(
                    new FormError(
                        'Your current password is incorrect.'
                    )
                );

            } else {

                $newPassword = $form
                    ->get('plainPassword')
                    ->getData();

                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $newPassword
                );

                $user->setPassword($hashedPassword);

                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Your password has been changed successfully.'
                );

                return $this->redirectToRoute(
                    'app_account_change_password'
                );
            }
        }

        return $this->render('account/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
