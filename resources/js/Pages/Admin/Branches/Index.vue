<template>
    <Head :title="t('শাখা সমূহ', 'Branches')" />
    <AdminLayout :title="t('শাখা ব্যবস্থাপনা', 'Branches')">
        <div class="max-w-5xl mx-auto space-y-4 sm:space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">{{ t('শাখা ও আউটলেট', 'Branches & Outlets') }}</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('বিভিন্ন শাখা, গোদাম ও বিলিং কাউন্টার পরিচালনা করুন', 'Manage multiple store locations, warehouses and billing counters') }}</p>
                </div>

                <button
                    @click="openCreateModal"
                    class="w-full sm:w-auto px-4 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-500 text-xs font-bold flex items-center justify-center gap-2 shadow-xs transition"
                >
                    <PlusIcon class="w-4 h-4 shrink-0" />
                    <span>{{ t('শাখা যোগ করুন', 'Add Branch') }}</span>
                </button>
            </div>

            <!-- Branches Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-4">
                <div
                    v-for="b in branches.data"
                    :key="b.id"
                    class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs relative hover:shadow-md transition space-y-3"
                >
                    <div class="flex justify-between items-start gap-2">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white">{{ b.name }}</h3>
                                <span
                                    v-if="b.is_main"
                                    class="px-2 py-0.5 text-[11px] font-bold rounded-md bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-900"
                                >
                                    {{ t('প্রধান শাখা', 'Main Branch') }}
                                </span>
                            </div>
                            <span class="text-xs font-mono text-slate-400 block mt-0.5">Code: {{ b.code }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <button
                                @click="openEditModal(b)"
                                class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition"
                                title="Edit"
                            >
                                <PencilIcon class="w-4 h-4" />
                            </button>
                            <button
                                v-if="!b.is_main"
                                @click="deleteBranch(b)"
                                class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-400 transition"
                                title="Delete"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                        <div v-if="b.phone" class="flex items-center gap-2">
                            <span class="text-slate-400">{{ t('ফোন:', 'Phone:') }}</span>
                            <a :href="'tel:' + b.phone" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ b.phone }}
                            </a>
                        </div>
                        <div v-if="b.email" class="flex items-center gap-2">
                            <span class="text-slate-400">{{ t('ইমেইল:', 'Email:') }}</span>
                            <span class="break-all">{{ b.email }}</span>
                        </div>
                        <div v-if="b.address" class="flex items-center gap-2">
                            <span class="text-slate-400">{{ t('ঠিকানা:', 'Address:') }}</span>
                            <span>{{ b.address }}</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center text-xs">
                        <span class="text-slate-400">{{ t('অবস্থা', 'Status') }}</span>
                        <span
                            class="px-2 py-0.5 rounded-md text-[11px] font-bold capitalize"
                            :class="b.status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
                        >
                            {{ b.status }}
                        </span>
                    </div>
                </div>

                <div v-if="branches.data.length === 0" class="col-span-1 md:col-span-2 bg-white dark:bg-slate-900 p-8 rounded-2xl border border-slate-200/90 dark:border-slate-800 text-center text-xs text-slate-400">
                    {{ t('কোনো শাখা সেট করা নেই। নতুন শাখা যোগ করতে "শাখা যোগ করুন" বাটনে ক্লিক করুন।', 'No branches configured. Click "Add Branch" to set up your store locations.') }}
                </div>
            </div>

            <!-- Modal -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-3 sm:p-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-4 sm:p-6 border border-slate-200 dark:border-slate-800 space-y-4">
                    <div class="flex justify-between items-center pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ isEditing ? t('শাখা সম্পাদনা করুন', 'Edit Branch') : t('নতুন শাখা যোগ করুন', 'Add New Branch') }}
                        </h3>
                        <button @click="showModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold leading-none">&times;</button>
                    </div>

                    <form @submit.prevent="submitBranch" class="space-y-3.5 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('শাখার নাম *', 'Branch Name *') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.name"
                                required
                                :placeholder="t('যেমন: ধানমন্ডি শাখা', 'e.g. Dhanmondi Outlet')"
                                class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('শাখা কোড *', 'Branch Code *') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.code"
                                required
                                placeholder="DHN-01"
                                class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600 uppercase"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    {{ t('ফোন নম্বর', 'Phone') }}
                                </label>
                                <input
                                    type="text"
                                    v-model="form.phone"
                                    placeholder="017XXXXXXXX"
                                    class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    {{ t('ইমেইল', 'Email') }}
                                </label>
                                <input
                                    type="email"
                                    v-model="form.email"
                                    placeholder="branch@store.com"
                                    class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('ঠিকানা', 'Address') }}
                            </label>
                            <textarea
                                v-model="form.address"
                                rows="2"
                                :placeholder="t('শাখার বিস্তারিত ঠিকানা...', 'Location details...')"
                                class="w-full px-3.5 py-2 border rounded-xl border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            ></textarea>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input
                                type="checkbox"
                                id="is_main"
                                v-model="form.is_main"
                                class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label for="is_main" class="text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                {{ t('প্রধান শাখা হিসেবে নির্ধারণ করুন', 'Set as Primary / Main Branch') }}
                            </label>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                {{ t('বাতিল', 'Cancel') }}
                            </button>
                            <button type="submit" :disabled="submitting" class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-xs transition disabled:opacity-50">
                                {{ isEditing ? t('শাখা আপডেট করুন', 'Update Branch') : t('শাখা তৈরি করুন', 'Create Branch') }}
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
