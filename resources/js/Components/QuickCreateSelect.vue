<script setup>
import { computed, nextTick, ref } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    modelValue: [String, Number],
    triggerOnly: Boolean,
    initialName: { type: String, default: '' },
    options: { type: Array, default: () => [] },
    resource: { type: String, required: true },
    placeholder: String,
    labelKey: { type: String, default: 'name' },
    descriptionKey: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue', 'change', 'created']);
const { t } = useLanguage();
const added = ref([]);
const options = computed(() => [...props.options, ...added.value.filter(a => !props.options.some(p => p.id === a.id))]);
const show = ref(false);
const busy = ref(false);
const loading = ref(false);
const errors = ref({});
const message = ref('');
const nameInput = ref();
const form = ref({});
const lookups = ref({ categories: [], brands: [], units: [] });
const product = computed(() => props.resource === 'products');
const titles = computed(() => ({
    categories: t('নতুন ক্যাটাগরি', 'New category'),
    brands: t('নতুন ব্র্যান্ড', 'New brand'),
    units: t('নতুন একক', 'New unit'),
    branches: t('নতুন শাখা', 'New branch'),
    products: t('নতুন পণ্য', 'New product'),
}));
const title = computed(() => titles.value[props.resource]);

async function open(name = '') {
    errors.value = {};
    message.value = '';
    form.value = {
        name, status: props.resource === 'branches' ? 'active' : true,
        short_name: '', code: '', type: 'product', sku: `P-${crypto.randomUUID()}`,
        barcode: '', category_id: '', brand_id: '', unit_id: '', selling_price: '',
        alert_quantity: 0, primary_image_index: 0,
    };
    show.value = true;
    if (product.value) {
        loading.value = true;
        try {
            const response = await axios.get(route('admin.products.create'), { headers: { Accept: 'application/json' } });
            lookups.value = response.data;
        } catch {
            message.value = t('তথ্য লোড হয়নি। বন্ধ করে আবার চেষ্টা করুন।', 'Could not load options. Close and try again.');
        } finally {
            loading.value = false;
        }
    }
    await nextTick();
    nameInput.value?.focus();
}

async function save() {
    if (busy.value || loading.value) return;
    busy.value = true;
    errors.value = {};
    message.value = '';
    try {
        const { data } = await axios.post(route(`admin.${props.resource}.store`), form.value, { headers: { Accept: 'application/json' } });
        if (!data.data?.id) throw new Error('Invalid response');
        const record = { ...data.data, displayName: data.data.name, current_stock: 0, last_unit_cost: 0 };
        added.value.push(record);
        emit('update:modelValue', record.id);
        emit('change', record);
        emit('created', record);
        show.value = false;
    } catch (error) {
        errors.value = error.response?.data?.errors || {};
        message.value = error.response?.data?.message || t('সংরক্ষণ হয়নি। আবার চেষ্টা করুন।', 'Could not save. Please try again.');
    } finally {
        busy.value = false;
    }
}
function select(id) { emit('update:modelValue', id); }
defineExpose({ open });
</script>

<template>
    <SearchableSelect v-if="!triggerOnly" :model-value="modelValue" :options="options" :label-key="labelKey"
        :description-key="descriptionKey" :placeholder="placeholder" creatable :create-label="title"
        @update:model-value="select" @change="emit('change', $event)" @create="open" />
    <button type="button" class="mt-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400" @click="open(initialName)">+ {{ title }}</button>
    <Teleport to="body">
    <Modal :show="show" :closeable="!busy" @close="show = false">
        <form class="space-y-4 p-6 text-slate-900 dark:text-slate-100" @submit.prevent.stop="save">
            <h2 class="text-lg font-bold">{{ title }}</h2>
            <p class="text-sm text-slate-500">{{ t('তৈরি হলে এখানেই নির্বাচিত হবে। চলতি ফর্মের তথ্য থাকবে।', 'Create and select here. Your current form stays in place.') }}</p>
            <p v-if="loading" role="status">{{ t('লোড হচ্ছে...', 'Loading...') }}</p>
            <p v-if="message" role="alert" class="text-sm text-rose-600">{{ message }}</p>
            <div v-for="(messages, field) in errors" :key="field" class="text-sm text-rose-600" role="alert">{{ messages.join(' ') }}</div>
            <label class="block text-sm">{{ t('নাম', 'Name') }} *
                <input ref="nameInput" v-model="form.name" required maxlength="255" class="mt-1 w-full rounded-lg dark:bg-slate-800" />
            </label>
            <label v-if="resource === 'units'" class="block text-sm">{{ t('সংক্ষিপ্ত নাম', 'Short name') }} *
                <input v-model="form.short_name" required maxlength="50" class="mt-1 w-full rounded-lg dark:bg-slate-800" />
            </label>
            <label v-if="resource === 'branches'" class="block text-sm">{{ t('শাখা কোড', 'Branch code') }} *
                <input v-model="form.code" required maxlength="50" class="mt-1 w-full rounded-lg dark:bg-slate-800" />
            </label>
            <template v-if="product && !loading">
                <label class="block text-sm">SKU *<input v-model="form.sku" required class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label>
                <label class="block text-sm">{{ t('বারকোড', 'Barcode') }}<input v-model="form.barcode" class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label>
                <div>{{ t('ক্যাটাগরি', 'Category') }} *
                    <QuickCreateSelect v-model="form.category_id" resource="categories" :options="lookups.categories" />
                </div>
                <div>{{ t('ব্র্যান্ড', 'Brand') }}
                    <QuickCreateSelect v-model="form.brand_id" resource="brands" :options="lookups.brands" />
                </div>
                <div>{{ t('একক', 'Unit') }} *
                    <QuickCreateSelect v-model="form.unit_id" resource="units" :options="lookups.units" />
                </div>
                <label class="block text-sm">{{ t('বিক্রয় মূল্য', 'Selling price') }} *
                    <input v-model="form.selling_price" type="number" min="0" step="0.01" required class="mt-1 w-full rounded-lg dark:bg-slate-800" />
                </label>
            </template>
            <div class="flex justify-end gap-3">
                <button type="button" :disabled="busy" class="rounded-lg border px-4 py-2" @click="show = false">{{ t('বাতিল', 'Cancel') }}</button>
                <button type="submit" :disabled="busy || loading || (product && (!form.category_id || !form.unit_id))" class="rounded-lg bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">{{ busy ? t('সংরক্ষণ হচ্ছে...', 'Saving...') : t('তৈরি ও নির্বাচন করুন', 'Create & select') }}</button>
            </div>
        </form>
    </Modal>
    </Teleport>
</template>
