<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const props = defineProps({
    tickets: Object,
    filters: Object,
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
    approved_l1: 'Approved L1',
    approved_l2: 'Approved L2',
    rejected_l1: 'Rejected L1',
    rejected_l2: 'Rejected L2',
    delivered: 'Delivered',
    delivery_failed: 'Delivery Failed',
}

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const selectedTickets = ref([])
const bulkNote = ref('')
const showBulkForm = ref(false)

function filter() {
    router.get('/admin/tickets', { search: search.value, status: statusFilter.value }, {
        preserveState: true,
        preserveScroll: true,
    })
}

function toggleSelect(ticketId) {
    const index = selectedTickets.value.indexOf(ticketId)

    if (index === -1) {
        selectedTickets.value.push(ticketId)
    } else {
        selectedTickets.value.splice(index, 1)
    }
}

function bulkApprove() {
    router.post('/admin/tickets/bulk-approve', {
        ticket_ids: selectedTickets.value,
        note: bulkNote.value,
    }, {
        onSuccess: () => {
            selectedTickets.value = []
            bulkNote.value = ''
            showBulkForm.value = false
        },
    })
}

function approve(ticketId) {
    router.post(`/admin/tickets/${ticketId}/approve`, { note: 'Approved' })
}

function reject(ticketId) {
    router.post(`/admin/tickets/${ticketId}/reject`, { note: 'Rejected' })
}

const isAdminL1 = props.auth.user?.role === 'admin_l1'
const isAdminL2 = props.auth.user?.role === 'admin_l2'

function canActOnTicket(ticket) {
    if (isAdminL1) {
        return ticket.status === 'pending'
    }

    if (isAdminL2) {
        return ticket.status === 'approved_l1'
    }

    return false
}
</script>

<template>
    <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Admin - Tickets</h1>
                <Button
                    v-if="selectedTickets.length > 0 && !showBulkForm"
                    @click="showBulkForm = true"
                >
                    Bulk Approve ({{ selectedTickets.length }})
                </Button>
            </div>

            <div v-if="flash?.success" class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                {{ flash.success }}
            </div>

            <div v-if="showBulkForm" class="rounded-md border p-4 space-y-4">
                <h3 class="font-medium">Bulk Approve {{ selectedTickets.length }} Tickets</h3>
                <Input v-model="bulkNote" placeholder="Approval note..." />
                <div class="flex gap-2">
                    <Button @click="bulkApprove" :disabled="!bulkNote">Confirm Bulk Approve</Button>
                    <Button variant="outline" @click="showBulkForm = false">Cancel</Button>
                </div>
            </div>

            <div class="flex gap-4">
                <Input
                    v-model="search"
                    placeholder="Search tickets..."
                    class="max-w-sm"
                    @keyup.enter="filter"
                />
                <select
                    v-model="statusFilter"
                    class="rounded-md border border-input bg-background px-3 py-2"
                    @change="filter"
                >
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved_l1">Approved L1</option>
                    <option value="approved_l2">Approved L2</option>
                    <option value="rejected_l1">Rejected L1</option>
                    <option value="rejected_l2">Rejected L2</option>
                    <option value="delivered">Delivered</option>
                    <option value="delivery_failed">Delivery Failed</option>
                </select>
            </div>

            <div class="rounded-md border">
                <table class="w-full">
                    <thead class="bg-muted">
                        <tr>
                            <th class="px-4 py-3 text-left">Select</th>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-left">Submitted By</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Created</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ticket in tickets.data" :key="ticket.id" class="border-t">
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="selectedTickets.includes(ticket.id)"
                                    @change="toggleSelect(ticket.id)"
                                    :disabled="!canActOnTicket(ticket)"
                                    class="h-4 w-4"
                                />
                            </td>
                            <td class="px-4 py-3">{{ ticket.id }}</td>
                            <td class="px-4 py-3">{{ ticket.title }}</td>
                            <td class="px-4 py-3">{{ ticket.user.name }}</td>
                            <td class="px-4 py-3">
                                <Badge :class="statusColors[ticket.status]">
                                    {{ statusLabels[ticket.status] }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">{{ new Date(ticket.created_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-3 space-x-2">
                                <Link :href="`/admin/tickets/${ticket.id}`">
                                    <Button variant="outline" size="sm">View</Button>
                                </Link>
                                <Button
                                    v-if="canActOnTicket(ticket)"
                                    variant="default"
                                    size="sm"
                                    @click="approve(ticket.id)"
                                >
                                    Approve
                                </Button>
                                <Button
                                    v-if="canActOnTicket(ticket)"
                                    variant="destructive"
                                    size="sm"
                                    @click="reject(ticket.id)"
                                >
                                    Reject
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="tickets.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">
                                No tickets found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Showing {{ tickets.from || 0 }} to {{ tickets.to || 0 }} of {{ tickets.total }} results
                </div>
                <div class="flex gap-2">
                    <Button
                        v-for="link in tickets.links"
                        :key="link.label"
                        :variant="link.active ? 'default' : 'outline'"
                        :disabled="!link.url"
                        @click="router.get(link.url)"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>
</template>
