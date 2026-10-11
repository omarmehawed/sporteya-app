import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { colors } from '../../theme/colors';

export const SelectSlotScreen = ({ route, navigation }: any) => {
  const { venueId, venueName } = route.params;
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
  const [slots, setSlots] = useState<any[]>([]);
  const [selectedSlot, setSelectedSlot] = useState<any>(null);
  const [matchType, setMatchType] = useState<'public' | 'mixed' | 'private'>('public');
  const [requiredPlayers, setRequiredPlayers] = useState(10);
  const [loadingSlots, setLoadingSlots] = useState(true);
  const [creating, setCreating] = useState(false);
  const [sports, setSports] = useState<any[]>([]);
  const [selectedSportId, setSelectedSportId] = useState<string>('');

  useEffect(() => {
    apiClient.get('/sports').then(res => {
      const list = res.data.sports || [];
      setSports(list);
      if (list.length > 0) setSelectedSportId(list[0].id);
    });
  }, []);

  useEffect(() => {
    setLoadingSlots(true);
    apiClient.get(`/venues/${venueId}/availability`, {
      params: { date },
    })
      .then(res => {
        setSlots(res.data.slots || []);
        if (res.data.slots?.length > 0) {
          setSelectedSlot(res.data.slots[0]);
        } else {
          setSelectedSlot(null);
        }
      })
      .catch(err => console.warn('Slots load error:', err))
      .finally(() => setLoadingSlots(false));
  }, [venueId, date]);

  const handleCreateLobby = async () => {
    if (!selectedSlot) {
      Alert.alert('تنبيه', 'يرجى اختيار موعد متاح من القائمة');
      return;
    }
    if (!selectedSportId) {
      Alert.alert('تنبيه', 'يرجى اختيار الرياضة');
      return;
    }

    try {
      setCreating(true);
      const res = await apiClient.post('/lobbies', {
        venue_id: venueId,
        sport_id: selectedSportId,
        match_date: date,
        start_time: selectedSlot.start_time,
        end_time: selectedSlot.end_time,
        required_players: requiredPlayers,
        match_type: matchType,
      });

      const { lobby } = res.data;
      navigation.replace('MatchLobby', { lobbyId: lobby.id });
    } catch (err: any) {
      Alert.alert('خطأ', err.response?.data?.message || 'تعذر إنشاء اللوبي');
    } finally {
      setCreating(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.header}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Text style={styles.backBtnText}>→</Text>
          </TouchableOpacity>
          <Text style={styles.title}>حجز موعد في {venueName}</Text>
        </View>

        {/* Date selector */}
        <Text style={styles.sectionTitle}>تاريخ المباراة</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.dateRow}>
          {[0, 1, 2, 3, 4].map(offset => {
            const d = new Date();
            d.setDate(d.getDate() + offset);
            const dateStr = d.toISOString().slice(0, 10);
            const isSelected = date === dateStr;
            return (
              <TouchableOpacity
                key={dateStr}
                style={[styles.dateChip, isSelected && styles.dateChipActive]}
                onPress={() => setDate(dateStr)}
              >
                <Text style={[styles.dateDay, isSelected && styles.dateTextActive]}>
                  {offset === 0 ? 'اليوم' : offset === 1 ? 'غداً' : dateStr.slice(5)}
                </Text>
              </TouchableOpacity>
            );
          })}
        </ScrollView>

        {/* Sport selector */}
        <Text style={styles.sectionTitle}>الرياضة</Text>
        <View style={styles.chipsRow}>
          {sports.map(s => (
            <TouchableOpacity
              key={s.id}
              style={[styles.chip, selectedSportId === s.id && styles.chipActive]}
              onPress={() => setSelectedSportId(s.id)}
            >
              <Text style={[styles.chipText, selectedSportId === s.id && styles.chipTextActive]}>
                {s.name_ar}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Available Slots */}
        <Text style={styles.sectionTitle}>المواعيد المتاحة</Text>
        {loadingSlots ? (
          <ActivityIndicator color={colors.primary} style={{ marginVertical: 20 }} />
        ) : slots.length === 0 ? (
          <View style={styles.emptyBox}>
            <Text style={styles.emptyText}>لا توجد مواعيد شاغرة في هذا اليوم</Text>
          </View>
        ) : (
          <View style={styles.slotsGrid}>
            {slots.map(s => {
              const isSelected = selectedSlot?.start_time === s.start_time;
              return (
                <TouchableOpacity
                  key={s.start_time}
                  style={[styles.slotCard, isSelected && styles.slotCardActive]}
                  onPress={() => setSelectedSlot(s)}
                >
                  <Text style={[styles.slotTime, isSelected && styles.slotTimeActive]}>
                    {s.start_time.slice(0, 5)} - {s.end_time.slice(0, 5)}
                  </Text>
                  <Text style={[styles.slotPrice, isSelected && styles.slotPriceActive]}>
                    {s.price} ج.م
                  </Text>
                </TouchableOpacity>
              );
            })}
          </View>
        )}

        {/* Match Type */}
        <Text style={styles.sectionTitle}>نوع الماتش</Text>
        <View style={styles.typeRow}>
          {[
            { id: 'public', label: 'عام (مفتوح للجميع)' },
            { id: 'mixed', label: 'مختلط (بالموافقة)' },
            { id: 'private', label: 'خاص (بالكود فقط)' },
          ].map(t => (
            <TouchableOpacity
              key={t.id}
              style={[styles.typeBtn, matchType === t.id && styles.typeBtnActive]}
              onPress={() => setMatchType(t.id as any)}
            >
              <Text style={[styles.typeText, matchType === t.id && styles.typeTextActive]}>
                {t.label}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Required players stepper */}
        <Text style={styles.sectionTitle}>عدد اللاعبين المطلوب لاكتمال الماتش</Text>
        <View style={styles.stepperBox}>
          <TouchableOpacity
            style={styles.stepBtn}
            onPress={() => setRequiredPlayers(Math.max(2, requiredPlayers - 2))}
          >
            <Text style={styles.stepBtnText}>-</Text>
          </TouchableOpacity>
          <Text style={styles.stepVal}>{requiredPlayers} لاعبين</Text>
          <TouchableOpacity
            style={styles.stepBtn}
            onPress={() => setRequiredPlayers(requiredPlayers + 2)}
          >
            <Text style={styles.stepBtnText}>+</Text>
          </TouchableOpacity>
        </View>

        {/* Submit CTA */}
        <TouchableOpacity
          style={[styles.submitBtn, (!selectedSlot || creating) && styles.submitBtnDisabled]}
          onPress={handleCreateLobby}
          disabled={!selectedSlot || creating}
          activeOpacity={0.8}
        >
          {creating ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.submitBtnText}>إنشاء غرفة اللوبي وبدء الدفع</Text>
          )}
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
  header: {
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
  title: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.textPrimary,
    textAlign: 'right',
  },
  dateRow: {
    gap: 8,
  },
  dateChip: {
    paddingVertical: 10,
    paddingHorizontal: 20,
    borderRadius: 10,
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.border,
  },
  dateChipActive: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  dateDay: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  dateTextActive: {
    color: colors.textWhite,
  },
  chipsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  chip: {
    paddingVertical: 8,
    paddingHorizontal: 14,
    borderRadius: 8,
    backgroundColor: colors.surface,
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
  slotsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  slotCard: {
    width: '48%',
    backgroundColor: colors.surface,
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
  },
  slotCardActive: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryMuted,
  },
  slotTime: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  slotTimeActive: {
    color: colors.primaryDark,
  },
  slotPrice: {
    fontSize: 12,
    color: colors.textMuted,
    marginTop: 4,
  },
  slotPriceActive: {
    color: colors.primaryDark,
    fontWeight: '700',
  },
  emptyBox: {
    backgroundColor: colors.surface,
    padding: 20,
    borderRadius: 10,
    alignItems: 'center',
  },
  emptyText: {
    color: colors.textMuted,
    fontSize: 13,
  },
  typeRow: {
    gap: 8,
  },
  typeBtn: {
    backgroundColor: colors.surface,
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.border,
  },
  typeBtnActive: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryMuted,
  },
  typeText: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textSecondary,
    textAlign: 'right',
  },
  typeTextActive: {
    color: colors.primaryDark,
    fontWeight: '700',
  },
  stepperBox: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: colors.surface,
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.border,
  },
  stepBtn: {
    width: 36,
    height: 36,
    borderRadius: 8,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepBtnText: {
    color: colors.textWhite,
    fontSize: 20,
    fontWeight: '800',
  },
  stepVal: {
    fontSize: 16,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  submitBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 10,
    marginBottom: 20,
  },
  submitBtnDisabled: {
    opacity: 0.5,
  },
  submitBtnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
});
