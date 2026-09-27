<template>
    <Head :title="t('শাখা সমূহ', 'Branches')" />
    <AdminLayout :title="t('শাখা ব্যবস্থাপনা', 'Branches')">
        <div class="container mx-auto px-4 py-6 max-w-5xl">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ t('শাখা ও আউটলেট', 'Branches & Outlets') }}</h2>
                    <p class="text-xs text-gray-500">{{ t('বিভিন্ন শাখা, গোদাম ও বিলিং কাউন্টার পরিচালনা করুন', 'Manage multiple store locations, warehouses and billing counters') }}</p>
                </div>

                <button
                    @click="openCreateModal"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium flex items-center gap-1.5 shadow-sm"
                >
                    <PlusIcon class="w-4 h-4" />
                    {{ t('শাখা যোগ করুন', 'Add Branch') }}
                </button>
            </div>

            <!-- Branches List -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="b in branches.data"
                    :key="b.id"
                    class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm relative hover:shadow-md transition"
                >
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-base text-gray-900 dark:text-white">{{ b.name }}</h3>
                                <span
                                    v-if="b.is_main"
                                    class="px-2 py-0.5 text-xs font-bold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                                >
                                    {{ t('প্রধান শাখা', 'Main Branch') }}
                                </span>
                            </div>
                            <span class="text-xs font-mono text-gray-500">Code: {{ b.code }}</span>
                        </div>

                        <div class="flex items-center gap-1">
                            <button
                                @click="openEditModal(b)"
                                class="p-1 text-amber-600 hover:bg-amber-50 rounded"
                                title="Edit"
                            >
                                <PencilIcon class="w-4 h-4" />
                            </button>
                            <button
                                v-if="!b.is_main"
                                @click="deleteBranch(b)"
                                class="p-1 text-red-600 hover:bg-red-50 rounded"
                                title="Delete"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-xs text-gray-600 dark:text-gray-300">
                        <div v-if="b.phone" class="flex items-center gap-2">
                            <span class="text-gray-400">Phone:</span>
                            <span class="font-medium text-gray-800 dark:text-white">{{ b.phone }}</span>
                        </div>
                        <div v-if="b.email" class="flex items-center gap-2">
                            <span class="text-gray-400">Email:</span>
                            <span>{{ b.email }}</span>
                        </div>
                        <div v-if="b.address" class="flex items-center gap-2">
                            <span class="text-gray-400">Address:</span>
                            <span>{{ b.address }}</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center text-xs">
                        <span class="text-gray-400">Status</span>
                        <span
                            class="px-2 py-0.5 rounded capitalize font-medium"
                            :class="b.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                        >
                            {{ b.status }}
                        </span>
                    </div>
                </div>

                <div v-if="branches.data.length === 0" class="col-span-2 bg-white dark:bg-gray-800 p-8 rounded-xl text-center text-gray-400">
                    No branches configured. Click "Add Branch" to set up your store locations.
                </div>
            </div>

            <!-- Modal -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ isEditing ? 'Edit Branch' : 'Add New Branch' }}
                        </h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                    </div>

                    <form @submit.prevent="submitBranch" class="mt-4 space-y-4 text-sm">
                        <div>
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">Branch Name *</label>
                            <input
                                type="text"
                                v-model="form.name"
                                required
                                placeholder="e.g. Dhanmondi Outlet"
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">Branch Code *</label>
                            <input
                                type="text"
                                v-model="form.code"
                                required
                                placeholder="e.g. DHN-01"
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 uppercase"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                                <input
                                    type="text"
                                    v-model="form.phone"
                                    placeholder="017..."
                                    class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                />
                            </div>
                            <div>
                                <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                <input
                                    type="email"
                                    v-model="form.email"
                                    class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">Address</label>
                            <textarea
                                v-model="form.address"
                                rows="2"
                                placeholder="Location details..."
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                            ></textarea>
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="is_main"
                                v-model="form.is_main"
                                class="rounded text-blue-600 focus:ring-blue-500"
                            />
                            <label for="is_main" class="text-xs font-medium text-gray-700 dark:text-gray-300">Set as Primary / Main Branch</label>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg">Cancel</button>
                            <button type="submit" :disabled="submitting" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                                {{ isEditing ? 'Update Branch' : 'Create Branch' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    Plus as PlusIcon,
    Pencil as PencilIcon,
    Trash as TrashIcon
} from 'lucide-vue-next'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    branches: Object,
})

const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const submitting = ref(false)

const form = ref({
    name: '',
    code: '',
    phone: '',
    email: '',
    address: '',
    is_main: false,
    status: 'active',
})

const openCreateModal = () => {
    isEditing.value = false
    editingId.value = null
    form.value = {
        name: '',
        code: '',
        phone: '',
        email: '',
        address: '',
        is_main: false,
        status: 'active',
    }
    showModal.value = true
}

const openEditModal = (b) => {
    isEditing.value = true
    editingId.value = b.id
    form.value = {
        name: b.name,
        code: b.code,
        phone: b.phone,
        email: b.email,
        address: b.address,
        is_main: b.is_main,
        status: b.status,
    }
    showModal.value = true
}

const submitBranch = () => {
    submitting.value = true
    if (isEditing.value) {
        router.put(route('admin.branches.update', editingId.value), form.value, {
            onSuccess: () => {
                showModal.value = false
                submitting.value = false
            },
            onError: () => {
                submitting.value = false
            }
        })
    } else {
        router.post(route('admin.branches.store'), form.value, {
            onSuccess: () => {
                showModal.value = false
                submitting.value = false
            },
            onError: () => {
                submitting.value = false
            }
        })
    }
}

const deleteBranch = (b) => {
    const msg = t(`আপনি কি "${b.name}" শাখাটি মুছে ফেলতে চান?`, `Are you sure you want to delete branch "${b.name}"?`)
    if (confirm(msg)) {
        router.delete(route('admin.branches.destroy', b.id))
    }
}
</script>
