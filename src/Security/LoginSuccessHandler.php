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

        // Retrieve the originally requested page.
        $targetPath = null;

        if ($request->hasSession()) {
            $session = $request->getSession();

            $targetPath = $session->get('_security.main.target_path');
            $session->remove('_security.main.target_path');
        }

        // Admin → Homepage
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_home')
            );
        }

        // Staff → Homepage
        if (in_array('ROLE_STAFF', $roles, true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_home')
            );
        }

        // Other users → Originally requested page.
        if (is_string($targetPath) && str_starts_with($targetPath, '/')) {
            return new RedirectResponse($targetPath);
        }

        // Default → Homepage.
        return new RedirectResponse(
            $this->urlGenerator->generate('app_home')
        );
    }
}
