import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, FlatList, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useLanguage } from '../../context/LanguageContext';
import { LanguageToggle } from '../../components/LanguageToggle';
import { colors } from '../../theme/colors';

export const HomeScreen = ({ navigation }: any) => {
  const { user } = useAuth();
  const { t, isRTL } = useLanguage();
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
          <View style={{ flex: 1 }}>
            <Text style={[styles.greeting, { textAlign: isRTL ? 'right' : 'left' }]}>
              {t.greeting} {user?.name || t.captain} 👋
            </Text>
            <Text style={[styles.subGreeting, { textAlign: isRTL ? 'right' : 'left' }]}>
              {t.readyForNextMatch}
            </Text>
          </View>
          <View style={styles.headerActions}>
            <LanguageToggle compact />
            <TouchableOpacity
              style={styles.notifBtn}
              onPress={() => navigation.navigate('Notifications')}
            >
              <Text style={{ fontSize: 18 }}>🔔</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Quick CTAs */}
        <View style={styles.ctaRow}>
          <TouchableOpacity
            style={[styles.ctaCard, { backgroundColor: colors.primary }]}
            onPress={() => navigation.navigate('SportVenues', {})}
            activeOpacity={0.8}
          >
            <Text style={styles.ctaIcon}>🏟️</Text>
            <Text style={styles.ctaTitle}>{t.bookVenue}</Text>
            <Text style={styles.ctaSub}>{t.exploreVenues}</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.ctaCard, { backgroundColor: colors.secondary }]}
            onPress={() => navigation.navigate('Discover')}
            activeOpacity={0.8}
          >
            <Text style={styles.ctaIcon}>⚡</Text>
            <Text style={styles.ctaTitle}>{t.joinOpenMatch}</Text>
            <Text style={styles.ctaSub}>{t.matchesNeedPlayers}</Text>
          </TouchableOpacity>
        </View>

        {/* Sports Carousel */}
        <Text style={[styles.sectionTitle, { textAlign: isRTL ? 'right' : 'left' }]}>
          {t.availableSports}
        </Text>
        <FlatList
          horizontal
          showsHorizontalScrollIndicator={false}
          data={sports}
          keyExtractor={(item) => item.id}
          contentContainerStyle={styles.sportsList}
          renderItem={({ item }) => {
            const sportName = isRTL ? item.name_ar : (item.name_en || item.name_ar);
            return (
              <TouchableOpacity
                style={styles.sportPill}
                onPress={() => navigation.navigate('SportVenues', { sportId: item.id, sportName })}
              >
                <Text style={styles.sportPillText}>{sportName}</Text>
              </TouchableOpacity>
            );
          }}
        />

        {/* Open Matches Section */}
        <View style={styles.sectionHeaderRow}>
          <Text style={styles.sectionTitle}>{t.openMatchesNow}</Text>
          <TouchableOpacity onPress={() => navigation.navigate('Discover')}>
            <Text style={styles.seeAllText}>{t.viewAll}</Text>
          </TouchableOpacity>
        </View>

        {lobbies.length === 0 ? (
          <View style={styles.emptyCard}>
            <Text style={styles.emptyText}>{t.noOpenMatches}</Text>
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
                <Text style={styles.matchSport}>
                  {isRTL ? (lobby.sport?.name_ar || 'كرة قدم') : (lobby.sport?.name_en || lobby.sport?.name_ar || 'Football')}
                </Text>
                <Text style={styles.matchVenue}>{lobby.venue?.name || 'الملعب'}</Text>
              </View>
              <View style={styles.matchDetails}>
                <Text style={styles.matchDate}>📅 {lobby.match_date} ({lobby.start_time?.slice(0, 5)})</Text>
                <Text style={styles.matchPlayers}>👥 {lobby.required_players} {t.playersNeeded}</Text>
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
  headerActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  greeting: {
    fontSize: 22,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  subGreeting: {
    fontSize: 14,
    color: colors.textMuted,
  },
  notifBtn: {
    width: 40,
    height: 40,
    borderRadius: 20,
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
    marginBottom: 12,
  },
  sportsList: {
    paddingBottom: 20,
    gap: 10,
  },
  sportPill: {
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 20,
    marginRight: 8,
  },
  sportPillText: {
    color: colors.textPrimary,
    fontSize: 14,
    fontWeight: '600',
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 10,
    marginBottom: 14,
  },
  seeAllText: {
    color: colors.primary,
    fontSize: 13,
    fontWeight: '700',
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
  matchCard: {
    backgroundColor: colors.surface,
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 12,
  },
  matchCardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  matchSport: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.primary,
  },
  matchVenue: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  matchDetails: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  matchDate: {
    fontSize: 13,
    color: colors.textMuted,
  },
  matchPlayers: {
    fontSize: 13,
    color: colors.secondary,
    fontWeight: '600',
  },
});
