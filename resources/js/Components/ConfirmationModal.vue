<template>
    <TransitionRoot appear :show="show" as="template">
        <Dialog as="div" @close="$emit('close')" class="relative z-50">
            <TransitionChild as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0">
                <div class="fixed inset-0 bg-black/25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4 text-center">
                    <TransitionChild as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-t-3xl sm:rounded-2xl bg-white dark:bg-slate-900 p-5 sm:p-6 text-left align-middle shadow-2xl transition-all border border-slate-200 dark:border-slate-800 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
                            <div class="sm:hidden flex justify-center pb-3">
                                <span class="w-10 h-1.5 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                            </div>

                            <DialogTitle as="h3" class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                                {{ title }}
                            </DialogTitle>

                            <div class="mt-2">
                                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                                    {{ message }}
                                </p>
                            </div>

                            <div class="mt-5 flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3">
                                <button type="button"
                                    class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                                    @click="$emit('close')">
                                    Cancel
                                </button>
                                <button type="button"
                                    class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-rose-500 shadow-xs transition"
                                    @click="$emit('confirm')">
                                    {{ confirmText }}
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

defineProps({
    show: {
        type: Boolean,
        required: true
    },
    title: {
        type: String,
        default: 'Confirm Action'
    },
    message: {
        type: String,
        default: 'Are you sure you want to proceed with this action?'
    },
    confirmText: {
        type: String,
        default: 'Confirm'
    }
})

defineEmits(['close', 'confirm'])
</script>
