import { ref, computed } from 'vue'

const currentLang = ref(typeof localStorage !== 'undefined' ? (localStorage.getItem('app_locale') || 'bn') : 'bn')
const isBangla = computed(() => currentLang.value === 'bn')

const banglaDigits = {
    '0': '০',
    '1': '১',
    '2': '২',
    '3': '৩',
    '4': '৪',
    '5': '৫',
    '6': '৬',
    '7': '৭',
    '8': '৮',
    '9': '৯'
}

export const toBanglaNumber = (val) => {
    if (val === null || val === undefined || val === '') return ''
    return String(val).replace(/[0-9]/g, digit => banglaDigits[digit] || digit)
}

export const formatCurrency = (val, withSymbol = true) => {
    const isBn = currentLang.value === 'bn'
    const num = Number(val || 0)
    const hasDecimal = Math.abs(num - Math.round(num)) >= 0.0001
    let formatted = num.toLocaleString('en-US', {
        minimumFractionDigits: hasDecimal ? 2 : 0,
        maximumFractionDigits: 2
    })
    if (isBn) {
        formatted = toBanglaNumber(formatted)
    }
    return withSymbol ? `৳ ${formatted}` : formatted
}

export const formatNumber = (val) => {
    if (val === null || val === undefined || val === '') return ''
    const isBn = currentLang.value === 'bn'
    const num = Number(val)
    if (isNaN(num)) {
        return isBn ? toBanglaNumber(val) : String(val)
    }
    const hasDecimal = Math.abs(num - Math.round(num)) >= 0.0001
    const formatted = hasDecimal ? num.toFixed(2) : String(Math.round(num))
    return isBn ? toBanglaNumber(formatted) : formatted
}

export function useLanguage() {
    const toggleLanguage = () => {
        currentLang.value = currentLang.value === 'bn' ? 'en' : 'bn'
        localStorage.setItem('app_locale', currentLang.value)
    }

    const setLanguage = (lang) => {
        currentLang.value = lang
        localStorage.setItem('app_locale', lang)
    }

    const t = (bnText, enText) => {
        return currentLang.value === 'bn' ? bnText : enText
    }

    return {
        currentLang,
        isBangla,
        toggleLanguage,
        setLanguage,
        t,
        toBanglaNumber,
        formatCurrency,
        formatNumber
    }
}

