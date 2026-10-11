import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, FlatList, TextInput, TouchableOpacity, ActivityIndicator, Alert, Share } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../../api/client';
import { colors } from '../../theme/colors';

export const GroupDetailScreen = ({ route, navigation }: any) => {
  const { groupId } = route.params;
  const [group, setGroup] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [inviteUsername, setInviteUsername] = useState('');
  const [inviting, setInviting] = useState(false);

  const fetchGroup = async () => {
    try {
      const res = await apiClient.get(`/booking-groups/${groupId}`);
      setGroup(res.data.group);
    } catch (err) {
      console.warn('Group fetch error:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchGroup();
  }, [groupId]);

  const handleInvite = async () => {
    if (!inviteUsername.trim()) return;
    try {
      setInviting(true);
      await apiClient.post(`/booking-groups/${groupId}/members`, {
        username: inviteUsername.trim().toLowerCase(),
      });
      setInviteUsername('');
      Alert.alert('تم', 'تمت إضافة العضو وإرسال إشعار له بنجاح');
      fetchGroup();
    } catch (err: any) {
      Alert.alert('خطأ', err.response?.data?.message || 'تعذر إضافة العضو');
    } finally {
      setInviting(false);
    }
  };

  const handleShare = async () => {
    if (!group) return;
    await Share.share({
      message: `انضم لمجموعتنا ${group.name} على سبورتيا باستخدام الكود: ${group.invite_code}`,
    });
  };

  if (loading || !group) {
    return (
      <SafeAreaView style={[styles.container, { justifyContent: 'center', alignItems: 'center' }]}>
        <ActivityIndicator size="large" color={colors.primary} />
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backBtnText}>→</Text>
        </TouchableOpacity>
        <Text style={styles.title}>{group.name}</Text>
      </View>

      <View style={styles.codeCard}>
        <Text style={styles.codeLabel}>كود الانضمام المباشر للمجموعة:</Text>
        <View style={styles.codeRow}>
          <Text style={styles.codeVal}>{group.invite_code}</Text>
          <TouchableOpacity style={styles.shareBtn} onPress={handleShare}>
            <Text style={styles.shareText}>مشاركة 🔗</Text>
          </TouchableOpacity>
        </View>
      </View>

      <View style={styles.inviteBox}>
        <Text style={styles.inviteLabel}>دعوة عضو بواسطة اسم المستخدم (@username):</Text>
        <View style={styles.inviteRow}>
          <TextInput
            style={styles.input}
            placeholder="اسم المستخدم..."
            value={inviteUsername}
            onChangeText={setInviteUsername}
            autoCapitalize="none"
            textAlign="right"
          />
          <TouchableOpacity
            style={styles.addBtn}
            onPress={handleInvite}
            disabled={inviting}
          >
            {inviting ? (
              <ActivityIndicator color={colors.textWhite} size="small" />
            ) : (
              <Text style={styles.addText}>إضافة</Text>
            )}
          </TouchableOpacity>
        </View>
      </View>

      <Text style={styles.membersTitle}>أعضاء المجموعة ({group.members?.length || 0}):</Text>
      <FlatList
        data={group.members}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.list}
        renderItem={({ item }) => (
          <View style={styles.memberRow}>
            <Text style={styles.memberName}>
              {item.player?.username ? `@${item.player.username}` : item.player?.user?.name || 'عضو'}
            </Text>
            <Text style={styles.memberStatus}>
              {item.status === 'active' ? 'عضو نشط ✓' : item.status}
            </Text>
          </View>
        )}
      />
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderColor: colors.border,
    gap: 12,
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
  codeCard: {
    backgroundColor: colors.surface,
    margin: 16,
    padding: 16,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
  },
  codeLabel: {
    fontSize: 13,
    color: colors.textMuted,
    textAlign: 'right',
    marginBottom: 6,
  },
  codeRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  codeVal: {
    fontSize: 18,
    fontWeight: '800',
    fontFamily: 'monospace',
    color: colors.primaryDark,
  },
  shareBtn: {
    backgroundColor: colors.surfaceSubtle,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 6,
  },
  shareText: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.secondary,
  },
  inviteBox: {
    backgroundColor: colors.surface,
    marginHorizontal: 16,
    marginBottom: 16,
    padding: 16,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
  },
  inviteLabel: {
    fontSize: 13,
    fontWeight: '600',
    color: colors.textSecondary,
    marginBottom: 8,
    textAlign: 'right',
  },
  inviteRow: {
    flexDirection: 'row',
    gap: 8,
  },
  input: {
    flex: 1,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 8,
    fontSize: 14,
  },
  addBtn: {
    backgroundColor: colors.primary,
    paddingHorizontal: 16,
    borderRadius: 8,
    justifyContent: 'center',
    alignItems: 'center',
  },
  addText: {
    color: colors.textWhite,
    fontSize: 13,
    fontWeight: '700',
  },
  membersTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.textPrimary,
    marginHorizontal: 16,
    marginBottom: 8,
    textAlign: 'right',
  },
  list: {
    paddingHorizontal: 16,
    gap: 8,
  },
  memberRow: {
    backgroundColor: colors.surface,
    padding: 14,
    borderRadius: 10,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  memberName: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  memberStatus: {
    fontSize: 12,
    color: colors.primaryDark,
    fontWeight: '600',
  },
});
