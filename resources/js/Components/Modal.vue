<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);
const dialog = ref();
const showSlot = ref(props.show);

watch(
    () => props.show,
    () => {
        if (props.show) {
            document.body.style.overflow = 'hidden';
            showSlot.value = true;

            dialog.value?.showModal();
        } else {
            setTimeout(() => {
                dialog.value?.close();
                showSlot.value = false;
                document.body.style.overflow = document.querySelector('dialog[open]') ? 'hidden' : '';
            }, 200);
        }
    },
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        const openDialogs = document.querySelectorAll('dialog[open]');
        if (openDialogs[openDialogs.length - 1] !== dialog.value) return;
        e.preventDefault();

        if (props.show) {
            close();
        }
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);

    document.body.style.overflow = [...document.querySelectorAll('dialog[open]')].some(item => item !== dialog.value) ? 'hidden' : '';
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});
</script>

<template>
    <dialog
        class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent"
        ref="dialog"
    >
        <div
            class="fixed inset-0 z-50 overflow-y-auto px-0 sm:px-4 flex items-end sm:items-center justify-center min-h-screen"
            scroll-region
        >
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="show"
                    class="fixed inset-0 transform transition-all"
                    @click="close"
                >
                    <div
                        class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                    />
                </div>
            </Transition>

            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-show="show"
                    class="transform rounded-t-3xl sm:rounded-2xl bg-white dark:bg-slate-900 shadow-2xl transition-all w-full max-h-[92dvh] sm:max-h-[90vh] flex flex-col border border-slate-200/90 dark:border-slate-800 overflow-hidden text-left"
                    :class="maxWidthClass"
                >
                    <!-- Drag handle pill for mobile app feel -->
                    <div class="sm:hidden flex justify-center pt-2.5 pb-1 shrink-0">
                        <span class="w-10 h-1.5 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                    </div>

                    <!-- Scrollable Modal Content Body -->
                    <div class="flex-1 overflow-y-auto overscroll-contain flex flex-col min-h-0">
                        <slot v-if="showSlot" />
                    </div>
                </div>
            </Transition>
        </div>
    </dialog>
</template>
