<?php

namespace App\DataFixtures;

use App\Entity\Commune;
use App\Entity\Media;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create("FR-fr");
        for ($i = 0; $i < 20; $i++) {
            $commune = new Commune();

            $codesPostaux = [];
            for ($j = 0; $j < $faker->numberBetween(1,5); $j++){
                array_push($codesPostaux,$faker->numberBetween(1000,80000));
            }
            $commune
                ->setNom($faker->state)
                ->setCode($faker->postcode)
                ->setCodeDepartement($faker->numberBetween(1000,80000))
                ->setCodesPostaux($codesPostaux)
                ->setCodeRegion($faker->numberBetween(25000,500000))
                ->setPopulation($faker->numberBetween(10000, 1000000));
            if (mt_rand(0,1) === 1){
                $media = new Media();
                $media->setCommune($commune)
                    ->setUrl($faker->imageUrl(640,480,'city'));
                $manager->persist($media);
            }
            $manager->persist($commune);
        }
        $manager->flush();
    }
}
