import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { colors } from '../../theme/colors';

export const LobbyPreviewScreen = ({ route, navigation }: any) => {
  const { inviteCode } = route.params;
  const [lobby, setLobby] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [joining, setJoining] = useState(false);

  useEffect(() => {
    apiClient.get(`/lobbies/join/${inviteCode}`)
      .then(res => setLobby(res.data.lobby))
      .catch(err => {
        Alert.alert('خطأ', 'رمز الدعوة غير صالح أو منتهي الصلاحية');
        navigation.goBack();
      })
      .finally(() => setLoading(false));
  }, [inviteCode]);

  const handleJoin = async () => {
    try {
      setJoining(true);
      await apiClient.post(`/lobbies/${lobby.id}/join`);
      navigation.replace('MatchLobby', { lobbyId: lobby.id });
    } catch (err: any) {
      Alert.alert('خطأ', err.response?.data?.message || 'تعذر الانضمام للوبي');
    } finally {
      setJoining(false);
    }
  };

  if (loading || !lobby) {
    return (
      <SafeAreaView style={[styles.container, { justifyContent: 'center', alignItems: 'center' }]}>
        <ActivityIndicator size="large" color={colors.primary} />
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>دعوة للانضمام إلى ماتش</Text>

        <View style={styles.card}>
          <Text style={styles.sport}>{lobby.sport?.name_ar || 'كرة قدم'}</Text>
          <Text style={styles.venue}>{lobby.venue?.name || 'الملعب'}</Text>
          <Text style={styles.date}>📅 {lobby.match_date} ({lobby.start_time?.slice(0, 5)} - {lobby.end_time?.slice(0, 5)})</Text>
          <Text style={styles.address}>📍 {lobby.venue?.city} - {lobby.venue?.address}</Text>

          <View style={styles.divider} />
          <Text style={styles.players}>
            👥 عدد اللاعبين الحاليين: {lobby.participants_count || 1} / {lobby.required_players}
          </Text>
        </View>

        {/* Slot Warning Banner (Correction C) */}
        {lobby.slot_warning && (
          <View style={styles.warningBox}>
            <Text style={styles.warningText}>
              ⚠️ في ماتش تاني على نفس الوقت ده. الأول يكمل الدفع هياخد الملعب.
            </Text>
          </View>
        )}

        <TouchableOpacity
          style={styles.joinBtn}
          onPress={handleJoin}
          disabled={joining}
          activeOpacity={0.8}
        >
          {joining ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.joinBtnText}>انضم للماتش الآن</Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backBtnText}>إلغاء والعودة</Text>
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
    fontSize: 22,
    fontWeight: '800',
    color: colors.textPrimary,
    textAlign: 'center',
    marginBottom: 24,
  },
  card: {
    backgroundColor: colors.surfaceSubtle,
    borderRadius: 16,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 16,
  },
  sport: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.primaryDark,
    marginBottom: 4,
  },
  venue: {
    fontSize: 20,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 8,
  },
  date: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 4,
  },
  address: {
    fontSize: 13,
    color: colors.textMuted,
  },
  divider: {
    height: 1,
    backgroundColor: colors.border,
    marginVertical: 14,
  },
  players: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  warningBox: {
    backgroundColor: '#FEF3C7',
    borderWidth: 1,
    borderColor: '#F59E0B',
    padding: 12,
    borderRadius: 10,
    marginBottom: 20,
  },
  warningText: {
    color: '#92400E',
    fontSize: 13,
    fontWeight: '700',
    textAlign: 'center',
  },
  joinBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
  },
  joinBtnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
  backBtn: {
    marginTop: 14,
    alignItems: 'center',
  },
  backBtnText: {
    color: colors.textMuted,
    fontSize: 14,
    fontWeight: '600',
  },
});
