export const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

export const getNumberLocale = () => {
    return localStorage.getItem('app_locale') === 'en' ? 'en-BD' : 'bn-BD'
}

export const formatYear = (year) => {
    return Number(year).toLocaleString(getNumberLocale(), { useGrouping: false })
}

export const formatCurrency = (amount) => {
    return new Intl.NumberFormat(getNumberLocale(), {
        maximumFractionDigits: 2,
        minimumFractionDigits: 2
    }).format(amount || 0);
};
