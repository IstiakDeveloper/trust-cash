<template>
  <div class="inline-block">
    <!-- Slot or Default Trigger Button -->
    <slot name="trigger" :open="openModal">
      <button
        v-if="showButton"
        type="button"
        @click="openModal()"
        :class="buttonClass || 'inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-500 transition-all cursor-pointer'"
      >
        <QrCodeIcon class="h-4 w-4" />
        <span>{{ buttonLabel || t('বারকোড প্রিন্ট', 'Print Barcodes') }}</span>
        <span v-if="effectiveProducts.length > 1" class="ml-1 rounded-md bg-emerald-700/60 px-1.5 py-0.5 text-[10px]">
          {{ formatNumber(effectiveProducts.length) }}
        </span>
      </button>
    </slot>

    <!-- Print Settings Modal -->
    <TransitionRoot appear :show="isOpen" as="template">
      <Dialog as="div" @close="closeModal" class="relative z-50">
        <TransitionChild
          enter="duration-300 ease-out"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="duration-200 ease-in"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
            <TransitionChild
              enter="duration-300 ease-out"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="duration-200 ease-in"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel class="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-2xl transition-all dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                  <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                      <QrCodeIcon class="h-5 w-5" />
                    </div>
                    <div>
                      <DialogTitle as="h3" class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ t('বারকোড প্রিন্ট সেটিংস', 'Barcode Print Settings') }}
                      </DialogTitle>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        {{ t('লেবেল সংখ্যা ও কাগজের সাইজ নির্বাচন করুন', 'Configure copies and label paper size') }}
                      </p>
                    </div>
                  </div>

                  <button
                    type="button"
                    @click="closeModal"
                    class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                  >
                    <XMarkIcon class="h-5 w-5" />
                  </button>
                </div>

                <!-- Modal Body -->
                <div class="mt-4 space-y-4">
                  <!-- Global Controls -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl border border-slate-200/60 dark:border-slate-700/60">
                    <div>
                      <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ t('কাগজের সাইজ', 'Paper Size') }}
                      </label>
                      <select
                        v-model="paperSize"
                        class="block w-full rounded-xl border-slate-200 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                      >
                        <option value="A4">{{ t('A4 স্টিকার শিট', 'A4 Sheet') }}</option>
                        <option value="80mm">{{ t('৮০ মিমি থার্মাল রোল (80mm Receipt/Label)', '80mm Roll') }}</option>
                        <option value="Letter">{{ t('লেটার সাইজ (Letter)', 'Letter') }}</option>
                      </select>
                    </div>

                    <div>
                      <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ t('ডিফল্ট কপি সংখ্যা', 'Default Copies') }}
                      </label>
                      <div class="flex items-center gap-2">
                        <input
                          type="number"
                          v-model.number="bulkCopies"
                          min="1"
                          max="100"
                          class="block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-xs font-semibold text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        />
                        <button
                          type="button"
                          @click="applyBulkCopies"
                          :title="t('সবার উপর প্রয়োগ করুন', 'Apply to all')"
                          class="shrink-0 rounded-xl bg-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 transition-all cursor-pointer"
                        >
                          {{ t('প্রয়োগ', 'Apply') }}
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Selected Products List -->
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                        {{ t('নির্বাচিত পণ্য তালিকা', 'Selected Products') }} ({{ formatNumber(effectiveProducts.length) }})
                      </span>
                      <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400">
                        {{ t('মোট প্রিন্ট লেবেল:', 'Total labels:') }} {{ formatNumber(totalLabels) }}
                      </span>
                    </div>

                    <div class="max-h-48 overflow-y-auto space-y-2 pr-1 divide-y divide-slate-100 dark:divide-slate-800">
                      <div
                        v-for="(prod, idx) in productCopiesList"
                        :key="prod.id"
                        class="pt-2 first:pt-0 flex items-center justify-between gap-3 text-xs"
                      >
                        <div class="min-w-0 flex-1">
                          <p class="font-bold text-slate-900 dark:text-white truncate">
                            {{ prod.name }}
                          </p>
                          <div class="flex items-center gap-2 text-[10px] text-slate-400">
                            <span>SKU: {{ prod.sku }}</span>
                            <span v-if="prod.selling_price">• {{ formatCurrency(prod.selling_price) }}</span>
                          </div>
                        </div>

                        <!-- Copies Input per product -->
                        <div class="flex items-center gap-1.5 shrink-0">
                          <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('কপি:', 'Copies:') }}</span>
                          <input
                            type="number"
                            v-model.number="prod.copies"
                            min="1"
                            max="100"
                            class="w-16 rounded-lg border-slate-200 bg-white py-1 px-2 text-center text-xs font-bold text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Modal Footer -->
                <div class="mt-6 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                  <button
                    type="button"
                    @click="closeModal"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-all cursor-pointer"
                  >
                    {{ t('বাতিল', 'Cancel') }}
                  </button>
                  <button
                    type="button"
                    @click="confirmPrint"
                    :disabled="isSubmitting || productCopiesList.length === 0"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 transition-all cursor-pointer"
                  >
                    <PrinterIcon class="h-4 w-4" />
                    <span>{{ isSubmitting ? t('লোড হচ্ছে...', 'Loading...') : t('প্রিন্ট প্রিভিউ দেখুন', 'Generate & Print') }}</span>
                  </button>
                </div>

              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue'
import { QrCodeIcon, PrinterIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useLanguage } from '@/composables/useLanguage'

const { t, formatCurrency, formatNumber } = useLanguage()

const props = defineProps({
  products: {
    type: Array,
    default: () => []
  },
  showButton: {
    type: Boolean,
    default: true
  },
  buttonClass: {
    type: String,
    default: ''
  },
  buttonLabel: {
    type: String,
    default: ''
  }
})

const isOpen = ref(false)
const isSubmitting = ref(false)
const bulkCopies = ref(1)
const paperSize = ref('A4')
const localProducts = ref([])

const effectiveProducts = computed(() => {
  return localProducts.value.length > 0 ? localProducts.value : props.products
})

const productCopiesList = ref([])

const syncCopiesList = () => {
  productCopiesList.value = effectiveProducts.value.map(p => ({
    id: p.id,
    name: p.name,
    sku: p.sku || 'N/A',
    selling_price: p.selling_price,
    copies: bulkCopies.value || 1
  }))
}

watch(effectiveProducts, () => {
  syncCopiesList()
}, { immediate: true })

const totalLabels = computed(() => {
  return productCopiesList.value.reduce((acc, p) => acc + (Number(p.copies) || 1), 0)
})

const applyBulkCopies = () => {
  const c = Math.max(1, Math.min(100, Number(bulkCopies.value) || 1))
  bulkCopies.value = c
  productCopiesList.value.forEach(p => {
    p.copies = c
  })
}

const openModal = (customProducts = null) => {
  if (customProducts && Array.isArray(customProducts)) {
    localProducts.value = customProducts
  } else {
    localProducts.value = []
  }
  syncCopiesList()
  isOpen.value = true
}

const closeModal = () => {
  isOpen.value = false
  isSubmitting.value = false
}

const confirmPrint = () => {
  if (productCopiesList.value.length === 0) return
  isSubmitting.value = true

  router.post('/admin/products/barcode/print', {
    products: productCopiesList.value.map(p => ({
      id: p.id,
      copies: Math.max(1, Math.min(100, Number(p.copies) || 1))
    })),
    paperSize: paperSize.value
  }, {
    onFinish: () => {
      isSubmitting.value = false
      closeModal()
    }
  })
}

defineExpose({
  openModal,
  closeModal
})
</script>
