import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, ActivityIndicator, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { useLanguage } from '../../context/LanguageContext';
import { colors } from '../../theme/colors';

export const MyBookingsScreen = ({ navigation }: any) => {
  const { t, isRTL } = useLanguage();
  const [tab, setTab] = useState<'upcoming' | 'completed' | 'cancelled'>('upcoming');
  const [bookings, setBookings] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchBookings = async () => {
    try {
      const bookingsRes = await apiClient.get('/lobbies/public?my=true');
      setBookings(bookingsRes.data.lobbies || []);
    } catch (err) {
      console.warn('Error fetching bookings:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchBookings();
  }, [tab]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchBookings();
    setRefreshing(false);
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={[styles.title, { textAlign: isRTL ? 'right' : 'left' }]}>
          {t.myBookings}
        </Text>

        <View style={styles.tabRow}>
          <TouchableOpacity
            style={[styles.tabBtn, tab === 'upcoming' && styles.tabBtnActive]}
            onPress={() => setTab('upcoming')}
          >
            <Text style={[styles.tabText, tab === 'upcoming' && styles.tabTextActive]}>
              {t.upcoming}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.tabBtn, tab === 'completed' && styles.tabBtnActive]}
            onPress={() => setTab('completed')}
          >
            <Text style={[styles.tabText, tab === 'completed' && styles.tabTextActive]}>
              {t.completed}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.tabBtn, tab === 'cancelled' && styles.tabBtnActive]}
            onPress={() => setTab('cancelled')}
          >
            <Text style={[styles.tabText, tab === 'cancelled' && styles.tabTextActive]}>
              {t.cancelled}
            </Text>
          </TouchableOpacity>
        </View>
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={colors.primary} style={{ flex: 1 }} />
      ) : (
        <FlatList
          data={bookings}
          keyExtractor={(item) => item.id}
          contentContainerStyle={styles.list}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={{ fontSize: 36, marginBottom: 8 }}>🎟️</Text>
              <Text style={styles.emptyTitle}>
                {isRTL ? 'لا توجد حجوزات في هذا التصنيف' : 'No bookings in this category'}
              </Text>
              <Text style={styles.emptySub}>
                {isRTL ? 'عندما تنضم أو تحجز مباراة ستظهر بيانات تذكرتك وكود الـ QR هنا' : 'When you book or join a match, your ticket and QR code will appear here'}
              </Text>
            </View>
          }
          renderItem={({ item }) => {
            const sportName = isRTL ? item.sport?.name_ar : (item.sport?.name_en || item.sport?.name_ar);
            let statusLabel = item.status;
            if (item.status === 'confirmed') statusLabel = `✓ ${t.confirmed}`;
            else if (item.status === 'forming') statusLabel = t.forming;
            else if (item.status === 'payment_pending') statusLabel = t.paymentPending;
            else if (item.status === 'cancelled') statusLabel = t.cancelled;
            else if (item.status === 'completed') statusLabel = t.completed;

            return (
              <View style={styles.card}>
                <View style={styles.cardTop}>
                  <Text style={styles.sport}>{sportName || (isRTL ? 'كرة قدم' : 'Football')}</Text>
                  <Text style={styles.status}>{statusLabel}</Text>
                </View>

                <Text style={styles.venue}>{item.venue?.name || (isRTL ? 'الملعب' : 'Venue')}</Text>
                <Text style={styles.time}>📅 {item.match_date} - {item.start_time?.slice(0, 5)}</Text>

                <View style={styles.actions}>
                  <TouchableOpacity
                    style={styles.qrBtn}
                    onPress={() => navigation.navigate('QRCheckin', { lobbyId: item.id, inviteCode: item.invite_code })}
                  >
                    <Text style={styles.qrBtnText}>📷 {t.checkinQr}</Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={styles.detailsBtn}
                    onPress={() => navigation.navigate('MatchLobby', { lobbyId: item.id })}
                  >
                    <Text style={styles.detailsBtnText}>{isRTL ? 'غرفة اللوبي' : 'Lobby'}</Text>
                  </TouchableOpacity>
                </View>
              </View>
            );
          }}
        />
      )}
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  header: {
    padding: 20,
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderColor: colors.border,
  },
  title: {
    fontSize: 20,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 16,
  },
  tabRow: {
    flexDirection: 'row',
    gap: 8,
  },
  tabBtn: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 8,
    alignItems: 'center',
    backgroundColor: colors.surfaceSubtle,
    borderWidth: 1,
    borderColor: colors.border,
  },
  tabBtnActive: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  tabText: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.textMuted,
  },
  tabTextActive: {
    color: colors.textWhite,
  },
  list: {
    padding: 16,
    gap: 12,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
  },
  cardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  sport: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.primary,
  },
  status: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.primary,
    backgroundColor: 'rgba(16, 185, 129, 0.1)',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  venue: {
    fontSize: 16,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 4,
  },
  time: {
    fontSize: 13,
    color: colors.textMuted,
    marginBottom: 14,
  },
  actions: {
    flexDirection: 'row',
    gap: 8,
  },
  qrBtn: {
    flex: 1,
    backgroundColor: colors.primary,
    paddingVertical: 10,
    borderRadius: 8,
    alignItems: 'center',
  },
  qrBtnText: {
    color: colors.textWhite,
    fontSize: 13,
    fontWeight: '700',
  },
  detailsBtn: {
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 8,
    backgroundColor: colors.surfaceSubtle,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
  },
  detailsBtnText: {
    color: colors.textPrimary,
    fontSize: 13,
    fontWeight: '600',
  },
  empty: {
    padding: 40,
    alignItems: 'center',
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 6,
  },
  emptySub: {
    fontSize: 13,
    color: colors.textMuted,
    textAlign: 'center',
  },
});
