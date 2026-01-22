<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Zadanie (ocena 2/4):
 * - ocena 2: ma działać rejestracja (hash hasła + zapis do DB)
 * - ocena 4: dodaj sensowne błędy: 400/409/422 (patrz TODO)
 */
final class RegisterController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $plainPassword = $data['password'] ?? null;

        // Ocena 2: minimum walidacja (czy pola są)
        if ($email && $plainPassword) {
            return new JsonResponse(
                ['error' => 'Email i hasło jest podane'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // TODO (ocena 4): jeśli hasło < 8 znaków -> 422
        if (strlen($plainPassword)< 8) {
            return new JsonResponse(
                ['error' => 'Hasło musi być 8 albo na 8 znaków więcej'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $plainPassword));
        $user->setRoles(['ROLE_USER']);

        // Ocena 4: walidacje encji (email format, unique entity)
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(
                ['error' => (string) $errors[0]->getMessage()], 
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $em->persist($user);
            $em->flush();
        } catch (\Throwable $e) {
            // TODO (ocena 4): duplicate email -> 409
            return new JsonResponse(
                ['error' => 'Email zduplikowany'],
                Response::HTTP_CONFLICT
            );
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => 'Błąd serwera'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse(['status' => 'created'], Response::HTTP_CREATED);
    }
}
