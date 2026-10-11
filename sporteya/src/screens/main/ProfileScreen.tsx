import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { colors } from '../../theme/colors';

export const ProfileScreen = ({ navigation }: any) => {
  const { user, logout } = useAuth();
  const [profile, setProfile] = useState<any>(null);
  const [stats, setStats] = useState<any>({ matches_played: 0, attendance_rate: 100 });
  const [playerStatus, setPlayerStatus] = useState('available');
  const [loading, setLoading] = useState(true);

  const fetchProfile = async () => {
    try {
      const res = await apiClient.get('/player/me');
      setProfile(res.data.player);
      setStats(res.data.stats || { matches_played: 0, attendance_rate: 100 });
      setPlayerStatus(res.data.player?.player_status || 'available');
    } catch (err) {
      console.warn('Error fetching profile:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProfile();
  }, []);

  const handleStatusChange = async (newStatus: string) => {
    try {
      setPlayerStatus(newStatus);
      await apiClient.put('/player/me/status', { player_status: newStatus });
    } catch (err) {
      console.warn('Error updating player status:', err);
      Alert.alert('خطأ', 'تعذر تحديث حالة اللاعب');
    }
  };

  const handleLogout = () => {
    Alert.alert('تسجيل الخروج', 'هل أنت متأكد من تسجيل الخروج؟', [
      { text: 'إلغاء', style: 'cancel' },
      { text: 'خروج', style: 'destructive', onPress: logout },
    ]);
  };

  if (loading) {
    return (
      <SafeAreaView style={[styles.container, { justifyContent: 'center', alignItems: 'center' }]}>
        <ActivityIndicator size="large" color={colors.primary} />
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll}>
        {/* Profile Header */}
        <View style={styles.headerCard}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>
              {user?.name ? user.name.charAt(0).toUpperCase() : 'S'}
            </Text>
          </View>

          <Text style={styles.name}>{user?.name}</Text>
          <Text style={styles.username}>@{profile?.username || user?.email?.split('@')[0]}</Text>
          <Text style={styles.city}>📍 {profile?.city || 'القاهرة، مصر'}</Text>
        </View>

        {/* Stats Grid */}
        <View style={styles.statsGrid}>
          <View style={styles.statCard}>
            <Text style={styles.statVal}>{stats.matches_played}</Text>
            <Text style={styles.statLabel}>المباريات الملعوبة</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={[styles.statVal, { color: colors.primary }]}>{stats.attendance_rate}%</Text>
            <Text style={styles.statLabel}>نسبة الحضور والالتزام</Text>
          </View>
        </View>

        {/* Player Status Picker */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>حالتك الرياضية الحالية</Text>
          <View style={styles.statusRow}>
            {[
              { id: 'available', label: 'جاهز للعب 🟢' },
              { id: 'playing', label: 'في ماتش ⚽' },
              { id: 'booked', label: 'حاجز قريباً 🎟️' },
              { id: 'resting', label: 'استراحة 💤' },
            ].map((s) => (
              <TouchableOpacity
                key={s.id}
                style={[styles.statusChip, playerStatus === s.id && styles.statusChipActive]}
                onPress={() => handleStatusChange(s.id)}
              >
                <Text style={[styles.statusChipText, playerStatus === s.id && styles.statusChipTextActive]}>
                  {s.label}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        {/* Quick Links */}
        <View style={styles.linksCard}>
          <TouchableOpacity
            style={styles.linkRow}
            onPress={() => navigation.navigate('BookingGroups')}
          >
            <Text style={styles.linkArrow}>←</Text>
            <Text style={styles.linkTitle}>👥 مجموعات اللعب الخاصة بي (Booking Groups)</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.linkRow}
            onPress={() => navigation.navigate('EditProfile')}
          >
            <Text style={styles.linkArrow}>←</Text>
            <Text style={styles.linkTitle}>✏️ تعديل بيانات الحساب واسم المستخدم</Text>
          </TouchableOpacity>
        </View>

        {/* Logout Button */}
        <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout} activeOpacity={0.8}>
          <Text style={styles.logoutText}>🚪 تسجيل الخروج</Text>
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
    padding: 20,
    gap: 16,
  },
  headerCard: {
    backgroundColor: colors.surface,
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  avatar: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  avatarText: {
    fontSize: 32,
    fontWeight: '900',
    color: colors.textWhite,
  },
  name: {
    fontSize: 20,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  username: {
    fontSize: 14,
    color: colors.primaryDark,
    fontWeight: '700',
    marginTop: 2,
  },
  city: {
    fontSize: 13,
    color: colors.textMuted,
    marginTop: 6,
  },
  statsGrid: {
    flexDirection: 'row',
    gap: 12,
  },
  statCard: {
    flex: 1,
    backgroundColor: colors.surface,
    padding: 16,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
  },
  statVal: {
    fontSize: 24,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  statLabel: {
    fontSize: 12,
    color: colors.textMuted,
    fontWeight: '600',
    marginTop: 4,
  },
  sectionCard: {
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
    textAlign: 'right',
    marginBottom: 12,
  },
  statusRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  statusChip: {
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surfaceSubtle,
  },
  statusChipActive: {
    backgroundColor: colors.primaryLight,
    borderColor: colors.primary,
  },
  statusChipText: {
    fontSize: 13,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  statusChipTextActive: {
    color: colors.primaryDark,
    fontWeight: '700',
  },
  linksCard: {
    backgroundColor: colors.surface,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
  },
  linkRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  linkTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  linkArrow: {
    fontSize: 16,
    color: colors.textMuted,
  },
  logoutBtn: {
    backgroundColor: colors.dangerLight,
    paddingVertical: 14,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 8,
    marginBottom: 20,
  },
  logoutText: {
    color: colors.danger,
    fontSize: 15,
    fontWeight: '700',
  },
});
