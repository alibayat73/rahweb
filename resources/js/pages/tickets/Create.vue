<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const fileName = ref('')

function handleFileChange(event) {
    const file = event.target.files[0]

    if (file) {
        fileName.value = file.name
    }
}
</script>

<template>
    <div class="mx-auto max-w-2xl space-y-6">
            <h1 class="text-2xl font-bold">Create New Ticket</h1>

            <Form action="/tickets" method="post" enctype="multipart/form-data" #default="{ errors, processing, wasSuccessful }">
                <div class="space-y-4">
                    <div>
                        <Label for="title">Title</Label>
                        <Input id="title" name="title" type="text" required />
                        <InputError v-if="errors.title" :message="errors.title" class="mt-1" />
                    </div>

                    <div>
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            required
                        ></textarea>
                        <InputError v-if="errors.description" :message="errors.description" class="mt-1" />
                    </div>

                    <div>
                        <Label for="attachment">Attachment (PDF or Image)</Label>
                        <div class="mt-1 flex items-center gap-2">
                            <Input
                                id="attachment"
                                name="attachment"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                required
                                @change="handleFileChange"
                            />
                            <span v-if="fileName" class="text-sm text-muted-foreground">{{ fileName }}</span>
                        </div>
                        <InputError v-if="errors.attachment" :message="errors.attachment" class="mt-1" />
                    </div>

                    <div class="flex gap-2">
                        <Button type="submit" :disabled="processing">
                            {{ processing ? 'Submitting...' : 'Submit Ticket' }}
                        </Button>
                    </div>

                    <div v-if="wasSuccessful" class="text-sm text-green-600">
                        Ticket submitted successfully!
                    </div>
                </div>
            </Form>
        </div>
</template>
