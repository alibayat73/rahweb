<?php

namespace Tests\Feature;

use App\Contracts\DeliveryClientInterface;
use App\Contracts\DeliveryResult;
use App\Domain\Enums\AdminRole;
use App\Domain\Enums\TicketStatus;
use App\Domain\Services\TicketDeliveryService;
use App\Jobs\SendTicketToWebservice;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketApprovedNotification;
use App\Notifications\TicketRejectedNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        unset($this->app[DeliveryClientInterface::class]);
        unset($this->app[TicketDeliveryService::class]);
        parent::tearDown();
    }

    #[Test]
    public function user_can_submit_ticket(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)->post('/tickets', [
            'title' => 'Test Ticket',
            'description' => 'This is a test ticket',
            'attachment' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'title' => 'Test Ticket',
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function user_cannot_submit_ticket_without_attachment(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tickets', [
            'title' => 'Test Ticket',
            'description' => 'This is a test ticket',
        ]);

        $response->assertSessionHasErrors('attachment');
    }

    #[Test]
    public function admin_level1_can_approve_ticket(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => AdminRole::AdminL1]);
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'status' => TicketStatus::Pending]);

        $response = $this->actingAs($admin)->post("/admin/tickets/{$ticket->id}/approve", [
            'note' => 'Approved by L1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'approved_l1']);
        Notification::assertSentTo($user, TicketApprovedNotification::class);
    }

    #[Test]
    public function admin_level1_can_reject_ticket(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => AdminRole::AdminL1]);
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'status' => TicketStatus::Pending]);

        $response = $this->actingAs($admin)->post("/admin/tickets/$ticket->id/reject", [
            'note' => 'Rejected by L1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'rejected_l1']);
        Notification::assertSentTo($user, TicketRejectedNotification::class);
    }

    #[Test]
    public function admin_level2_can_approve_ticket_and_dispatch_job(): void
    {
        Notification::fake();
        Queue::fake();

        $admin = User::factory()->create(['role' => AdminRole::AdminL2]);
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'status' => TicketStatus::ApprovedL1]);

        $response = $this->actingAs($admin)->post("/admin/tickets/{$ticket->id}/approve", [
            'note' => 'Approved by L2',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'approved_l2']);
        Queue::assertPushed(SendTicketToWebservice::class);
        Notification::assertSentTo($user, TicketApprovedNotification::class);
    }

    #[Test]
    public function admin_level2_can_reject_ticket(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => AdminRole::AdminL2]);
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'status' => TicketStatus::ApprovedL1]);

        $response = $this->actingAs($admin)->post("/admin/tickets/{$ticket->id}/reject", [
            'note' => 'Rejected by L2',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'rejected_l2']);
        Notification::assertSentTo($user, TicketRejectedNotification::class);
    }

    #[Test]
    public function full_ticket_flow_success(): void
    {
        Notification::fake();
        Queue::fake();

        $adminL1 = User::factory()->create(['role' => AdminRole::AdminL1]);
        $adminL2 = User::factory()->create(['role' => AdminRole::AdminL2]);
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'status' => TicketStatus::Pending]);

        $this->actingAs($adminL1)->post("/admin/tickets/$ticket->id/approve", ['note' => 'L1 approved']);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'approved_l1']);

        $this->actingAs($adminL2)->post("/admin/tickets/$ticket->id/approve", ['note' => 'L2 approved']);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'approved_l2']);

        Queue::assertPushed(SendTicketToWebservice::class);

        $mockClient = Mockery::mock(DeliveryClientInterface::class);
        $mockClient->shouldReceive('send')
            ->once()
            ->andReturn(DeliveryResult::success(200, 'OK'));

        $this->app->instance(DeliveryClientInterface::class, $mockClient);

        $job = new SendTicketToWebservice($ticket->refresh());
        $job->handle(app(TicketDeliveryService::class));

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'delivered']);
        $this->assertDatabaseHas('delivery_attempts', ['ticket_id' => $ticket->id, 'status' => 'success']);
    }

    #[Test]
    #[RunInSeparateProcess]
    public function full_ticket_flow_with_delivery_failure(): void
    {
        Notification::fake();
        Queue::fake();

        $adminL1 = User::factory()->create(['role' => AdminRole::AdminL1]);
        $adminL2 = User::factory()->create(['role' => AdminRole::AdminL2]);
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'status' => TicketStatus::Pending]);

        $this->actingAs($adminL1)
            ->post("/admin/tickets/$ticket->id/approve", ['note' => 'L1 approved'])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertEquals(TicketStatus::ApprovedL1, $ticket->status);

        $this->actingAs($adminL2)
            ->post("/admin/tickets/$ticket->id/approve", ['note' => 'L2 approved'])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertEquals(TicketStatus::ApprovedL2, $ticket->status);

        Queue::assertPushed(SendTicketToWebservice::class);

        $mockClient = Mockery::mock(DeliveryClientInterface::class);
        $mockClient->shouldReceive('send')
            ->once()
            ->andReturn(DeliveryResult::failure(500, 'Internal server error'));

        $this->app->instance(DeliveryClientInterface::class, $mockClient);

        $job = new SendTicketToWebservice($ticket);

        try {
            $job->handle(app(TicketDeliveryService::class));
        } catch (\Exception) {
        }

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'delivery_failed']);
        $this->assertDatabaseHas('delivery_attempts', ['ticket_id' => $ticket->id, 'status' => 'failed']);
    }

    #[Test]
    public function regular_user_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Pending]);

        $response = $this->actingAs($user)->post("/admin/tickets/{$ticket->id}/approve", ['note' => 'Test']);
        $response->assertForbidden();
    }

    #[Test]
    public function admin_l1_cannot_approve_l1_approved_ticket(): void
    {
        $admin = User::factory()->create(['role' => AdminRole::AdminL1]);
        $ticket = Ticket::factory()->create(['status' => TicketStatus::ApprovedL1]);

        $response = $this->actingAs($admin)->post("/admin/tickets/{$ticket->id}/approve", ['note' => 'Test']);
        $response->assertForbidden();
    }

    #[Test]
    public function user_cannot_view_other_users_tickets(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->get("/tickets/{$ticket->id}");
        $response->assertForbidden();
    }

    #[Test]
    public function admin_can_bulk_approve_tickets(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => AdminRole::AdminL1]);
        $tickets = Ticket::factory()->count(3)->create(['status' => TicketStatus::Pending]);

        $response = $this->actingAs($admin)->post('/admin/tickets/bulk-approve', [
            'ticket_ids' => $tickets->pluck('id')->toArray(),
            'note' => 'Bulk approved',
        ]);

        $response->assertRedirect();

        foreach ($tickets as $ticket) {
            $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'approved_l1']);
        }
    }
}
