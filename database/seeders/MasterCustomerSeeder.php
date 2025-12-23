<?php

use Illuminate\Database\Seeder;
use App\Models\MasterCustomer;
use Illuminate\Support\Str;

class MasterCustomerSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create('id_ID');

        // Helper untuk membuat alias 3 huruf
        function generateAlias($companyName) {
            $words = explode(' ', strtoupper($companyName));
            $letters = '';
            foreach ($words as $word) {
                if (strlen($letters) >= 3) break;
                $letters .= substr($word, 0, 1);
            }
            return str_pad($letters, 3, 'X'); // fallback kalau kata kurang dari 3
        }

        // Buat 8 data MMLN
        for ($i = 1; $i <= 8; $i++) {
            $name = $faker->company;
            MasterCustomer::create([
                'cust_code' => strtoupper(Str::random(rand(2,3))),
                'alias' => generateAlias($name),
                'type' => 'MMLN',
                'name' => $name,
                'address' => $faker->address,
                'pic_name' => $faker->name,
                'pic_contact' => $faker->phoneNumber,
                'email' => $faker->safeEmail,
                'pay_terms' => $faker->numberBetween(15, 60) . ' days',
                'fob' => $faker->city,
                'delivery_method' => rand(1, 3),
                'bill_to' => $faker->address,
                'ship_to' => $faker->address,
                'status' => 1
            ]);
        }

        // Buat 7 data MMTEI
        for ($i = 1; $i <= 7; $i++) {
            $name = $faker->company;
            MasterCustomer::create([
                'cust_code' => strtoupper(Str::random(rand(2,3))),
                'alias' => generateAlias($name),
                'type' => 'MMTEI',
                'name' => $name,
                'address' => $faker->address,
                'pic_name' => $faker->name,
                'pic_contact' => $faker->phoneNumber,
                'email' => $faker->safeEmail,
                'pay_terms' => $faker->numberBetween(15, 60) . ' days',
                'fob' => $faker->city,
                'delivery_method' => rand(1, 3),
                'bill_to' => $faker->address,
                'ship_to' => $faker->address,
                'status' => 1
            ]);
        }
    }
}
