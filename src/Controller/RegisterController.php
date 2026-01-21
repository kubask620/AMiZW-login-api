<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(
                ['error' => 'Niepoprawny JSON'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setUsername($data['username'] ?? '');

        if (empty($data['password'])) {
            return new JsonResponse(
                ['error' => 'Hasło jest wymagane'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $data['password']
        );

        $user->setPassword($hashedPassword);

        /** WALIDACJA */
        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            return new JsonResponse(
                ['error' => (string) $errors[0]->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        /** ZAPIS DO BAZY */
        try {
            $em->persist($user);
            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            return new JsonResponse(
                ['error' => 'Email już istnieje'],
                Response::HTTP_CONFLICT
            );
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => 'Błąd serwera'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse(
            ['status' => 'created'],
            Response::HTTP_CREATED
        );
    }
}
