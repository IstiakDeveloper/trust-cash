<template>
    <AdminLayout :title="t('পণ্য সম্পাদন করুন', 'Edit Product') + ' - ' + product.name">
        <Head :title="t('পণ্য সম্পাদন করুন', 'Edit Product')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                        {{ t('পণ্য তথ্য সংশোধন ও সম্পাদন', 'Edit Product') }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('পণ্যের নাম, দাম, ছবি ও স্পেসিফিকেশন আপডেট করুন', 'Update product details, pricing, stock alert, and images') }}
                    </p>
                </div>
                <Link :href="route('admin.products.index')"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <ArrowLeftIcon class="h-4 w-4" />
                    <span>{{ t('পণ্য তালিকায় ফিরুন', 'Back to Products') }}</span>
                </Link>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information Card -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">
                        {{ t('মৌলিক তথ্য', 'Basic Information') }}
                    </h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                        <div class="sm:col-span-4">
                            <InputLabel for="name" :value="t('পণ্যের নাম *', 'Product Name *')" class="text-xs font-bold" />
                            <TextInput id="name" v-model="form.name" type="text"
                                class="mt-1 block w-full text-xs rounded-xl"
                                :placeholder="t('পণ্যের নাম লিখুন', 'Enter product name')" required />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="sku" :value="t('SKU কোড *', 'SKU *')" class="text-xs font-bold" />
                            <div class="mt-1 flex rounded-xl shadow-xs overflow-hidden border border-slate-200 dark:border-slate-700">
                                <input id="sku" v-model="form.sku" type="text"
                                    class="block w-full border-0 px-3 py-2 text-xs font-mono text-slate-900 focus:ring-0 dark:bg-slate-800 dark:text-slate-100"
                                    required />
                                <button type="button"
                                    class="inline-flex items-center bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 dark:bg-slate-700 dark:text-slate-200"
                                    @click="generateSku">
                                    <ArrowPathIcon class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError :message="form.errors.sku" class="mt-1" />
                        </div>

                        <!-- Category & Brand -->
                        <div class="sm:col-span-3">
                            <InputLabel for="category_id" :value="t('ক্যাটাগরি *', 'Category *')" class="text-xs font-bold" />
                            <select id="category_id" v-model="form.category_id"
                                class="mt-1 block w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                required>
                                <option value="">{{ t('ক্যাটাগরি নির্বাচন করুন', 'Select Category') }}</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.category_id" class="mt-1" />
                        </div>

                        <div class="sm:col-span-3">
                            <InputLabel for="brand_id" :value="t('ব্র্যান্ড', 'Brand')" class="text-xs font-bold" />
                            <select id="brand_id" v-model="form.brand_id"
                                class="mt-1 block w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">{{ t('ব্র্যান্ড নির্বাচন করুন', 'Select Brand') }}</option>
                                <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                    {{ brand.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.brand_id" class="mt-1" />
                        </div>

                        <!-- Barcode & Unit -->
                        <div class="sm:col-span-3">
                            <InputLabel for="barcode" :value="t('বারকোড *', 'Barcode *')" class="text-xs font-bold" />
                            <div class="mt-1 flex rounded-xl shadow-xs overflow-hidden border border-slate-200 dark:border-slate-700">
                                <input id="barcode" v-model="form.barcode" type="text"
                                    class="block w-full border-0 px-3 py-2 text-xs font-mono text-slate-900 focus:ring-0 dark:bg-slate-800 dark:text-slate-100"
                                    required />
                                <button type="button"
                                    class="inline-flex items-center bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 dark:bg-slate-700 dark:text-slate-200"
                                    @click="generateBarcode">
                                    <ArrowPathIcon class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError :message="form.errors.barcode" class="mt-1" />
                        </div>

                        <div class="sm:col-span-3">
                            <InputLabel for="unit_id" :value="t('পরিমাপের একক *', 'Unit *')" class="text-xs font-bold" />
                            <select id="unit_id" v-model="form.unit_id"
                                class="mt-1 block w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                required>
                                <option value="">{{ t('একক নির্বাচন করুন', 'Select Unit') }}</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                    {{ unit.name }} ({{ unit.short_name }})
                                </option>
                            </select>
                            <InputError :message="form.errors.unit_id" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Pricing Card -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">
                        {{ t('মূল্য ও স্টক সতর্কতা', 'Pricing & Stock Alert') }}
                    </h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="selling_price" :value="t('বিক্রয় মূল্য (টাকা) *', 'Selling Price (BDT) *')" class="text-xs font-bold" />
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-xs font-bold text-slate-400">৳</span>
                                </div>
                                <input id="selling_price" v-model="form.selling_price" type="number"
                                    step="0.01" min="0" required placeholder="0.00"
                                    class="block w-full rounded-xl border-slate-200 py-2 pl-7 pr-3 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            </div>
                            <InputError :message="form.errors.selling_price" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="alert_quantity" :value="t('সীমিত স্টক সতর্কতা সীমা *', 'Alert Quantity *')" class="text-xs font-bold" />
                            <TextInput id="alert_quantity" v-model="form.alert_quantity"
                                type="number" min="0" class="mt-1 block w-full text-xs rounded-xl" required />
                            <InputError :message="form.errors.alert_quantity" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">
                        {{ t('অতিরিক্ত তথ্য ও বিবরণ', 'Additional Information') }}
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <InputLabel for="description" :value="t('পণ্যের বিস্তারিত বিবরণ', 'Description')" class="text-xs font-bold" />
                            <textarea id="description" v-model="form.description" rows="3"
                                class="mt-1 block w-full rounded-xl border-slate-200 bg-white p-3 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                :placeholder="t('পণ্য সংক্রান্ত নোট বা বিবরণ...', 'Enter product description')"></textarea>
                        </div>

                        <!-- Specifications -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <InputLabel :value="t('পণ্যের বৈশিষ্ট্য (Specifications)', 'Specifications')" class="text-xs font-bold" />
                                <button type="button"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800"
                                    @click="addSpecification">
                                    <PlusIcon class="h-3.5 w-3.5" />
                                    <span>{{ t('বৈশিষ্ট্য যোগ', 'Add Specification') }}</span>
                                </button>
                            </div>

                            <div v-for="(spec, index) in specifications" :key="index"
                                class="flex items-center gap-3">
                                <TextInput v-model="spec.key" type="text" class="flex-1 text-xs rounded-xl"
                                    :placeholder="t('বৈশিষ্ট্যের নাম', 'Specification name')" />
                                <TextInput v-model="spec.value" type="text" class="flex-1 text-xs rounded-xl"
                                    :placeholder="t('মান', 'Specification value')" />
                                <button type="button"
                                    class="p-2 text-rose-500 hover:text-rose-700"
                                    @click="removeSpecification(index)">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Images -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2">
                        {{ t('পণ্যের ছবিসমূহ', 'Product Images') }}
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                        {{ t('তারকা চিহ্নে ক্লিক করে মূল (Primary) ছবি নির্ধারণ করুন।', 'Click star icon to set as primary image.') }}
                    </p>

                    <!-- Current Existing Images -->
                    <div class="mb-4">
                        <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                            {{ t('বর্তমান ছবিসমূহ', 'Current Images') }}
                        </h4>
                        <div v-if="existingImages.length" class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                            <div v-for="image in existingImages" :key="image.id"
                                class="relative aspect-square rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 group">
                                <img :src="getImageUrl(image.url || image.image)" class="h-full w-full object-cover">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <button type="button" @click="setExistingImageAsPrimary(image.id)"
                                        class="p-1.5 text-white hover:text-amber-400"
                                        :class="{ 'text-amber-400': primaryImageId === image.id }">
                                        <StarIcon class="h-5 w-5" :class="{ 'fill-current': primaryImageId === image.id }" />
                                    </button>
                                    <button type="button" @click="deleteExistingImage(image.id)" class="p-1.5 text-white hover:text-rose-400">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                                <div v-if="primaryImageId === image.id"
                                    class="absolute top-1.5 left-1.5 bg-indigo-600 text-white px-2 py-0.5 rounded-md text-[10px] font-bold">
                                    {{ t('মূল ছবি', 'Primary') }}
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-400">{{ t('কোনো সংরক্ষিত ছবি নেই।', 'No images yet.') }}</p>
                    </div>

                    <!-- Upload New Images -->
                    <div class="rounded-2xl border-2 border-dashed border-slate-200 p-6 text-center hover:border-indigo-500 dark:border-slate-700 transition-colors"
                        tabindex="0"
                        @dragover.prevent
                        @drop.prevent="onFilesDrop"
                        @paste.prevent="onPasteImages">
                        <PhotoIcon class="mx-auto h-10 w-10 text-slate-400" />
                        <div class="mt-2 text-xs font-semibold text-slate-600 dark:text-slate-400">
                            <label for="file-upload" class="cursor-pointer font-bold text-indigo-600 hover:text-indigo-500">
                                <span>{{ t('নতুন ছবি যোগ করুন', 'Upload new files') }}</span>
                                <input id="file-upload" type="file" multiple class="sr-only" accept="image/*" @change="onImagesSelected">
                            </label>
                            <span class="ml-1">{{ t('অথবা ড্র্যাগ অ্যান্ড ড্রপ করুন', 'or drag and drop') }}</span>
                        </div>
                    </div>

                    <!-- New Upload Previews -->
                    <div v-if="imagesPreviews.length" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                        <div v-for="(preview, index) in imagesPreviews" :key="index"
                            class="relative aspect-square rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 group">
                            <img :src="preview" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <button type="button" @click="setNewImageAsPrimary(index)"
                                    class="p-1.5 text-white hover:text-amber-400"
                                    :class="{ 'text-amber-400': primaryImageIndex === index }">
                                    <StarIcon class="h-5 w-5" :class="{ 'fill-current': primaryImageIndex === index }" />
                                </button>
                                <button type="button" @click="removeNewImage(index)" class="p-1.5 text-white hover:text-rose-400">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>
                            <div v-if="primaryImageIndex === index"
                                class="absolute top-1.5 left-1.5 bg-indigo-600 text-white px-2 py-0.5 rounded-md text-[10px] font-bold">
                                {{ t('মূল ছবি', 'Primary') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.products.index')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        {{ t('বাতিল', 'Cancel') }}
                    </Link>
                    <PrimaryButton type="submit" :disabled="form.processing" class="rounded-xl text-xs bg-indigo-600 hover:bg-indigo-500">
                        {{ form.processing ? t('সংরক্ষণ হচ্ছে...', 'Updating...') : t('তথ্য পরিবর্তন সংরক্ষণ করুন', 'Update Product') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';
import {
    ArrowLeftIcon,
    PhotoIcon,
    TrashIcon,
    ArrowPathIcon,
    StarIcon,
    PlusIcon
} from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useLanguage } from '@/composables/useLanguage';
import { getImageUrl } from '@/utils/image';

const { t } = useLanguage();

const props = defineProps({
    product: Object,
    categories: Array,
    brands: Array,
    units: Array
});

const form = useForm({
    name: props.product?.name || '',
    sku: props.product?.sku || '',
    barcode: props.product?.barcode || '',
    category_id: props.product?.category_id || '',
    brand_id: props.product?.brand_id || '',
    unit_id: props.product?.unit_id || '',
    selling_price: props.product?.selling_price || '',
    alert_quantity: props.product?.alert_quantity || 0,
    description: props.product?.description || '',
    specifications: props.product?.specifications || {},
    images: [],
    primary_image_index: null,
    primary_image_id: null,
    status: props.product?.status !== undefined ? props.product.status : true
});

const specifications = ref([]);
onMounted(() => {
    if (props.product?.specifications && Object.keys(props.product.specifications).length > 0) {
        specifications.value = Object.entries(props.product.specifications).map(([key, value]) => ({ key, value }));
    } else {
        specifications.value = [{ key: '', value: '' }];
    }

    const primaryImage = existingImages.value.find(img => img.is_primary);
    if (primaryImage) {
        primaryImageId.value = primaryImage.id;
    } else if (existingImages.value.length > 0) {
        primaryImageId.value = existingImages.value[0].id;
    }
    form.primary_image_id = primaryImageId.value;
});

const addSpecification = () => {
    specifications.value.push({ key: '', value: '' });
};

const removeSpecification = (index) => {
    specifications.value.splice(index, 1);
};

const imagesPreviews = ref([]);
const existingImages = ref(props.product?.images || []);
const primaryImageId = ref(null);
const primaryImageIndex = ref(null);
const pendingImageFiles = ref([]);

const deleteExistingImage = (imageId) => {
    axios.delete(route('admin.products.delete-image', imageId))
        .then(() => {
            existingImages.value = existingImages.value.filter(img => img.id !== imageId);
            if (primaryImageId.value === imageId) {
                primaryImageId.value = existingImages.value.length > 0 ? existingImages.value[0].id : null;
                form.primary_image_id = primaryImageId.value;

                if (!primaryImageId.value && imagesPreviews.value.length > 0) {
                    primaryImageIndex.value = 0;
                    form.primary_image_index = 0;
                    form.primary_image_id = null;
                }
            }
        })
        .catch(error => console.error('Error deleting image:', error));
};

const setExistingImageAsPrimary = (imageId) => {
    primaryImageId.value = imageId;
    primaryImageIndex.value = null;
    form.primary_image_id = imageId;
    form.primary_image_index = null;
};

const handleFilesForUpdate = (files) => {
    const validFiles = files.filter(file => {
        if (file.size > 10 * 1024 * 1024) {
            alert(t('ফাইলের আকার সর্বোচ্চ ১০ মেগাবাইট হতে পারবে', 'Maximum file size is 10MB'));
            return false;
        }
        if (!file.type.startsWith('image/')) {
            alert(t('অনুগ্রহ করে ছবি নির্বাচন করুন', 'Please select an image file'));
            return false;
        }
        return true;
    });

    validFiles.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            imagesPreviews.value.push(e.target.result);
        };
        reader.readAsDataURL(file);
        pendingImageFiles.value.push(file);
    });

    if (primaryImageId.value === null && primaryImageIndex.value === null && imagesPreviews.value.length === validFiles.length) {
        primaryImageIndex.value = 0;
    }
};

const onPasteImages = (event) => {
    const items = Array.from(event.clipboardData?.items || []);
    const files = items
        .filter(i => i.kind === 'file' && i.type?.startsWith('image/'))
        .map(i => i.getAsFile())
        .filter(Boolean);

    if (files.length) {
        handleFilesForUpdate(files);
        if (primaryImageId.value === null && primaryImageIndex.value === null) {
            primaryImageIndex.value = 0;
        }
    }
};

const onImagesSelected = (event) => {
    const files = Array.from(event.target.files);
    handleFilesForUpdate(files);
};

const onFilesDrop = (event) => {
    const files = Array.from(event.dataTransfer.files).filter(file => file.type.startsWith('image/'));
    if (files.length > 0) {
        handleFilesForUpdate(files);
    }
};

const removeNewImage = (index) => {
    imagesPreviews.value.splice(index, 1);
    pendingImageFiles.value.splice(index, 1);

    if (primaryImageIndex.value === index) {
        primaryImageIndex.value = null;
        if (existingImages.value.length > 0 && !primaryImageId.value) {
            primaryImageId.value = existingImages.value[0].id;
        }
    } else if (primaryImageIndex.value !== null && primaryImageIndex.value > index) {
        primaryImageIndex.value--;
    }
};

const setNewImageAsPrimary = (index) => {
    primaryImageIndex.value = index;
    primaryImageId.value = null;
    form.primary_image_index = index;
    form.primary_image_id = null;
};

const generateBarcode = () => {
    const randomDigits = Array.from({ length: 6 }, () => Math.floor(Math.random() * 10));
    form.barcode = randomDigits.join('');
};

const generateSku = () => {
    if (!form.name) return;

    const namePrefix = form.name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase();

    const randomNum = Math.floor(Math.random() * 10000)
        .toString()
        .padStart(4, '0');

    form.sku = `${namePrefix}-${randomNum}`;
};

const submit = () => {
    const specsObject = specifications.value.reduce((acc, spec) => {
        if (spec.key && spec.value) {
            acc[spec.key] = spec.value;
        }
        return acc;
    }, {});

    form.specifications = specsObject;
    form.images = pendingImageFiles.value;
    form.primary_image_index = primaryImageIndex.value;
    form.primary_image_id = primaryImageId.value;

    form.post(route('admin.products.update', props.product.id) + '?_method=PUT', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            imagesPreviews.value = [];
            pendingImageFiles.value = [];
        }
    });
};
</script>
