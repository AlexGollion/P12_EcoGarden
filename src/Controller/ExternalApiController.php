<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
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

        try {
            $meteo = $cachePool->get($idCache, function (ItemInterface $item) use ($httpClient, $ville) {
                $item->tag('meteoCache');
                $item->expiresAt(new \DateTime("+2 days"));
                $response = $httpClient->request(
                    'GET',
                    "https://api.openweathermap.org/data/2.5/weather?q=$ville,fr&appid=fe4e9dbc3fcb4ae6531a843c7ed4b732"
                );
                $data = $response->toArray();
                return $data;
            });
        } catch (ClientExceptionInterface $e) {
            if ($e->getCode() == 404) {
                throw new HttpException(404, 'Ville introuvable');
            }

            throw $e;
        }

        $meteoJson = $serializer->serialize($meteo, 'json');
        return new JsonResponse($meteoJson, Response::HTTP_OK, [], true);
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

        if ($response->getStatusCode() == 404) {
            $data = [
                'status' => $response->getStatusCode(),
                'message' => 'Ville introuvable'
            ];
            $dataJson = $serializer->serialize($data, 'json');
            return new JsonResponse($dataJson, $response->getStatusCode(), [], true);
        }

        return new JsonResponse($response->getContent(), $response->getStatusCode(), [], true);
    }
}
