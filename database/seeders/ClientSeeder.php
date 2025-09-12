<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user to assign as creator
        $admin = User::where('role', 'admin')->first();
        
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@zentropay.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
            ]);
        }

        $clients = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'phone' => '+1-555-0123',
                'address' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'USA',
                'postal_code' => '10001',
                'company_name' => 'Smith Enterprises',
                'tax_id' => 'TAX123456789',
                'website' => 'https://smithenterprises.com',
                'notes' => 'Preferred contact method: Email',
                'credit_limit' => 50000.00,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@techcorp.com',
                'phone' => '+1-555-0456',
                'address' => '456 Tech Avenue',
                'city' => 'San Francisco',
                'state' => 'CA',
                'country' => 'USA',
                'postal_code' => '94105',
                'company_name' => 'TechCorp Solutions',
                'tax_id' => 'TAX987654321',
                'website' => 'https://techcorp.com',
                'notes' => 'High priority client - responds quickly',
                'credit_limit' => 100000.00,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'michael.brown@retailco.com',
                'phone' => '+1-555-0789',
                'address' => '789 Retail Boulevard',
                'city' => 'Chicago',
                'state' => 'IL',
                'country' => 'USA',
                'postal_code' => '60601',
                'company_name' => 'RetailCo Inc',
                'tax_id' => 'TAX456789123',
                'website' => 'https://retailco.com',
                'notes' => 'Bulk orders - negotiate pricing',
                'credit_limit' => 25000.00,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily.davis@consulting.com',
                'phone' => '+1-555-0321',
                'address' => '321 Consulting Lane',
                'city' => 'Boston',
                'state' => 'MA',
                'country' => 'USA',
                'postal_code' => '02101',
                'company_name' => 'Davis Consulting',
                'tax_id' => 'TAX789123456',
                'website' => 'https://davisconsulting.com',
                'notes' => 'Quarterly billing preferred',
                'credit_limit' => 15000.00,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Robert Wilson',
                'email' => 'robert.wilson@startup.io',
                'phone' => '+1-555-0654',
                'address' => '654 Startup Street',
                'city' => 'Austin',
                'state' => 'TX',
                'country' => 'USA',
                'postal_code' => '73301',
                'company_name' => 'StartupIO',
                'tax_id' => 'TAX321654987',
                'website' => 'https://startupio.com',
                'notes' => 'New client - monitor payment patterns',
                'credit_limit' => 10000.00,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
        ];

        foreach ($clients as $clientData) {
            Client::create($clientData);
        }
    }
}
