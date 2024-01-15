<?php


use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Review;
use App\Factory\CarFactory;
use App\Factory\ReviewFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ReviewsTest
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        ReviewFactory::createMany(100);

        $response = static::createClient()->request('GET', '/reviews');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/Review',
            '@id' => '/reviews',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
            'hydra:view' => [
                '@id' => '/reviews?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/reviews?page=1',
                'hydra:last' => '/reviews?page=4',
                'hydra:next' => '/reviews?page=2',
            ],
        ]);

        $this->assertCount(30, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(Review::class);
    }

    public function testCreate(): void
    {
        $car = CarFactory::createOne([
            'rating' => '8',
            'text' => 'Nice',
            'carId' => '4',
        ]);
        $response = static::createClient()->request('POST', '/reviews', ['json' => [
            'rating' => '8',
            'text' => 'Nice',
            'carId' => $car->getId(),
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Review',
            '@type' => 'Review',
            'rating' => '8',
            'text' => 'Nice',
            'carId' => $car->getId(),
        ]);
        $this->assertMatchesRegularExpression('~^/reviews/\d+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(Review::class);
    }

    public function testCreateInvalid(): void
    {
        static::createClient()->request('POST', '/reviews', ['json' => [
            'rating' => '134',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolationList',
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred'
        ]);
    }

    public function testUpdate(): void
    {
        CarFactory::createOne(['rating' => '8', 'brand' => 'Toyota']);

        $client = static::createClient();
        $rating = $this->findRatingBy(Review::class, ['rating' => '8']);

        // Use the PATCH method here to do a partial update
        $client->request('PATCH', $rating, [
            'json' => [
                'title' => 'updated title',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $rating,
            'rating' => '8',
            'text' => 'Nice',
        ]);
    }

    public function testDelete(): void
    {
        Review::createOne(['rating' => '8']);

        $client = static::createClient();
        $model = $this->findModelBy(Review::class, ['rating' => '8']);

        $client->request('DELETE', $model);

        $this->assertResponseStatusCodeSame(204);
    }
}