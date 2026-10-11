import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, TextInput, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as Location from 'expo-location';
import { apiClient } from '../../api/client';
import { useLanguage } from '../../context/LanguageContext';
import { colors } from '../../theme/colors';

export const SelectLocationScreen = ({ navigation }: any) => {
  const { t, isRTL } = useLanguage();
  const [city, setCity] = useState(isRTL ? 'القاهرة' : 'Cairo');
  const [coords, setCoords] = useState<{ latitude: number; longitude: number } | null>(null);
  const [loadingGps, setLoadingGps] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const handleUseGps = async () => {
    try {
      setLoadingGps(true);
      const { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert(t.error, isRTL ? 'تم رفض إذن الوصول للموقع. يمكنك كتابة المدينة يدوياً.' : 'Location permission denied. You can enter your city manually.');
        return;
      }

      const location = await Location.getCurrentPositionAsync({});
      setCoords({
        latitude: location.coords.latitude,
        longitude: location.coords.longitude,
      });
      Alert.alert(t.success, isRTL ? 'تم تحديد موقعك بدقة بنجاح.' : 'Your GPS location was captured successfully.');
    } catch (err) {
      console.warn('Location error:', err);
      Alert.alert(t.error, isRTL ? 'تعذر جلب موقعك عبر الـ GPS.' : 'Failed to get GPS location.');
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
      Alert.alert(t.error, err.response?.data?.message || t.onboardingError);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <Text style={[styles.title, { textAlign: isRTL ? 'right' : 'left' }]}>{t.chooseLocation}</Text>
        <Text style={[styles.subtitle, { textAlign: isRTL ? 'right' : 'left' }]}>
          {isRTL ? 'يساعدنا تحديد موقعك في إظهار أقرب الملاعب والماتشات في منطقتك' : 'Setting your location helps us show nearest venues and matches in your area'}
        </Text>

        <TouchableOpacity
          style={styles.gpsBtn}
          onPress={handleUseGps}
          disabled={loadingGps}
          activeOpacity={0.8}
        >
          {loadingGps ? (
            <ActivityIndicator color={colors.primary} />
          ) : (
            <>
              <Text style={styles.gpsIcon}>📍</Text>
              <Text style={styles.gpsText}>
                {coords ? (isRTL ? 'تم تحديد الإحداثيات ✓' : 'GPS Captured ✓') : t.useGps}
              </Text>
            </>
          )}
        </TouchableOpacity>

        <Text style={[styles.label, { textAlign: isRTL ? 'right' : 'left' }]}>{t.venueCity}</Text>
        <TextInput
          style={[styles.input, { textAlign: isRTL ? 'right' : 'left' }]}
          placeholder={isRTL ? "مثال: القاهرة، الشيخ زايد، الإسكندرية" : "e.g. Cairo, Zayed, Alexandria"}
          value={city}
          onChangeText={setCity}
        />

        <TouchableOpacity
          style={styles.continueBtn}
          onPress={handleContinue}
          disabled={submitting}
          activeOpacity={0.8}
        >
          {submitting ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.continueBtnText}>{t.continueBtn}</Text>
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
    padding: 24,
    justifyContent: 'center',
    flex: 1,
  },
  title: {
    fontSize: 24,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 14,
    color: colors.textMuted,
    marginBottom: 28,
    lineHeight: 20,
  },
  gpsBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(16, 185, 129, 0.1)',
    borderWidth: 1.5,
    borderColor: colors.primary,
    borderRadius: 12,
    paddingVertical: 16,
    marginBottom: 24,
    gap: 8,
  },
  gpsIcon: {
    fontSize: 18,
  },
  gpsText: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.primary,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textSecondary,
    marginBottom: 8,
  },
  input: {
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontSize: 16,
    color: colors.textPrimary,
    backgroundColor: colors.surface,
    marginBottom: 24,
  },
  continueBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
  },
  continueBtnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
});
