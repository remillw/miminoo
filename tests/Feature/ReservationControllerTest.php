<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdApplication;
use App\Models\Address;
use App\Models\Conversation;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use App\Models\BabysitterProfile;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Tests\TestCase;
use Mockery;

class ReservationControllerTest extends TestCase
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
    }

    /** @test */
    public function parent_can_create_reservation_from_application()
    {
        [$parent, $babysitter, $application] = $this->createApplicationScenario();

        // Mock Stripe PaymentIntent creation
        $mockPaymentIntent = (object) [
            'id' => 'pi_test123',
            'client_secret' => 'pi_test123_secret',
            'amount' => 5000, // 50.00 EUR in cents
        ];

        $this->stripeService
            ->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn($mockPaymentIntent);

        $this->actingAs($parent);

        $response = $this->post(route('reservations.create-from-application', $application), [
            'final_rate' => 20.00
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('reservations/payment', $response->getTargetUrl());

        $this->assertDatabaseHas('reservations', [
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => $application->id,
            'hourly_rate' => 20.00,
            'status' => 'pending_payment',
            'stripe_payment_intent_id' => 'pi_test123',
        ]);
    }

    /** @test */
    public function non_parent_cannot_create_reservation_from_application()
    {
        [$parent, $babysitter, $application] = $this->createApplicationScenario();

        $this->actingAs($babysitter);

        $response = $this->post(route('reservations.create-from-application', $application), [
            'final_rate' => 20.00
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function parent_can_confirm_payment_with_payment_intent()
    {
        [$parent, $babysitter, $reservation] = $this->createReservationScenario();

        // Mock Stripe PaymentIntent retrieval
        $mockPaymentIntent = (object) [
            'id' => 'pi_test123',
            'status' => 'succeeded',
            'payment_method' => 'pm_test456'
        ];

        $this->stripeService
            ->shouldReceive('retrievePaymentIntent')
            ->with('pi_test123')
            ->once()
            ->andReturn($mockPaymentIntent);

        $this->stripeService
            ->shouldReceive('savePaymentMethod')
            ->with('pm_test456', $parent)
            ->once();

        $this->actingAs($parent);

        $response = $this->post(route('reservations.confirm-payment', $reservation), [
            'payment_intent_id' => 'pi_test123',
            'save_payment_method' => true
        ]);

        $response->assertRedirect(route('messaging.index'));
        $response->assertSessionHas('success', 'Paiement confirmé ! La réservation est maintenant active.');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'paid',
        ]);
    }

    /** @test */
    public function parent_can_confirm_payment_with_saved_payment_method()
    {
        [$parent, $babysitter, $reservation] = $this->createReservationScenario();

        // Mock Stripe PaymentIntent creation with saved method
        $mockPaymentIntent = (object) [
            'id' => 'pi_new123',
            'status' => 'succeeded',
        ];

        $this->stripeService
            ->shouldReceive('createPaymentIntentWithSavedMethod')
            ->with(5000, 'eur', 'pm_saved123', $parent, 0, $babysitter)
            ->once()
            ->andReturn($mockPaymentIntent);

        $this->actingAs($parent);

        $response = $this->post(route('reservations.confirm-payment', $reservation), [
            'payment_method_id' => 'pm_saved123'
        ]);

        $response->assertRedirect(route('messaging.index'));

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'paid',
            'stripe_payment_intent_id' => 'pi_new123',
        ]);
    }

    /** @test */
    public function payment_confirmation_fails_with_unsuccessful_payment_intent()
    {
        [$parent, $babysitter, $reservation] = $this->createReservationScenario();

        // Mock failed PaymentIntent
        $mockPaymentIntent = (object) [
            'id' => 'pi_test123',
            'status' => 'requires_payment_method', // Failed status
        ];

        $this->stripeService
            ->shouldReceive('retrievePaymentIntent')
            ->with('pi_test123')
            ->once()
            ->andReturn($mockPaymentIntent);

        $this->actingAs($parent);

        $response = $this->post(route('reservations.confirm-payment', $reservation), [
            'payment_intent_id' => 'pi_test123'
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => 'Le paiement n\'a pas été confirmé'
        ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'pending_payment', // Status unchanged
        ]);
    }

    /** @test */
    public function parent_can_cancel_reservation_with_refund()
    {
        [$parent, $babysitter, $reservation] = $this->createPaidReservationScenario();

        // Mock refund creation
        $mockRefund = (object) ['id' => 'refund_test123'];
        
        $this->stripeService
            ->shouldReceive('createRefundWithBabysitterDeduction')
            ->with('pi_test123', $reservation, 'parent_unavailable')
            ->once()
            ->andReturn($mockRefund);

        $this->actingAs($parent);

        $response = $this->post(route('reservations.cancel', $reservation), [
            'reason' => 'parent_unavailable',
            'note' => 'Emergency came up'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Réservation annulée avec succès'
        ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled_by_parent',
            'cancellation_reason' => 'parent_unavailable',
            'cancellation_note' => 'Emergency came up',
        ]);
    }

    /** @test */
    public function babysitter_can_cancel_reservation()
    {
        [$parent, $babysitter, $reservation] = $this->createPaidReservationScenario();

        // Mock refund creation
        $mockRefund = (object) ['id' => 'refund_test123'];
        
        $this->stripeService
            ->shouldReceive('createRefundWithBabysitterDeduction')
            ->with('pi_test123', $reservation, 'babysitter_unavailable')
            ->once()
            ->andReturn($mockRefund);

        $this->actingAs($babysitter);

        $response = $this->post(route('reservations.cancel', $reservation), [
            'reason' => 'babysitter_unavailable',
            'note' => 'Got sick'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Réservation annulée avec succès'
        ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled_by_babysitter',
            'cancellation_reason' => 'babysitter_unavailable',
            'cancellation_note' => 'Got sick',
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_cancel_reservation()
    {
        [$parent, $babysitter, $reservation] = $this->createPaidReservationScenario();

        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        $response = $this->post(route('reservations.cancel', $reservation), [
            'reason' => 'other',
            'note' => 'Should not work'
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'paid', // Status unchanged
        ]);
    }

    /** @test */
    public function babysitter_can_start_service()
    {
        [$parent, $babysitter, $reservation] = $this->createPaidReservationScenario();

        $this->actingAs($babysitter);

        $response = $this->post(route('reservations.start-service', $reservation));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Service démarré avec succès'
        ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'active',
        ]);

        $reservation->refresh();
        $this->assertNotNull($reservation->service_start_at);
    }

    /** @test */
    public function parent_cannot_start_service()
    {
        [$parent, $babysitter, $reservation] = $this->createPaidReservationScenario();

        $this->actingAs($parent);

        $response = $this->post(route('reservations.start-service', $reservation));

        $response->assertStatus(403);
    }

    /** @test */
    public function babysitter_can_complete_service()
    {
        [$parent, $babysitter, $reservation] = $this->createActiveReservationScenario();

        $this->actingAs($babysitter);

        $response = $this->post(route('reservations.complete-service', $reservation));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Service terminé avec succès. Les fonds seront libérés dans 24h.'
        ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'service_completed',
        ]);

        $reservation->refresh();
        $this->assertNotNull($reservation->service_end_at);
        $this->assertNotNull($reservation->funds_released_at);
    }

    /** @test */
    public function parent_can_complete_service()
    {
        [$parent, $babysitter, $reservation] = $this->createActiveReservationScenario();

        $this->actingAs($parent);

        $response = $this->post(route('reservations.complete-service', $reservation));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Service terminé avec succès. Les fonds seront libérés dans 24h.'
        ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'service_completed',
        ]);
    }

    /** @test */
    public function show_returns_reservation_details_for_parent()
    {
        [$parent, $babysitter, $reservation] = $this->createPaidReservationScenario();

        $this->actingAs($parent);

        $response = $this->get(route('reservations.show', $reservation));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'reservation' => [
                'id' => $reservation->id,
                'status' => 'paid',
                'hourly_rate' => 15.00,
                'parent' => [
                    'id' => $parent->id,
                    'name' => $parent->firstname . ' ' . $parent->lastname,
                ],
                'babysitter' => [
                    'id' => $babysitter->id,
                    'name' => $babysitter->firstname . ' ' . $babysitter->lastname,
                ]
            ]
        ]);
    }

    /** @test */
    public function show_returns_reservation_details_for_babysitter()
    {
        [$parent, $babysitter, $reservation] = $this->createPaidReservationScenario();

        $this->actingAs($babysitter);

        $response = $this->get(route('reservations.show', $reservation));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'reservation' => [
                'id' => $reservation->id,
                'status' => 'paid',
            ]
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_view_reservation()
    {
        [$parent, $babysitter, $reservation] = $this->createPaidReservationScenario();

        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        $response = $this->get(route('reservations.show', $reservation));

        $response->assertStatus(403);
    }

    /** @test */
    public function parent_can_access_application_payment_page()
    {
        [$parent, $babysitter, $application] = $this->createApplicationScenario();

        // Mock Stripe PaymentIntent creation
        $mockPaymentIntent = (object) [
            'id' => 'pi_test123',
            'client_secret' => 'pi_test123_secret',
            'amount' => 5000,
        ];

        $this->stripeService
            ->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn($mockPaymentIntent);

        $this->actingAs($parent);

        $response = $this->get(route('reservations.application-payment', $application));

        $response->assertRedirect();
        $this->assertStringContainsString('reservations/payment', $response->getTargetUrl());
    }

    /** @test */
    public function show_payment_page_renders_correctly()
    {
        [$parent, $babysitter, $reservation] = $this->createReservationScenario();

        $this->actingAs($parent);

        $response = $this->get(route('reservations.payment', $reservation));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Reservations/Payment')
                ->has('reservation')
                ->where('reservation.id', $reservation->id)
                ->where('reservation.status', 'pending_payment')
                ->has('stripePublishableKey')
        );
    }

    /** @test */
    public function get_payment_intent_returns_client_secret()
    {
        [$parent, $babysitter, $reservation] = $this->createReservationScenario();

        // Mock Stripe PaymentIntent retrieval
        $mockPaymentIntent = (object) [
            'id' => 'pi_test123',
            'client_secret' => 'pi_test123_secret',
            'status' => 'requires_payment_method'
        ];

        $this->stripeService
            ->shouldReceive('retrievePaymentIntent')
            ->with('pi_test123')
            ->once()
            ->andReturn($mockPaymentIntent);

        $this->actingAs($parent);

        $response = $this->get(route('reservations.payment-intent', $reservation));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'client_secret' => 'pi_test123_secret',
            'status' => 'requires_payment_method'
        ]);
    }

    /** @test */
    public function validation_fails_for_invalid_reservation_data()
    {
        [$parent, $babysitter, $application] = $this->createApplicationScenario();

        $this->actingAs($parent);

        $response = $this->post(route('reservations.create-from-application', $application), [
            'final_rate' => -10 // Invalid negative rate
        ]);

        $response->assertSessionHasErrors(['final_rate']);
    }

    /** @test */
    public function cannot_create_reservation_from_non_reservable_application()
    {
        [$parent, $babysitter, $application] = $this->createApplicationScenario();

        // Change application status to non-reservable
        $application->update(['status' => 'declined']);

        $this->actingAs($parent);

        $response = $this->post(route('reservations.create-from-application', $application), [
            'final_rate' => 20.00
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => 'Cette candidature ne peut plus être réservée'
        ]);
    }

    // Helper Methods

    private function createApplicationScenario(): array
    {
        // Create address
        $address = Address::create([
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);

        // Create parent
        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->first());

        // Create babysitter
        $babysitter = User::factory()->create([
            'stripe_account_id' => 'acct_test123'
        ]);
        $babysitter->roles()->attach(Role::where('name', 'babysitter')->first());
        
        BabysitterProfile::create([
            'user_id' => $babysitter->id,
            'verification_status' => 'verified',
            'hourly_rate' => 15.00,
        ]);

        // Create announcement
        $ad = Ad::create([
            'parent_id' => $parent->id,
            'title' => 'Test Babysitting',
            'address_id' => $address->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'status' => 'active',
            'hourly_rate' => 15.00,
            'children' => [['nom' => 'Emma', 'age' => '3', 'unite' => 'ans']],
        ]);

        // Create application
        $application = AdApplication::create([
            'ad_id' => $ad->id,
            'babysitter_id' => $babysitter->id,
            'status' => 'accepted',
            'proposed_rate' => 15.00,
        ]);

        // Create conversation
        Conversation::create([
            'application_id' => $application->id,
            'status' => 'active',
        ]);

        return [$parent, $babysitter, $application];
    }

    private function createReservationScenario(): array
    {
        [$parent, $babysitter, $application] = $this->createApplicationScenario();

        $reservation = Reservation::create([
            'parent_id' => $parent->id,
            'babysitter_id' => $babysitter->id,
            'application_id' => $application->id,
            'status' => 'pending_payment',
            'hourly_rate' => 15.00,
            'deposit_amount' => 45.00,
            'service_fee' => 5.00,
            'total_deposit' => 50.00,
            'babysitter_amount' => 45.00,
            'stripe_payment_intent_id' => 'pi_test123',
            'reserved_at' => now(),
            'payment_due_at' => now()->addHours(2),
        ]);

        return [$parent, $babysitter, $reservation];
    }

    private function createPaidReservationScenario(): array
    {
        [$parent, $babysitter, $reservation] = $this->createReservationScenario();

        $reservation->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return [$parent, $babysitter, $reservation];
    }

    private function createActiveReservationScenario(): array
    {
        [$parent, $babysitter, $reservation] = $this->createPaidReservationScenario();

        $reservation->update([
            'status' => 'active',
            'service_start_at' => now(),
        ]);

        return [$parent, $babysitter, $reservation];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}