import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Image, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as ImagePicker from 'expo-image-picker';
import { apiClient } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useLanguage } from '../../context/LanguageContext';
import { colors } from '../../theme/colors';

export const UploadAvatarScreen = () => {
  const { refreshUser, setSetupComplete } = useAuth();
  const { t, isRTL } = useLanguage();
  const [avatarUri, setAvatarUri] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const pickImage = async () => {
    try {
      const res = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        allowsEditing: true,
        aspect: [1, 1],
        quality: 0.8,
      });

      if (!res.canceled && res.assets[0]) {
        setAvatarUri(res.assets[0].uri);
      }
    } catch (err) {
      console.warn('Image picker error:', err);
    }
  };

  const handleFinish = async () => {
    try {
      setLoading(true);

      if (avatarUri) {
        const formData = new FormData();
        const filename = avatarUri.split('/').pop() || 'avatar.jpg';
        const match = /\.(\w+)$/.exec(filename);
        const type = match ? `image/${match[1]}` : `image/jpeg`;

        formData.append('avatar', {
          uri: avatarUri,
          name: filename,
          type,
        } as any);

        await apiClient.post('/onboarding/avatar', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }

      // Complete onboarding atomically (Correction B)
      await apiClient.put('/onboarding/complete');
      setSetupComplete(true);
      await refreshUser();
    } catch (err: any) {
      Alert.alert(t.error, err.response?.data?.message || t.onboardingError);
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <Text style={[styles.title, { textAlign: isRTL ? 'right' : 'left' }]}>{t.uploadAvatar}</Text>
        <Text style={[styles.subtitle, { textAlign: isRTL ? 'right' : 'left' }]}>
          {isRTL ? 'اختر صورة واضحة لملفك الشخصي حتى يتعرف عليك زملاؤك في الماتش' : 'Choose a clear profile picture so your teammates recognize you on the pitch'}
        </Text>

        <TouchableOpacity style={styles.avatarBox} onPress={pickImage} activeOpacity={0.8}>
          {avatarUri ? (
            <Image source={{ uri: avatarUri }} style={styles.avatarImg} />
          ) : (
            <View style={styles.placeholder}>
              <Text style={styles.cameraIcon}>📸</Text>
              <Text style={styles.uploadText}>{isRTL ? 'رفع صورة' : 'Upload photo'}</Text>
            </View>
          )}
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.continueBtn}
          onPress={handleFinish}
          disabled={loading}
          activeOpacity={0.8}
        >
          {loading ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.continueBtnText}>{t.finishOnboarding}</Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity style={styles.skipBtn} onPress={handleFinish} disabled={loading}>
          <Text style={styles.skipBtnText}>{t.skipBtn}</Text>
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
    alignItems: 'center',
    flex: 1,
  },
  title: {
    fontSize: 24,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 8,
    width: '100%',
  },
  subtitle: {
    fontSize: 14,
    color: colors.textMuted,
    marginBottom: 32,
    lineHeight: 20,
    width: '100%',
  },
  avatarBox: {
    width: 140,
    height: 140,
    borderRadius: 70,
    backgroundColor: colors.surfaceSubtle,
    borderWidth: 2,
    borderColor: colors.border,
    borderStyle: 'dashed',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 36,
    overflow: 'hidden',
  },
  avatarImg: {
    width: '100%',
    height: '100%',
  },
  placeholder: {
    alignItems: 'center',
  },
  cameraIcon: {
    fontSize: 32,
    marginBottom: 6,
  },
  uploadText: {
    fontSize: 13,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  continueBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    width: '100%',
  },
  continueBtnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
  skipBtn: {
    marginTop: 16,
    padding: 10,
  },
  skipBtnText: {
    color: colors.textMuted,
    fontSize: 14,
    fontWeight: '600',
  },
});
