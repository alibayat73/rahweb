<?php

namespace Tests\Unit;

use App\Contracts\DeliveryClientInterface;
use App\Contracts\DeliveryResult;
use App\Domain\Enums\TicketStatus;
use App\Domain\Services\TicketWorkflowService;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $fakeClient = Mockery::mock(DeliveryClientInterface::class);
        $fakeClient->shouldReceive('send')
            ->andReturn(DeliveryResult::success());

        $this->app->instance(DeliveryClientInterface::class, $fakeClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Http::fake([]);
        unset($this->app[DeliveryClientInterface::class]);
        unset($this->app[TicketWorkflowService::class]);
        parent::tearDown();
    }

    #[Test]
    public function level1_can_approve_pending_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin_l1']);
        $this->actingAs($admin);
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Pending]);

        $workflow = new TicketWorkflowService;
        $result = $workflow->approve($ticket, 'Looks good');

        $this->assertEquals(TicketStatus::ApprovedL1, $result->status);
        $this->assertDatabaseHas('ticket_decisions', [
            'ticket_id' => $ticket->id,
            'decision' => 'approved',
            'level' => 1,
        ]);
    }

    #[Test]
    public function level1_cannot_approve_already_approved_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin_l1']);
        $this->actingAs($admin);
        $ticket = Ticket::factory()->create(['status' => TicketStatus::ApprovedL1]);

        $workflow = new TicketWorkflowService;

        $this->expectException(InvalidArgumentException::class);
        $workflow->approve($ticket, 'Test');
    }

    #[Test]
    public function level1_can_reject_pending_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin_l1']);
        $this->actingAs($admin);
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Pending]);

        $workflow = new TicketWorkflowService;
        $result = $workflow->reject($ticket, 'Invalid request');

        $this->assertEquals(TicketStatus::RejectedL1, $result->status);
        $this->assertDatabaseHas('ticket_decisions', [
            'ticket_id' => $ticket->id,
            'decision' => 'rejected',
            'level' => 1,
        ]);
    }

    #[Test]
    public function level2_can_approve_l1_approved_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin_l2']);
        $this->actingAs($admin);
        $ticket = Ticket::factory()->create(['status' => TicketStatus::ApprovedL1]);

        $workflow = new TicketWorkflowService;
        $result = $workflow->approve($ticket, 'Final approval');

        $this->assertEquals(TicketStatus::ApprovedL2, $result->status);
        $this->assertDatabaseHas('ticket_decisions', [
            'ticket_id' => $ticket->id,
            'decision' => 'approved',
            'level' => 2,
        ]);
    }

    #[Test]
    public function level2_cannot_approve_pending_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin_l2']);
        $this->actingAs($admin);
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Pending]);

        $workflow = new TicketWorkflowService;

        $this->expectException(InvalidArgumentException::class);
        $workflow->approve($ticket, 'Test');
    }

    #[Test]
    public function level2_can_reject_l1_approved_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin_l2']);
        $this->actingAs($admin);
        $ticket = Ticket::factory()->create(['status' => TicketStatus::ApprovedL1]);

        $workflow = new TicketWorkflowService;
        $result = $workflow->reject($ticket, 'Needs more info');

        $this->assertEquals(TicketStatus::RejectedL2, $result->status);
    }

    #[Test]
    public function can_mark_as_delivered(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::ApprovedL2]);

        $workflow = new TicketWorkflowService;
        $result = $workflow->markAsDelivered($ticket);

        $this->assertEquals(TicketStatus::Delivered, $result->status);
    }

    #[Test]
    public function cannot_mark_pending_ticket_as_delivered(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Pending]);

        $workflow = new TicketWorkflowService;

        $this->expectException(InvalidArgumentException::class);
        $workflow->markAsDelivered($ticket);
    }

    #[Test]
    public function can_mark_as_delivery_failed(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::ApprovedL2]);

        $workflow = new TicketWorkflowService;
        $result = $workflow->markAsDeliveryFailed($ticket);

        $this->assertEquals(TicketStatus::DeliveryFailed, $result->status);
    }

    #[Test]
    public function terminal_states_are_correct(): void
    {
        $this->assertTrue(TicketStatus::RejectedL1->isTerminal());
        $this->assertTrue(TicketStatus::RejectedL2->isTerminal());
        $this->assertTrue(TicketStatus::Delivered->isTerminal());
        $this->assertFalse(TicketStatus::Pending->isTerminal());
        $this->assertFalse(TicketStatus::ApprovedL1->isTerminal());
        $this->assertFalse(TicketStatus::ApprovedL2->isTerminal());
        $this->assertTrue(TicketStatus::DeliveryFailed->isTerminal());
    }
}
