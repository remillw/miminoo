<?php

namespace Tests\Feature\Settings;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_notification_settings()
    {
        // Arrange
        $user = User::factory()->create([
            'email_notifications' => true,
            'push_notifications' => true,
            'sms_notifications' => false,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->post(route('settings.notifications'), [
                'email_notifications' => false,
                'push_notifications' => true,
                'sms_notifications' => true,
            ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Préférences de notifications mises à jour avec succès');

        // Verify database was updated
        $user->refresh();
        $this->assertFalse($user->email_notifications);
        $this->assertTrue($user->push_notifications);
        $this->assertTrue($user->sms_notifications);
    }

    public function test_notification_settings_accept_boolean_values()
    {
        // Arrange
        $user = User::factory()->create();

        // Act - Test with string boolean values (common from frontend)
        $response = $this->actingAs($user)
            ->post(route('settings.notifications'), [
                'email_notifications' => 'true',
                'push_notifications' => 'false',
                'sms_notifications' => '1',
            ]);

        // Assert
        $response->assertRedirect();
        $user->refresh();
        $this->assertTrue($user->email_notifications);
        $this->assertFalse($user->push_notifications);
        $this->assertTrue($user->sms_notifications);
    }

    public function test_notification_settings_default_to_false_when_not_provided()
    {
        // Arrange
        $user = User::factory()->create([
            'email_notifications' => true,
            'push_notifications' => true,
            'sms_notifications' => true,
        ]);

        // Act - Send empty request
        $response = $this->actingAs($user)
            ->post(route('settings.notifications'), []);

        // Assert
        $response->assertRedirect();
        $user->refresh();
        $this->assertFalse($user->email_notifications);
        $this->assertFalse($user->push_notifications);
        $this->assertFalse($user->sms_notifications);
    }

    public function test_guest_cannot_update_notification_settings()
    {
        // Act
        $response = $this->post(route('settings.notifications'), [
            'email_notifications' => false,
        ]);

        // Assert
        $response->assertRedirect(route('login'));
    }
}