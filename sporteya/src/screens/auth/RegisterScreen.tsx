import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ActivityIndicator, Alert, ScrollView } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../../context/AuthContext';
import { colors } from '../../theme/colors';
import { ar } from '../../i18n/ar';

export const RegisterScreen = ({ navigation }: any) => {
  const { register } = useAuth();
  const [role, setRole] = useState<'player' | 'venue_owner'>('player');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(false);

  const handleRegister = async () => {
    if (!name.trim() || !email.trim() || !password || !passwordConfirmation) {
      Alert.alert('تنبيه', 'يرجى ملء جميع الحقول المطلوبة');
      return;
    }
    if (password !== passwordConfirmation) {
      Alert.alert('خطأ', 'كلمة المرور وتأكيدها غير متطابقين');
      return;
    }

    try {
      setLoading(true);
      await register({
        name: name.trim(),
        email: email.trim().toLowerCase(),
        password,
        password_confirmation: passwordConfirmation,
        role,
      });
      // RootNavigator will reactively switch to OnboardingStack or VenueOwnerStack
    } catch (err: any) {
      const msg = err.response?.data?.message || 'حدث خطأ أثناء إنشاء الحساب';
      Alert.alert('تعذر التسجيل', msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll}>
        <Text style={styles.title}>{ar.register}</Text>

        {/* Role Selector (Correction 2A) */}
        <Text style={styles.label}>{ar.roleSelect}</Text>
        <View style={styles.roleContainer}>
          <TouchableOpacity
            style={[styles.roleBtn, role === 'player' && styles.roleBtnActive]}
            onPress={() => setRole('player')}
            activeOpacity={0.8}
          >
            <Text style={[styles.roleText, role === 'player' && styles.roleTextActive]}>
              ⚽ {ar.playerRole}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.roleBtn, role === 'venue_owner' && styles.roleBtnActive]}
            onPress={() => setRole('venue_owner')}
            activeOpacity={0.8}
          >
            <Text style={[styles.roleText, role === 'venue_owner' && styles.roleTextActive]}>
              🏟️ {ar.ownerRole}
            </Text>
          </TouchableOpacity>
        </View>

        <Text style={styles.label}>{ar.name}</Text>
        <TextInput
          style={styles.input}
          placeholder="مثال: عمر محمد"
          value={name}
          onChangeText={setName}
          textAlign="right"
        />

        <Text style={styles.label}>{ar.email}</Text>
        <TextInput
          style={styles.input}
          placeholder="user@example.com"
          keyboardType="email-address"
          autoCapitalize="none"
          value={email}
          onChangeText={setEmail}
          textAlign="right"
        />

        <Text style={styles.label}>{ar.password}</Text>
        <TextInput
          style={styles.input}
          placeholder="••••••••"
          secureTextEntry
          value={password}
          onChangeText={setPassword}
          textAlign="right"
        />

        <Text style={styles.label}>{ar.passwordConfirmation}</Text>
        <TextInput
          style={styles.input}
          placeholder="••••••••"
          secureTextEntry
          value={passwordConfirmation}
          onChangeText={setPasswordConfirmation}
          textAlign="right"
        />

        <TouchableOpacity
          style={styles.submitBtn}
          onPress={handleRegister}
          disabled={loading}
          activeOpacity={0.8}
        >
          {loading ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.submitBtnText}>{ar.register}</Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.linkRow}
          onPress={() => navigation.navigate('Login')}
        >
          <Text style={styles.linkText}>{ar.haveAccount}</Text>
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
    padding: 24,
  },
  title: {
    fontSize: 24,
    fontWeight: '800',
    color: colors.textPrimary,
    textAlign: 'center',
    marginBottom: 24,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textSecondary,
    marginBottom: 8,
    textAlign: 'right',
  },
  roleContainer: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 20,
  },
  roleBtn: {
    flex: 1,
    paddingVertical: 14,
    borderRadius: 10,
    borderWidth: 1.5,
    borderColor: colors.border,
    alignItems: 'center',
    backgroundColor: colors.surfaceSubtle,
  },
  roleBtnActive: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryMuted,
  },
  roleText: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  roleTextActive: {
    color: colors.primaryDark,
  },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontSize: 15,
    marginBottom: 16,
    backgroundColor: colors.surface,
    color: colors.textPrimary,
  },
  submitBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 8,
  },
  submitBtnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
  linkRow: {
    marginTop: 20,
    alignItems: 'center',
  },
  linkText: {
    color: colors.secondary,
    fontSize: 14,
    fontWeight: '600',
  },
});
