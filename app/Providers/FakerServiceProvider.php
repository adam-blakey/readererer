<?php

namespace App\Providers;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Support\ServiceProvider;
use App\Faker\PlaceHoldImageFakerProvider;

class FakerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(FakerGenerator::class, function () {
            $faker = FakerFactory::create();

            $faker->addProvider(new PlaceHoldImageFakerProvider($faker));

            return $faker;
        });

        $this->app->singleton(EloquentFactory::class, function ($app) {
            return EloquentFactory::construct(
                $app->make(FakerGenerator::class), $this->app->databasePath('factories')
            );
        });
    }
}