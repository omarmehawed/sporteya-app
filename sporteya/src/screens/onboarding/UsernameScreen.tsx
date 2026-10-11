import React, { useState, useEffect, useRef } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { colors } from '../../theme/colors';
import { ar } from '../../i18n/ar';

export const UsernameScreen = ({ navigation }: any) => {
  const { user } = useAuth();
  const initialUsername = user?.player_profile?.username || '';
  const [username, setUsername] = useState(initialUsername);
  const [checking, setChecking] = useState(false);
  const [isAvailable, setIsAvailable] = useState<boolean | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const checkTimeout = useRef<any>(null);

  useEffect(() => {
    if (!username.trim() || username.trim().length < 3) {
      setIsAvailable(null);
      setChecking(false);
      return;
    }

    if (checkTimeout.current) {
      clearTimeout(checkTimeout.current);
    }

    setChecking(true);
    checkTimeout.current = setTimeout(async () => {
      try {
        // 404 = available, 200 = taken
        const res = await apiClient.get(`/player/${username.trim()}`);
        if (res.status === 200) {
          // If it's the current user's existing username, treat as available
          if (res.data?.player?.id === user?.player_profile?.id) {
            setIsAvailable(true);
          } else {
            setIsAvailable(false);
          }
        }
      } catch (err: any) {
        if (err.response?.status === 404) {
          setIsAvailable(true);
        } else {
          setIsAvailable(null);
        }
      } finally {
        setChecking(false);
      }
    }, 500);

    return () => {
      if (checkTimeout.current) clearTimeout(checkTimeout.current);
    };
  }, [username]);

  const handleContinue = async () => {
    if (!username.trim() || isAvailable === false) {
      return;
    }

    try {
      setSubmitting(true);
      await apiClient.post('/onboarding/username', {
        username: username.trim(),
      });
      navigation.navigate('SelectLocation');
    } catch (err: any) {
      const msg = err.response?.data?.message || 'تعذر حفظ اسم المستخدم';
      Alert.alert('تنبيه', msg);
    } finally {
      setSubmitting(false);
    }
  };

  const handleSkip = () => {
    navigation.navigate('SelectLocation');
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>{ar.chooseUsername}</Text>
        <Text style={styles.subtitle}>
          {ar.usernameDesc.replace('{username}', username || '...')}
        </Text>

        <View style={styles.inputBox}>
          <Text style={styles.atPrefix}>@</Text>
          <TextInput
            style={styles.input}
            value={username}
            onChangeText={(text) => setUsername(text.toLowerCase().replace(/[^a-z0-9_]/g, ''))}
            placeholder="username"
            autoCapitalize="none"
            autoCorrect={false}
          />
          {checking && <ActivityIndicator size="small" color={colors.primary} style={styles.statusIcon} />}
          {!checking && isAvailable === true && (
            <Text style={[styles.statusIcon, { color: colors.success }]}>✓</Text>
          )}
          {!checking && isAvailable === false && (
            <Text style={[styles.statusIcon, { color: colors.danger }]}>✗</Text>
          )}
        </View>

        {isAvailable === true && (
          <Text style={styles.successMsg}>{ar.usernameAvailable}</Text>
        )}
        {isAvailable === false && (
          <Text style={styles.errorMsg}>{ar.usernameTaken}</Text>
        )}

        <TouchableOpacity
          style={[styles.btn, (isAvailable === false || submitting) && styles.btnDisabled]}
          onPress={handleContinue}
          disabled={isAvailable === false || submitting}
          activeOpacity={0.8}
        >
          {submitting ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.btnText}>{ar.continueBtn}</Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity style={styles.skipBtn} onPress={handleSkip}>
          <Text style={styles.skipBtnText}>{ar.skipBtn}</Text>
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
  inputBox: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: 12,
    paddingHorizontal: 16,
    backgroundColor: colors.surfaceSubtle,
    marginBottom: 8,
  },
  atPrefix: {
    fontSize: 18,
    fontWeight: '700',
    color: colors.textMuted,
    marginRight: 6,
  },
  input: {
    flex: 1,
    paddingVertical: 14,
    fontSize: 16,
    color: colors.textPrimary,
  },
  statusIcon: {
    fontSize: 18,
    fontWeight: '900',
    marginLeft: 8,
  },
  successMsg: {
    fontSize: 13,
    color: colors.success,
    textAlign: 'right',
    marginBottom: 20,
    fontWeight: '600',
  },
  errorMsg: {
    fontSize: 13,
    color: colors.danger,
    textAlign: 'right',
    marginBottom: 20,
    fontWeight: '600',
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 16,
  },
  btnDisabled: {
    opacity: 0.5,
  },
  btnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
  skipBtn: {
    marginTop: 16,
    alignItems: 'center',
  },
  skipBtnText: {
    color: colors.textMuted,
    fontSize: 14,
    fontWeight: '600',
  },
});
