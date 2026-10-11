import React from 'react';
import { TouchableOpacity, Text, StyleSheet, View } from 'react-native';
import { useLanguage } from '../context/LanguageContext';
import { colors } from '../theme/colors';

interface LanguageToggleProps {
  style?: object;
  compact?: boolean;
}

export const LanguageToggle = ({ style, compact = false }: LanguageToggleProps) => {
  const { language, toggleLanguage } = useLanguage();

  const isAr = language === 'ar';
  const label = isAr ? 'English 🇬🇧' : 'العربية 🇪🇬';

  return (
    <TouchableOpacity
      style={[styles.btn, compact && styles.compactBtn, style]}
      onPress={toggleLanguage}
      activeOpacity={0.7}
      accessibilityLabel="Switch Language"
    >
      <Text style={styles.icon}>🌐</Text>
      <Text style={[styles.text, compact && styles.compactText]}>{label}</Text>
    </TouchableOpacity>
  );
};

const styles = StyleSheet.create({
  btn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.15)',
    paddingVertical: 8,
    paddingHorizontal: 14,
    borderRadius: 20,
    alignSelf: 'center',
  },
  compactBtn: {
    paddingVertical: 5,
    paddingHorizontal: 10,
    borderRadius: 14,
  },
  icon: {
    fontSize: 14,
    marginRight: 6,
  },
  text: {
    color: colors.textPrimary,
    fontSize: 13,
    fontWeight: '700',
  },
  compactText: {
    fontSize: 11,
  },
});
