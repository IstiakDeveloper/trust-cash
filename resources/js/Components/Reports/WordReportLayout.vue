<template>
    <div class="word-report-root">
        <!-- Action Toolbar (Hidden during print) -->
        <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 uppercase tracking-wide">
                    {{ orientation === 'landscape' ? t('A4 ল্যান্ডস্কেপ', 'A4 Landscape') : t('A4 পোর্ট্রেট', 'A4 Portrait') }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ t('ব্ল্যাক অ্যান্ড হোয়াইট অফিসিয়াল ফরম্যাট', 'Official B&W Word Line Format') }}
                </span>
            </div>

            <div class="flex items-center space-x-2">
                <button
                    type="button"
                    @click="downloadPdf"
                    :disabled="isGeneratingPdf"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-800 dark:text-gray-200 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors disabled:opacity-50"
                >
                    <svg class="w-4 h-4 mr-1.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ isGeneratingPdf ? t('পিডিএফ তৈরি হচ্ছে...', 'Generating PDF...') : t('পিডিএফ ডাউনলোড', 'Download PDF') }}</span>
                </button>

                <button
                    type="button"
                    @click="printReport"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white transition-colors"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>{{ t('প্রিন্ট করুন', 'Print Report') }}</span>
                </button>
            </div>
        </div>

        <!-- Printable Document Canvas -->
        <div class="word-report-canvas-wrapper flex justify-center">
            <div
                ref="reportCanvasRef"
                class="word-report-sheet bg-white text-black"
                :class="[orientation === 'landscape' ? 'sheet-landscape' : 'sheet-portrait']"
            >
                <!-- 1. Header (Dynamic Tenant Info - Compact Word Layout) -->
                <div class="report-header text-center avoid-break">
                    <h1 class="company-name font-bold tracking-tight">
                        {{ companyName }}
                    </h1>
                    <div v-if="companyDetails" class="company-details">
                        {{ companyDetails }}
                    </div>
                    <div class="header-divider"></div>
                </div>

                <!-- 2. Report Title & Meta Box (Compact) -->
                <div class="report-meta text-center avoid-break">
                    <div class="report-title uppercase font-bold tracking-wider inline-block border border-black">
                        {{ title }}
                    </div>
                    <div v-if="subtitle" class="report-subtitle font-medium">
                        {{ subtitle }}
                    </div>
                    <div v-if="dateRange" class="report-period">
                        <span class="font-semibold">{{ t('সময়কাল:', 'Period:') }}</span> {{ dateRange }}
                    </div>
                    <div v-if="accountInfo" class="report-account font-medium">
                        {{ accountInfo }}
                    </div>
                </div>

                <!-- 3. Report Main Body (Slot) -->
                <div class="report-body">
                    <slot />
                </div>

                <!-- 4. Signatures (Optional) -->
                <div v-if="showSignatures" class="report-signatures avoid-break grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="signature-line border-t border-black w-3/4 mx-auto mb-1"></div>
                        <span>{{ t('প্রস্তুতকারী', 'Prepared By') }}</span>
                    </div>
                    <div>
                        <div class="signature-line border-t border-black w-3/4 mx-auto mb-1"></div>
                        <span>{{ t('নিরীক্ষক', 'Checked By') }}</span>
                    </div>
                    <div>
                        <div class="signature-line border-t border-black w-3/4 mx-auto mb-1"></div>
                        <span>{{ t('অনুমোদনকারী', 'Authorized Signature') }}</span>
                    </div>
                </div>

                <!-- 5. Footer (TrustCash Branding + Meta) -->
                <div class="report-footer avoid-break border-t border-black flex justify-between items-center text-gray-700">
                    <div class="footer-left">
                        {{ t('প্রস্তুতকরণ:', 'Generated:') }} {{ currentDateTime }}
                    </div>
                    <div class="footer-center font-bold tracking-wide text-black">
                        TrustCash
                    </div>
                    <div class="footer-right">
                        {{ t('পৃষ্ঠা:', 'Page:') }} <span class="page-num">1</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useLanguage } from '@/composables/useLanguage'
import html2pdf from 'html2pdf.js'

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    subtitle: {
        type: String,
        default: ''
    },
    dateRange: {
        type: String,
        default: ''
    },
    accountInfo: {
        type: String,
        default: ''
    },
    orientation: {
        type: String,
        default: 'portrait', // 'portrait' | 'landscape'
        validator: (v) => ['portrait', 'landscape'].includes(v)
    },
    showSignatures: {
        type: Boolean,
        default: true
    },
    fileName: {
        type: String,
        default: ''
    }
})

