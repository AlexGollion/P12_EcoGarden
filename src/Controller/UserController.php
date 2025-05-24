<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class UserController extends AbstractController
{
    #[Route('/api/user', name: 'app_user', methods: ['POST'])]
    public function createUer(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setRoles(['ROLE_USER']);
        $hashPassword = $hasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashPassword);
        $entityManager->persist($user);
        $entityManager->flush();

        $jsonUser = $serializer->serialize($user, 'json');

        return new JsonResponse([
            $jsonUser, Response::HTTP_CREATED, [], true
        ]);
    }

    #[Route('/api/user/{id}', name: 'app_users_edit', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager,
         User $currentUser, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $updatedUser = $serializer->deserialize($request->getContent(), 
            User::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);
        
        $content = $request->toArray();
        $password = $content['password'];
        $passwordHash = $hasher->hashPassword($updatedUser, $password);
        $updatedUser->setPassword($passwordHash);

        $entityManager->persist($updatedUser);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/user/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(User $user, EntityManagerInterface $entityManager, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $cachePool->invalidateTags(["meteoCache"]);
        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/debug', name: 'app_debug', methods: ['GET'])]
    public function debugHeaders(Request $request): JsonResponse    
    {
        return new JsonResponse([
            'authorization' => $request->headers->get('Authorization'),
            'headers' => $request->headers->all(),
        ]);
    }
}
