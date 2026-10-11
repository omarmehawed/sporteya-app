import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { colors } from '../../theme/colors';

export const VenueDetailScreen = ({ route, navigation }: any) => {
  const { venueId } = route.params;
  const [venue, setVenue] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    apiClient.get(`/venues/${venueId}`)
      .then(res => setVenue(res.data.venue))
      .catch(err => {
        Alert.alert('خطأ', 'تعذر تحميل بيانات الملعب');
        navigation.goBack();
      })
      .finally(() => setLoading(false));
  }, [venueId]);

  if (loading || !venue) {
    return (
      <SafeAreaView style={[styles.container, { justifyContent: 'center', alignItems: 'center' }]}>
        <ActivityIndicator size="large" color={colors.primary} />
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll}>
        {/* Top Nav */}
        <View style={styles.navBar}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Text style={styles.backBtnText}>→</Text>
          </TouchableOpacity>
          <Text style={styles.navTitle}>{venue.name}</Text>
        </View>

        {/* Hero Card */}
        <View style={styles.heroCard}>
          <Text style={styles.name}>{venue.name}</Text>
          <Text style={styles.city}>📍 {venue.city} - {venue.address}</Text>
          <Text style={styles.rating}>⭐ {Number(venue.rating_avg || 5.0).toFixed(1)} ({venue.rating_count || 0} تقييم)</Text>
          {venue.description && <Text style={styles.desc}>{venue.description}</Text>}
        </View>

        {/* Amenities */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>المرافق والخدمات</Text>
          <View style={styles.amenities}>
            {venue.has_lighting && <Text style={styles.badge}>💡 إضاءة ليلية متوفرة</Text>}
            {venue.has_changing_room && <Text style={styles.badge}>👕 غرف تبديل وحمامات</Text>}
            {venue.has_parking && <Text style={styles.badge}>🚗 موقف سيارات متاح</Text>}
          </View>
        </View>

        {/* Cancellation Policy */}
        {venue.cancellation_policy && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>سياسة الإلغاء والاسترداد</Text>
            <View style={styles.policyBox}>
              <Text style={styles.policyText}>
                • استرداد 100% قبل: {venue.cancellation_policy.full_refund_hours} ساعة من الموعد
              </Text>
              <Text style={styles.policyText}>
                • استرداد 50% قبل: {venue.cancellation_policy.half_refund_hours} ساعة من الموعد
              </Text>
            </View>
          </View>
        )}

        {/* CTA */}
        <TouchableOpacity
          style={styles.bookBtn}
          onPress={() => navigation.navigate('SelectSlot', { venueId: venue.id, venueName: venue.name })}
          activeOpacity={0.8}
        >
          <Text style={styles.bookBtnText}>اختيار الموعد وإنشاء الماتش</Text>
        </TouchableOpacity>
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
    gap: 16,
  },
  navBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
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
  heroCard: {
    backgroundColor: colors.surface,
    borderRadius: 16,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.border,
  },
  name: {
    fontSize: 22,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 4,
  },
  city: {
    fontSize: 14,
    color: colors.textMuted,
    marginBottom: 8,
  },
  rating: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.accent,
    marginBottom: 12,
  },
  desc: {
    fontSize: 14,
    color: colors.textSecondary,
    lineHeight: 22,
  },
  section: {
    backgroundColor: colors.surface,
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 12,
    textAlign: 'right',
  },
  amenities: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  badge: {
    backgroundColor: colors.surfaceSubtle,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 6,
    fontSize: 13,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  policyBox: {
    backgroundColor: colors.surfaceSubtle,
    padding: 12,
    borderRadius: 8,
    gap: 6,
  },
  policyText: {
    fontSize: 13,
    color: colors.textSecondary,
    textAlign: 'right',
  },
  bookBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 8,
  },
  bookBtnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
});
