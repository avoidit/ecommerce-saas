<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import ActionSection from '@/Components/ActionSection.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    integrations: Array,
    availablePlatforms: Object,
});

const showAddModal = ref(false);
const showEditModal = ref(false);
const editingCredential = ref(null);

const addForm = useForm({
    platform: 'ebay',
    environment: 'sandbox',
    name: '',
    client_id: '',
    client_secret: '',
});

const editForm = useForm({
    name: '',
    client_id: '',
    client_secret: '',
    is_active: true,
});

const openAddModal = () => {
    addForm.reset();
    showAddModal.value = true;
};

const addIntegration = () => {
    addForm.post(route('integrations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            addForm.reset();
        },
    });
};

const openEditModal = (credential) => {
    editingCredential.value = credential;
    editForm.name = credential.name;
    editForm.is_active = credential.is_active;
    showEditModal.value = true;
};

const updateIntegration = () => {
    editForm.put(route('integrations.update', editingCredential.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            editingCredential.value = null;
        },
    });
};

const deleteIntegration = (credential) => {
    if (confirm('Are you sure you want to delete this integration? All associated tokens will be deactivated.')) {
        useForm({}).delete(route('integrations.destroy', credential.id), {
            preserveScroll: true,
        });
    }
};

const verifyCredentials = (credential) => {
    useForm({}).post(route('integrations.verify', credential.id), {
        preserveScroll: true,
    });
};

const connectOAuth = (credential) => {
    if (credential.authorization_url) {
        window.location.href = credential.authorization_url;
    }
};

const disconnectOAuth = (credential) => {
    if (confirm('Are you sure you want to disconnect from ' + credential.platform + '?')) {
        useForm({}).post(route('integrations.' + credential.platform + '.disconnect', credential.environment), {
            preserveScroll: true,
        });
    }
};

const getStatusBadge = (credential) => {
    if (!credential.is_complete) {
        return { text: 'Incomplete', class: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' };
    }
    if (credential.has_active_token) {
        return { text: 'Connected', class: 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' };
    }
    return { text: 'Not Connected', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' };
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Platform Integrations
                </h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Connect your e-commerce platforms to manage inventory and orders
                </p>
            </div>
            <PrimaryButton @click="openAddModal">
                Add Integration
            </PrimaryButton>
        </div>

        <!-- Integrations List -->
        <div class="grid grid-cols-1 gap-4">
            <div
                v-for="integration in integrations"
                :key="integration.id"
                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                {{ integration.name }}
                            </h4>
                            <span
                                :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    getStatusBadge(integration).class
                                ]"
                            >
                                {{ getStatusBadge(integration).text }}
                            </span>
                            <span
                                v-if="integration.environment === 'sandbox'"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100"
                            >
                                Sandbox
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ availablePlatforms[integration.platform]?.description }}
                        </p>
                        <p v-if="integration.last_verified_at" class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            Last verified: {{ new Date(integration.last_verified_at).toLocaleString() }}
                        </p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <SecondaryButton
                            v-if="integration.is_complete && !integration.has_active_token"
                            @click="connectOAuth(integration)"
                            class="text-sm"
                        >
                            Connect
                        </SecondaryButton>

                        <DangerButton
                            v-if="integration.has_active_token"
                            @click="disconnectOAuth(integration)"
                            class="text-sm"
                        >
                            Disconnect
                        </DangerButton>

                        <SecondaryButton
                            @click="verifyCredentials(integration)"
                            class="text-sm"
                        >
                            Verify
                        </SecondaryButton>

                        <SecondaryButton
                            @click="openEditModal(integration)"
                            class="text-sm"
                        >
                            Edit
                        </SecondaryButton>

                        <DangerButton
                            @click="deleteIntegration(integration)"
                            class="text-sm"
                        >
                            Delete
                        </DangerButton>
                    </div>
                </div>
            </div>

            <div v-if="integrations.length === 0" class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">
                    No integrations configured. Click "Add Integration" to get started.
                </p>
            </div>
        </div>

        <!-- Available Platforms -->
        <div class="mt-8">
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">
                Available Platforms
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div
                    v-for="(platform, key) in availablePlatforms"
                    :key="key"
                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                >
                    <h5 class="font-medium text-gray-900 dark:text-gray-100">
                        {{ platform.name }}
                        <span
                            v-if="platform.coming_soon"
                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                        >
                            Coming Soon
                        </span>
                    </h5>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ platform.description }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Add Integration Modal -->
        <DialogModal :show="showAddModal" @close="showAddModal = false">
            <template #title>
                Add Integration
            </template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="platform" value="Platform" />
                        <select
                            id="platform"
                            v-model="addForm.platform"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        >
                            <option value="ebay">eBay</option>
                            <option value="amazon" disabled>Amazon (Coming Soon)</option>
                            <option value="newegg" disabled>Newegg (Coming Soon)</option>
                        </select>
                        <InputError :message="addForm.errors.platform" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="environment" value="Environment" />
                        <select
                            id="environment"
                            v-model="addForm.environment"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        >
                            <option value="sandbox">Sandbox (Testing)</option>
                            <option value="production">Production</option>
                        </select>
                        <InputError :message="addForm.errors.environment" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="name" value="Name (Optional)" />
                        <TextInput
                            id="name"
                            v-model="addForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g., Main eBay Account"
                        />
                        <InputError :message="addForm.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="client_id" value="Client ID / App ID" />
                        <TextInput
                            id="client_id"
                            v-model="addForm.client_id"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="addForm.errors.client_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="client_secret" value="Client Secret / Cert ID" />
                        <TextInput
                            id="client_secret"
                            v-model="addForm.client_secret"
                            type="password"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="addForm.errors.client_secret" class="mt-2" />
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>Note:</strong> For eBay, you can get your credentials from the
                            <a href="https://developer.ebay.com/my/keys" target="_blank" class="underline">
                                eBay Developer Portal
                            </a>
                        </p>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="showAddModal = false">
                    Cancel
                </SecondaryButton>

                <PrimaryButton
                    class="ml-3"
                    :class="{ 'opacity-25': addForm.processing }"
                    :disabled="addForm.processing"
                    @click="addIntegration"
                >
                    Add Integration
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Edit Integration Modal -->
        <DialogModal :show="showEditModal" @close="showEditModal = false">
            <template #title>
                Edit Integration
            </template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="edit_name" value="Name" />
                        <TextInput
                            id="edit_name"
                            v-model="editForm.name"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="editForm.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="edit_client_id" value="Client ID / App ID (Optional - leave blank to keep current)" />
                        <TextInput
                            id="edit_client_id"
                            v-model="editForm.client_id"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Leave blank to keep current"
                        />
                        <InputError :message="editForm.errors.client_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="edit_client_secret" value="Client Secret / Cert ID (Optional - leave blank to keep current)" />
                        <TextInput
                            id="edit_client_secret"
                            v-model="editForm.client_secret"
                            type="password"
                            class="mt-1 block w-full"
                            placeholder="Leave blank to keep current"
                        />
                        <InputError :message="editForm.errors.client_secret" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <input
                            id="edit_is_active"
                            v-model="editForm.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                        />
                        <InputLabel for="edit_is_active" value="Active" class="ml-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="showEditModal = false">
                    Cancel
                </SecondaryButton>

                <PrimaryButton
                    class="ml-3"
                    :class="{ 'opacity-25': editForm.processing }"
                    :disabled="editForm.processing"
                    @click="updateIntegration"
                >
                    Update Integration
                </PrimaryButton>
            </template>
        </DialogModal>
    </div>
</template>
