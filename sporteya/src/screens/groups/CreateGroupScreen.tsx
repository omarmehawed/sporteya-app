import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TextInput, TouchableOpacity, ActivityIndicator, Alert, ScrollView } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { colors } from '../../theme/colors';

export const CreateGroupScreen = ({ navigation }: any) => {
  const [name, setName] = useState('');
  const [sports, setSports] = useState<any[]>([]);
  const [selectedSportId, setSelectedSportId] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    apiClient.get('/sports').then(res => {
      const list = res.data.sports || [];
      setSports(list);
      if (list.length > 0) setSelectedSportId(list[0].id);
    });
  }, []);

  const handleCreate = async () => {
    if (!name.trim()) {
      Alert.alert('تنبيه', 'يرجى إدخال اسم المجموعة');
      return;
    }

    try {
      setSubmitting(true);
      await apiClient.post('/booking-groups', {
        name: name.trim(),
        sport_id: selectedSportId,
      });
      Alert.alert('تم', 'تم إنشاء مجموعة اللعب بنجاح');
      navigation.goBack();
    } catch (err: any) {
      Alert.alert('خطأ', err.response?.data?.message || 'تعذر إنشاء المجموعة');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.header}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Text style={styles.backBtnText}>→</Text>
          </TouchableOpacity>
          <Text style={styles.title}>إنشاء مجموعة جديدة</Text>
        </View>

        <Text style={styles.label}>اسم المجموعة *</Text>
        <TextInput
          style={styles.input}
          placeholder="مثال: شلة الجمعة، أبطال المعادي"
          value={name}
          onChangeText={setName}
          textAlign="right"
        />

        <Text style={styles.label}>الرياضة الأساسية للمجموعة *</Text>
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

        <TouchableOpacity
          style={styles.btn}
          onPress={handleCreate}
          disabled={submitting}
          activeOpacity={0.8}
        >
          {submitting ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.btnText}>تأكيد وإنشاء المجموعة</Text>
          )}
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.surface,
  },
  scroll: {
    padding: 20,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 24,
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
  label: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.textSecondary,
    marginBottom: 8,
    textAlign: 'right',
  },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontSize: 15,
    marginBottom: 20,
  },
  chipsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 32,
  },
  chip: {
    paddingVertical: 8,
    paddingHorizontal: 14,
    borderRadius: 8,
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
    fontWeight: '600',
    color: colors.textSecondary,
  },
  chipTextActive: {
    color: colors.textWhite,
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
  },
  btnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
});
