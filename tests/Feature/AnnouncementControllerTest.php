<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdApplication;
use App\Models\Address;
use App\Models\Role;
use App\Models\User;
use App\Models\BabysitterProfile;
use App\Models\ParentProfile;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Tests\TestCase;
use Mockery;

class AnnouncementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'parent']);
        Role::create(['name' => 'babysitter']);
        Role::create(['name' => 'admin']);
        
        // Disable notifications for testing
        Notification::fake();
    }

    /** @test */
    public function index_displays_active_announcements_only()
    {
        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        // Create parent user
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        // Create active announcement
        $activeAd = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Active Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        // Create inactive announcement
        Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Expired Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->subDay(),
            'date_end' => now()->subDay()->addHours(4),
            'status' => 'expired',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        $response = $this->get(route('announcements.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Annonces')
                ->has('announcements')
                ->where('announcements.data.0.id', $activeAd->id)
                ->has('announcements.data', 1) // Only active announcement
        );
    }

    /** @test */
    public function index_filters_by_min_rate()
    {
        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        // Create announcements with different rates
        $lowRateAd = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Low Rate Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 12.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        $highRateAd = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'High Rate Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 20.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        // Test filtering by minimum rate
        $response = $this->get(route('announcements.index', ['min_rate' => 15]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Annonces')
                ->has('announcements.data', 1)
                ->where('announcements.data.0.id', $highRateAd->id)
        );
    }

    /** @test */
    public function index_filters_by_age_range()
    {
        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        // Create announcement with baby (months)
        $babyAd = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Baby Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Baby', 'age' => '8', 'unite' => 'mois']],
        ]);

        // Create announcement with older child
        $olderChildAd = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Older Child Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Child', 'age' => '8', 'unite' => 'ans']],
        ]);

        // Test filtering for babies (<3 years)
        $response = $this->get(route('announcements.index', ['age_range' => '<3']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Annonces')
                ->has('announcements.data', 1)
                ->where('announcements.data.0.id', $babyAd->id)
        );
    }

    /** @test */
    public function authenticated_babysitter_can_apply_to_announcement()
    {
        // Create parent
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        // Create verified babysitter
        $babysitter = User::factory()->create([
            'stripe_account_id' => 'acct_test123'
        ]);
        $babysitter->roles()->attach(Role::where('name', 'babysitter')->first());
        
        // Create verified babysitter profile
        BabysitterProfile::create([
            'user_id' => $babysitter->id,
            'verification_status' => 'verified',
            'hourly_rate' => 15.00,
            'description' => 'Experienced babysitter',
        ]);

        // Mock Stripe service
        $stripeService = Mockery::mock(StripeService::class);
        $stripeService->shouldReceive('getAccountDetails')
            ->with($babysitter)
            ->once()
            ->andReturn([
                'charges_enabled' => true,
                'payouts_enabled' => true
            ]);
        
        $this->app->instance(StripeService::class, $stripeService);

        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        $announcement = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Test Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        $this->actingAs($babysitter);

        $response = $this->post(route('announcements.apply', $announcement), [
            'motivation_note' => 'I would love to help with your children',
            'proposed_rate' => 16.00,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Votre candidature a été envoyée avec succès.');

        $this->assertDatabaseHas('ad_applications', [
            'ad_id' => $announcement->id,
            'babysitter_id' => $babysitter->id,
            'motivation_note' => 'I would love to help with your children',
            'proposed_rate' => 16.00,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function unverified_babysitter_cannot_apply_to_announcement()
    {
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        $babysitter = User::factory()->create();
        $babysitter->roles()->attach(Role::where('name', 'babysitter')->first());
        
        // Create unverified babysitter profile
        BabysitterProfile::create([
            'user_id' => $babysitter->id,
            'verification_status' => 'pending',
            'hourly_rate' => 15.00,
        ]);

        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        $announcement = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Test Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        $this->actingAs($babysitter);

        $response = $this->post(route('announcements.apply', $announcement));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Votre compte n\'est pas vérifié. Vous devez compléter votre profil et demander la vérification avant de pouvoir postuler aux annonces.');

        $this->assertDatabaseMissing('ad_applications', [
            'ad_id' => $announcement->id,
            'babysitter_id' => $babysitter->id,
        ]);
    }

    /** @test */
    public function babysitter_without_stripe_account_cannot_apply()
    {
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        $babysitter = User::factory()->create([
            'stripe_account_id' => null // No Stripe account
        ]);
        $babysitter->roles()->attach(Role::where('name', 'babysitter')->first());
        
        BabysitterProfile::create([
            'user_id' => $babysitter->id,
            'verification_status' => 'verified',
            'hourly_rate' => 15.00,
        ]);

        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        $announcement = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Test Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        $this->actingAs($babysitter);

        $response = $this->post(route('announcements.apply', $announcement));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Merci de vous rendre dans "Paiements" pour finaliser votre profil de paiement avant de pouvoir postuler aux annonces.');
    }

    /** @test */
    public function babysitter_cannot_apply_twice_to_same_announcement()
    {
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        $babysitter = User::factory()->create([
            'stripe_account_id' => 'acct_test123'
        ]);
        $babysitter->roles()->attach(Role::where('name', 'babysitter')->first());
        
        BabysitterProfile::create([
            'user_id' => $babysitter->id,
            'verification_status' => 'verified',
            'hourly_rate' => 15.00,
        ]);

        // Mock Stripe service
        $stripeService = Mockery::mock(StripeService::class);
        $stripeService->shouldReceive('getAccountDetails')
            ->with($babysitter)
            ->once()
            ->andReturn([
                'charges_enabled' => true,
                'payouts_enabled' => true
            ]);
        
        $this->app->instance(StripeService::class, $stripeService);

        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        $announcement = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Test Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        // Create existing application
        AdApplication::create([
            'ad_id' => $announcement->id,
            'babysitter_id' => $babysitter->id,
            'status' => 'pending',
            'proposed_rate' => 15.00,
        ]);

        $this->actingAs($babysitter);

        $response = $this->post(route('announcements.apply', $announcement));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Vous avez déjà postulé à cette annonce.');
    }

    /** @test */
    public function parent_can_create_announcement()
    {
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        $this->actingAs($parent);

        $response = $this->get(route('announcements.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('CreateAnnouncement')
                ->has('user')
                ->where('isGuest', false)
        );
    }

    /** @test */
    public function guest_can_create_announcement()
    {
        $response = $this->get(route('announcements.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('CreateAnnouncement')
                ->where('isGuest', true)
                ->where('user', null)
        );
    }

    /** @test */
    public function authenticated_parent_can_store_announcement()
    {
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        $this->actingAs($parent);

        $announcementData = [
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'children' => [
                ['nom' => 'Emma', 'age' => '3', 'unite' => 'ans'],
                ['nom' => 'Luca', 'age' => '5', 'unite' => 'ans']
            ],
            'address' => '123 Test Street, Paris',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'additional_info' => 'Very well-behaved children',
            'hourly_rate' => 15.00,
            'estimated_duration' => 8,
            'estimated_total' => 120.00,
        ];

        $response = $this->post(route('announcements.store'), $announcementData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Annonce créée avec succès !');

        $this->assertDatabaseHas('ads', [
            'parent_id' => $parent->id,
            'hourly_rate' => 15.00,
            'is_guest' => false,
        ]);

        $this->assertDatabaseHas('addresses', [
            'address' => '123 Test Street, Paris',
            'postal_code' => '75001',
            'country' => 'France',
        ]);
    }

    /** @test */
    public function guest_can_store_announcement_with_email()
    {
        $announcementData = [
            'email' => 'parent@example.com',
            'guest_firstname' => 'Marie',
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'children' => [
                ['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']
            ],
            'address' => '123 Test Street, Paris',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'hourly_rate' => 15.00,
            'estimated_duration' => 8,
            'estimated_total' => 120.00,
        ];

        $response = $this->post(route('announcements.store'), $announcementData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Votre annonce a été créée avec succès ! Vérifiez votre email pour les instructions.');

        $this->assertDatabaseHas('ads', [
            'parent_id' => null,
            'guest_email' => 'parent@example.com',
            'guest_firstname' => 'Marie',
            'is_guest' => true,
            'hourly_rate' => 15.00,
        ]);
    }

    /** @test */
    public function announcement_show_displays_correct_data()
    {
        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        $parent = User::factory()->create([
            'firstname' => 'John',
            'lastname' => 'Doe'
        ]);
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        $announcement = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Test Babysitting Job',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
            'additional_info' => 'Special instructions here',
        ]);

        // Create expected slug
        $expectedSlug = now()->addDay()->format('Y-m-d') . '-test-babysitting-job-' . $announcement->id;

        $response = $this->get(route('announcements.show', ['slug' => $expectedSlug]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('AnnouncementDetail')
                ->where('announcement.id', $announcement->id)
                ->where('announcement.title', 'Test Babysitting Job')
                ->where('announcement.parent.firstname', 'John')
                ->where('announcement.parent.lastname', 'Doe')
                ->has('announcement.address')
        );
    }

    /** @test */
    public function parent_can_edit_their_own_announcement()
    {
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        $announcement = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Test Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        $this->actingAs($parent);

        $response = $this->get(route('announcements.edit', $announcement));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('EditAnnouncement')
                ->has('announcement')
                ->where('announcement.id', $announcement->id)
        );
    }

    /** @test */
    public function parent_cannot_edit_others_announcement()
    {
        $parent1 = User::factory()->create();
        $parent1->roles()->attach(Role::where('name', 'parent')->first());
        
        $parent2 = User::factory()->create();
        $parent2->roles()->attach(Role::where('name', 'parent')->first());

        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        $announcement = Ad::create([
            'parent_id' => $parent1->id,
            'title' => 'Test Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        $this->actingAs($parent2);

        $response = $this->get(route('announcements.edit', $announcement));

        $response->assertStatus(403);
    }

    /** @test */
    public function parent_can_update_their_announcement()
    {
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        $announcement = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Test Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        $this->actingAs($parent);

        $updateData = [
            'additional_info' => 'Updated information',
            'date_start' => now()->addDays(2)->toDateTimeString(),
            'date_end' => now()->addDays(2)->addHours(5)->toDateTimeString(),
            'hourly_rate' => 18.00,
            'children' => [
                ['nom' => 'Emma', 'age_range' => '3-6-ans'],
                ['nom' => 'Luca', 'age_range' => '6-10-ans']
            ],
        ];

        $response = $this->put(route('announcements.update', $announcement), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Annonce mise à jour avec succès !');

        $this->assertDatabaseHas('ads', [
            'id' => $announcement->id,
            'hourly_rate' => 18.00,
            'additional_info' => 'Updated information',
        ]);
    }

    /** @test */
    public function parent_can_delete_their_announcement()
    {
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        $announcement = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Test Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        $this->actingAs($parent);

        $response = $this->delete(route('announcements.destroy', $announcement));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Annonce supprimée avec succès !');

        $this->assertSoftDeleted('ads', [
            'id' => $announcement->id,
        ]);
    }

    /** @test */
    public function parent_can_cancel_announcement_with_refunds()
    {
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        $announcement = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Test Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        $this->actingAs($parent);

        $response = $this->post(route('announcements.cancel', $announcement), [
            'reason' => 'found_other_solution',
            'note' => 'Found a family member to help'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Annonce annulée avec succès'
        ]);

        $this->assertDatabaseHas('ads', [
            'id' => $announcement->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'found_other_solution',
            'cancellation_note' => 'Found a family member to help',
        ]);
    }

    /** @test */
    public function validation_fails_for_invalid_announcement_data()
    {
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        $this->actingAs($parent);

        $invalidData = [
            'date' => 'invalid-date',
            'start_time' => '25:00', // Invalid time
            'children' => [], // Empty children array
            'hourly_rate' => -5, // Negative rate
        ];

        $response = $this->post(route('announcements.store'), $invalidData);

        $response->assertSessionHasErrors([
            'date',
            'children',
            'address',
            'postal_code',
            'country',
            'latitude',
            'longitude',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}