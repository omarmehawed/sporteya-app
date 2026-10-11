import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TextInput, TouchableOpacity, ScrollView, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { colors } from '../../theme/colors';

export const EditProfileScreen = ({ navigation }: any) => {
  const { user, refreshUser } = useAuth();
  const [username, setUsername] = useState(user?.player_profile?.username || '');
  const [city, setCity] = useState(user?.player_profile?.city || 'Cairo');
  const [bio, setBio] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const handleSave = async () => {
    try {
      setSubmitting(true);
      await apiClient.put('/player/me', {
        username: username.trim(),
        city: city.trim(),
        bio: bio.trim() || null,
      });

      await refreshUser();
      Alert.alert('تم', 'تم حفظ التعديلات بنجاح');
      navigation.goBack();
    } catch (err: any) {
      if (err.response?.status === 409) {
        Alert.alert('تنبيه', 'اسم المستخدم هذا مأخوذ بالفعل، يرجى اختيار اسم آخر');
      } else {
        Alert.alert('خطأ', err.response?.data?.message || 'تعذر حفظ البيانات');
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.header}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Text style={styles.backBtnText}>→</Text>
          </TouchableOpacity>
          <Text style={styles.title}>تعديل بيانات الحساب</Text>
        </View>

        <Text style={styles.label}>اسم المستخدم (@username)</Text>
        <TextInput
          style={styles.input}
          value={username}
          onChangeText={(t) => setUsername(t.toLowerCase().replace(/[^a-z0-9_]/g, ''))}
          autoCapitalize="none"
          textAlign="right"
        />

        <Text style={styles.label}>المدينة</Text>
        <TextInput
          style={styles.input}
          value={city}
          onChangeText={setCity}
          textAlign="right"
        />

        <Text style={styles.label}>نبذة عنك (Bio)</Text>
        <TextInput
          style={[styles.input, { minHeight: 80 }]}
          multiline
          numberOfLines={3}
          placeholder="مركزك المفضل، أسلوب لعبك..."
          value={bio}
          onChangeText={setBio}
          textAlign="right"
        />

        <TouchableOpacity
          style={styles.btn}
          onPress={handleSave}
          disabled={submitting}
          activeOpacity={0.8}
        >
          {submitting ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.btnText}>حفظ التغييرات</Text>
          )}
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.surface,
  },
  scroll: {
    padding: 20,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 24,
  },
  backBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: colors.surfaceSubtle,
    alignItems: 'center',
    justifyContent: 'center',
  },
  backBtnText: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  title: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  label: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.textSecondary,
    marginBottom: 6,
    textAlign: 'right',
  },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontSize: 15,
    marginBottom: 20,
    backgroundColor: colors.surfaceSubtle,
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 10,
  },
  btnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
});
