<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Car;
use App\Factory\CarFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CarsTest
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        CarFactory::createMany(100);

        $response = static::createClient()->request('GET', '/cars');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/Car',
            '@id' => '/cars',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
            'hydra:view' => [
                '@id' => '/cars?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/cars?page=1',
                'hydra:last' => '/cars?page=4',
                'hydra:next' => '/cars?page=2',
            ],
        ]);

        $this->assertCount(30, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(Car::class);
    }

    public function testCreate(): void
    {
        $response = static::createClient()->request('POST', '/cars', ['json' => [
            'brand' => 'Honda',
            'model' => 'Amaze',
            'color' => 'blue',
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Car',
            '@type' => 'Car',
            'brand' => 'Honda',
            'model' => 'Amaze',
            'color' => 'blue'
        ]);
        $this->assertMatchesRegularExpression('~^/cars/\d+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(Car::class);
    }

    public function testCreateInvalid(): void
    {
        static::createClient()->request('POST', '/cars', ['json' => [
            'year' => '134',
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
        CarFactory::createOne(['model' => 'Camary', 'brand' => 'Toyota']);

        $client = static::createClient();
        // findIriBy allows to retrieve the IRI of an item by searching for some of its properties.
        $brand = $this->findBrandBy(Car::class, ['model' => 'Camary']);

        // Use the PATCH method here to do a partial update
        $client->request('PATCH', $brand, [
            'json' => [
                'title' => 'updated title',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $brand,
            'isbn' => '9781344037075',
            'title' => 'updated title',
        ]);
    }

    public function testDelete(): void
    {
        Car::createOne(['model' => 'XXXX']);

        $client = static::createClient();
        $model = $this->findModelBy(Car::class, ['model' => 'XXXX']);

        $client->request('DELETE', $model);

        $this->assertResponseStatusCodeSame(204);
    }
}