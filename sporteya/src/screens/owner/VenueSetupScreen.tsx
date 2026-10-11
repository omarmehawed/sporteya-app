import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, TextInput, ScrollView, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useLanguage } from '../../context/LanguageContext';
import { colors } from '../../theme/colors';

export const VenueSetupScreen = () => {
  const { setSetupComplete, refreshUser } = useAuth();
  const { t, isRTL } = useLanguage();
  const [step, setStep] = useState(1);
  const [sportsList, setSportsList] = useState<any[]>([]);
  const [loadingSports, setLoadingSports] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  // Step 1: Basic Info
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [address, setAddress] = useState('');
  const [city, setCity] = useState('Cairo');
  const [phone, setPhone] = useState('');
  const [hasLighting, setHasLighting] = useState(true);
  const [hasChangingRoom, setHasChangingRoom] = useState(true);
  const [hasParking, setHasParking] = useState(true);

  // Step 2: Sports
  const [selectedSports, setSelectedSports] = useState<{ [id: string]: { court_count: number; players_per_team: number } }>({});

  useEffect(() => {
    apiClient.get('/sports')
      .then(res => setSportsList(res.data.sports || []))
      .catch(err => console.warn('Sports fetch error:', err))
      .finally(() => setLoadingSports(false));
  }, []);

  const toggleSport = (sportId: string) => {
    setSelectedSports(prev => {
      const copy = { ...prev };
      if (copy[sportId]) {
        delete copy[sportId];
      } else {
        copy[sportId] = { court_count: 1, players_per_team: 5 };
      }
      return copy;
    });
  };

  const updateCourtCount = (sportId: string, delta: number) => {
    setSelectedSports(prev => {
      if (!prev[sportId]) return prev;
      const count = Math.max(1, prev[sportId].court_count + delta);
      return { ...prev, [sportId]: { ...prev[sportId], court_count: count } };
    });
  };

  const handleSubmit = async () => {
    if (!name.trim() || !address.trim() || !city.trim()) {
      Alert.alert('تنبيه', 'يرجى إكمال بيانات الملعب الأساسية');
      setStep(1);
      return;
    }

    const sportsPayload = Object.entries(selectedSports).map(([sport_id, config]) => ({
      sport_id,
      court_count: config.court_count,
      players_per_team: config.players_per_team,
    }));

    if (sportsPayload.length === 0) {
      Alert.alert('تنبيه', 'يرجى تحديد رياضة واحدة على الأقل يدعمها الملعب');
      setStep(2);
      return;
    }

    try {
      setSubmitting(true);
      await apiClient.post('/venues', {
        name: name.trim(),
        description: description.trim() || null,
        address: address.trim(),
        city: city.trim(),
        latitude: 30.0444,
        longitude: 31.2357,
        phone: phone.trim() || null,
        has_lighting: hasLighting,
        has_changing_room: hasChangingRoom,
        has_parking: hasParking,
        sports: sportsPayload,
      });

      // Atomically mark setup complete (Correction B)
      setSetupComplete(true);
      await refreshUser();
    } catch (err: any) {
      Alert.alert('خطأ', err.response?.data?.message || 'تعذر تسجيل الملعب');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.topBar}>
        <Text style={styles.topTitle}>{t.venueSetupTitle}</Text>
        <View style={styles.stepsIndicator}>
          {[1, 2, 3].map(s => (
            <View key={s} style={[styles.stepDot, step >= s && styles.stepDotActive]} />
          ))}
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        {step === 1 && (
          <View>
            <Text style={styles.stepTitle}>1. {t.venueSetupStep1}</Text>

            <Text style={[styles.label, { textAlign: isRTL ? 'right' : 'left' }]}>{t.venueName} *</Text>
            <TextInput
              style={[styles.input, { textAlign: isRTL ? 'right' : 'left' }]}
              placeholder={isRTL ? "مثال: ملاعب النخبة الرياضية" : "e.g. Elite Sports Arena"}
              value={name}
              onChangeText={setName}
            />

            <Text style={[styles.label, { textAlign: isRTL ? 'right' : 'left' }]}>{t.venueCity} *</Text>
            <TextInput
              style={[styles.input, { textAlign: isRTL ? 'right' : 'left' }]}
              placeholder={isRTL ? "مثال: القاهرة" : "e.g. Cairo"}
              value={city}
              onChangeText={setCity}
            />

            <Text style={[styles.label, { textAlign: isRTL ? 'right' : 'left' }]}>{t.venueAddress} *</Text>
            <TextInput
              style={[styles.input, { textAlign: isRTL ? 'right' : 'left' }]}
              placeholder={isRTL ? "الشارع، المنطقة، أقرب علامة مميزة" : "Street, area, nearest landmark"}
              value={address}
              onChangeText={setAddress}
            />

            <Text style={[styles.label, { textAlign: isRTL ? 'right' : 'left' }]}>{t.venuePhone}</Text>
            <TextInput
              style={[styles.input, { textAlign: isRTL ? 'right' : 'left' }]}
              placeholder="+2010xxxxxxxx"
              keyboardType="phone-pad"
              value={phone}
              onChangeText={setPhone}
            />

            <Text style={[styles.label, { textAlign: isRTL ? 'right' : 'left' }]}>{isRTL ? 'المرافق والخدمات' : 'Amenities & Facilities'}</Text>
            <View style={styles.amenityRow}>
              <TouchableOpacity
                style={[styles.amenityChip, hasLighting && styles.amenityChipActive]}
                onPress={() => setHasLighting(!hasLighting)}
              >
                <Text style={[styles.amenityText, hasLighting && styles.amenityTextActive]}>
                  💡 {t.lighting}
                </Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.amenityChip, hasChangingRoom && styles.amenityChipActive]}
                onPress={() => setHasChangingRoom(!hasChangingRoom)}
              >
                <Text style={[styles.amenityText, hasChangingRoom && styles.amenityTextActive]}>
                  👕 {t.changingRooms}
                </Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.amenityChip, hasParking && styles.amenityChipActive]}
                onPress={() => setHasParking(!hasParking)}
              >
                <Text style={[styles.amenityText, hasParking && styles.amenityTextActive]}>
                  🚗 {t.parking}
                </Text>
              </TouchableOpacity>
            </View>

            <TouchableOpacity style={styles.nextBtn} onPress={() => setStep(2)}>
              <Text style={styles.nextBtnText}>{isRTL ? 'التالي: الرياضات والملاعب ←' : 'Next: Sports & Courts →'}</Text>
            </TouchableOpacity>
          </View>
        )}

        {step === 2 && (
          <View>
            <Text style={styles.stepTitle}>2. {t.venueSetupStep2}</Text>
            <Text style={styles.hint}>{isRTL ? 'حدد الرياضات وعدد الملاعب المتوفرة لكل رياضة' : 'Select sports and number of available courts'}</Text>

            {loadingSports ? (
              <ActivityIndicator color={colors.primary} />
            ) : (
              sportsList.map(sport => {
                const isSelected = !!selectedSports[sport.id];
                const sportName = isRTL ? sport.name_ar : (sport.name_en || sport.name_ar);
                return (
                  <View key={sport.id} style={[styles.sportCard, isSelected && styles.sportCardActive]}>
                    <TouchableOpacity
                      style={styles.sportHeader}
                      onPress={() => toggleSport(sport.id)}
                    >
                      <Text style={styles.sportName}>{sportName}</Text>
                      <Text style={{ fontSize: 18 }}>{isSelected ? '✓' : '+'}</Text>
                    </TouchableOpacity>

                    {isSelected && (
                      <View style={styles.stepperRow}>
                        <Text style={styles.stepperLabel}>{isRTL ? 'عدد الملاعب المتاحة:' : 'Available courts:'}</Text>
                        <View style={styles.stepper}>
                          <TouchableOpacity
                            style={styles.stepperBtn}
                            onPress={() => updateCourtCount(sport.id, -1)}
                          >
                            <Text style={styles.stepperBtnText}>-</Text>
                          </TouchableOpacity>
                          <Text style={styles.stepperVal}>{selectedSports[sport.id].court_count}</Text>
                          <TouchableOpacity
                            style={styles.stepperBtn}
                            onPress={() => updateCourtCount(sport.id, 1)}
                          >
                            <Text style={styles.stepperBtnText}>+</Text>
                          </TouchableOpacity>
                        </View>
                      </View>
                    )}
                  </View>
                );
              })
            )}

            <View style={styles.navRow}>
              <TouchableOpacity style={styles.prevBtn} onPress={() => setStep(1)}>
                <Text style={styles.prevBtnText}>{isRTL ? '→ السابق' : '← Previous'}</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.nextBtn} onPress={() => setStep(3)}>
                <Text style={styles.nextBtnText}>{isRTL ? 'التالي: إنهاء التسجيل ←' : 'Next: Review & Submit →'}</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}

        {step === 3 && (
          <View>
            <Text style={styles.stepTitle}>3. {t.venueSetupStep3}</Text>
            <Text style={styles.hint}>
              {isRTL
                ? 'تم إعداد بيانات ملعبك بنجاح! بمجرد الإرسال سيقوم فريق سبورتيا بمراجعة وتوثيق الملعب، ويمكنك إدارة الأسعار وأوقات العمل من لوحة التحكم.'
                : 'Your venue details are ready! Once submitted, the Sporteya team will review and verify your venue.'}
            </Text>

            <View style={styles.summaryBox}>
              <Text style={styles.summaryItem}>🏟️ {name || (isRTL ? 'الملعب' : 'Venue')}</Text>
              <Text style={styles.summaryItem}>📍 {city} - {address}</Text>
              <Text style={styles.summaryItem}>
                ⚽ {Object.keys(selectedSports).length} {isRTL ? 'رياضات محددة' : 'sports selected'}
              </Text>
            </View>

            <TouchableOpacity
              style={styles.submitBtn}
              onPress={handleSubmit}
              disabled={submitting}
            >
              {submitting ? (
                <ActivityIndicator color={colors.textWhite} />
              ) : (
                <Text style={styles.submitBtnText}>{t.completeVenueSetup}</Text>
              )}
            </TouchableOpacity>

            <TouchableOpacity style={styles.prevBtn} onPress={() => setStep(2)}>
              <Text style={styles.prevBtnText}>{isRTL ? '→ العودة لتعديل الرياضات' : '← Back to edit sports'}</Text>
            </TouchableOpacity>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.surface,
  },
  topBar: {
    padding: 16,
    borderBottomWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
  },
  topTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 8,
  },
  stepsIndicator: {
    flexDirection: 'row',
    gap: 8,
  },
  stepDot: {
    width: 24,
    height: 6,
    borderRadius: 3,
    backgroundColor: colors.border,
  },
  stepDotActive: {
    backgroundColor: colors.primary,
  },
  scroll: {
    padding: 20,
  },
  stepTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
    textAlign: 'right',
    marginBottom: 16,
  },
  hint: {
    fontSize: 14,
    color: colors.textMuted,
    textAlign: 'right',
    marginBottom: 20,
    lineHeight: 22,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textSecondary,
    marginBottom: 6,
    textAlign: 'right',
  },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontSize: 15,
    marginBottom: 16,
  },
  amenityRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 24,
  },
  amenityChip: {
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surfaceSubtle,
  },
  amenityChipActive: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryMuted,
  },
  amenityText: {
    fontSize: 13,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  amenityTextActive: {
    color: colors.primaryDark,
  },
  sportCard: {
    backgroundColor: colors.surfaceSubtle,
    borderRadius: 10,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: colors.border,
  },
  sportCardActive: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryMuted,
  },
  sportHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  sportName: {
    fontSize: 16,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  stepperRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 10,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  stepperLabel: {
    fontSize: 13,
    color: colors.textSecondary,
  },
  stepper: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  stepperBtn: {
    width: 32,
    height: 32,
    borderRadius: 6,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepperBtnText: {
    color: colors.textWhite,
    fontSize: 18,
    fontWeight: '800',
  },
  stepperVal: {
    fontSize: 16,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  navRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 20,
    gap: 12,
  },
  nextBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 14,
    borderRadius: 10,
    alignItems: 'center',
    flex: 1,
  },
  nextBtnText: {
    color: colors.textWhite,
    fontSize: 15,
    fontWeight: '700',
  },
  prevBtn: {
    backgroundColor: colors.surfaceSubtle,
    borderWidth: 1,
    borderColor: colors.border,
    paddingVertical: 14,
    paddingHorizontal: 20,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 12,
  },
  prevBtnText: {
    color: colors.textSecondary,
    fontSize: 14,
    fontWeight: '600',
  },
  summaryBox: {
    backgroundColor: colors.surfaceSubtle,
    borderRadius: 12,
    padding: 16,
    marginBottom: 24,
    gap: 8,
    borderWidth: 1,
    borderColor: colors.border,
  },
  summaryItem: {
    fontSize: 15,
    fontWeight: '600',
    color: colors.textPrimary,
    textAlign: 'right',
  },
  submitBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
  },
  submitBtnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
});
