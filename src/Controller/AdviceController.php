<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\AdviceRepository;
use App\Entity\Advice;

final class AdviceController extends AbstractController
{
    #[Route('/api/advices', name: 'app_advices', methods: ['GET'])]
    public function getAdvices(AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $adviceList = $adviceRepository->findAll();
        $jsonAdivceList = $serializer->serialize($adviceList, 'json');

        return new JsonResponse([
            $jsonAdivceList, Response::HTTP_OK, [], true
        ]);
    }
    
    #[Route('/api/advices/{id}', name: 'app_advices_month', methods: ['GET'])]
    public function getAdvicesMonth(int $id, AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        if ($id <1 || $id > 12) {
            return new JsonResponse([
                'message' => 'Month must be between 1 and 12', Response::HTTP_BAD_REQUEST
            ]);
        }
        else {
            $adviceList = $adviceRepository->findByMonth($id);
    
            $jsonAdviceList = $serializer->serialize($adviceList, 'json');
            return new JsonResponse([
                $jsonAdviceList, Response::HTTP_OK, [], true
            ]);
            
        }


    }
}
