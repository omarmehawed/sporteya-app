import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Image, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as ImagePicker from 'expo-image-picker';
import { apiClient } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { colors } from '../../theme/colors';
import { ar } from '../../i18n/ar';

export const UploadAvatarScreen = () => {
  const { refreshUser, setSetupComplete } = useAuth();
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
      Alert.alert('خطأ', err.response?.data?.message || 'تعذر إكمال عملية التهيئة');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>{ar.uploadAvatar}</Text>
        <Text style={styles.subtitle}>ضع صورتك الشخصية لتظهر في تشكيل المباريات وعلى بروفايلك</Text>

        <TouchableOpacity style={styles.avatarBox} onPress={pickImage} activeOpacity={0.8}>
          {avatarUri ? (
            <Image source={{ uri: avatarUri }} style={styles.avatarImg} />
          ) : (
            <View style={styles.placeholder}>
              <Text style={styles.placeholderIcon}>📷</Text>
              <Text style={styles.placeholderText}>اضغط لاختيار صورة</Text>
            </View>
          )}
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.btn}
          onPress={handleFinish}
          disabled={loading}
          activeOpacity={0.8}
        >
          {loading ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.btnText}>{ar.finishOnboarding}</Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity style={styles.skipBtn} onPress={handleFinish} disabled={loading}>
          <Text style={styles.skipBtnText}>تخطي هذه الخطوة والبدء مباشرة</Text>
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
    alignItems: 'center',
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
    textAlign: 'center',
    marginBottom: 36,
  },
  avatarBox: {
    width: 140,
    height: 140,
    borderRadius: 70,
    borderWidth: 2,
    borderColor: colors.primary,
    borderStyle: 'dashed',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 40,
    overflow: 'hidden',
    backgroundColor: colors.primaryMuted,
  },
  avatarImg: {
    width: '100%',
    height: '100%',
  },
  placeholder: {
    alignItems: 'center',
    gap: 4,
  },
  placeholderIcon: {
    fontSize: 32,
  },
  placeholderText: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.primaryDark,
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    width: '100%',
    alignItems: 'center',
  },
  btnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
  skipBtn: {
    marginTop: 16,
  },
  skipBtnText: {
    color: colors.textMuted,
    fontSize: 14,
    fontWeight: '600',
  },
});
