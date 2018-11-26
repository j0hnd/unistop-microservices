<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Application;
use App\Models\Service;
use App\Models\User;
use App\Models\Token;
use Tymon\JWTAuth\Exceptions\JWTException;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('applications')->truncate();
        DB::table('services')->truncate();
        DB::table('tokens')->truncate();
        DB::table('tokenables')->truncate();
        Model::unguard();

        $application = Application::create([
            'name' => 'Unistop',
            'slug' => 'unistop',
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $services = [
            [
                'name' => 'MyTravelCompared',
                'salt' => '',
                'enabled' => 1,
                'created_by' => 1
            ],
            [
                'name' => 'Clockatoo',
                'salt' => '',
                'enabled' => 1,
                'created_by' => 1
            ]
        ];

        foreach ($services as $service) {
            $service['slug'] = str_slug($service['name']);
            $service['salt'] = Hash::make(str_random(8));
            $service = new Service($service);
            $service->save();

            $application->services()->save($service);

            $factory = JWTFactory::addClaims([
                'sub' => $application->id,
                'iss' => $service->name,
                'iat' => now()->timestamp,
                'exp' => JWTFactory::getTTL(),
                'nbf' => now()->timestamp,
                'jti' => $service->salt
            ]);

            $payload = JWTAuth::encode($factory->make());

            $token = Token::create(['token' => $payload]);

            $token->application()->attach($application->id);
            $token->services()->attach($service->id);
        }

        Model::reguard();
    }
}
