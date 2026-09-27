<template>
    <Head :title="t('খরচের খাত / ক্যাটাগরি', 'Expense Categories')" />
    <AdminLayout :title="t('খরচের ক্যাটাগরি ব্যবস্থাপনা', 'Expense Categories')">
        <div class="container mx-auto px-4 py-6">
            <!-- Header Row -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ t('খরচের খাত তালিকা', 'Expense Categories') }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ t('খরচের ক্যাটাগরি তৈরি ও পরিচালনা করুন', 'Manage and organize expense categories') }}</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold flex items-center gap-2 shadow-sm transition"
                >
                    <PlusIcon class="w-4 h-4" />
                    {{ t('নতুন ক্যাটাগরি যোগ করুন', 'Add Category') }}
                </button>
            </div>

            <!-- Categories Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('ক্যাটাগরির নাম', 'Category Name') }}</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('বিবরণ', 'Description') }}</th>
                                <th class="px-6 py-3.5 text-center font-bold text-gray-600 dark:text-gray-300">{{ t('অবস্থা', 'Status') }}</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 dark:text-gray-300">{{ t('অ্যাকশন', 'Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="category in categories.data" :key="category.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition">
                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ category.name }}</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ category.description || '—' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-2.5 py-1 text-xs font-bold rounded-full"
                                        :class="category.status
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'"
                                    >
                                        {{ category.status ? t('সক্রিয়', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="editCategory(category)" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition" :title="t('সম্পাদনা', 'Edit')">
                                        <EditIcon class="w-4 h-4 inline" />
                                    </button>
                                    <button @click="deleteCategory(category)" class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition" :title="t('মুছুন', 'Delete')">
                                        <TrashIcon class="w-4 h-4 inline" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-gray-400 font-medium">
                                    {{ t('কোনো ক্যাটাগরি পাওয়া যায়নি।', 'No categories found.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="categories.links && categories.links.length > 3" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-1">
                    <Link
                        v-for="(link, i) in categories.links"
                        :key="i"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1.5 rounded-lg border text-sm font-medium transition"
                        :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border-gray-200 dark:border-gray-700'"
                    />
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    {{ isEditing ? t('ক্যাটাগরি সম্পাদনা করুন', 'Edit Category') : t('নতুন ক্যাটাগরি যোগ করুন', 'Add Category') }}
                </h3>
                <form @submit.prevent="submitForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ t('ক্যাটাগরির নাম *', 'Category Name *') }}</label>
                            <input type="text" v-model="form.name" required class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" :placeholder="t('যেমন: বেতন, ভাড়া, বিদ্যুৎ...', 'e.g. Salary, Rent, Utilities...')" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ t('বিবরণ (ঐচ্ছিক)', 'Description (Optional)') }}</label>
                            <textarea v-model="form.description" rows="2" class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" v-model="form.status" id="cat_status" class="w-4 h-4 rounded border-gray-300 text-blue-600" />
                            <label for="cat_status" class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('সক্রিয় হিসেবে চিহ্নিত করুন', 'Mark as Active') }}</label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 border rounded-xl text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700">
                            {{ t('বাতিল', 'Cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition">
                            {{ isEditing ? t('আপডেট করুন', 'Update') : t('সংরক্ষণ করুন', 'Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'
import { useLanguage } from '@/composables/useLanguage'
import {
    Plus as PlusIcon,
    Edit as EditIcon,
    Trash as TrashIcon
} from 'lucide-vue-next'

const { t } = useLanguage()

const props = defineProps({
    categories: Object
})

const showModal = ref(false)
const isEditing = ref(false)
const currentCategory = ref(null)

const form = reactive({
    name: '',
    description: '',
    status: true
})

const openCreateModal = () => {
    isEditing.value = false
    resetForm()
    showModal.value = true
}

const editCategory = (category) => {
    isEditing.value = true
    currentCategory.value = category
    Object.assign(form, {
        name: category.name,
        description: category.description,
        status: category.status
    })
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    resetForm()
}

const resetForm = () => {
    Object.assign(form, {
        name: '',
        description: '',
        status: true
    })
    currentCategory.value = null
}

const submitForm = () => {
    if (isEditing.value) {
        router.post(route('admin.expense-categories.update', currentCategory.value.id), {
            ...form,
            _method: 'PUT'
        }, {
            onSuccess: () => closeModal()
        })
    } else {
        router.post(route('admin.expense-categories.store'), form, {
            onSuccess: () => closeModal()
        })
    }
}

const deleteCategory = (category) => {
    const confirmMsg = t('আপনি কি নিশ্চিত যে এই ক্যাটাগরিটি মুছে ফেলতে চান?', 'Are you sure you want to delete this category?')
    if (confirm(confirmMsg)) {
        router.delete(route('admin.expense-categories.destroy', category.id))
    }
}
</script>
