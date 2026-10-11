import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, FlatList, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { colors } from '../../theme/colors';
import { ar } from '../../i18n/ar';

export const HomeScreen = ({ navigation }: any) => {
  const { user } = useAuth();
  const [sports, setSports] = useState<any[]>([]);
  const [lobbies, setLobbies] = useState<any[]>([]);
  const [refreshing, setRefreshing] = useState(false);

  const loadData = async () => {
    try {
      const [sportsRes, lobbiesRes] = await Promise.all([
        apiClient.get('/sports'),
        apiClient.get('/lobbies/public'),
      ]);
      setSports(sportsRes.data.sports || []);
      setLobbies(lobbiesRes.data.lobbies || []);
    } catch (err) {
      console.warn('Home data load error:', err);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
      >
        {/* Header */}
        <View style={styles.header}>
          <View>
            <Text style={styles.greeting}>أهلاً، {user?.name || 'كابتن'} 👋</Text>
            <Text style={styles.subGreeting}>جاهز للماتش الجاي؟</Text>
          </View>
          <TouchableOpacity
            style={styles.notifBtn}
            onPress={() => navigation.navigate('Notifications')}
          >
            <Text style={{ fontSize: 20 }}>🔔</Text>
          </TouchableOpacity>
        </View>

        {/* Quick CTAs */}
        <View style={styles.ctaRow}>
          <TouchableOpacity
            style={[styles.ctaCard, { backgroundColor: colors.primary }]}
            onPress={() => navigation.navigate('SportVenues', {})}
            activeOpacity={0.8}
          >
            <Text style={styles.ctaIcon}>🏟️</Text>
            <Text style={styles.ctaTitle}>احجز ملعب</Text>
            <Text style={styles.ctaSub}>استكشف الملاعب القريبة</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.ctaCard, { backgroundColor: colors.secondary }]}
            onPress={() => navigation.navigate('Discover')}
            activeOpacity={0.8}
          >
            <Text style={styles.ctaIcon}>⚡</Text>
            <Text style={styles.ctaTitle}>انضم لماتش</Text>
            <Text style={styles.ctaSub}>ماتشات محتاجة لعيبة</Text>
          </TouchableOpacity>
        </View>

        {/* Sports Carousel */}
        <Text style={styles.sectionTitle}>الرياضات المتاحة</Text>
        <FlatList
          horizontal
          showsHorizontalScrollIndicator={false}
          data={sports}
          keyExtractor={(item) => item.id}
          contentContainerStyle={styles.sportsList}
          renderItem={({ item }) => (
            <TouchableOpacity
              style={styles.sportPill}
              onPress={() => navigation.navigate('SportVenues', { sportId: item.id, sportName: item.name_ar })}
            >
              <Text style={styles.sportPillText}>{item.name_ar}</Text>
            </TouchableOpacity>
          )}
        />

        {/* Open Matches Section */}
        <View style={styles.sectionHeaderRow}>
          <Text style={styles.sectionTitle}>ماتشات مفتوحة قريباً</Text>
          <TouchableOpacity onPress={() => navigation.navigate('Discover')}>
            <Text style={styles.seeAllText}>عرض الكل ←</Text>
          </TouchableOpacity>
        </View>

        {lobbies.length === 0 ? (
          <View style={styles.emptyCard}>
            <Text style={styles.emptyText}>لا توجد ماتشات مفتوحة حالياً في منطقتك</Text>
          </View>
        ) : (
          lobbies.slice(0, 3).map((lobby) => (
            <TouchableOpacity
              key={lobby.id}
              style={styles.matchCard}
              onPress={() => navigation.navigate('LobbyPreview', { inviteCode: lobby.invite_code })}
              activeOpacity={0.8}
            >
              <View style={styles.matchCardTop}>
                <Text style={styles.matchSport}>{lobby.sport?.name_ar || 'كرة قدم'}</Text>
                <Text style={styles.matchVenue}>{lobby.venue?.name || 'الملعب'}</Text>
              </View>
              <View style={styles.matchDetails}>
                <Text style={styles.matchDate}>📅 {lobby.match_date} ({lobby.start_time?.slice(0, 5)})</Text>
                <Text style={styles.matchPlayers}>👥 {lobby.required_players} لاعبين مطلوبين</Text>
              </View>
            </TouchableOpacity>
          ))
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
    padding: 20,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  greeting: {
    fontSize: 22,
    fontWeight: '800',
    color: colors.textPrimary,
    textAlign: 'right',
  },
  subGreeting: {
    fontSize: 14,
    color: colors.textMuted,
    textAlign: 'right',
  },
  notifBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: colors.surface,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  ctaRow: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 28,
  },
  ctaCard: {
    flex: 1,
    borderRadius: 14,
    padding: 16,
    alignItems: 'flex-start',
  },
  ctaIcon: {
    fontSize: 28,
    marginBottom: 8,
  },
  ctaTitle: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '800',
  },
  ctaSub: {
    color: 'rgba(255,255,255,0.85)',
    fontSize: 12,
    marginTop: 2,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
    textAlign: 'right',
    marginBottom: 12,
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 20,
    marginBottom: 12,
  },
  seeAllText: {
    color: colors.primaryDark,
    fontSize: 13,
    fontWeight: '700',
  },
  sportsList: {
    gap: 10,
    paddingBottom: 10,
  },
  sportPill: {
    backgroundColor: colors.surface,
    paddingHorizontal: 18,
    paddingVertical: 10,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: colors.border,
  },
  sportPillText: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  matchCard: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 12,
  },
  matchCardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  matchSport: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.primaryDark,
  },
  matchVenue: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  matchDetails: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 4,
  },
  matchDate: {
    fontSize: 13,
    color: colors.textSecondary,
  },
  matchPlayers: {
    fontSize: 13,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  emptyCard: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  emptyText: {
    color: colors.textMuted,
    fontSize: 14,
  },
});
