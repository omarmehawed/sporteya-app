import React from 'react';
import { View, ActivityIndicator } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Text } from 'react-native';

import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

// Auth screens
import { WelcomeScreen } from '../screens/auth/WelcomeScreen';
import { RegisterScreen } from '../screens/auth/RegisterScreen';
import { LoginScreen } from '../screens/auth/LoginScreen';

// Onboarding screens
import { SelectSportsScreen } from '../screens/onboarding/SelectSportsScreen';
import { UsernameScreen } from '../screens/onboarding/UsernameScreen';
import { SelectLocationScreen } from '../screens/onboarding/SelectLocationScreen';
import { UploadAvatarScreen } from '../screens/onboarding/UploadAvatarScreen';

// Owner screens
import { VenueSetupScreen } from '../screens/owner/VenueSetupScreen';

// Main tab screens
import { HomeScreen } from '../screens/main/HomeScreen';
import { DiscoverScreen } from '../screens/main/DiscoverScreen';
import { MyBookingsScreen } from '../screens/main/MyBookingsScreen';
import { ProfileScreen } from '../screens/main/ProfileScreen';

// Stack screens
import { SportVenuesScreen } from '../screens/venue/SportVenuesScreen';
import { VenueDetailScreen } from '../screens/venue/VenueDetailScreen';
import { SelectSlotScreen } from '../screens/venue/SelectSlotScreen';
import { PaymentScreen } from '../screens/venue/PaymentScreen';
import { QRCheckinScreen } from '../screens/venue/QRCheckinScreen';
import { MatchLobbyScreen } from '../screens/lobby/MatchLobbyScreen';
import { LobbyPreviewScreen } from '../screens/lobby/LobbyPreviewScreen';
import { BookingGroupsScreen } from '../screens/groups/BookingGroupsScreen';
import { GroupDetailScreen } from '../screens/groups/GroupDetailScreen';
import { CreateGroupScreen } from '../screens/groups/CreateGroupScreen';
import { NotificationsScreen } from '../screens/other/NotificationsScreen';
import { PostMatchScreen } from '../screens/other/PostMatchScreen';
import { EditProfileScreen } from '../screens/other/EditProfileScreen';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

const TabNavigator = () => {
  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarStyle: {
          backgroundColor: colors.surface,
          borderTopColor: colors.border,
          height: 60,
          paddingBottom: 8,
          paddingTop: 6,
        },
        tabBarLabelStyle: {
          fontSize: 12,
          fontWeight: '700',
        },
      }}
    >
      <Tab.Screen
        name="Home"
        component={HomeScreen}
        options={{
          tabBarLabel: 'الرئيسية',
          tabBarIcon: ({ color }) => <Text style={{ fontSize: 18, color }}>🏠</Text>,
        }}
      />
      <Tab.Screen
        name="Discover"
        component={DiscoverScreen}
        options={{
          tabBarLabel: 'استكشف',
          tabBarIcon: ({ color }) => <Text style={{ fontSize: 18, color }}>🔍</Text>,
        }}
      />
      <Tab.Screen
        name="MyBookings"
        component={MyBookingsScreen}
        options={{
          tabBarLabel: 'حجوزاتي',
          tabBarIcon: ({ color }) => <Text style={{ fontSize: 18, color }}>🎟️</Text>,
        }}
      />
      <Tab.Screen
        name="Profile"
        component={ProfileScreen}
        options={{
          tabBarLabel: 'حسابي',
          tabBarIcon: ({ color }) => <Text style={{ fontSize: 18, color }}>👤</Text>,
        }}
      />
    </Tab.Navigator>
  );
};

export const RootNavigator = () => {
  const { user, token, isLoading } = useAuth();

  if (isLoading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background }}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        {!token ? (
          // Auth Stack (no token)
          <Stack.Group>
            <Stack.Screen name="Welcome" component={WelcomeScreen} />
            <Stack.Screen name="Register" component={RegisterScreen} />
            <Stack.Screen name="Login" component={LoginScreen} />
          </Stack.Group>
        ) : !user?.setup_complete ? (
          // Onboarding Stacks based on role
          user?.role === 'venue_owner' ? (
            <Stack.Group>
              <Stack.Screen name="VenueSetup" component={VenueSetupScreen} />
            </Stack.Group>
          ) : (
            <Stack.Group>
              <Stack.Screen name="SelectSports" component={SelectSportsScreen} />
              <Stack.Screen name="Username" component={UsernameScreen} />
              <Stack.Screen name="SelectLocation" component={SelectLocationScreen} />
              <Stack.Screen name="UploadAvatar" component={UploadAvatarScreen} />
            </Stack.Group>
          )
        ) : (
          // Main Stack (setup_complete = true)
          <Stack.Group>
            <Stack.Screen name="MainTabs" component={TabNavigator} />
            <Stack.Screen name="SportVenues" component={SportVenuesScreen} />
            <Stack.Screen name="VenueDetail" component={VenueDetailScreen} />
            <Stack.Screen name="SelectSlot" component={SelectSlotScreen} />
            <Stack.Screen name="Payment" component={PaymentScreen} />
            <Stack.Screen name="QRCheckin" component={QRCheckinScreen} />
            <Stack.Screen name="MatchLobby" component={MatchLobbyScreen} />
            <Stack.Screen name="LobbyPreview" component={LobbyPreviewScreen} />
            <Stack.Screen name="BookingGroups" component={BookingGroupsScreen} />
            <Stack.Screen name="GroupDetail" component={GroupDetailScreen} />
            <Stack.Screen name="CreateGroup" component={CreateGroupScreen} />
            <Stack.Screen name="Notifications" component={NotificationsScreen} />
            <Stack.Screen name="PostMatch" component={PostMatchScreen} />
            <Stack.Screen name="EditProfile" component={EditProfileScreen} />
          </Stack.Group>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
};
