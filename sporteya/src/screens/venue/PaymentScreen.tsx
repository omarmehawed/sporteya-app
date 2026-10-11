import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { colors } from '../../theme/colors';

export const PaymentScreen = ({ route, navigation }: any) => {
  const { lobbyId, amount } = route.params;
  const [loading, setLoading] = useState(false);

  const handleSimulatePayment = async () => {
    try {
      setLoading(true);

      // 1. Initiate payment (Correction G)
      const initRes = await apiClient.post('/payments/initiate', {
        lobby_id: lobbyId,
      });

      const { payment } = initRes.data;

      // 2. Call Paymob callback to complete webhook processing
      await apiClient.post('/payments/callback', {
        obj: {
          id: 'test_txn_' + Date.now(),
          success: true,
          amount_cents: (payment.amount * 100),
          order: {
            merchant_order_id: payment.id,
          },
        },
      });

      Alert.alert('نجاح الدفع', 'تم سداد نصيبك في المباراة بنجاح عبر Paymob! ✓', [
        {
          text: 'العودة لغرفة الماتش',
          onPress: () => navigation.replace('MatchLobby', { lobbyId }),
        },
      ]);
    } catch (err: any) {
      console.warn('Payment flow error:', err);
      Alert.alert('خطأ في الدفع', err.response?.data?.message || 'تعذر استكمال عملية الدفع');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <View style={styles.card}>
          <Text style={styles.icon}>💳</Text>
          <Text style={styles.title}>بوابة دفع Paymob</Text>
          <Text style={styles.sub}>سداد نصيبك لحجز الملعب وتأكيد مشاركتك في الماتش</Text>

          <View style={styles.amountBox}>
            <Text style={styles.amountLabel}>المبلغ المطلوب سداده:</Text>
            <Text style={styles.amountVal}>{amount || 150} ج.م</Text>
          </View>

          <View style={styles.methodsBox}>
            <Text style={styles.methodItem}>• بطاقات الائتمان والخصم المباشر (Visa / Mastercard)</Text>
            <Text style={styles.methodItem}>• محافظ الهاتف الذكية (Vodafone Cash, InstaPay, etc.)</Text>
          </View>

          <TouchableOpacity
            style={styles.payBtn}
            onPress={handleSimulatePayment}
            disabled={loading}
            activeOpacity={0.8}
          >
            {loading ? (
              <ActivityIndicator color={colors.textWhite} />
            ) : (
              <Text style={styles.payBtnText}>تأكيد وسداد {amount || 150} ج.م</Text>
            )}
          </TouchableOpacity>

          <TouchableOpacity style={styles.cancelBtn} onPress={() => navigation.goBack()} disabled={loading}>
            <Text style={styles.cancelText}>إلغاء والعودة</Text>
          </TouchableOpacity>
        </View>
      </View>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  content: {
    flex: 1,
    padding: 24,
    justifyContent: 'center',
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 20,
    padding: 24,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
  },
  icon: {
    fontSize: 48,
    marginBottom: 12,
  },
  title: {
    fontSize: 22,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 6,
  },
  sub: {
    fontSize: 13,
    color: colors.textMuted,
    textAlign: 'center',
    marginBottom: 24,
    lineHeight: 18,
  },
  amountBox: {
    width: '100%',
    backgroundColor: colors.surfaceSubtle,
    borderRadius: 12,
    padding: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
    borderWidth: 1,
    borderColor: colors.border,
  },
  amountLabel: {
    fontSize: 14,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  amountVal: {
    fontSize: 22,
    fontWeight: '900',
    color: colors.primaryDark,
  },
  methodsBox: {
    width: '100%',
    marginBottom: 24,
    gap: 6,
  },
  methodItem: {
    fontSize: 12,
    color: colors.textSecondary,
    textAlign: 'right',
  },
  payBtn: {
    backgroundColor: colors.primary,
    width: '100%',
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginBottom: 12,
  },
  payBtnText: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '700',
  },
  cancelBtn: {
    paddingVertical: 8,
  },
  cancelText: {
    color: colors.textMuted,
    fontSize: 14,
    fontWeight: '600',
  },
});
