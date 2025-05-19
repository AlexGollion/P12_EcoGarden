<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\AdviceRepository;
use App\Entity\Advice;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[IsGranted('ROLE_USER')]
final class AdviceController extends AbstractController
{
    #[Route('/api/advices', name: 'app_advices_get', methods: ['GET'])]
    public function getAdvices(AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $month = date('m');
        $adviceList = $adviceRepository->findByMonth($month);
        $jsonAdivceList = $serializer->serialize($adviceList, 'json');

        return new JsonResponse([
            $jsonAdivceList, Response::HTTP_OK, [], true
        ]);
    }
    
    #[Route('/api/advices/{id}', name: 'app_advices_month', methods: ['GET'])]
    public function getAdvicesMonth(int $id, AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $this->monthCheck($id);
        
        $adviceList = $adviceRepository->findByMonth($id);
    
        $jsonAdviceList = $serializer->serialize($adviceList, 'json');
        return new JsonResponse([
            $jsonAdviceList, Response::HTTP_OK, [], true
        ]);
    }

    #[Route('/api/advices', name: 'app_advices_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function newAdvice(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer, 
        UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $advice = $serializer->deserialize($request->getContent(), Advice::class, 'json');
        $idMonth = $advice->getMonth();

        $this->monthCheck($idMonth);
            
        $entityManager->persist($advice);
        $entityManager->flush();
    
        $jsonAdvice = $serializer->serialize($advice, 'json');

        $location = $urlGenerator->generate('app_advices_month', ['id' => $advice->getMonth()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse([
            $jsonAdvice, Response::HTTP_CREATED, ["Location" => $location], true
        ]);
    }

    #[Route('/api/advices/{id}', name: 'app_advices_edit', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editAdvice(int $id, Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer, AdviceRepository $adviceRepository): JsonResponse
    {
        $advice = $adviceRepository->find($id);

        $content = $request->toArray();

        foreach ($content as $key => $value) {
            switch ($key) {
                case 'month':
                    $advice->setMonth($value);
                    break;
                case 'description':
                    $advice->setDescription($value);
                    break;
                default:
                    break;
            }
        }

        $idMonth = $advice->getMonth();

        if ($idMonth <1 || $idMonth > 12) {
            /*return new JsonResponse([
                ['message' => 'Month must be between 1 and 12'], Response::HTTP_BAD_REQUEST, [], true
            ]);*/
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "Error Processing Request");
            
        }
            
        $entityManager->persist($advice);
        $entityManager->flush();
    
        $jsonAdvice = $serializer->serialize($advice, 'json');

        return new JsonResponse([null, Response::HTTP_NO_CONTENT]);
    }

    #[Route('/api/advices/{id}', name: 'app_advices_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteAdvice(Advice $advice, EntityManagerInterface $entityManager, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $cachePool->invalidateTags(["meteoCache"]);
        $entityManager->remove($advice);
        $entityManager->flush();
    
        return new JsonResponse([null, Response::HTTP_NO_CONTENT]);
    }

    private function monthCheck(int $idMonth) : void 
    {
        if ($idMonth <1 || $idMonth > 12) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "Month must be between 1 and 12");
        }
    }
}