const { t } = useLanguage()
const page = usePage()
const reportCanvasRef = ref(null)
const isGeneratingPdf = ref(false)

// Dynamic Tenant Settings from Inertia Props (Zero Hardcoding)
const companyName = computed(() => {
    return page.props.business_name || page.props.app_name || 'TrustCash'
})

const companyDetails = computed(() => {
    if (page.props.business_details) {
        return page.props.business_details
    }
    const parts = []
    if (page.props.business_address) {
        parts.push(page.props.business_address)
    }
    if (page.props.business_phone) {
        parts.push(`Phone: ${page.props.business_phone}`)
    }
    if (page.props.business_email) {
        parts.push(`Email: ${page.props.business_email}`)
    }
    return parts.join(' | ')
})

const currentDateTime = ref('')

const updateCurrentTime = () => {
    const now = new Date()
    currentDateTime.value = now.toLocaleDateString('bn-BD', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

onMounted(() => {
    updateCurrentTime()
})

const printReport = () => {
    updateCurrentTime()
    window.print()
}

const downloadPdf = async () => {
    if (!reportCanvasRef.value) return
    isGeneratingPdf.value = true
    updateCurrentTime()

    // Critical fix for html2canvas font metrics baseline calculation:
    // Tailwind's preflight rule `img { display: block }` breaks html2canvas's FontMetrics probe,
    // which caused all text in canvas to be shifted down to touch the bottom border.
    const styleFix = document.createElement('style')
    styleFix.id = 'html2canvas-fontmetrics-fix'
    styleFix.innerHTML = 'img { display: inline-block !important; }'
    document.head.appendChild(styleFix)

    const sheetEl = reportCanvasRef.value
    sheetEl.classList.add('pdf-export-active')

    // Wait for document fonts and DOM reflow so html2canvas captures exact printable dimensions
    if (document.fonts && document.fonts.ready) {
        await document.fonts.ready
    }
    await new Promise((resolve) => setTimeout(resolve, 80))

    try {
        const cleanTitle = (props.title || 'report')
            .toLowerCase()
            .replace(/[^a-z0-9]/gi, '-')
            .replace(/-+/g, '-')

        const outputFileName = props.fileName || `${cleanTitle}-${new Date().toISOString().split('T')[0]}.pdf`

        const opt = {
            margin: props.orientation === 'landscape' ? [5, 6, 5, 6] : [6, 8, 8, 8],
            filename: outputFileName,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                logging: false,
                scrollY: 0,
                scrollX: 0,
                onclone: (clonedDoc) => {
                    const clonedStyle = clonedDoc.createElement('style')
                    clonedStyle.innerHTML = `
                        img { display: inline-block !important; }
                    `
                    clonedDoc.head.appendChild(clonedStyle)
                }
            },
            jsPDF: {
                unit: 'mm',
                format: 'a4',
                orientation: props.orientation
            },
            pagebreak: {
                mode: ['css', 'legacy'],
                before: ['.page-break', '.page-break-before'],
                after: ['.page-break-after'],
                avoid: ['tr', '.report-header', '.report-meta', '.report-signatures', '.report-footer', '.avoid-break']
            }
        }

        await html2pdf().set(opt).from(sheetEl).save()
    } catch (err) {
        console.error('PDF generation error:', err)
        window.print()
    } finally {
        document.getElementById('html2canvas-fontmetrics-fix')?.remove()
        sheetEl.classList.remove('pdf-export-active')
        isGeneratingPdf.value = false
    }
}

defineExpose({
    printReport,
    downloadPdf
})
</script>

<style>
/* ============================================================
   SCREEN STYLES (Word Document Sheet Representation)
   ============================================================ */
.word-report-sheet {
    font-family: 'Inter', 'Noto Sans Bengali', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    color: #000000;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #d1d5db;
    padding: 16px 22px;
    width: 100%;
    box-sizing: border-box;
}

.sheet-portrait {
    max-width: 210mm;
    min-height: 297mm;
}

.sheet-landscape {
    max-width: 297mm;
    min-height: 210mm;
}

/* Compact Word Document Header */
.report-header {
    margin-bottom: 4px;
}

.report-header .company-name {
    font-size: 15pt;
    line-height: 1.15;
    font-weight: 700;
    color: #000000;
    margin: 0 0 2px 0;
}

.report-header .company-details {
    font-size: 8pt;
    line-height: 1.25;
    color: #333333;
}

.header-divider {
    border-bottom: 1px solid #000000;
    width: 100%;
    margin: 4px 0 6px 0;
}

/* Compact Report Meta */
.report-meta {
    margin-bottom: 8px;
}

.report-meta .report-title {
    font-size: 8.5pt;
    line-height: 1.2;
    padding: 2px 10px;
    background: #ffffff;
}

.report-meta .report-subtitle {
    font-size: 8pt;
    line-height: 1.2;
    margin-top: 2px;
    color: #222222;
}

.report-meta .report-period,
.report-meta .report-account {
    font-size: 7.5pt;
    line-height: 1.2;
    margin-top: 2px;
    color: #333333;
}

/* Compact Signatures & Footer */
.report-signatures {
    margin-top: 24px;
    padding-top: 10px;
    font-size: 8pt;
    line-height: 1.2;
}

.report-footer {
    margin-top: 12px;
    padding-top: 4px;
    font-size: 7.5pt;
    line-height: 1.2;
}

/* ============================================================
   WORD FILE TABLE STYLING (Solid Line Art Accounting Standard)
   ============================================================ */
.word-table {
    width: 100%;
    border-collapse: collapse !important;
    margin-top: 4px;
    margin-bottom: 8px;
    font-size: 8pt;
    color: #000000;
    background: #ffffff;
    page-break-inside: auto !important;
    break-inside: auto !important;
}

.word-table thead {
    display: table-header-group !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
}

.word-table tfoot {
    display: table-footer-group !important;
}

.word-table tr {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
}

.word-table th,
.word-table td {
    border: 1px solid #000000 !important;
    padding: 4.5px 6px !important;
    font-size: 8pt !important;
    line-height: 1.35 !important;
    vertical-align: middle !important;
    box-sizing: border-box;
}

.word-table th {
    background-color: #f2f2f2 !important;
    font-weight: 700 !important;
    text-align: left;
    color: #000000;
    padding: 5px 6px !important;
}

.word-table .section-header-row td {
    background-color: #e5e5e5 !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    border: 1px solid #000000 !important;
    padding: 3.5px 5px !important;
}

.word-table .subtotal-row td {
    font-weight: 700 !important;
    background-color: #fafafa !important;
    border-top: 1px solid #000000 !important;
    border-bottom: 1px solid #000000 !important;
}

.word-table .total-row td,
.word-table .grand-total td {
    font-weight: 700 !important;
    background-color: #f0f0f0 !important;
    border-top: 1.5px solid #000000 !important;
    border-bottom: 3px double #000000 !important; /* Traditional Accounting Double Line */
}

/* Alignment utilities */
.word-table .text-right { text-align: right !important; }
.word-table .text-center { text-align: center !important; }
.word-table .text-left { text-align: left !important; }

/* Page break markers */
.page-break,
.page-break-before {
    page-break-before: always !important;
    break-before: page !important;
    display: block !important;
    height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
}

.avoid-break {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
}

/* ============================================================
   ACTIVE PDF EXPORT MODE (During html2pdf Generation)
   Eliminates screen shadows, outer paddings, and sets 1:1 print widths
   ============================================================ */
.word-report-sheet.pdf-export-active {
    box-shadow: none !important;
    border: none !important;
    padding: 0 !important;
    margin: 0 !important;
    min-height: auto !important;
    background: #ffffff !important;
}

.sheet-portrait.pdf-export-active {
    width: 194mm !important;
    max-width: 194mm !important;
}

.sheet-landscape.pdf-export-active {
    width: 285mm !important;
    max-width: 285mm !important;
}

/* ============================================================
   PRINT MEDIA RULES (Window.print() & Save as PDF)
   ============================================================ */
@media print {
    .word-report-root .no-print {
        display: none !important;
    }

    .word-report-canvas-wrapper {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .word-report-sheet {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
        min-height: auto !important;
    }

    .word-table {
        width: 100% !important;
        page-break-inside: auto !important;
    }

    .word-table tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .word-table thead {
        display: table-header-group !important;
    }

    .avoid-break,
    .report-header,
    .report-meta,
    .report-signatures,
    .report-footer {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>

