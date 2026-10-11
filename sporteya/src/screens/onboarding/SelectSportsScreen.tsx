import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, FlatList, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { useLanguage } from '../../context/LanguageContext';
import { colors } from '../../theme/colors';

export const SelectSportsScreen = ({ navigation }: any) => {
  const { t, isRTL } = useLanguage();
  const [sports, setSports] = useState<any[]>([]);
  const [selectedSports, setSelectedSports] = useState<{ [id: string]: 'beginner' | 'intermediate' | 'advanced' }>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    fetchSports();
  }, []);

  const fetchSports = async () => {
    try {
      const res = await apiClient.get('/sports');
      setSports(res.data.sports || []);
    } catch (err) {
      console.warn('Failed to fetch sports:', err);
    } finally {
      setLoading(false);
    }
  };

  const toggleSport = (sportId: string) => {
    setSelectedSports(prev => {
      const copy = { ...prev };
      if (copy[sportId]) {
        delete copy[sportId];
      } else {
        copy[sportId] = 'intermediate';
      }
      return copy;
    });
  };

  const setLevel = (sportId: string, level: 'beginner' | 'intermediate' | 'advanced') => {
    setSelectedSports(prev => ({
      ...prev,
      [sportId]: level,
    }));
  };

  const handleContinue = async () => {
    const sportsList = Object.entries(selectedSports).map(([sport_id, skill_level]) => ({
      sport_id,
      skill_level,
    }));

    if (sportsList.length === 0) {
      Alert.alert(t.error, isRTL ? 'يرجى اختيار رياضة واحدة على الأقل' : 'Please select at least one sport');
      return;
    }

    try {
      setSaving(true);
      await apiClient.post('/onboarding/sports', { sports: sportsList });
      navigation.navigate('Username');
    } catch (err: any) {
      Alert.alert(t.error, err.response?.data?.message || t.onboardingError);
    } finally {
      setSaving(false);
    }
  };

  const getLevelLabel = (lvl: 'beginner' | 'intermediate' | 'advanced') => {
    if (lvl === 'beginner') return isRTL ? 'مبتدئ' : 'Beginner';
    if (lvl === 'intermediate') return isRTL ? 'متوسط' : 'Intermediate';
    return isRTL ? 'محترف' : 'Advanced';
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={[styles.title, { textAlign: isRTL ? 'right' : 'left' }]}>{t.chooseSports}</Text>
        <Text style={[styles.subtitle, { textAlign: isRTL ? 'right' : 'left' }]}>{t.chooseSportsDesc}</Text>
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={colors.primary} style={{ flex: 1 }} />
      ) : (
        <FlatList
          data={sports}
          keyExtractor={(item) => item.id}
          contentContainerStyle={styles.listContent}
          renderItem={({ item }) => {
            const isSelected = !!selectedSports[item.id];
            const sportName = isRTL ? item.name_ar : (item.name_en || item.name_ar);
            return (
              <View style={[styles.sportCard, isSelected && styles.sportCardActive]}>
                <TouchableOpacity
                  style={styles.sportHeader}
                  onPress={() => toggleSport(item.id)}
                  activeOpacity={0.7}
                >
                  <Text style={styles.sportName}>{sportName}</Text>
                  <View style={[styles.checkbox, isSelected && styles.checkboxActive]}>
                    {isSelected && <Text style={styles.checkmark}>✓</Text>}
                  </View>
                </TouchableOpacity>

                {isSelected && (
                  <View style={styles.skillRow}>
                    {(['beginner', 'intermediate', 'advanced'] as const).map((lvl) => (
                      <TouchableOpacity
                        key={lvl}
                        style={[
                          styles.skillChip,
                          selectedSports[item.id] === lvl && styles.skillChipActive,
                        ]}
                        onPress={() => setLevel(item.id, lvl)}
                      >
                        <Text
                          style={[
                            styles.skillText,
                            selectedSports[item.id] === lvl && styles.skillTextActive,
                          ]}
                        >
                          {getLevelLabel(lvl)}
                        </Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                )}
              </View>
            );
          }}
        />
      )}

      <View style={styles.footer}>
        <TouchableOpacity
          style={styles.submitBtn}
          onPress={handleContinue}
          disabled={saving}
          activeOpacity={0.8}
        >
          {saving ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.submitBtnText}>{t.continueBtn}</Text>
          )}
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  header: {
    padding: 24,
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderColor: colors.border,
  },
  title: {
    fontSize: 22,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 6,
  },
  subtitle: {
    fontSize: 14,
    color: colors.textMuted,
  },
  listContent: {
    padding: 20,
    gap: 12,
  },
  sportCard: {
    backgroundColor: colors.surface,
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
  },
  sportCardActive: {
    borderColor: colors.primary,
    backgroundColor: 'rgba(16, 185, 129, 0.04)',
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
  checkbox: {
    width: 24,
    height: 24,
    borderRadius: 6,
    borderWidth: 2,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkboxActive: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  checkmark: {
    color: colors.textWhite,
    fontSize: 14,
    fontWeight: '800',
  },
  skillRow: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 14,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  skillChip: {
    flex: 1,
    paddingVertical: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    backgroundColor: colors.surfaceSubtle,
  },
  skillChipActive: {
    borderColor: colors.secondary,
    backgroundColor: 'rgba(59, 130, 246, 0.12)',
  },
  skillText: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  skillTextActive: {
    color: colors.secondary,
  },
  footer: {
    padding: 20,
    backgroundColor: colors.surface,
    borderTopWidth: 1,
    borderTopColor: colors.border,
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
