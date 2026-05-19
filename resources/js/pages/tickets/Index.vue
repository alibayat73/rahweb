<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const props = defineProps({
    tickets: Object,
    filters: Object,
    can: Object,
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

function filter() {
    router.get('/tickets', { search: search.value, status: statusFilter.value }, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>

<template>
    <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Tickets</h1>
                <Link href="/tickets/create">
                    <Button>Create Ticket</Button>
                </Link>
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
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Created</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ticket in tickets.data" :key="ticket.id" class="border-t">
                            <td class="px-4 py-3">{{ ticket.id }}</td>
                            <td class="px-4 py-3">{{ ticket.title }}</td>
                            <td class="px-4 py-3">
                                <Badge :class="statusColors[ticket.status]">
                                    {{ statusLabels[ticket.status] }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">{{ new Date(ticket.created_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-3">
                                <Link :href="`/tickets/${ticket.id}`">
                                    <Button variant="outline" size="sm">View</Button>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="tickets.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
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
