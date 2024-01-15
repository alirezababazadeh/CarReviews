<?php

namespace App\DataFixtures;

use App\Story\DefaultCarsStory;
use App\Story\DefaultReviewsStory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        DefaultCarsStory::load();
        DefaultReviewsStory::load();
    }
}
