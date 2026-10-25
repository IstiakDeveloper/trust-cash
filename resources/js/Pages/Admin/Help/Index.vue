<template>
    <Head :title="t('সাহায্য', 'Help')" />
    <AdminLayout :title="t('সাহায্য', 'Help')">
        <div class="mx-auto max-w-6xl">
            <div class="mb-4 sm:mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">
                        {{ t('দোকান চালানোর সাহায্য', 'Shop help') }}
                    </h1>
                    <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-2xl">
                        {{ t('উপর থেকে নিচ পর্যন্ত করুন। ভিডিও এলে প্রতিটি অংশের উপরে চলবে।', 'Follow from top to bottom. The video will play at the top of each part once it is added.') }}
                    </p>
                </div>
                <div class="relative w-full sm:w-72">
                    <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                    <input
                        v-model="query"
                        type="search"
                        :placeholder="t('যেমন: বাকি, স্টক...', 'Try: due, stock...')"
                        class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-800 shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    />
                </div>
            </div>

            <div class="lg:grid lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-6">
                <aside class="mb-4 lg:mb-0">
                    <div class="lg:sticky lg:top-20 rounded-2xl border border-slate-200 bg-white p-2 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <p class="px-2 py-1.5 text-[11px] font-bold text-slate-400">
                            {{ t('অংশ', 'Parts') }} · {{ formatNumber(filtered.length) }}
                        </p>
                        <nav class="max-h-[46vh] space-y-0.5 overflow-y-auto lg:max-h-[calc(100vh-8rem)]">
                            <button
                                v-for="part in filtered"
                                :key="part.id"
                                type="button"
                                class="flex w-full items-center gap-2 rounded-xl px-2.5 py-2 text-left text-xs font-semibold transition"
                                :class="activeId === part.id
                                    ? 'bg-indigo-600 text-white'
                                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                                @click="goTo(part.id)"
                            >
                                <span
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md text-[10px] font-bold"
                                    :class="activeId === part.id ? 'bg-white/20' : 'bg-slate-100 text-slate-500 dark:bg-slate-800'"
                                >
                                    {{ partNumber(part) }}
                                </span>
                                <span class="truncate">{{ t(part.titleBn, part.titleEn) }}</span>
                            </button>
                            <p v-if="filtered.length === 0" class="px-2 py-4 text-xs text-slate-400">
                                {{ t('এই খোঁজে কিছু নেই।', 'Nothing matches that search.') }}
                            </p>
                        </nav>
                    </div>
                </aside>

                <div class="space-y-4">
                    <div
                        v-if="filtered.length === 0"
                        class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900"
                    >
                        {{ t('অন্য শব্দ দিয়ে খুঁজুন।', 'Try another word.') }}
                    </div>
                    <article
                        v-for="part in filtered"
                        :id="part.id"
                        :key="part.id"
                        class="scroll-mt-20 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="border-b border-slate-100 px-4 py-4 sm:px-5 dark:border-slate-800">
                            <p class="text-[11px] font-bold text-indigo-600 dark:text-indigo-300">
                                {{ t('অংশ', 'Part') }} {{ partNumber(part) }}
                            </p>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">
                                {{ t(part.titleBn, part.titleEn) }}
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ t(part.whereBn, part.whereEn) }}
                            </p>
                        </div>

                        <div class="space-y-4 px-4 py-4 sm:px-5">
                            <div class="aspect-video overflow-hidden rounded-2xl bg-slate-900">
                                <iframe
                                    v-if="part.youtubeId"
                                    class="h-full w-full"
                                    :src="`https://www.youtube-nocookie.com/embed/${part.youtubeId}`"
                                    :title="t(part.titleBn, part.titleEn)"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen
                                ></iframe>
                                <div v-else class="flex h-full flex-col items-center justify-center px-6 text-center">
                                    <i class="fas fa-play mb-2 text-lg text-white"></i>
                                    <p class="text-sm font-bold text-white">{{ t('ভিডিও শীঘ্রই আসবে', 'Video coming soon') }}</p>
                                </div>
                            </div>

                            <ol class="space-y-2">
                                <li
                                    v-for="(step, stepIndex) in part.steps"
                                    :key="step.bn"
                                    class="flex gap-3 text-sm leading-relaxed text-slate-700 dark:text-slate-200"
                                >
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-[11px] font-bold text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300">
                                        {{ formatNumber(stepIndex + 1) }}
                                    </span>
                                    <span>{{ t(step.bn, step.en) }}</span>
                                </li>
                            </ol>

                            <div class="flex flex-wrap gap-2">
                                <Link
                                    v-for="link in part.links"
                                    :key="link.href"
                                    :href="link.href"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-bold text-white hover:bg-indigo-500"
                                >
                                    {{ t(link.labelBn, link.labelEn) }}
                                    <i class="fas fa-arrow-right text-[10px]"></i>
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLanguage } from '@/composables/useLanguage'
import { helpParts } from '@/data/tenantHelp'

const { t, formatNumber } = useLanguage()
const query = ref('')
const activeId = ref(helpParts[0]?.id || '')

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase()
    if (!q) return helpParts
    return helpParts.filter((part) => {
        const blob = [
            part.titleBn,
            part.titleEn,
            part.whereBn,
            part.whereEn,
            ...part.steps.flatMap((step) => [step.bn, step.en]),
            ...part.links.flatMap((link) => [link.labelBn, link.labelEn]),
        ].join(' ').toLowerCase()
        return blob.includes(q)
    })
})

const partNumber = (part) => formatNumber(helpParts.findIndex((item) => item.id === part.id) + 1)

const goTo = async (id) => {
    activeId.value = id
    await nextTick()
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

onMounted(() => {
    const hash = window.location.hash.replace('#', '')
    if (hash && helpParts.some((part) => part.id === hash)) {
        goTo(hash)
    }
})
</script>
