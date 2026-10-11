import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator, Alert, Share } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { colors } from '../../theme/colors';

export const MatchLobbyScreen = ({ route, navigation }: any) => {
  const { lobbyId } = route.params;
  const { user } = useAuth();
  const [lobby, setLobby] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [timeLeft, setTimeLeft] = useState<string>('');

  const fetchLobby = async () => {
    try {
      const res = await apiClient.get(`/lobbies/${lobbyId}`);
      setLobby(res.data.lobby);
    } catch (err: any) {
      console.warn('Lobby fetch error:', err);
      Alert.alert('خطأ', 'تعذر تحميل بيانات اللوبي');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchLobby();
    const interval = setInterval(fetchLobby, 10000); // Polling fallback for real-time
    return () => clearInterval(interval);
  }, [lobbyId]);

  // Countdown timer derived from expires_at
  useEffect(() => {
    if (!lobby?.expires_at || lobby?.status !== 'forming') {
      setTimeLeft('');
      return;
    }

    const timer = setInterval(() => {
      const diff = new Date(lobby.expires_at).getTime() - new Date().getTime();
      if (diff <= 0) {
        setTimeLeft('00:00');
        clearInterval(timer);
      } else {
        const mins = Math.floor(diff / 60000);
        const secs = Math.floor((diff % 60000) / 1000);
        setTimeLeft(`${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`);
      }
    }, 1000);

    return () => clearInterval(timer);
  }, [lobby?.expires_at, lobby?.status]);

  const handleShareInvite = async () => {
    if (!lobby?.invite_code) return;
    try {
      await Share.share({
        message: `انضم لماتشنا على سبورتيا في ملعب ${lobby.venue?.name}! كود الانضمام: ${lobby.invite_code}`,
      });
    } catch (err) {
      console.warn('Share error:', err);
    }
  };

  const handlePay = () => {
    navigation.navigate('Payment', { lobbyId: lobby.id, amount: 150 });
  };

  const handleCancel = () => {
    Alert.alert('إلغاء اللوبي', 'هل تريد بالتأكيد إلغاء هذا اللوبي واسترداد المدفوعات؟', [
      { text: 'تراجع', style: 'cancel' },
      {
        text: 'إلغاء الماتش',
        style: 'destructive',
        onPress: async () => {
          try {
            await apiClient.delete(`/lobbies/${lobby.id}`);
            Alert.alert('تم', 'تم إلغاء الماتش بنجاح');
            navigation.goBack();
          } catch (err: any) {
            Alert.alert('خطأ', err.response?.data?.message || 'تعذر الإلغاء');
          }
        },
      },
    ]);
  };

  if (loading || !lobby) {
    return (
      <SafeAreaView style={[styles.container, { justifyContent: 'center', alignItems: 'center' }]}>
        <ActivityIndicator size="large" color={colors.primary} />
      </SafeAreaView>
    );
  }

  const isCreator = lobby.creator_id === user?.player_profile?.id;
  const isPaid = lobby.participants?.some(
    (p: any) => p.player_id === user?.player_profile?.id && p.payment_status === 'paid'
  );

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll}>
        {/* Top Status Banner */}
        <View style={styles.statusBanner}>
          <Text style={styles.statusTitle}>
            {lobby.status === 'confirmed' ? '✓ تم تأكيد الحجز والملعب جاهز!' :
             lobby.status === 'forming' ? '⏳ غرفة التجميع مفتوحة' :
             lobby.status === 'payment_pending' ? '💳 جاري استكمال الدفع' : lobby.status}
          </Text>
          {timeLeft ? (
            <Text style={styles.timerText}>⏰ الوقت المتبقي لاكتمال الدفع: {timeLeft}</Text>
          ) : null}
        </View>

        {/* Slot Warning Banner (Correction C) */}
        {lobby.slot_warning && lobby.status === 'forming' && (
          <View style={styles.warningBanner}>
            <Text style={styles.warningText}>
              ⚠️ في ماتش تاني على نفس الوقت ده. الأول يكمل الدفع هياخد الملعب.
            </Text>
          </View>
        )}

        {/* Match Info Card */}
        <View style={styles.card}>
          <Text style={styles.sport}>{lobby.sport?.name_ar || 'كرة قدم'}</Text>
          <Text style={styles.venue}>{lobby.venue?.name || 'الملعب'}</Text>
          <Text style={styles.date}>📅 {lobby.match_date} ({lobby.start_time?.slice(0, 5)} - {lobby.end_time?.slice(0, 5)})</Text>
          <Text style={styles.address}>📍 {lobby.venue?.address}, {lobby.venue?.city}</Text>

          <View style={styles.inviteRow}>
            <Text style={styles.inviteCode}>كود الدعوة: {lobby.invite_code}</Text>
            <TouchableOpacity style={styles.shareBtn} onPress={handleShareInvite}>
              <Text style={styles.shareBtnText}>مشاركة 🔗</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Participants List */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>
            قائمة اللاعبين ({lobby.participants?.length || 0} / {lobby.required_players})
          </Text>

          {lobby.participants?.map((p: any) => (
            <View key={p.id} style={styles.participantRow}>
              <View>
                <Text style={styles.participantName}>
                  {p.player?.username ? `@${p.player.username}` : 'لاعب'}
                </Text>
                <Text style={styles.participantStatus}>
                  {p.invite_status === 'accepted' ? 'مؤكد الانضمام' : p.invite_status}
                </Text>
              </View>

              <View style={[
                styles.paymentBadge,
                p.payment_status === 'paid' ? styles.paymentPaid : styles.paymentPending
              ]}>
                <Text style={[
                  styles.paymentBadgeText,
                  p.payment_status === 'paid' ? styles.paymentPaidText : styles.paymentPendingText
                ]}>
                  {p.payment_status === 'paid' ? 'دفع ✓' : 'لم يدفع بعد'}
                </Text>
              </View>
            </View>
          ))}
        </View>

        {/* Action Buttons */}
        {!isPaid && lobby.status === 'forming' && (
          <TouchableOpacity style={styles.payBtn} onPress={handlePay} activeOpacity={0.8}>
            <Text style={styles.payBtnText}>💳 دفع نصيبي في المباراة (Paymob)</Text>
          </TouchableOpacity>
        )}

        {isCreator && (
          <TouchableOpacity style={styles.cancelBtn} onPress={handleCancel} activeOpacity={0.8}>
            <Text style={styles.cancelBtnText}>إلغاء الماتش واسترداد المبالغ</Text>
          </TouchableOpacity>
        )}
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  scroll: {
    padding: 16,
    gap: 14,
  },
  statusBanner: {
    backgroundColor: colors.dark,
    padding: 16,
    borderRadius: 12,
    alignItems: 'center',
  },
  statusTitle: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '800',
    marginBottom: 4,
  },
  timerText: {
    color: colors.accent,
    fontSize: 14,
    fontWeight: '700',
  },
  warningBanner: {
    backgroundColor: '#FEF3C7',
    borderWidth: 1.5,
    borderColor: '#F59E0B',
    padding: 14,
    borderRadius: 12,
  },
  warningText: {
    color: '#92400E',
    fontSize: 13,
    fontWeight: '700',
    textAlign: 'center',
    lineHeight: 18,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
  },
  sport: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.primaryDark,
    marginBottom: 2,
  },
  venue: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 4,
  },
  date: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 2,
  },
  address: {
    fontSize: 13,
    color: colors.textMuted,
    marginBottom: 12,
  },
  inviteRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  inviteCode: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  shareBtn: {
    backgroundColor: colors.surfaceSubtle,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 6,
  },
  shareBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.secondary,
  },
  cardTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 12,
    textAlign: 'right',
  },
  participantRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  participantName: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  participantStatus: {
    fontSize: 12,
    color: colors.textMuted,
  },
  paymentBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 6,
  },
  paymentBadgeText: {
    fontSize: 12,
    fontWeight: '700',
  },
  paymentPaid: {
    backgroundColor: colors.primaryLight,
  },
  paymentPaidText: {
    color: colors.primaryDark,
    fontSize: 12,
    fontWeight: '700',
  },
  paymentPending: {
    backgroundColor: colors.warningLight,
  },
  paymentPendingText: {
    color: '#92400E',
    fontSize: 12,
    fontWeight: '700',
  },
  payBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
  },
  payBtnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
  cancelBtn: {
    backgroundColor: colors.surfaceSubtle,
    borderWidth: 1,
    borderColor: colors.danger,
    paddingVertical: 14,
    borderRadius: 12,
    alignItems: 'center',
  },
  cancelBtnText: {
    color: colors.danger,
    fontSize: 14,
    fontWeight: '700',
  },
});
