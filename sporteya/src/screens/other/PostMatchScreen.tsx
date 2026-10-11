import React, { useState } from 'react';
import { View, Text, StyleSheet, TextInput, TouchableOpacity, ScrollView, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { colors } from '../../theme/colors';

export const PostMatchScreen = ({ route, navigation }: any) => {
  const { lobbyId, venueId } = route.params || {};
  const [teamAScore, setTeamAScore] = useState('5');
  const [teamBScore, setTeamBScore] = useState('3');
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async () => {
    try {
      setSubmitting(true);

      if (lobbyId) {
        await apiClient.post('/matches/result', {
          lobby_id: lobbyId,
          team_a_score: parseInt(teamAScore, 10),
          team_b_score: parseInt(teamBScore, 10),
        });
      }

      if (venueId) {
        await apiClient.post(`/venues/${venueId}/reviews`, {
          rating,
          comment: comment.trim() || null,
        });
      }

      Alert.alert('شكراً لك!', 'تم تسجيل نتيجة المباراة وتقييمك للملعب بنجاح ✓', [
        { text: 'الرئيسية', onPress: () => navigation.navigate('Home') },
      ]);
    } catch (err: any) {
      Alert.alert('خطأ', err.response?.data?.message || 'تعذر تسجيل النتيجة أو التقييم');
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
          <Text style={styles.title}>تسجيل نتيجة الماتش والتقييم</Text>
        </View>

        {/* Score Input Card */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>نتيجة المباراة</Text>
          <View style={styles.scoreRow}>
            <View style={styles.teamBox}>
              <Text style={styles.teamLabel}>الفريق أ</Text>
              <TextInput
                style={styles.scoreInput}
                keyboardType="number-pad"
                value={teamAScore}
                onChangeText={setTeamAScore}
              />
            </View>

            <Text style={styles.vsText}>VS</Text>

            <View style={styles.teamBox}>
              <Text style={styles.teamLabel}>الفريق ب</Text>
              <TextInput
                style={styles.scoreInput}
                keyboardType="number-pad"
                value={teamBScore}
                onChangeText={setTeamBScore}
              />
            </View>
          </View>
        </View>

        {/* Venue Rating Card */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>تقييم الملعب والتجربة</Text>
          <View style={styles.starsRow}>
            {[1, 2, 3, 4, 5].map((star) => (
              <TouchableOpacity key={star} onPress={() => setRating(star)}>
                <Text style={[styles.star, star <= rating && styles.starActive]}>★</Text>
              </TouchableOpacity>
            ))}
          </View>

          <Text style={styles.inputLabel}>تعليقك على الملعب (اختياري):</Text>
          <TextInput
            style={styles.commentInput}
            multiline
            numberOfLines={3}
            placeholder="أرضية الملعب، الإضاءة، تعامل الطاقم..."
            value={comment}
            onChangeText={setComment}
            textAlign="right"
          />
        </View>

        <TouchableOpacity
          style={styles.btn}
          onPress={handleSubmit}
          disabled={submitting}
          activeOpacity={0.8}
        >
          {submitting ? (
            <ActivityIndicator color={colors.textWhite} />
          ) : (
            <Text style={styles.btnText}>إرسال النتيجة والتقييم</Text>
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
  card: {
    backgroundColor: colors.surface,
    borderRadius: 16,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.border,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: colors.textPrimary,
    textAlign: 'right',
    marginBottom: 16,
  },
  scoreRow: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'center',
  },
  teamBox: {
    alignItems: 'center',
    gap: 6,
  },
  teamLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  scoreInput: {
    width: 70,
    height: 60,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: 12,
    textAlign: 'center',
    fontSize: 24,
    fontWeight: '900',
    backgroundColor: colors.surfaceSubtle,
  },
  vsText: {
    fontSize: 18,
    fontWeight: '900',
    color: colors.textMuted,
  },
  starsRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 10,
    marginBottom: 16,
  },
  star: {
    fontSize: 34,
    color: colors.border,
  },
  starActive: {
    color: colors.accent,
  },
  inputLabel: {
    fontSize: 13,
    color: colors.textSecondary,
    marginBottom: 6,
    textAlign: 'right',
  },
  commentInput: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    padding: 12,
    fontSize: 14,
    minHeight: 80,
    textAlignVertical: 'top',
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 8,
  },
  btnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
});
