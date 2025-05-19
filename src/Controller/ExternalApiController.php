<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\UserRepository;

#[IsGranted('ROLE_USER')]
final class ExternalApiController extends AbstractController
{
    #[Route('/api/meteo/{ville}', name: 'app_external_api_ville', methods: ['GET'])]
    public function getMeteoVille(HttpClientInterface $httpClient, string $ville, TagAwareCacheInterface $cachePool,
        SerializerInterface $serializer): JsonResponse
    {

        $idCache = "meteo-$ville";
        $response = $cachePool->get($idCache, function (ItemInterface $item) use ($httpClient, $ville) {
            $item->tag("meteoCache");
            $response = $httpClient->request(
                'GET',
                "https://api.openweathermap.org/data/2.5/weather?q=$ville,fr&appid=fe4e9dbc3fcb4ae6531a843c7ed4b732"
            );

            return [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode()
            ];
            
        });  

        return new JsonResponse($response['content'], $response['status'], [], true);
    }

    #[Route('/api/meteo', name: 'app_external_api_ville_default', methods: ['GET'])]
    public function getMeteoVilleDefault(HttpClientInterface $httpClient, TagAwareCacheInterface $cachePool,
        SerializerInterface $serializer, UserRepository $userRepository): JsonResponse
    {
        $user = $this->getUser();

        $postCode = $user->getPostcode();

        $response = $httpClient->request(
            'GET',
            "https://api.openweathermap.org/data/2.5/weather?q=$postCode,fr&appid=fe4e9dbc3fcb4ae6531a843c7ed4b732"
        );


        return new JsonResponse($response->getContent(), $response->getStatusCode(), [], true);
    }
}
