<template>
    <AdminLayout :title="t('ব্র্যান্ড ব্যবস্থাপনা', 'Brands')">
        <Head :title="t('ব্র্যান্ড ব্যবস্থাপনা', 'Brands')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                        {{ t('পণ্যের ব্র্যান্ড', 'Brands Management') }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('পণ্য প্রস্তুতকারী বা আমদানিকারক ব্র্যান্ড পরিচালনা করুন', 'Manage your product brands and manufacturers') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="openModal"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-all">
                        <PlusIcon class="h-4 w-4" />
                        <span>{{ t('নতুন ব্র্যান্ড যোগ', 'Add Brand') }}</span>
                    </button>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <!-- Search Input -->
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <MagnifyingGlassIcon class="h-4 w-4 text-slate-400" />
                        </div>
                        <input v-model="search" type="text"
                            class="block w-full rounded-xl border-slate-200 bg-white py-2 pl-9 pr-3 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            :placeholder="t('ব্র্যান্ড খুঁজুন...', 'Search brands...')" @input="debouncedSearch">
                    </div>

                    <!-- Status Filter -->
                    <select v-model="status"
                        class="block w-full rounded-xl border-slate-200 bg-white py-2 pl-3 pr-8 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        @change="filterStatus">
                        <option value="">{{ t('সকল অবস্থা', 'All Status') }}</option>
                        <option :value="1">{{ t('সক্রিয়', 'Active') }}</option>
                        <option :value="0">{{ t('নিষ্ক্রিয়', 'Inactive') }}</option>
                    </select>
                </div>
            </div>

            <!-- Brands Table -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-900">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                        <tr>
                            <th class="px-5 py-3 text-left">{{ t('ব্র্যান্ড', 'Brand') }}</th>
                            <th class="px-4 py-3 text-center">{{ t('অবস্থা', 'Status') }}</th>
                            <th class="px-5 py-3 text-right">{{ t('অ্যাকশন', 'Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-if="brands.data.length === 0">
                            <td colspan="3" class="px-6 py-10 text-center text-slate-400">
                                {{ t('কোনো ব্র্যান্ড পাওয়া যায়নি।', 'No brands found.') }}
                            </td>
                        </tr>
                        <tr v-for="brand in brands.data" :key="brand.id"
                            class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                                        <img v-if="brand.logo" :src="getImageUrl(brand.logo)"
                                            class="h-9 w-9 object-cover" :alt="brand.name">
                                        <Squares2X2Icon v-else class="h-5 w-5 text-slate-400" />
                                    </div>
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ brand.name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span :class="[
                                    'inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold',
                                    brand.status
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                        : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                                ]">
                                    {{ brand.status ? t('সক্রিয়', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-medium">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="editBrand(brand)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-indigo-600 hover:text-indigo-800 dark:border-slate-700 dark:bg-slate-800 dark:text-indigo-400">
                                        <PencilIcon class="h-4 w-4" />
                                    </button>
                                    <button @click="deleteBrand(brand)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-rose-600 hover:text-rose-800 dark:border-slate-700 dark:bg-slate-800 dark:text-rose-400">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="brands.links && brands.links.length > 3" class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">
                    <Pagination :links="brands.links" />
                </div>
            </div>

            <!-- Brand Modal -->
            <Modal :show="showModal" @close="closeModal" maxWidth="md">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                            {{ editing ? t('ব্র্যান্ড সম্পাদন করুন', 'Edit Brand') : t('নতুন ব্র্যান্ড যোগ করুন', 'Add Brand') }}
                        </h3>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <InputLabel for="name" :value="t('ব্র্যান্ডের নাম *', 'Brand Name *')" class="text-xs font-bold" />
                            <TextInput id="name" v-model="form.name" type="text"
                                class="mt-1 block w-full text-xs rounded-xl border-slate-200" required
                                :placeholder="t('যেমনঃ Unilever, Walton...', 'Enter brand name')" />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="logo" :value="t('লোগো বা ছবি', 'Brand Logo')" class="text-xs font-bold" />
                            <div class="mt-1 flex items-center gap-3">
                                <div v-if="imagePreview || form.logo" class="h-12 w-12 rounded-xl border border-slate-200 overflow-hidden flex-shrink-0">
                                    <img :src="imagePreview || getImageUrl(form.logo)"
                                        class="h-12 w-12 object-cover">
                                </div>
                                <label
                                    class="relative cursor-pointer rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    <span>{{ t('ছবি নির্বাচন করুন', 'Upload a file') }}</span>
                                    <input type="file" class="sr-only" @change="updateLogo" accept="image/*">
                                </label>
                            </div>
                            <InputError :message="form.errors.logo" class="mt-1" />
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ t('সক্রিয় অবস্থা', 'Active Status') }}</span>
                            <button type="button" :class="[
                                form.status ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-slate-700',
                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
                            ]" @click="form.status = !form.status">
                                <span :class="[
                                    form.status ? 'translate-x-5' : 'translate-x-0',
                                    'pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow-xs transition duration-200 ease-in-out'
                                ]" />
                            </button>
                        </div>

                        <div class="mt-6 flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <SecondaryButton @click="closeModal" class="rounded-xl text-xs">{{ t('বাতিল', 'Cancel') }}</SecondaryButton>
                            <PrimaryButton :disabled="form.processing" class="rounded-xl text-xs bg-indigo-600 hover:bg-indigo-500">
                                {{ editing ? t('সংরক্ষণ করুন', 'Update') : t('তৈরি করুন', 'Create') }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- Add Confirm Dialog -->
            <ConfirmDialog v-model:show="showConfirmDialog" :title="t('ব্র্যান্ড মুছে ফেলুন', 'Delete Brand')" :message="confirmMessage"
                :confirmText="t('মুছে ফেলুন', 'Delete')" :cancelText="t('বাতিল', 'Cancel')" @confirm="confirmDelete" />
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    TrashIcon,
    XMarkIcon,
    Squares2X2Icon
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useLanguage } from '@/composables/useLanguage';
import { getImageUrl } from '@/utils/image';

const { t } = useLanguage();

const props = defineProps({
    brands: Object,
    filters: Object
});

const showModal = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const imagePreview = ref(null);
const showConfirmDialog = ref(false);
const confirmMessage = ref('');
const brandToDelete = ref(null);

const form = useForm({
    name: '',
    logo: null,
    status: true
});

const openModal = () => {
    editing.value = false;
    form.reset();
    form.clearErrors();
    imagePreview.value = null;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
    editing.value = false;
    imagePreview.value = null;
};

const updateLogo = (e) => {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 1024 * 1024) {
            alert(t('ফাইলের আকার সর্বোচ্চ ১ মেগাবাইট হতে হবে', 'File size must be less than 1MB'));
            e.target.value = '';
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert(t('অনুগ্রহ করে একটি ছবি ফাইল নির্বাচন করুন', 'Please select an image file'));
            e.target.value = '';
            return;
        }

        imagePreview.value = URL.createObjectURL(file);
        form.logo = file;
    }
};

