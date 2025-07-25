<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

trait TestConfig
{
    use RefreshDatabase;

    /**
     * Setup the test database and essential data
     */
    protected function setUpTestDatabase(): void
    {
        // Ensure we're using a clean in-memory SQLite database for tests
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        
        // Clear any existing connections
        DB::purge('sqlite');
        
        // Run migrations
        $this->artisan('migrate:fresh');
        
        // Create essential roles
        $this->createEssentialRoles();
    }

    /**
     * Create essential roles for testing
     */
    protected function createEssentialRoles(): void
    {
        $roles = ['parent', 'babysitter', 'admin'];
        
        foreach ($roles as $roleName) {
            \App\Models\Role::firstOrCreate(['name' => $roleName]);
        }
    }

    /**
     * Create a verified babysitter user for testing
     */
    protected function createVerifiedBabysitter(array $attributes = []): \App\Models\User
    {
        $babysitter = \App\Models\User::factory()->create(array_merge([
            'stripe_account_id' => 'acct_test' . uniqid()
        ], $attributes));
        
        $babysitter->roles()->attach(\App\Models\Role::where('name', 'babysitter')->first());
        
        \App\Models\BabysitterProfile::create([
            'user_id' => $babysitter->id,
            'verification_status' => 'verified',
            'hourly_rate' => 15.00,
            'description' => 'Experienced babysitter',
        ]);

        return $babysitter;
    }

    /**
     * Create a parent user for testing
     */
    protected function createParent(array $attributes = []): \App\Models\User
    {
        $parent = \App\Models\User::factory()->create($attributes);
        $parent->roles()->attach(\App\Models\Role::where('name', 'parent')->first());
        
        return $parent;
    }

    /**
     * Create an address for testing
     */
    protected function createAddress(array $attributes = []): \App\Models\Address
    {
        return \App\Models\Address::create(array_merge([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ], $attributes));
    }

    /**
     * Create an announcement for testing
     */
    protected function createAnnouncement(\App\Models\User $parent, \App\Models\Address $address, array $attributes = []): \App\Models\Ad
    {
        return \App\Models\Ad::create(array_merge([
            'parent_id' => $parent->id,
            'title' => 'Test Babysitting Job',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ], $attributes));
    }
}