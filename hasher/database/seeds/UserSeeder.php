<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$username = "root@hasher.dev";
    	$password = "r00+h@sh3r";

        $default = [
        	'first_name' => 'Root',
			'last_name' => 'Root',
			'email' => $username,
			'password' => \Illuminate\Support\Facades\Hash::make($password)
		];

		$roles = [
			'slug' => 'admin',
			'name' => 'Admin',
			'permissions' => json_encode(['all' => true])
		];

        try {
			Model::unguard();

			DB::beginTransaction();

			DB::table('users')->truncate();
			DB::table('roles')->truncate();
			DB::table('role_users')->truncate();

			\App\Models\Role::create($roles);

			$user = Sentinel::registerAndActivate($default);

			if ($user) {
				$username = $user->email;

				// assign role
				$user = Sentinel::findById($user->id);

				$role = Sentinel::findRoleByName('admin');

				$role->users()->attach($user);

				DB::commit();

				$this->command->info("Default user is '".$username."', password is '".$password."'");
			} else {
				DB::rollback();

				$this->command->alert("Error in registering default user.");
			}

			Model::reguard();

		} catch (\Exception $e) {
			DB::rollback();

        	$this->command->alert($e->getMessage());
		}
    }
}
