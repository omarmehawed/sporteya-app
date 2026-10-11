import React, { useState, useEffect, useRef } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useLanguage } from '../../context/LanguageContext';
import { colors } from '../../theme/colors';

export const UsernameScreen = ({ navigation }: any) => {
  const { user } = useAuth();
  const { t, isRTL } = useLanguage();
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
      const msg = err.response?.data?.message || t.onboardingError;
      Alert.alert(t.error, msg);
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
        <Text style={[styles.title, { textAlign: isRTL ? 'right' : 'left' }]}>{t.chooseUsername}</Text>
        <Text style={[styles.subtitle, { textAlign: isRTL ? 'right' : 'left' }]}>
          {t.usernameDesc.replace('{username}', username || 'player')}
        </Text>

        <View style={styles.inputBox}>
          <TextInput
            style={[styles.input, { textAlign: isRTL ? 'right' : 'left' }]}
            placeholder="username_example"
            autoCapitalize="none"
            autoCorrect={false}
            value={username}
            onChangeText={setUsername}
          />
          {checking && <ActivityIndicator size="small" color={colors.primary} style={styles.spinner} />}
        </View>

        {isAvailable === true && (
          <Text style={[styles.statusText, { color: colors.primary, textAlign: isRTL ? 'right' : 'left' }]}>
            {t.usernameAvailable}
          </Text>
        )}
        {isAvailable === false && (
          <Text style={[styles.statusText, { color: colors.danger, textAlign: isRTL ? 'right' : 'left' }]}>
            {t.usernameTaken}
          </Text>
        )}

        <TouchableOpacity
          style={[
            styles.continueBtn,
            (isAvailable === false || checking || !username.trim()) && styles.continueBtnDisabled,
          ]}
          onPress={handleContinue}
          disabled={isAvailable === false || checking || !username.trim() || submitting}
          activeOpacity={0.8}
        >
          {submitting ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.continueBtnText}>{t.continueBtn}</Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity style={styles.skipBtn} onPress={handleSkip}>
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
    marginBottom: 24,
    lineHeight: 20,
  },
  inputBox: {
    position: 'relative',
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
  },
  spinner: {
    position: 'absolute',
    left: 16,
    top: 16,
  },
  statusText: {
    fontSize: 13,
    fontWeight: '700',
    marginBottom: 20,
  },
  continueBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 10,
  },
  continueBtnDisabled: {
    opacity: 0.5,
  },
  continueBtnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
  skipBtn: {
    marginTop: 16,
    alignItems: 'center',
    padding: 10,
  },
  skipBtnText: {
    color: colors.textMuted,
    fontSize: 14,
    fontWeight: '600',
  },
});
