<template>
    <TransitionRoot appear :show="true" as="template">
        <Dialog as="div" class="relative z-10" @close="$emit('close')">
            <TransitionChild as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0">
                <div class="fixed inset-0 bg-black bg-opacity-25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <TransitionChild as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95">
                        <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 text-left align-middle shadow-2xl transition-all">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <DialogTitle as="h3" class="text-base font-bold text-slate-900 dark:text-white">
                                        {{ t('বিক্রি সফলভাবে সম্পন্ন হয়েছে!', 'Sale Completed Successfully!') }}
                                    </DialogTitle>
                                    <p class="text-xs text-slate-400">{{ t('মেমো তৈরি ও সেভ হয়েছে', 'Invoice has been recorded') }}</p>
                                </div>
                            </div>

                            <div class="mt-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 space-y-2 text-xs">
                                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                    <span>{{ t('মেমো নং', 'Invoice No') }}:</span>
                                    <span class="font-bold text-slate-900 dark:text-white font-mono">{{ sale.invoice_no }}</span>
                                </div>
                                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                    <span>{{ t('মোট টাকা', 'Total Amount') }}:</span>
                                    <span class="font-bold text-slate-900 dark:text-white">৳{{ formatNumber(sale.total) }}</span>
                                </div>
                                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                    <span>{{ t('পরিশোধিত', 'Paid Amount') }}:</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">৳{{ formatNumber(sale.paid) }}</span>
                                </div>
                                <div v-if="sale.due > 0" class="flex justify-between pt-1 border-t border-slate-200 dark:border-slate-700 text-rose-600 dark:text-rose-400 font-bold">
                                    <span>{{ t('বাকি টাকা', 'Due Amount') }}:</span>
                                    <span>৳{{ formatNumber(sale.due) }}</span>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-2.5">
                                <button @click="$emit('print', sale.id)"
                                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950 dark:hover:bg-indigo-900 px-4 py-2.5 text-xs font-bold text-indigo-700 dark:text-indigo-300 transition-all">
                                    <PrinterIcon class="w-4 h-4" />
                                    <span>{{ t('রসিদ প্রিন্ট করুন', 'Print Receipt') }}</span>
                                </button>
                                <button @click="$emit('close')"
                                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white transition-all shadow-sm">
                                    <PlusCircleIcon class="w-4 h-4" />
                                    <span>{{ t('পরবর্তী বিক্রি', 'Next Sale') }}</span>
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
    Printer as PrinterIcon,
    PlusCircle as PlusCircleIcon
} from 'lucide-vue-next'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    sale: {
        type: Object,
        required: true
    }
})

defineEmits(['close', 'print'])

const formatNumber = (value) => {
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}
</script>