const submit = () => {
    if (editing.value) {
        form.put(route('admin.brands.update', editing.value), {
            onSuccess: () => {
                closeModal();
                imagePreview.value = null;
            },
            preserveScroll: true,
            preserveState: true,
            forceFormData: true,
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    } else {
        form.post(route('admin.brands.store'), {
            onSuccess: () => {
                closeModal();
                imagePreview.value = null;
            },
            preserveScroll: true,
            preserveState: true,
            forceFormData: true
        });
    }
};

const editBrand = (brand) => {
    editing.value = brand.id;
    form.name = brand.name;
    form.status = Boolean(brand.status);
    form.logo = brand.logo;
    showModal.value = true;
};

const deleteBrand = (brand) => {
    brandToDelete.value = brand;
    confirmMessage.value = t(
        `আপনি কি নিশ্চিত যে "${brand.name}" ব্র্যান্ডটি মুছে ফেলতে চান?`,
        `Are you sure you want to delete "${brand.name}"? This action cannot be undone.`
    );
    showConfirmDialog.value = true;
};

const confirmDelete = () => {
    if (brandToDelete.value) {
        router.delete(route('admin.brands.destroy', brandToDelete.value.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showConfirmDialog.value = false;
                brandToDelete.value = null;
            }
        });
    }
};

const debouncedSearch = debounce(() => {
    router.get(
        route('admin.brands.index'),
        { search: search.value, status: status.value },
        { preserveState: true, preserveScroll: true }
    );
}, 300);

const filterStatus = () => {
    router.get(
        route('admin.brands.index'),
        { search: search.value, status: status.value },
        { preserveState: true, preserveScroll: true }
    );
};

watch(() => props.filters, (newFilters) => {
    search.value = newFilters?.search || '';
    status.value = newFilters?.status || '';
}, { deep: true });
</script>
