import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ActivityIndicator, Alert, ScrollView } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../../context/AuthContext';
import { useLanguage } from '../../context/LanguageContext';
import { LanguageToggle } from '../../components/LanguageToggle';
import { colors } from '../../theme/colors';

export const RegisterScreen = ({ navigation }: any) => {
  const { register } = useAuth();
  const { t, isRTL } = useLanguage();
  const [role, setRole] = useState<'player' | 'venue_owner'>('player');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(false);

  const handleRegister = async () => {
    if (!name.trim() || !email.trim() || !password || !passwordConfirmation) {
      Alert.alert(t.error, t.fillAllFields);
      return;
    }
    if (password !== passwordConfirmation) {
      Alert.alert(t.error, t.passwordsDontMatch);
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
      const msg = err.response?.data?.message || t.registerFailed;
      Alert.alert(t.error, msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.topBar}>
        <LanguageToggle compact />
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <Text style={styles.title}>{t.register}</Text>

        {/* Role Selector (Correction 2A) */}
        <Text style={[styles.label, { textAlign: isRTL ? 'right' : 'left' }]}>{t.roleSelect}</Text>
        <View style={styles.roleContainer}>
          <TouchableOpacity
            style={[styles.roleBtn, role === 'player' && styles.roleBtnActive]}
            onPress={() => setRole('player')}
            activeOpacity={0.8}
          >
            <Text style={[styles.roleText, role === 'player' && styles.roleTextActive]}>
              ⚽ {t.playerRole}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.roleBtn, role === 'venue_owner' && styles.roleBtnActive]}
            onPress={() => setRole('venue_owner')}
            activeOpacity={0.8}
          >
            <Text style={[styles.roleText, role === 'venue_owner' && styles.roleTextActive]}>
              🏟️ {t.ownerRole}
            </Text>
          </TouchableOpacity>
        </View>

        <Text style={[styles.label, { textAlign: isRTL ? 'right' : 'left' }]}>{t.name}</Text>
        <TextInput
          style={[styles.input, { textAlign: isRTL ? 'right' : 'left' }]}
          placeholder={isRTL ? "مثال: عمر محمد" : "e.g. Omar Mohamed"}
          value={name}
          onChangeText={setName}
        />

        <Text style={[styles.label, { textAlign: isRTL ? 'right' : 'left' }]}>{t.email}</Text>
        <TextInput
          style={[styles.input, { textAlign: isRTL ? 'right' : 'left' }]}
          placeholder="user@example.com"
          keyboardType="email-address"
          autoCapitalize="none"
          value={email}
          onChangeText={setEmail}
        />

        <Text style={[styles.label, { textAlign: isRTL ? 'right' : 'left' }]}>{t.password}</Text>
        <TextInput
          style={[styles.input, { textAlign: isRTL ? 'right' : 'left' }]}
          placeholder="••••••••"
          secureTextEntry
          value={password}
          onChangeText={setPassword}
        />

        <Text style={[styles.label, { textAlign: isRTL ? 'right' : 'left' }]}>{t.passwordConfirmation}</Text>
        <TextInput
          style={[styles.input, { textAlign: isRTL ? 'right' : 'left' }]}
          placeholder="••••••••"
          secureTextEntry
          value={passwordConfirmation}
          onChangeText={setPasswordConfirmation}
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
            <Text style={styles.submitBtnText}>{t.register}</Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.linkRow}
          onPress={() => navigation.navigate('Login')}
        >
          <Text style={styles.linkText}>{t.haveAccount}</Text>
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
  topBar: {
    paddingHorizontal: 20,
    paddingTop: 8,
    alignItems: 'flex-end',
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
  },
  roleContainer: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 20,
  },
  roleBtn: {
    flex: 1,
    paddingVertical: 14,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: 12,
    alignItems: 'center',
    backgroundColor: colors.surface,
  },
  roleBtnActive: {
    borderColor: colors.primary,
    backgroundColor: 'rgba(16, 185, 129, 0.08)',
  },
  roleText: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  roleTextActive: {
    color: colors.primary,
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
