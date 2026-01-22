<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Zadanie (ocena 3):
 * - endpoint /api/me ma działać [dopisać roles]
 */
final class MeController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(UserInterface $user): JsonResponse
    {
        return new JsonResponse([
            'id' => method_exists($user, 'getId') ? $user->getId() : null,
            'email' => method_exists($user, 'getEmail') ? $user->getEmail() : null,
            'roles' => $user->getRoles(),
        ]);
    }
}
