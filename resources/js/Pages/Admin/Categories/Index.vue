<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import Pagination from '@/Components/Pagination.vue'
import { useLanguage } from '@/composables/useLanguage'
import { PlusIcon, MagnifyingGlassIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'

const { t } = useLanguage()

const props = defineProps({
    categories: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    }
})

const searchForm = ref({
    search: props.filters.search || ''
})

let searchTimer = null
const search = () => {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get('/admin/categories', { search: searchForm.value.search }, {
            preserveState: true,
            preserveScroll: true
        })
    }, 350)
}

const deleteCategory = (id) => {
    router.delete(`/admin/categories/${id}`)
}

const showConfirmDialog = ref(false)
const categoryToDelete = ref(null)

const confirmDelete = (category) => {
    categoryToDelete.value = category
    showConfirmDialog.value = true
}

const handleConfirmDelete = () => {
    if (categoryToDelete.value) {
        deleteCategory(categoryToDelete.value.id)
        showConfirmDialog.value = false
        categoryToDelete.value = null
    }
}
</script>

<template>
    <AdminLayout :title="t('ক্যাটাগরি ব্যবস্থাপনা', 'Categories')">
        <Head :title="t('ক্যাটাগরি ব্যবস্থাপনা', 'Categories')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                        {{ t('পণ্যের ক্যাটাগরি', 'Categories Management') }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('পণ্যসমূহ সাজানোর জন্য বিভিন্ন ক্যাটাগরি তৈরি ও পরিচালনা করুন', 'Manage item categories and classifications') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.categories.create')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-all">
                        <PlusIcon class="h-4 w-4" />
                        <span>{{ t('নতুন ক্যাটাগরি', 'Add Category') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Search -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                <div class="relative max-w-md">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <MagnifyingGlassIcon class="h-4 w-4 text-slate-400" />
                    </div>
                    <input v-model="searchForm.search" @input="search"
                        :placeholder="t('ক্যাটাগরি নাম দিয়ে খুঁজুন...', 'Search categories...')"
                        class="block w-full rounded-xl border-slate-200 bg-white py-2 pl-9 pr-3 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                </div>
            </div>

            <!-- Categories Table & Mobile Cards -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <!-- Mobile Card View (< sm) -->
                <div class="sm:hidden divide-y divide-slate-100 dark:divide-slate-800 p-3 space-y-3">
                    <div v-if="categories.data.length === 0" class="py-8 text-center text-xs text-slate-400">
                        {{ t('কোনো ক্যাটাগরি পাওয়া যায়নি।', 'No categories found.') }}
                    </div>

                    <div v-for="category in categories.data" :key="category.id" class="pt-3 first:pt-0 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white leading-snug">
                                    {{ category.name }}
                                </h4>
                                <p class="text-[11px] font-mono text-slate-400 mt-0.5">
                                    {{ category.slug }}
                                </p>
                            </div>
                            <span :class="[
                                'inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold shrink-0',
                                category.status
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                    : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                            ]">
                                {{ category.status ? t('সক্রিয়', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                {{ t('প্যারেন্ট:', 'Parent:') }} <strong class="text-slate-700 dark:text-slate-300">{{ category.parent?.name || t('কোনোটি নয়', 'None') }}</strong>
                            </span>

                            <div class="flex items-center gap-1.5">
                                <Link :href="route('admin.categories.edit', category.id)"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-indigo-600 hover:text-indigo-800 dark:border-slate-700 dark:bg-slate-800 dark:text-indigo-400 active:scale-90 transition-all">
                                    <PencilIcon class="h-4 w-4" />
                                </Link>
                                <button @click="confirmDelete(category)"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-rose-600 hover:text-rose-800 dark:border-slate-700 dark:bg-slate-800 dark:text-rose-400 active:scale-90 transition-all">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table (sm+) -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                            <tr>
                                <th class="px-5 py-3 text-left">{{ t('ক্যাটাগরি নাম', 'Category Name') }}</th>
                                <th class="px-4 py-3 text-left">{{ t('স্লাগ (Slug)', 'Slug') }}</th>
                                <th class="px-4 py-3 text-left">{{ t('মূল ক্যাটাগরি', 'Parent Category') }}</th>
                                <th class="px-4 py-3 text-center">{{ t('অবস্থা', 'Status') }}</th>
                                <th class="px-5 py-3 text-right">{{ t('অ্যাকশন', 'Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-if="categories.data.length === 0">
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                    {{ t('কোনো ক্যাটাগরি পাওয়া যায়নি।', 'No categories found.') }}
                                </td>
                            </tr>
                            <tr v-for="category in categories.data" :key="category.id"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white">{{ category.name }}</td>
                                <td class="px-4 py-3.5 font-mono text-slate-500 dark:text-slate-400">{{ category.slug }}</td>
                                <td class="px-4 py-3.5 font-medium text-slate-600 dark:text-slate-300">
                                    {{ category.parent?.name || t('কোনোটি নয়', 'None') }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span :class="[
                                        'inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold',
                                        category.status
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                                    ]">
                                        {{ category.status ? t('সক্রিয়', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right font-medium">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link :href="route('admin.categories.edit', category.id)"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-indigo-600 hover:text-indigo-800 dark:border-slate-700 dark:bg-slate-800 dark:text-indigo-400">
                                            <PencilIcon class="h-4 w-4" />
                                        </Link>
                                        <button @click="confirmDelete(category)"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-rose-600 hover:text-rose-800 dark:border-slate-700 dark:bg-slate-800 dark:text-rose-400">
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="categories.links && categories.links.length > 3" class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">
                    <Pagination :links="categories.links" />
                </div>
            </div>
        </div>

        <!-- Confirm Dialog Component -->
        <ConfirmDialog
            v-model:show="showConfirmDialog"
            :title="t('ক্যাটাগরি মুছে ফেলুন', 'Delete Category')"
            :message="t(`আপনি কি নিশ্চিত যে '${categoryToDelete?.name}' ক্যাটাগরিটি মুছে ফেলতে চান?`, `Are you sure you want to delete '${categoryToDelete?.name}'?`)"
            :confirm-text="t('মুছে ফেলুন', 'Delete')"
            :cancel-text="t('বাতিল', 'Cancel')"
            @confirm="handleConfirmDelete"
        />
    </AdminLayout>
</template>
