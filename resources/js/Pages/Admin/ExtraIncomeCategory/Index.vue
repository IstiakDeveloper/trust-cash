<!-- resources/js/Pages/Admin/ExtraIncomeCategory/Index.vue -->
<template>
    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <h2 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-gray-200">
                    Extra Income Categories
                </h2>
                <Link :href="route('admin.extra-income-categories.create')"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span>Add Category</span>
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4">
                <!-- Search and Filter -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-3 sm:p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <input type="text" v-model="search" @input="debouncedSearch"
                                placeholder="Search categories..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <select v-model="filters.status"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Status</option>
                                <option :value="true">Active</option>
                                <option :value="false">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Categories Data Container -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <!-- Mobile Cards View (md:hidden) -->
                    <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
                        <div
                            v-for="category in categories.data"
                            :key="category.id"
                            class="p-3.5 sm:p-4 space-y-2 hover:bg-gray-50/50 dark:hover:bg-gray-700/30"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                    {{ category.name }}
                                </div>
                                <span
                                    :class="[
                                        'px-2 py-0.5 inline-flex text-xs font-semibold rounded-full',
                                        category.status
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200'
                                    ]">
                                    {{ category.status ? 'Active' : 'Inactive' }}
                                </span>
                            </div>

                            <p v-if="category.description" class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                {{ category.description }}
                            </p>

                            <div class="flex items-center justify-between pt-1 border-t border-gray-50 dark:border-gray-700/50 text-xs">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Total Income: </span>
                                    <span class="font-semibold text-green-600 dark:text-green-400">
                                        {{ formatCurrency(category.total_income || 0) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Link :href="route('admin.extra-income-categories.edit', category.id)"
                                        class="px-2.5 py-1 text-xs font-medium rounded text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30">
                                        Edit
                                    </Link>
                                    <button @click="confirmDelete(category)"
                                        class="px-2.5 py-1 text-xs font-medium rounded text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="categories.data.length === 0" class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            No categories found
                        </div>
                    </div>

                    <!-- Desktop Categories Table (hidden md:block) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Category Name
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Total Income
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="category in categories.data" :key="category.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ category.name }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ category.description || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                                            {{ formatCurrency(category.total_income || 0) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'px-2.5 py-0.5 inline-flex text-xs font-semibold rounded-full',
                                                category.status
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200'
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200'
                                            ]">
                                            {{ category.status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <Link :href="route('admin.extra-income-categories.edit', category.id)"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                Edit
                                            </Link>
                                            <button @click="confirmDelete(category)"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="categories.data.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        No categories found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-100 dark:border-gray-700 sm:px-6">
                        <Pagination :links="categories.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="confirmingCategoryDeletion" @close="closeModal">
            <div class="p-5 sm:p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">
                    Delete Category
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Are you sure you want to delete this category? This action cannot be undone.
                </p>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" @click="closeModal"
                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-center">
                        Cancel
                    </button>
                    <button type="button" :disabled="form.processing" @click="deleteCategory"
                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 text-center">
                        Delete Category
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    categories: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        required: true
    }
})

const search = ref(props.filters.search)
const confirmingCategoryDeletion = ref(false)
const categoryToDelete = ref(null)

const form = useForm({})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
    }).format(amount)
}

const debouncedSearch = debounce(() => {
    router.get(route('admin.extra-income-categories.index'), {
        search: search.value,
        status: filters.status
    }, { preserveState: true, preserveScroll: true })
}, 300)

const confirmDelete = (category) => {
    categoryToDelete.value = category
    confirmingCategoryDeletion.value = true
}

const closeModal = () => {
    confirmingCategoryDeletion.value = false
    categoryToDelete.value = null
}

const deleteCategory = () => {
    if (categoryToDelete.value) {
        form.delete(route('admin.extra-income-categories.destroy', categoryToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        })
    }
}

watch(() => props.filters.status, (value) => {
    router.get(route('admin.extra-income-categories.index'), {
        search: search.value,
        status: value
    }, { preserveState: true, preserveScroll: true })
})
</script>
