<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_their_notifications(): void
    {
        $user = User::factory()->client()->create();
        $message = Message::factory()->create();
        $user->notify(new NewMessageNotification($message));

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 1);
    }

    public function test_user_can_mark_a_single_notification_as_read(): void
    {
        $user = User::factory()->client()->create();
        $message = Message::factory()->create();
        $user->notify(new NewMessageNotification($message));
        $notification = $user->notifications()->first();

        $response = $this->actingAs($user)->post(route('notifications.read', $notification->id));

        $response->assertRedirect();
        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->client()->create();
        $conversation = Conversation::factory()->create();
        $user->notify(new NewMessageNotification(Message::factory()->create(['conversation_id' => $conversation->id])));
        $user->notify(new NewMessageNotification(Message::factory()->create(['conversation_id' => $conversation->id])));

        $this->actingAs($user)->post(route('notifications.read-all'));

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
