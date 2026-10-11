import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, ActivityIndicator, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { useLanguage } from '../../context/LanguageContext';
import { colors } from '../../theme/colors';

export const DiscoverScreen = ({ navigation }: any) => {
  const { t, isRTL } = useLanguage();
  const [lobbies, setLobbies] = useState<any[]>([]);
  const [sports, setSports] = useState<any[]>([]);
  const [selectedSportId, setSelectedSportId] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchData = async () => {
    try {
      const [sportsRes, lobbiesRes] = await Promise.all([
        apiClient.get('/sports'),
        apiClient.get('/lobbies/public', {
          params: selectedSportId ? { sport_id: selectedSportId } : {},
        }),
      ]);
      setSports(sportsRes.data.sports || []);
      setLobbies(lobbiesRes.data.lobbies || []);
    } catch (err) {
      console.warn('Error fetching discover data:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [selectedSportId]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={[styles.title, { textAlign: isRTL ? 'right' : 'left' }]}>
          {isRTL ? 'استكشف الماتشات المفتوحة' : 'Discover Open Matches'}
        </Text>
        <Text style={[styles.subtitle, { textAlign: isRTL ? 'right' : 'left' }]}>
          {isRTL ? 'انضم للمباريات الجارية التي تبحث عن لاعبين إضافيين' : 'Join active matches looking for extra players'}
        </Text>
      </View>

      {/* Sport Filter Chips */}
      <View style={styles.filterSection}>
        <FlatList
          horizontal
          showsHorizontalScrollIndicator={false}
          data={[{ id: null, name_ar: t.allSports, name_en: t.allSports }, ...sports]}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ paddingHorizontal: 16, gap: 8 }}
          renderItem={({ item }) => {
            const isSelected = selectedSportId === item.id;
            const label = isRTL ? item.name_ar : (item.name_en || item.name_ar);
            return (
              <TouchableOpacity
                style={[styles.chip, isSelected && styles.chipActive]}
                onPress={() => setSelectedSportId(item.id)}
              >
                <Text style={[styles.chipText, isSelected && styles.chipTextActive]}>
                  {label}
                </Text>
              </TouchableOpacity>
            );
          }}
        />
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={colors.primary} style={{ flex: 1 }} />
      ) : (
        <FlatList
          data={lobbies}
          keyExtractor={(item) => item.id}
          contentContainerStyle={styles.list}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={{ fontSize: 36, marginBottom: 8 }}>⚽</Text>
              <Text style={styles.emptyTitle}>
                {isRTL ? 'لا توجد ماتشات مفتوحة حالياً' : 'No open matches currently'}
              </Text>
              <Text style={styles.emptySub}>
                {isRTL ? 'يمكنك حجز ملعب وإنشاء ماتش جديد ودعوة اللاعبين!' : 'You can book a court, create a match, and invite players!'}
              </Text>
            </View>
          }
          renderItem={({ item }) => {
            const sportName = isRTL ? item.sport?.name_ar : (item.sport?.name_en || item.sport?.name_ar);
            const matchTypeLabel = item.match_type === 'public'
              ? (isRTL ? 'عام' : 'Public')
              : (isRTL ? 'مختلط' : 'Mixed');

            return (
              <TouchableOpacity
                style={styles.card}
                onPress={() => navigation.navigate('LobbyPreview', { inviteCode: item.invite_code })}
                activeOpacity={0.8}
              >
                <View style={styles.cardHeader}>
                  <Text style={styles.sport}>{sportName || (isRTL ? 'كرة' : 'Sport')}</Text>
                  <Text style={styles.matchType}>{matchTypeLabel}</Text>
                </View>

                <Text style={styles.venueName}>{item.venue?.name || (isRTL ? 'ملعب غير محدد' : 'Venue')}</Text>
                <Text style={styles.venueCity}>📍 {item.venue?.city || (isRTL ? 'القاهرة' : 'Cairo')}</Text>

                <View style={styles.cardFooter}>
                  <Text style={styles.date}>📅 {item.match_date} ({item.start_time?.slice(0, 5)})</Text>
                  <Text style={styles.players}>👥 {item.required_players} {t.playersNeeded}</Text>
                </View>
              </TouchableOpacity>
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
  },
  subtitle: {
    fontSize: 13,
    color: colors.textMuted,
    marginTop: 4,
  },
  filterSection: {
    paddingVertical: 12,
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderColor: colors.border,
  },
  chip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: colors.background,
    borderWidth: 1,
    borderColor: colors.border,
  },
  chipActive: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  chipText: {
    fontSize: 13,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  chipTextActive: {
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
  cardHeader: {
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
  matchType: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.secondary,
    backgroundColor: 'rgba(59, 130, 246, 0.1)',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 8,
  },
  venueName: {
    fontSize: 16,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 4,
  },
  venueCity: {
    fontSize: 13,
    color: colors.textMuted,
    marginBottom: 12,
  },
  cardFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  date: {
    fontSize: 13,
    color: colors.textSecondary,
  },
  players: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.secondary,
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
