<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import IntegrationsTab from './Partials/IntegrationsTab.vue';
import GeneralTab from './Partials/GeneralTab.vue';

defineProps({
    integrations: Array,
    generalSettings: Object,
    availablePlatforms: Object,
});

const activeTab = ref('integrations');

const tabs = [
    { id: 'integrations', name: 'Integrations', icon: 'LinkIcon' },
    { id: 'general', name: 'General Settings', icon: 'CogIcon' },
    { id: 'notifications', name: 'Notifications', icon: 'BellIcon' },
    { id: 'warehouse', name: 'Warehouse', icon: 'BuildingStorefrontIcon' },
];
</script>

<template>
    <AppLayout title="Settings">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Tabs -->
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    activeTab === tab.id
                                        ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                {{ tab.name }}
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="p-6">
                        <IntegrationsTab
                            v-if="activeTab === 'integrations'"
                            :integrations="integrations"
                            :available-platforms="availablePlatforms"
                        />

                        <GeneralTab
                            v-if="activeTab === 'general'"
                            :settings="generalSettings"
                        />

                        <div v-if="activeTab === 'notifications'" class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400">
                                Notification settings coming soon
                            </p>
                        </div>

                        <div v-if="activeTab === 'warehouse'" class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400">
                                Warehouse settings coming soon
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
