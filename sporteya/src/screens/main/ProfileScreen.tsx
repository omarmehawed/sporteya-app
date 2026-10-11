import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useLanguage } from '../../context/LanguageContext';
import { LanguageToggle } from '../../components/LanguageToggle';
import { colors } from '../../theme/colors';

export const ProfileScreen = ({ navigation }: any) => {
  const { user, logout } = useAuth();
  const { t, isRTL, language, setLanguage } = useLanguage();
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
      Alert.alert(t.error, isRTL ? 'تعذر تحديث حالة اللاعب' : 'Could not update player status');
    }
  };

  const handleLogout = () => {
    Alert.alert(t.logoutConfirmTitle, t.logoutConfirmMsg, [
      { text: t.cancel, style: 'cancel' },
      { text: t.logout, style: 'destructive', onPress: logout },
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
          <Text style={styles.city}>📍 {profile?.city || (isRTL ? 'القاهرة، مصر' : 'Cairo, Egypt')}</Text>
        </View>

        {/* Stats Grid */}
        <View style={styles.statsGrid}>
          <View style={styles.statCard}>
            <Text style={styles.statVal}>{stats.matches_played}</Text>
            <Text style={styles.statLabel}>{t.matchesPlayed}</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={[styles.statVal, { color: colors.primary }]}>{stats.attendance_rate}%</Text>
            <Text style={styles.statLabel}>{t.attendanceRate}</Text>
          </View>
        </View>

        {/* Player Status Picker */}
        <View style={styles.sectionCard}>
          <Text style={[styles.sectionTitle, { textAlign: isRTL ? 'right' : 'left' }]}>
            {t.playerStatusTitle}
          </Text>
          <View style={styles.statusRow}>
            {[
              { id: 'available', label: t.statusAvailable },
              { id: 'booked', label: t.statusBooked },
              { id: 'away', label: t.statusAway },
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

        {/* Language Selection Card */}
        <View style={styles.sectionCard}>
          <Text style={[styles.sectionTitle, { textAlign: isRTL ? 'right' : 'left' }]}>
            {t.languageSetting}
          </Text>
          <View style={styles.langRow}>
            <TouchableOpacity
              style={[styles.langChip, language === 'ar' && styles.langChipActive]}
              onPress={() => setLanguage('ar')}
              activeOpacity={0.8}
            >
              <Text style={[styles.langChipText, language === 'ar' && styles.langChipTextActive]}>
                🇪🇬 العربية
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.langChip, language === 'en' && styles.langChipActive]}
              onPress={() => setLanguage('en')}
              activeOpacity={0.8}
            >
              <Text style={[styles.langChipText, language === 'en' && styles.langChipTextActive]}>
                🇬🇧 English
              </Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Quick Links */}
        <View style={styles.linksCard}>
          <TouchableOpacity
            style={styles.linkRow}
            onPress={() => navigation.navigate('BookingGroups')}
          >
            <Text style={styles.linkArrow}>{isRTL ? '←' : '→'}</Text>
            <Text style={styles.linkTitle}>{t.mySportsGroups}</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.linkRow}
            onPress={() => navigation.navigate('Notifications')}
          >
            <Text style={styles.linkArrow}>{isRTL ? '←' : '→'}</Text>
            <Text style={styles.linkTitle}>{t.notificationsItem}</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.linkRow}
            onPress={() => navigation.navigate('EditProfile')}
          >
            <Text style={styles.linkArrow}>{isRTL ? '←' : '→'}</Text>
            <Text style={styles.linkTitle}>✏️ {t.editProfile}</Text>
          </TouchableOpacity>
        </View>

        {/* Logout Button */}
        <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout} activeOpacity={0.8}>
          <Text style={styles.logoutText}>🚪 {t.logout}</Text>
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
    paddingBottom: 40,
  },
  headerCard: {
    backgroundColor: colors.surface,
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 16,
  },
  avatar: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 14,
  },
  avatarText: {
    fontSize: 32,
    fontWeight: '800',
    color: colors.textWhite,
  },
  name: {
    fontSize: 20,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 4,
  },
  username: {
    fontSize: 14,
    color: colors.primary,
    fontWeight: '600',
    marginBottom: 6,
  },
  city: {
    fontSize: 13,
    color: colors.textMuted,
  },
  statsGrid: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 16,
  },
  statCard: {
    flex: 1,
    backgroundColor: colors.surface,
    borderRadius: 14,
    padding: 18,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  statVal: {
    fontSize: 24,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 4,
  },
  statLabel: {
    fontSize: 12,
    color: colors.textMuted,
    textAlign: 'center',
  },
  sectionCard: {
    backgroundColor: colors.surface,
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 12,
  },
  statusRow: {
    flexDirection: 'row',
    gap: 8,
    flexWrap: 'wrap',
  },
  statusChip: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surface,
  },
  statusChipActive: {
    borderColor: colors.primary,
    backgroundColor: 'rgba(16, 185, 129, 0.1)',
  },
  statusChipText: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  statusChipTextActive: {
    color: colors.primary,
  },
  langRow: {
    flexDirection: 'row',
    gap: 10,
  },
  langChip: {
    flex: 1,
    paddingVertical: 12,
    alignItems: 'center',
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: colors.border,
    backgroundColor: colors.surface,
  },
  langChipActive: {
    borderColor: colors.primary,
    backgroundColor: 'rgba(16, 185, 129, 0.12)',
  },
  langChipText: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  langChipTextActive: {
    color: colors.primary,
  },
  linksCard: {
    backgroundColor: colors.surface,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 20,
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
    backgroundColor: 'rgba(239, 68, 68, 0.1)',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.danger,
  },
  logoutText: {
    color: colors.danger,
    fontSize: 15,
    fontWeight: '700',
  },
});
