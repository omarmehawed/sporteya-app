import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import QRCode from 'react-native-qrcode-svg';
import { apiClient } from '../../api/client';
import { colors } from '../../theme/colors';

export const QRCheckinScreen = ({ route, navigation }: any) => {
  const { lobbyId, inviteCode } = route.params;
  const [token, setToken] = useState<string>('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Generate or fetch checkin QR token for lobby
    apiClient.get(`/lobbies/${lobbyId}`)
      .then(res => {
        // Use unique hash or booking qr_token if confirmed
        const lobby = res.data.lobby;
        setToken(lobby.id + '-' + (lobby.invite_code || 'sporteya'));
      })
      .catch(err => {
        console.warn('Lobby token fetch error:', err);
        setToken(lobbyId || 'sporteya-ticket');
      })
      .finally(() => setLoading(false));
  }, [lobbyId]);

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <View style={styles.navBar}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Text style={styles.backBtnText}>→</Text>
          </TouchableOpacity>
          <Text style={styles.navTitle}>تذكرة الدخول والـ QR</Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.title}>رمز الدخول لتسجيل الحضور</Text>
          <Text style={styles.sub}>
            أظهر هذا الرمز لمسؤول الملعب عند الوصول لتأكيد حضورك وتفادي احتساب الغياب
          </Text>

          <View style={styles.qrWrapper}>
            {loading ? (
              <ActivityIndicator size="large" color={colors.primary} />
            ) : token ? (
              <QRCode value={token} size={200} color={colors.dark} backgroundColor="white" />
            ) : (
              <Text>تعذر إنشاء الكود</Text>
            )}
          </View>

          <Text style={styles.codeText}>كود الحجز: {inviteCode || 'SPORTEYA'}</Text>
          <Text style={styles.windowNotice}>
            ⏱️ نافذة تسجيل الحضور متاحة من 30 دقيقة قبل بدء الماتش وحتى 60 دقيقة بعد بدئه
          </Text>
        </View>
      </View>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  content: {
    flex: 1,
    padding: 20,
  },
  navBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 20,
  },
  backBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: colors.surface,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  backBtnText: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  navTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 20,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  title: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 8,
  },
  sub: {
    fontSize: 13,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 18,
    marginBottom: 24,
  },
  qrWrapper: {
    padding: 16,
    backgroundColor: 'white',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 20,
  },
  codeText: {
    fontSize: 16,
    fontWeight: '800',
    color: colors.primaryDark,
    marginBottom: 12,
  },
  windowNotice: {
    fontSize: 12,
    color: colors.textSecondary,
    textAlign: 'center',
    backgroundColor: colors.surfaceSubtle,
    padding: 10,
    borderRadius: 8,
  },
});
