<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'

const props = defineProps({
    ticket: Object,
    attachment_url: String,
    flash: Object,
    auth: Object,
})

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved_l1: 'bg-blue-100 text-blue-800',
    approved_l2: 'bg-purple-100 text-purple-800',
    rejected_l1: 'bg-red-100 text-red-800',
    rejected_l2: 'bg-red-100 text-red-800',
    delivered: 'bg-green-100 text-green-800',
    delivery_failed: 'bg-orange-100 text-orange-800',
}

const statusLabels = {
    pending: 'Pending',
    approved_l1: 'Approved by Level 1',
    approved_l2: 'Approved by Level 2',
    rejected_l1: 'Rejected by Level 1',
    rejected_l2: 'Rejected by Level 2',
    delivered: 'Delivered',
    delivery_failed: 'Delivery Failed',
}

const approveNote = ref('')
const rejectNote = ref('')
const showApproveForm = ref(false)
const showRejectForm = ref(false)

const isAdmin = props.auth.user?.role === 'admin_l1' || props.auth.user?.role === 'admin_l2'
const isAdminL1 = props.auth.user?.role === 'admin_l1'
const isAdminL2 = props.auth.user?.role === 'admin_l2'

const canApprove = isAdmin && (
    (isAdminL1 && props.ticket.status === 'pending') ||
    (isAdminL2 && props.ticket.status === 'approved_l1')
)

const canReject = isAdmin && (
    (isAdminL1 && props.ticket.status === 'pending') ||
    (isAdminL2 && props.ticket.status === 'approved_l1')
)

function approve() {
    router.post(`/admin/tickets/${props.ticket.id}/approve`, { note: approveNote.value }, {
        onSuccess: () => {
            approveNote.value = ''
            showApproveForm.value = false
        },
    })
}

function reject() {
    router.post(`/admin/tickets/${props.ticket.id}/reject`, { note: rejectNote.value }, {
        onSuccess: () => {
            rejectNote.value = ''
            showRejectForm.value = false
        },
    })
}
</script>

<template>
    <div class="mx-auto max-w-4xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <Link href="/admin/tickets" class="text-sm text-muted-foreground hover:underline">
                        ← Back to Tickets
                    </Link>
                    <h1 class="mt-2 text-2xl font-bold">{{ ticket.title }}</h1>
                </div>
                <Badge :class="statusColors[ticket.status]" class="text-sm">
                    {{ statusLabels[ticket.status] }}
                </Badge>
            </div>

            <div v-if="flash?.success" class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                {{ flash.success }}
            </div>

            <div class="rounded-md border p-6 space-y-4">
                <div>
                    <h3 class="text-sm font-medium text-muted-foreground">Description</h3>
                    <p class="mt-1">{{ ticket.description }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-muted-foreground">Submitted By</h3>
                    <p class="mt-1">{{ ticket.user.name }} ({{ ticket.user.email }})</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-muted-foreground">Attachment</h3>
                    <a :href="attachment_url" target="_blank" class="mt-1 text-blue-600 hover:underline">
                        View Attachment
                    </a>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-muted-foreground">Created</h3>
                    <p class="mt-1">{{ new Date(ticket.created_at).toLocaleString() }}</p>
                </div>
            </div>

            <div v-if="ticket.decisions && ticket.decisions.length > 0" class="rounded-md border p-6 space-y-4">
                <h2 class="text-lg font-semibold">Decision History</h2>
                <div v-for="decision in ticket.decisions" :key="decision.id" class="border-b pb-4 last:border-0">
                    <div class="flex items-center gap-2">
                        <Badge :class="decision.decision === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                            {{ decision.decision === 'approved' ? 'Approved' : 'Rejected' }}
                        </Badge>
                        <span class="text-sm text-muted-foreground">by {{ decision.admin.name }} (Level {{ decision.level }})</span>
                    </div>
                    <p v-if="decision.note" class="mt-2 text-sm">{{ decision.note }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ new Date(decision.created_at).toLocaleString() }}</p>
                </div>
            </div>

            <div v-if="ticket.delivery_attempts && ticket.delivery_attempts.length > 0" class="rounded-md border p-6 space-y-4">
                <h2 class="text-lg font-semibold">Delivery Attempts</h2>
                <div v-for="attempt in ticket.delivery_attempts" :key="attempt.id" class="border-b pb-4 last:border-0">
                    <div class="flex items-center gap-2">
                        <Badge :class="attempt.status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                            {{ attempt.status === 'success' ? 'Success' : 'Failed' }}
                        </Badge>
                        <span class="text-sm">Status Code: {{ attempt.response_code }}</span>
                    </div>
                    <p class="mt-1 text-sm">{{ attempt.response_message }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ new Date(attempt.attempted_at).toLocaleString() }}</p>
                </div>
            </div>

            <div v-if="canApprove" class="rounded-md border p-6 space-y-4">
                <Button v-if="!showApproveForm" @click="showApproveForm = true">
                    Approve Ticket
                </Button>
                <div v-if="showApproveForm" class="space-y-4">
                    <Label for="approve-note">Approval Note (required)</Label>
                    <textarea
                        id="approve-note"
                        v-model="approveNote"
                        rows="3"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    ></textarea>
                    <div class="flex gap-2">
                        <Button @click="approve" :disabled="!approveNote">Confirm Approve</Button>
                        <Button variant="outline" @click="showApproveForm = false">Cancel</Button>
                    </div>
                </div>
            </div>

            <div v-if="canReject" class="rounded-md border p-6 space-y-4">
                <Button v-if="!showRejectForm" variant="destructive" @click="showRejectForm = true">
                    Reject Ticket
                </Button>
                <div v-if="showRejectForm" class="space-y-4">
                    <Label for="reject-note">Rejection Note (required)</Label>
                    <textarea
                        id="reject-note"
                        v-model="rejectNote"
                        rows="3"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    ></textarea>
                    <div class="flex gap-2">
                        <Button variant="destructive" @click="reject" :disabled="!rejectNote">Confirm Reject</Button>
                        <Button variant="outline" @click="showRejectForm = false">Cancel</Button>
                    </div>
                </div>
            </div>
        </div>
</template>
