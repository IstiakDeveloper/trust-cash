import { toBanglaNumber } from '@/composables/useLanguage'

export function formatCurrency(value, withSymbol = true) {
    const isBn = typeof localStorage !== 'undefined' ? localStorage.getItem('app_locale') !== 'en' : true
    const num = Number(value || 0)
    const hasDecimal = Math.abs(num - Math.round(num)) >= 0.0001
    const amount = num.toLocaleString('en-US', {
        minimumFractionDigits: hasDecimal ? 2 : 0,
        maximumFractionDigits: 2
    })
    const formatted = isBn ? toBanglaNumber(amount) : amount
    return withSymbol ? `৳ ${formatted}` : formatted
}

