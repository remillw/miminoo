<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdApplication;
use App\Models\Address;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Models\BabysitterProfile;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Tests\TestCase;
use Mockery;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $stripeService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'parent']);
        Role::create(['name' => 'babysitter']);
        Role::create(['name' => 'admin']);
        
        // Mock Stripe service
        $this->stripeService = Mockery::mock(StripeService::class);
        $this->app->instance(StripeService::class, $this->stripeService);
        
        // Disable notifications for testing
        Notification::fake();
        
        // Use fake storage for testing
        Storage::fake('local');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function parent_can_access_payments_index_page()
    {
        $parent = $this->createParent();
        
        $this->actingAs($parent);

        $response = $this->get(route('payments.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Payments/Index')
                ->where('mode', 'parent')
                ->has('stats')
                ->has('transactions')
                ->has('filters')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function babysitter_can_access_payments_index_page_in_babysitter_mode()
    {
        $babysitter = $this->createVerifiedBabysitter();

        // Mock Stripe account creation
        $this->stripeService
            ->shouldReceive('createConnectAccount')
            ->with($babysitter)
            ->once();

        // Mock Stripe account status methods
        $this->stripeService
            ->shouldReceive('getAccountStatus')
            ->with($babysitter)
            ->once()
            ->andReturn('active');

        $this->stripeService
            ->shouldReceive('getAccountDetails')
            ->with($babysitter)
            ->once()
            ->andReturn([
                'charges_enabled' => true,
                'payouts_enabled' => true,
            ]);

        $this->stripeService
            ->shouldReceive('getAccountBalance')
            ->with($babysitter)
            ->once()
            ->andReturn([
                'available' => [['amount' => 5000, 'currency' => 'eur']],
                'pending' => [['amount' => 1500, 'currency' => 'eur']],
            ]);

        $this->stripeService
            ->shouldReceive('getRecentTransactions')
            ->with($babysitter, 10)
            ->once()
            ->andReturn([]);

        $this->actingAs($babysitter);

        $response = $this->get(route('payments.index', ['mode' => 'babysitter']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Payments/Index')
                ->where('mode', 'babysitter')
                ->has('accountStatus')
                ->has('accountDetails')
                ->has('accountBalance')
                ->has('recentTransactions')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function babysitter_without_stripe_account_gets_account_created_automatically()
    {
        $babysitter = $this->createVerifiedBabysitter([
            'stripe_account_id' => null
        ]);

        // Mock Stripe account creation
        $this->stripeService
            ->shouldReceive('createConnectAccount')
            ->with($babysitter)
            ->once();

        // Mock subsequent calls
        $this->stripeService
            ->shouldReceive('getAccountStatus')
            ->with($babysitter)
            ->once()
            ->andReturn('pending');

        $this->actingAs($babysitter);

        $response = $this->get(route('payments.index', ['mode' => 'babysitter']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Payments/Index')
                ->where('mode', 'babysitter')
                ->where('accountStatus', 'pending')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function parent_payments_page_shows_correct_statistics()
    {
        $parent = $this->createParent();
        
        // Create test reservations with different statuses
        $address = $this->createAddress();
        $babysitter = $this->createVerifiedBabysitter();
        
        $ad = $this->createAnnouncement($parent, $address);
        
        // Completed reservation
        $completedReservation = Reservation::create([
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => null,
            'status' => 'completed',
            'total_deposit' => 50.00,
            'hourly_rate' => 15.00,
            'deposit_amount' => 45.00,
            'service_fee' => 5.00,
            'babysitter_amount' => 45.00,
            'service_start_at' => now()->subHours(3),
            'service_end_at' => now()->subHours(1),
        ]);

        // Pending payment reservation
        $pendingReservation = Reservation::create([
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => null,
            'status' => 'pending_payment',
            'total_deposit' => 60.00,
            'hourly_rate' => 18.00,
            'deposit_amount' => 54.00,
            'service_fee' => 6.00,
            'babysitter_amount' => 54.00,
        ]);

        $this->actingAs($parent);

        $response = $this->get(route('payments.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Payments/Index')
                ->where('stats.total_spent', 50.00)
                ->where('stats.total_reservations', 2)
                ->where('stats.pending_payments', 1)
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function babysitter_payments_page_shows_funds_status_correctly()
    {
        $babysitter = $this->createVerifiedBabysitter();
        $parent = $this->createParent();
        
        // Mock Stripe calls
        $this->mockBabysitterStripeAccount($babysitter);

        // Create a completed reservation
        $reservation = Reservation::create([
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => null,
            'status' => 'service_completed',
            'total_deposit' => 50.00,
            'hourly_rate' => 15.00,
            'deposit_amount' => 45.00,
            'service_fee' => 5.00,
            'babysitter_amount' => 45.00,
            'service_start_at' => now()->subHours(3),
            'service_end_at' => now()->subHour(),
        ]);

        $this->actingAs($babysitter);

        $response = $this->get(route('payments.index', ['mode' => 'babysitter']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Payments/Index')
                ->where('mode', 'babysitter')
                ->has('recentTransactions.data', 1)
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function parent_can_download_invoice_for_completed_reservation()
    {
        $parent = $this->createParent();
        $babysitter = $this->createVerifiedBabysitter();
        
        // Create a completed reservation
        $reservation = Reservation::create([
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => null,
            'status' => 'completed',
            'total_deposit' => 50.00,
            'hourly_rate' => 15.00,
            'deposit_amount' => 45.00,
            'service_fee' => 5.00,
            'babysitter_amount' => 45.00,
            'service_start_at' => now()->subHours(3),
            'service_end_at' => now()->subHour(),
        ]);

        $this->actingAs($parent);

        $response = $this->get(route('payments.download-invoice', $reservation));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function parent_cannot_download_invoice_for_incomplete_reservation()
    {
        $parent = $this->createParent();
        $babysitter = $this->createVerifiedBabysitter();
        
        // Create a pending reservation
        $reservation = Reservation::create([
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => null,
            'status' => 'pending_payment',
            'total_deposit' => 50.00,
            'hourly_rate' => 15.00,
            'deposit_amount' => 45.00,
            'service_fee' => 5.00,
            'babysitter_amount' => 45.00,
            'service_start_at' => now()->addHour(),
            'service_end_at' => now()->addHours(3),
        ]);

        $this->actingAs($parent);

        $response = $this->get(route('payments.download-invoice', $reservation));

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'La facture n\'est pas encore disponible pour cette réservation. Statut actuel: pending_payment'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function parent_cannot_download_invoice_before_service_ends()
    {
        $parent = $this->createParent();
        $babysitter = $this->createVerifiedBabysitter();
        
        // Create a paid reservation with future service end time
        $reservation = Reservation::create([
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => null,
            'status' => 'paid',
            'total_deposit' => 50.00,
            'hourly_rate' => 15.00,
            'deposit_amount' => 45.00,
            'service_fee' => 5.00,
            'babysitter_amount' => 45.00,
            'service_start_at' => now()->addHour(),
            'service_end_at' => now()->addHours(3),
        ]);

        $this->actingAs($parent);

        $response = $this->get(route('payments.download-invoice', $reservation));

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'La facture n\'est disponible qu\'après la fin du service de babysitting.'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function other_user_cannot_download_invoice_for_reservation()
    {
        $parent1 = $this->createParent();
        $parent2 = $this->createParent();
        $babysitter = $this->createVerifiedBabysitter();
        
        // Create a completed reservation for parent1
        $reservation = Reservation::create([
            'parent_id' => $parent1->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => null,
            'status' => 'completed',
            'total_deposit' => 50.00,
            'hourly_rate' => 15.00,
            'deposit_amount' => 45.00,
            'service_fee' => 5.00,
            'babysitter_amount' => 45.00,
            'service_start_at' => now()->subHours(3),
            'service_end_at' => now()->subHour(),
        ]);

        // Try to access as parent2
        $this->actingAs($parent2);

        $response = $this->get(route('payments.download-invoice', $reservation));

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function payment_filters_work_correctly_for_parent()
    {
        $parent = $this->createParent();
        $babysitter = $this->createVerifiedBabysitter();
        
        // Create reservations from different time periods
        $oldReservation = Reservation::create([
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => null,
            'status' => 'completed',
            'total_deposit' => 50.00,
            'hourly_rate' => 15.00,
            'deposit_amount' => 45.00,
            'service_fee' => 5.00,
            'babysitter_amount' => 45.00,
            'created_at' => now()->subMonths(2),
            'service_start_at' => now()->subMonths(2),
            'service_end_at' => now()->subMonths(2)->addHours(2),
        ]);

        $recentReservation = Reservation::create([
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => null,
            'status' => 'completed',
            'total_deposit' => 60.00,
            'hourly_rate' => 18.00,
            'deposit_amount' => 54.00,
            'service_fee' => 6.00,
            'babysitter_amount' => 54.00,
            'created_at' => now()->subDays(5),
            'service_start_at' => now()->subDays(5),
            'service_end_at' => now()->subDays(5)->addHours(2),
        ]);

        $this->actingAs($parent);

        // Test month filter
        $response = $this->get(route('payments.index', ['date_filter' => 'month']));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Payments/Index')
                ->has('transactions.data', 1) // Should only show recent reservation
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function babysitter_payment_filters_work_correctly()
    {
        $babysitter = $this->createVerifiedBabysitter();
        $parent = $this->createParent();
        
        // Mock Stripe calls
        $this->mockBabysitterStripeAccount($babysitter);

        // Create reservations with different statuses
        $paidReservation = Reservation::create([
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => null,
            'status' => 'paid',
            'total_deposit' => 50.00,
            'hourly_rate' => 15.00,
            'deposit_amount' => 45.00,
            'service_fee' => 5.00,
            'babysitter_amount' => 45.00,
            'service_start_at' => now()->addHour(),
            'service_end_at' => now()->addHours(3),
        ]);

        $completedReservation = Reservation::create([
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => null,
            'status' => 'service_completed',
            'total_deposit' => 60.00,
            'hourly_rate' => 18.00,
            'deposit_amount' => 54.00,
            'service_fee' => 6.00,
            'babysitter_amount' => 54.00,
            'service_start_at' => now()->subHours(3),
            'service_end_at' => now()->subHour(),
        ]);

        $this->actingAs($babysitter);

        // Test status filter
        $response = $this->get(route('payments.index', [
            'mode' => 'babysitter',
            'status' => 'service_completed'
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Payments/Index')
                ->where('mode', 'babysitter')
                ->has('recentTransactions.data', 1) // Should only show completed reservation
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ajax_invoice_download_returns_base64_pdf()
    {
        $parent = $this->createParent();
        $babysitter = $this->createVerifiedBabysitter();
        
        // Create a completed reservation
        $reservation = Reservation::create([
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => null,
            'status' => 'completed',
            'total_deposit' => 50.00,
            'hourly_rate' => 15.00,
            'deposit_amount' => 45.00,
            'service_fee' => 5.00,
            'babysitter_amount' => 45.00,
            'service_start_at' => now()->subHours(3),
            'service_end_at' => now()->subHour(),
        ]);

        $this->actingAs($parent);

        $response = $this->getJson(route('payments.download-invoice', $reservation));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Facture générée avec succès',
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'pdf_base64',
            'filename'
        ]);
    }

    // Helper Methods

    private function createParent(array $attributes = []): User
    {
        $parent = User::factory()->create($attributes);
        $parent->roles()->attach(Role::where('name', 'parent')->first());
        
        return $parent;
    }

    private function createVerifiedBabysitter(array $attributes = []): User
    {
        $babysitter = User::factory()->create(array_merge([
            'stripe_account_id' => 'acct_test' . uniqid()
        ], $attributes));
        
        $babysitter->roles()->attach(Role::where('name', 'babysitter')->first());
        
        BabysitterProfile::create([
            'user_id' => $babysitter->id,
            'verification_status' => 'verified',
            'hourly_rate' => 15.00,
            'description' => 'Experienced babysitter',
        ]);

        return $babysitter;
    }

    private function createAddress(array $attributes = []): Address
    {
        return Address::create(array_merge([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ], $attributes));
    }

    private function createAnnouncement(User $parent, Address $address, array $attributes = []): Ad
    {
        return Ad::create(array_merge([
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

    private function mockBabysitterStripeAccount(User $babysitter): void
    {
        $this->stripeService
            ->shouldReceive('getAccountStatus')
            ->with($babysitter)
            ->andReturn('active');

        $this->stripeService
            ->shouldReceive('getAccountDetails')
            ->with($babysitter)
            ->andReturn([
                'charges_enabled' => true,
                'payouts_enabled' => true,
            ]);

        $this->stripeService
            ->shouldReceive('getAccountBalance')
            ->with($babysitter)
            ->andReturn([
                'available' => [['amount' => 5000, 'currency' => 'eur']],
                'pending' => [['amount' => 1500, 'currency' => 'eur']],
            ]);

        $this->stripeService
            ->shouldReceive('getRecentTransactions')
            ->with($babysitter, 10)
            ->andReturn([]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}