<script setup>
import { ref } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Users,
    UserPlus,
    Search,
    Shield,
    Mail,
    Phone,
    Edit2,
    Trash2,
    CheckCircle2,
    XCircle,
    X,
    Lock,
    KeyRound,
    UserCheck,
    AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    users: Object,
    roles: Array,
    maxUsers: Number,
    currentCount: Number,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const role_id = ref(props.filters?.role_id || '');
const status = ref(props.filters?.status || '');

const handleFilter = () => {
    router.get(route('admin.users.index'), {
        search: search.value,
        role_id: role_id.value,
        status: status.value,
    }, { preserveState: true, replace: true });
};

// Modals
const showModal = ref(false);
const isEditing = ref(false);
const editingUser = ref(null);

const form = useForm({
    name: '',
    username: '',
    email: '',
    phone: '',
    password: '',
    role_id: props.roles?.[0]?.id || 1,
    status: true,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingUser.value = null;
    form.reset();
    form.status = true;
    form.role_id = props.roles?.[0]?.id || 1;
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    editingUser.value = user;
    form.name = user.name;
    form.username = user.username || '';
    form.email = user.email;
    form.phone = user.phone || '';
    form.password = '';
    form.role_id = user.role_id;
    form.status = Boolean(user.status);
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.users.update', editingUser.value.id), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    }
};

const deleteUser = (user) => {
    if (confirm(`আপনি কি নিশ্চিত যে "${user.name}" ইউজারকে মুছে ফেলতে চান?`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="স্টাফ ও ইউজার ম্যানেজমেন্ট" />

        <div class="space-y-6">
            <!-- Header & Stats Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <Users class="w-6 h-6 text-emerald-500" />
                        <span>স্টাফ ও রোল ম্যানেজমেন্ট</span>
                    </h1>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
                        আপনার দোকানের সকল স্টাফ, ম্যানেজার ও সেলসম্যানদের অ্যাকাউন্ট ও রোল পরিচালনা করুন
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Quota Pill -->
                    <div class="px-3.5 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 text-xs flex items-center gap-2 shadow-sm">
                        <span class="text-gray-500 dark:text-slate-400">প্যাকেজ ইউজার কোটা:</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">
                            {{ currentCount }} / {{ maxUsers }}
                        </span>
                    </div>

                    <button
                        @click="openCreateModal"
                        :disabled="currentCount >= maxUsers"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-medium text-xs shadow-md shadow-emerald-500/10 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <UserPlus class="w-4 h-4" />
                        <span>নতুন স্টাফ যোগ করুন</span>
                    </button>
                </div>
            </div>

            <!-- Limit Warning Banner (if full) -->
            <div v-if="currentCount >= maxUsers" class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-400 text-xs flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <AlertCircle class="w-5 h-5 shrink-0 text-amber-500" />
                    <span>আপনার বর্তমান প্যাকেজের ইউজার লিমিট (সর্বোচ্চ {{ maxUsers }} জন) পূর্ণ হয়েছে। অতিরিক্ত স্টাফ যুক্ত করতে প্যাকেজ আপগ্রেড করুন।</span>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="relative flex-1">
                    <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="search"
                        @input="handleFilter"
                        type="text"
                        placeholder="নাম, ইউজারনেম, ইমেইল বা ফোন দিয়ে খুঁজুন..."
                        class="w-full pl-9 pr-4 py-2 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-xs text-gray-900 dark:text-white focus:border-emerald-500 focus:ring-0"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <select
                        v-model="role_id"
                        @change="handleFilter"
                        class="px-3 py-2 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-xs text-gray-900 dark:text-white focus:border-emerald-500 font-medium"
                    >
                        <option value="">সকল রোল</option>
                        <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>

                    <select
                        v-model="status"
                        @change="handleFilter"
                        class="px-3 py-2 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-xs text-gray-900 dark:text-white focus:border-emerald-500 font-medium"
                    >
                        <option value="">সকল স্ট্যাটাস</option>
                        <option value="active">অ্যাক্টিভ</option>
                        <option value="inactive">নিষ্ক্রিয়</option>
                    </select>
                </div>
            </div>

            <!-- Users Table Card -->
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-slate-950/60 border-b border-gray-100 dark:border-slate-800 text-gray-500 dark:text-slate-400">
                            <tr>
                                <th class="py-3 px-4 font-semibold">স্টাফ / ইউজার</th>
                                <th class="py-3 px-4 font-semibold">ফোন নম্বর</th>
                                <th class="py-3 px-4 font-semibold">রোল (Role)</th>
                                <th class="py-3 px-4 font-semibold">স্ট্যাটাস</th>
                                <th class="py-3 px-4 font-semibold text-right">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800/60">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold flex items-center justify-center text-sm shrink-0 border border-emerald-500/20">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                                                {{ user.name }}
                                                <span v-if="user.username" class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-mono font-semibold border border-emerald-500/20">
                                                    @{{ user.username }}
                                                </span>
                                                <span v-if="user.id === $page.props.auth?.user?.id" class="text-[10px] px-1.5 py-0.2 rounded bg-blue-500/10 text-blue-500 dark:text-blue-400 font-semibold border border-blue-500/20">
                                                    You
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-gray-500 dark:text-slate-400">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3 px-4 text-gray-600 dark:text-slate-300">
                                    <div class="flex items-center gap-1.5">
                                        <Phone class="w-3.5 h-3.5 text-gray-400" />
                                        <span>{{ user.phone || 'ফোন নম্বর নেই' }}</span>
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[11px] font-bold tracking-wide border inline-flex items-center gap-1',
                                            user.role?.slug === 'admin' ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20' :
                                            user.role?.slug === 'manager' ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' :
                                            'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                                        ]"
                                    >
                                        <Shield class="w-3 h-3" />
                                        {{ user.role?.name || 'Staff' }}
                                    </span>
                                </td>

                                <td class="py-3 px-4">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border',
                                            user.status ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-500 border-rose-500/20'
                                        ]"
                                    >
                                        {{ user.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            @click="openEditModal(user)"
                                            title="Edit User"
                                            class="p-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 transition"
                                        >
                                            <Edit2 class="w-3.5 h-3.5" />
                                        </button>

                                        <button
                                            v-if="user.id !== $page.props.auth?.user?.id"
                                            @click="deleteUser(user)"
                                            title="Delete User"
                                            class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 transition"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="py-12 text-center text-gray-400 dark:text-slate-500">
                                    কোনো স্টাফ পাওয়া যায়নি।
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.links?.length > 3" class="p-4 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between text-xs text-gray-500 dark:text-slate-400">
                    <div>
                        মোট {{ users.total }} জন স্টাফ
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in users.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-medium transition',
                                link.active ? 'bg-emerald-500 text-white font-bold' : 'hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-600 dark:text-slate-400',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit User Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <UserPlus class="w-5 h-5 text-emerald-500" />
                        {{ isEditing ? 'স্টাফ এডিট করুন' : 'নতুন স্টাফ অ্যাকাউন্ট তৈরি' }}
                    </h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block text-gray-700 dark:text-slate-300 font-medium mb-1">পূর্ণ নাম *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="যেমন: মোঃ সাব্বির আহমেদ"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-gray-900 dark:text-white focus:border-emerald-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-gray-700 dark:text-slate-300 font-medium mb-1">লগইন ইমেইল *</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="sabbir@store.com"
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-gray-900 dark:text-white focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-slate-300 font-medium mb-1">ইউজারনেম (Username)</label>
                            <input
                                v-model="form.username"
                                type="text"
                                placeholder="যেমন: sabbir_pos"
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-gray-900 dark:text-white focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-gray-700 dark:text-slate-300 font-medium mb-1">ফোন নম্বর</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                placeholder="017XXXXXXXX"
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-gray-900 dark:text-white focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-slate-300 font-medium mb-1">দায়িত্ব / রোল *</label>
                            <select
                                v-model="form.role_id"
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-gray-900 dark:text-white focus:border-emerald-500 font-semibold"
                            >
                                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-slate-300 font-medium mb-1">
                            {{ isEditing ? 'নতুন পাসওয়ার্ড (পরিবর্তন করতে চাইলে লিখুন)' : 'পাসওয়ার্ড (কমপক্ষে ৪ সংখ্যা) *' }}
                        </label>
                        <input
                            v-model="form.password"
                            type="password"
                            :required="!isEditing"
                            minlength="4"
                            placeholder="কমপক্ষে ৪ ডিজিটের পাসওয়ার্ড..."
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-gray-900 dark:text-white focus:border-emerald-500"
                        />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="status"
                            v-model="form.status"
                            class="rounded bg-gray-100 dark:bg-slate-900 border-gray-300 dark:border-slate-700 text-emerald-500 focus:ring-0"
                        />
                        <label for="status" class="text-gray-700 dark:text-slate-300 cursor-pointer font-medium">
                            অ্যাকাউন্ট অ্যাক্টিভ রাখুন (সরাসরি লগইন করতে পারবে)
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-300"
                        >
                            বাতিল
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold shadow-md shadow-emerald-500/20 disabled:opacity-50"
                        >
                            {{ isEditing ? 'আপডেট করুন' : 'তৈরি করুন' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
