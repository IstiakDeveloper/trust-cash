import { toBanglaNumber } from '@/composables/useLanguage'

export const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

export const getNumberLocale = () => {
    return (typeof localStorage !== 'undefined' && localStorage.getItem('app_locale') === 'en') ? 'en-BD' : 'bn-BD'
}

export const formatYear = (year) => {
    const isBn = getNumberLocale() === 'bn-BD'
    const val = Number(year).toLocaleString('en-US', { useGrouping: false })
    return isBn ? toBanglaNumber(val) : val
}

export const formatCurrency = (amount, withSymbol = true) => {
    const isBn = getNumberLocale() === 'bn-BD'
    const num = Number(amount || 0)
    const hasDecimal = Math.abs(num - Math.round(num)) >= 0.0001
    let formatted = num.toLocaleString('en-US', {
        maximumFractionDigits: 2,
        minimumFractionDigits: hasDecimal ? 2 : 0
    })
    if (isBn) {
        formatted = toBanglaNumber(formatted)
    }
    return withSymbol ? `৳ ${formatted}` : formatted
}

