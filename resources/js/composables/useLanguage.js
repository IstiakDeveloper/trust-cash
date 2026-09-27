import { ref } from 'vue'

const currentLang = ref(localStorage.getItem('app_locale') || 'bn')

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
        toggleLanguage,
        setLanguage,
        t
    }
}
