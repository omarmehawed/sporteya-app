import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, TextInput, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as Location from 'expo-location';
import { apiClient } from '../../api/client';
import { colors } from '../../theme/colors';
import { ar } from '../../i18n/ar';

export const SelectLocationScreen = ({ navigation }: any) => {
  const [city, setCity] = useState('Cairo');
  const [coords, setCoords] = useState<{ latitude: number; longitude: number } | null>(null);
  const [loadingGps, setLoadingGps] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const handleUseGps = async () => {
    try {
      setLoadingGps(true);
      const { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('تنبيه', 'تم رفض إذن الوصول للموقع. يمكنك كتابة المدينة يدوياً.');
        return;
      }

      const location = await Location.getCurrentPositionAsync({});
      setCoords({
        latitude: location.coords.latitude,
        longitude: location.coords.longitude,
      });
      Alert.alert('تم', 'تم تحديد موقعك بدقة بنجاح.');
    } catch (err) {
      console.warn('Location error:', err);
      Alert.alert('خطأ', 'تعذر جلب موقعك عبر الـ GPS.');
    } finally {
      setLoadingGps(false);
    }
  };

  const handleContinue = async () => {
    try {
      setSubmitting(true);
      const payload: any = { city: city.trim() };
      if (coords) {
        payload.latitude = coords.latitude;
        payload.longitude = coords.longitude;
      }
      await apiClient.post('/onboarding/location', payload);
      navigation.navigate('UploadAvatar');
    } catch (err: any) {
      Alert.alert('خطأ', err.response?.data?.message || 'تعذر حفظ الموقع');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>{ar.chooseLocation}</Text>
        <Text style={styles.subtitle}>يساعدنا تحديد موقعك في إظهار أقرب الملاعب والماتشات في منطقتك</Text>

        <TouchableOpacity
          style={[styles.gpsBtn, coords ? styles.gpsBtnActive : null]}
          onPress={handleUseGps}
          disabled={loadingGps}
          activeOpacity={0.8}
        >
          {loadingGps ? (
            <ActivityIndicator color={colors.primary} />
          ) : (
            <Text style={[styles.gpsText, coords ? styles.gpsTextActive : null]}>
              📍 {coords ? 'تم تحديد الموقع بالـ GPS ✓' : ar.useGps}
            </Text>
          )}
        </TouchableOpacity>

        <View style={styles.dividerRow}>
          <View style={styles.line} />
          <Text style={styles.orText}>أو اختر المدينة</Text>
          <View style={styles.line} />
        </View>

        <View style={styles.cityGrid}>
          {['القاهرة', 'الجيزة', 'الإسكندرية', 'المنصورة', 'طنطا'].map((c) => (
            <TouchableOpacity
              key={c}
              style={[styles.cityChip, city === c && styles.cityChipActive]}
              onPress={() => setCity(c)}
            >
              <Text style={[styles.cityText, city === c && styles.cityTextActive]}>{c}</Text>
            </TouchableOpacity>
          ))}
        </View>

        <TextInput
          style={styles.cityInput}
          placeholder="أو اكتب اسم مدينتك..."
          value={city}
          onChangeText={setCity}
          textAlign="right"
        />

        <TouchableOpacity
          style={styles.btn}
          onPress={handleContinue}
          disabled={submitting}
          activeOpacity={0.8}
        >
          {submitting ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.btnText}>{ar.continueBtn}</Text>
          )}
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.surface,
  },
  content: {
    flex: 1,
    padding: 24,
    justifyContent: 'center',
  },
  title: {
    fontSize: 24,
    fontWeight: '800',
    color: colors.textPrimary,
    textAlign: 'center',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 14,
    color: colors.textMuted,
    textAlign: 'center',
    marginBottom: 28,
  },
  gpsBtn: {
    paddingVertical: 14,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: colors.primary,
    backgroundColor: colors.primaryMuted,
    alignItems: 'center',
    marginBottom: 24,
  },
  gpsBtnActive: {
    backgroundColor: colors.primaryLight,
  },
  gpsText: {
    color: colors.primaryDark,
    fontSize: 15,
    fontWeight: '700',
  },
  gpsTextActive: {
    color: colors.primaryDark,
  },
  dividerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 20,
  },
  line: {
    flex: 1,
    height: 1,
    backgroundColor: colors.border,
  },
  orText: {
    paddingHorizontal: 12,
    fontSize: 13,
    color: colors.textMuted,
  },
  cityGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 16,
    justifyContent: 'center',
  },
  cityChip: {
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surfaceSubtle,
  },
  cityChipActive: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  cityText: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  cityTextActive: {
    color: colors.textWhite,
  },
  cityInput: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontSize: 15,
    marginBottom: 28,
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
  },
  btnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
});
