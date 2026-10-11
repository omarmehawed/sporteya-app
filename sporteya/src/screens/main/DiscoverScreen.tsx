import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, ActivityIndicator, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { colors } from '../../theme/colors';

export const DiscoverScreen = ({ navigation }: any) => {
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
        <Text style={styles.title}>استكشف الماتشات المفتوحة</Text>
        <Text style={styles.subtitle}>انضم للمباريات الجارية التي تبحث عن لاعبين إضافيين</Text>
      </View>

      {/* Sport Filter Chips */}
      <View style={styles.filterSection}>
        <FlatList
          horizontal
          showsHorizontalScrollIndicator={false}
          data={[{ id: null, name_ar: 'الكل' }, ...sports]}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ paddingHorizontal: 16, gap: 8 }}
          renderItem={({ item }) => {
            const isSelected = selectedSportId === item.id;
            return (
              <TouchableOpacity
                style={[styles.chip, isSelected && styles.chipActive]}
                onPress={() => setSelectedSportId(item.id)}
              >
                <Text style={[styles.chipText, isSelected && styles.chipTextActive]}>
                  {item.name_ar}
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
              <Text style={styles.emptyTitle}>لا توجد ماتشات مفتوحة حالياً</Text>
              <Text style={styles.emptySub}>يمكنك حجز ملعب وإنشاء ماتش جديد ودعوة اللاعبين!</Text>
            </View>
          }
          renderItem={({ item }) => (
            <TouchableOpacity
              style={styles.card}
              onPress={() => navigation.navigate('LobbyPreview', { inviteCode: item.invite_code })}
              activeOpacity={0.8}
            >
              <View style={styles.cardHeader}>
                <Text style={styles.sport}>{item.sport?.name_ar || 'كرة'}</Text>
                <Text style={styles.matchType}>
                  {item.match_type === 'public' ? 'عام' : 'مختلط'}
                </Text>
              </View>

              <Text style={styles.venueName}>{item.venue?.name || 'ملعب غير محدد'}</Text>
              <Text style={styles.venueCity}>📍 {item.venue?.city || 'القاهرة'}</Text>

              <View style={styles.cardFooter}>
                <Text style={styles.date}>📅 {item.match_date} ({item.start_time?.slice(0, 5)})</Text>
                <Text style={styles.players}>👥 {item.required_players} لاعبين</Text>
              </View>
            </TouchableOpacity>
          )}
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
    textAlign: 'right',
  },
  subtitle: {
    fontSize: 13,
    color: colors.textMuted,
    textAlign: 'right',
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
    backgroundColor: colors.surfaceSubtle,
    borderWidth: 1,
    borderColor: colors.border,
  },
  chipActive: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  chipText: {
    fontSize: 13,
    fontWeight: '700',
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
    marginBottom: 6,
  },
  sport: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.primaryDark,
  },
  matchType: {
    fontSize: 12,
    color: colors.textMuted,
    backgroundColor: colors.surfaceSubtle,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 4,
  },
  venueName: {
    fontSize: 17,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 2,
  },
  venueCity: {
    fontSize: 13,
    color: colors.textMuted,
    marginBottom: 12,
  },
  cardFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  date: {
    fontSize: 13,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  players: {
    fontSize: 13,
    color: colors.primaryDark,
    fontWeight: '700',
  },
  empty: {
    padding: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 4,
  },
  emptySub: {
    fontSize: 13,
    color: colors.textMuted,
    textAlign: 'center',
  },
});
