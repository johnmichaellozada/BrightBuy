<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token
    ): RedirectResponse {
        $user = $token->getUser();
        $roles = $user->getRoles();

        // Admin → Admin Dashboard
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_dashboard')
            );
        }

        // Staff → Staff Dashboard
        if (in_array('ROLE_STAFF', $roles, true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_staff_dashboard')
            );
        }

        // Customer → BrightBuy Homepage
        return new RedirectResponse('/');
    }
}
