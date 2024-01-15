<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Car;
use App\Factory\CarFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CarsTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        CarFactory::createMany(100);

        $response = static::createClient()->request('GET', '/api/cars');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/contexts/Car',
            '@id' => '/api/cars',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
        ]);

        $this->assertCount(30, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(Car::class);
    }

    public function testCreate(): void
    {
        $response = static::createClient()->request(
            'POST',
            '/api/cars',
            [
                'json' => [
                    'brand' => 'Honda',
                    'model' => 'Amaze',
                    'color' => 'blue',
                ],
                'headers' => [
                    'content-type' => 'application/ld+json; charset=utf-8'
                ]
            ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Car',
            '@type' => 'Car',
            'brand' => 'Honda',
            'model' => 'Amaze',
            'color' => 'blue'
        ]);
        $this->assertMatchesRegularExpression('~^/api/cars/\d+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(Car::class);
    }

    public function testUpdate(): void
    {
        $car = CarFactory::createOne(['model' => 'Camary', 'brand' => 'Toyota']);

        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/cars/' . $car->getId(),
            [
                'json' => [
                    "brand" => "Toyota",
                    "model" => "NCary",
                    "color" => "Black"
                ],
                'headers' => [
                    'content-type' => 'application/ld+json; charset=utf-8'
                ]
            ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            "@context" => "/api/contexts/Car",
            "@id" => "/api/cars/" . $car->getId(),
            "@type" => "Car",
            "id" => $car->getId(),
            "brand" => "Toyota",
            "model" => "NCary",
            "color" => "Black"
        ]);
    }

    public function testDelete(): void
    {
        $car = CarFactory::createOne(['model' => 'XXXX']);

        $client = static::createClient();

        $client->request('DELETE', '/api/cars/' . $car->getId());

        $this->assertResponseStatusCodeSame(204);
        static::getContainer()->get('doctrine')->getRepository(Car::class)->findOneBy(['model' => 'XXXX']);
    }
}