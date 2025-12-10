<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    settings: {
        company_name: props.settings?.company_name || '',
        timezone: props.settings?.timezone || 'America/Chicago',
        currency: props.settings?.currency || 'USD',
    },
});

const updateSettings = () => {
    form.put(route('settings.general.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-6">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                General Settings
            </h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Configure your team's general preferences
            </p>
        </div>

        <form @submit.prevent="updateSettings" class="space-y-6">
            <div>
                <InputLabel for="company_name" value="Company Name" />
                <TextInput
                    id="company_name"
                    v-model="form.settings.company_name"
                    type="text"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors['settings.company_name']" class="mt-2" />
            </div>

            <div>
                <InputLabel for="timezone" value="Timezone" />
                <select
                    id="timezone"
                    v-model="form.settings.timezone"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                >
                    <option value="America/New_York">Eastern Time</option>
                    <option value="America/Chicago">Central Time</option>
                    <option value="America/Denver">Mountain Time</option>
                    <option value="America/Los_Angeles">Pacific Time</option>
                    <option value="UTC">UTC</option>
                </select>
                <InputError :message="form.errors['settings.timezone']" class="mt-2" />
            </div>

            <div>
                <InputLabel for="currency" value="Currency" />
                <select
                    id="currency"
                    v-model="form.settings.currency"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                >
                    <option value="USD">USD - US Dollar</option>
                    <option value="EUR">EUR - Euro</option>
                    <option value="GBP">GBP - British Pound</option>
                    <option value="CAD">CAD - Canadian Dollar</option>
                </select>
                <InputError :message="form.errors['settings.currency']" class="mt-2" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Save Settings
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </div>
</template>
