import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { I18nManager } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import { ar, Translations } from '../i18n/ar';
import { en } from '../i18n/en';

export type Language = 'ar' | 'en';

interface LanguageContextType {
  language: Language;
  isRTL: boolean;
  t: Translations;
  setLanguage: (lang: Language) => Promise<void>;
  toggleLanguage: () => Promise<void>;
}

const LANGUAGE_KEY = 'sporteya_language';

const LanguageContext = createContext<LanguageContextType | undefined>(undefined);

export const LanguageProvider = ({ children }: { children: ReactNode }) => {
  const [language, setLanguageState] = useState<Language>('ar');

  useEffect(() => {
    // Load persisted language
    const loadSavedLanguage = async () => {
      try {
        const saved = await SecureStore.getItemAsync(LANGUAGE_KEY);
        if (saved === 'en' || saved === 'ar') {
          setLanguageState(saved);
          applyRTL(saved);
        } else {
          // Default to Arabic
          applyRTL('ar');
        }
      } catch (err) {
        console.warn('Error loading language preference:', err);
      }
    };
    loadSavedLanguage();
  }, []);

  const applyRTL = (lang: Language) => {
    try {
      const isArabic = lang === 'ar';
      if (I18nManager.isRTL !== isArabic) {
        I18nManager.allowRTL(isArabic);
        I18nManager.forceRTL(isArabic);
      }
    } catch (e) {
      console.warn('I18nManager update error:', e);
    }
  };

  const setLanguage = async (newLang: Language) => {
    try {
      setLanguageState(newLang);
      await SecureStore.setItemAsync(LANGUAGE_KEY, newLang);
      applyRTL(newLang);
    } catch (err) {
      console.warn('Error saving language preference:', err);
    }
  };

  const toggleLanguage = async () => {
    const next = language === 'ar' ? 'en' : 'ar';
    await setLanguage(next);
  };

  const t = language === 'ar' ? ar : en;
  const isRTL = language === 'ar';

  return (
    <LanguageContext.Provider
      value={{
        language,
        isRTL,
        t,
        setLanguage,
        toggleLanguage,
      }}
    >
      {children}
    </LanguageContext.Provider>
  );
};

export const useLanguage = (): LanguageContextType => {
  const context = useContext(LanguageContext);
  if (!context) {
    throw new Error('useLanguage must be used within a LanguageProvider');
  }
  return context;
};
