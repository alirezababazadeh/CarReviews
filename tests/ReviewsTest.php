<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Review;
use App\Factory\CarFactory;
use App\Factory\ReviewFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ReviewsTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        ReviewFactory::createMany(100);

        $response = static::createClient()->request('GET', '/api/reviews');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/contexts/Review',
            '@id' => '/api/reviews',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
        ]);

        $this->assertCount(30, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(Review::class);
    }

    public function testCreate(): void
    {
        $car = CarFactory::createOne([
            'brand' => 'Toyota',
            'model' => 'Camary',
            'color' => 'red',
        ]);

        $response = static::createClient()->request(
            'POST',
            '/api/reviews',
            [
                'json' => [
                    'rating' => 8,
                    'text' => 'Nice',
                    'car' => '/api/cars/' . $car->getId(),
                ],
                'headers' => [
                    'content-type' => 'application/ld+json; charset=utf-8'
                ]
            ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Review',
            '@type' => 'Review',
            'rating' => 8,
            'text' => 'Nice',
            'car' => '/api/cars/' . $car->getId(),
        ]);
        $this->assertMatchesRegularExpression('~^/api/reviews/\d+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(Review::class);
    }

    public function testCreateInvalid(): void
    {
        $car = CarFactory::createOne([
            'brand' => 'Toyota',
            'model' => 'Camary',
            'color' => 'red',
        ]);

        static::createClient()->request(
            'POST',
            '/api/reviews',
            [
                'json' => [
                    'rating' => 134,
                    'car' => '/api/cars/' . $car->getId()
                ],
                'headers' => [
                    'content-type' => 'application/ld+json; charset=utf-8'
                ]
            ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolationList',
            'title' => 'An error occurred',
            'hydra:description' => 'rating: This value should be less than 11.',
        ]);
    }

    public function testUpdate(): void
    {
        $review = ReviewFactory::createOne(['rating' => 8, 'text' => 'Beauty']);

        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/reviews/' . $review->getId(),
            [
                'json' => [
                    'rating' => 6,
                ],
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ]
            ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/api/reviews/' . $review->getId(),
            'rating' => 6,
            'text' => 'Beauty',
        ]);
    }

    public function testDelete(): void
    {
        $review = ReviewFactory::createOne(['rating' => 9]);

        $client = static::createClient();

        $client->request('DELETE', '/api/reviews/' . $review->getId());

        $this->assertResponseStatusCodeSame(204);
        static::getContainer()->get('doctrine')->getRepository(Review::class)->findOneBy(['rating' => 9]);
    }
}