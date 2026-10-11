import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { colors } from '../../theme/colors';

export const SportVenuesScreen = ({ route, navigation }: any) => {
  const { sportId, sportName } = route.params || {};
  const [venues, setVenues] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    apiClient.get('/venues', {
      params: sportId ? { sport_id: sportId } : {},
    })
      .then(res => setVenues(res.data.venues || []))
      .catch(err => console.warn('Venues fetch error:', err))
      .finally(() => setLoading(false));
  }, [sportId]);

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backBtnText}>→</Text>
        </TouchableOpacity>
        <Text style={styles.title}>{sportName ? `ملاعب ${sportName}` : 'جميع الملاعب'}</Text>
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={colors.primary} style={{ flex: 1 }} />
      ) : (
        <FlatList
          data={venues}
          keyExtractor={(item) => item.id}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={{ fontSize: 32, marginBottom: 8 }}>🏟️</Text>
              <Text style={styles.emptyText}>لا توجد ملاعب مسجلة حالياً لهذه الرياضة</Text>
            </View>
          }
          renderItem={({ item }) => (
            <TouchableOpacity
              style={styles.card}
              onPress={() => navigation.navigate('VenueDetail', { venueId: item.id })}
              activeOpacity={0.8}
            >
              <View style={styles.cardHeader}>
                <Text style={styles.name}>{item.name}</Text>
                <Text style={styles.rating}>⭐ {Number(item.rating_avg || 5.0).toFixed(1)}</Text>
              </View>

              <Text style={styles.address}>📍 {item.city} - {item.address}</Text>

              <View style={styles.amenities}>
                {item.has_lighting && <Text style={styles.tag}>💡 إضاءة</Text>}
                {item.has_changing_room && <Text style={styles.tag}>👕 غرف تبديل</Text>}
                {item.has_parking && <Text style={styles.tag}>🚗 موقف</Text>}
              </View>

              <View style={styles.cardFooter}>
                <Text style={styles.ctaText}>عرض الأوقات المتاحة والحجز ←</Text>
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
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderColor: colors.border,
    gap: 12,
  },
  backBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: colors.surfaceSubtle,
    alignItems: 'center',
    justifyContent: 'center',
  },
  backBtnText: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  title: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
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
    marginBottom: 4,
  },
  name: {
    fontSize: 17,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  rating: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.accent,
  },
  address: {
    fontSize: 13,
    color: colors.textMuted,
    marginBottom: 10,
  },
  amenities: {
    flexDirection: 'row',
    gap: 6,
    marginBottom: 12,
  },
  tag: {
    fontSize: 11,
    backgroundColor: colors.surfaceSubtle,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 4,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  cardFooter: {
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  ctaText: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.primaryDark,
    textAlign: 'left',
  },
  empty: {
    padding: 40,
    alignItems: 'center',
  },
  emptyText: {
    color: colors.textMuted,
    fontSize: 14,
  },
});
