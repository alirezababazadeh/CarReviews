<?php

namespace App\Controller;

use App\Repository\CarRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class TopReviewsController extends AbstractController
{
    #[Route('/cars/{carId}/reviews.{_format}', name: 'top-reviews', methods: ['GET'])]
    public function __invoke(CarRepository $carRepository, ReviewRepository $repository, int $carId): Response
    {
        return $this->json($repository->findByCar($carRepository->find($carId)));
    }
}