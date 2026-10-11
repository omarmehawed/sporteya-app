import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, FlatList, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { colors } from '../../theme/colors';
import { ar } from '../../i18n/ar';

export const SelectSportsScreen = ({ navigation }: any) => {
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
      Alert.alert('تنبيه', 'يرجى اختيار رياضة واحدة على الأقل');
      return;
    }

    try {
      setSaving(true);
      await apiClient.post('/onboarding/sports', { sports: sportsList });
      navigation.navigate('Username');
    } catch (err: any) {
      Alert.alert('خطأ', err.response?.data?.message || 'تعذر حفظ الرياضات المختارة');
    } finally {
      setSaving(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>{ar.chooseSports}</Text>
        <Text style={styles.subtitle}>{ar.chooseSportsDesc}</Text>
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
            return (
              <View style={[styles.sportCard, isSelected && styles.sportCardActive]}>
                <TouchableOpacity
                  style={styles.sportHeader}
                  onPress={() => toggleSport(item.id)}
                  activeOpacity={0.7}
                >
                  <Text style={styles.sportName}>{item.name_ar}</Text>
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
                          {lvl === 'beginner' ? 'مبتدئ' : lvl === 'intermediate' ? 'متوسط' : 'محترف'}
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
          style={styles.btn}
          onPress={handleContinue}
          disabled={saving}
          activeOpacity={0.8}
        >
          {saving ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.btnText}>{ar.continueBtn}</Text>
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
    padding: 20,
    borderBottomWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surface,
  },
  title: {
    fontSize: 22,
    fontWeight: '800',
    color: colors.textPrimary,
    textAlign: 'right',
  },
  subtitle: {
    fontSize: 14,
    color: colors.textMuted,
    textAlign: 'right',
    marginTop: 4,
  },
  listContent: {
    padding: 16,
    gap: 12,
  },
  sportCard: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    padding: 16,
    borderWidth: 1.5,
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
    fontSize: 17,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  checkbox: {
    width: 24,
    height: 24,
    borderRadius: 6,
    borderWidth: 1.5,
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
    fontWeight: '800',
    fontSize: 14,
  },
  skillRow: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  skillChip: {
    flex: 1,
    paddingVertical: 6,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    backgroundColor: colors.surface,
  },
  skillChipActive: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  skillText: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  skillTextActive: {
    color: colors.textWhite,
  },
  footer: {
    padding: 16,
    backgroundColor: colors.surface,
    borderTopWidth: 1,
    borderColor: colors.border,
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 15,
    borderRadius: 10,
    alignItems: 'center',
  },
  btnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
});
