import React, { createContext, useContext, useEffect, useState } from 'react';
import * as SecureStore from 'expo-secure-store';
import { apiClient } from '../api/client';

export interface User {
  id: string;
  name: string;
  email: string;
  role: 'player' | 'venue_owner' | 'admin';
  setup_complete: boolean;
  status: string;
  player_profile?: {
    id: string;
    username: string;
    city?: string;
    avatar_url?: string;
    onboarding_done: boolean;
    player_status: string;
  };
}

interface AuthContextType {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<User>;
  register: (payload: { name: string; email: string; password: string; password_confirmation: string; role: 'player' | 'venue_owner' }) => Promise<{ user: User; generated_username?: string }>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<User | null>;
  setSetupComplete: (complete: boolean) => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  const refreshUser = async (): Promise<User | null> => {
    try {
      const storedToken = await SecureStore.getItemAsync('auth_token');
      if (!storedToken) {
        setUser(null);
        setToken(null);
        return null;
      }
      setToken(storedToken);
      const res = await apiClient.get('/auth/me');
      const userData: User = res.data.user;
      setUser(userData);
      return userData;
    } catch (error) {
      console.warn('Failed to refresh user:', error);
      setUser(null);
      setToken(null);
      await SecureStore.deleteItemAsync('auth_token');
      return null;
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    refreshUser();
  }, []);

  const login = async (email: string, password: string): Promise<User> => {
    const res = await apiClient.post('/auth/login', { email, password });
    const { token: receivedToken, user: receivedUser } = res.data;
    await SecureStore.setItemAsync('auth_token', receivedToken);
    await SecureStore.setItemAsync('user_role', receivedUser.role);
    setToken(receivedToken);
    setUser(receivedUser);
    return receivedUser;
  };

  const register = async (payload: { name: string; email: string; password: string; password_confirmation: string; role: 'player' | 'venue_owner' }) => {
    const res = await apiClient.post('/auth/register', payload);
    const { token: receivedToken, user: receivedUser, generated_username } = res.data;
    await SecureStore.setItemAsync('auth_token', receivedToken);
    await SecureStore.setItemAsync('user_role', receivedUser.role);
    setToken(receivedToken);
    setUser(receivedUser);
    return { user: receivedUser, generated_username };
  };

  const logout = async () => {
    try {
      await apiClient.post('/auth/logout');
    } catch (err) {
      console.warn('Logout API error:', err);
    } finally {
      await SecureStore.deleteItemAsync('auth_token');
      await SecureStore.deleteItemAsync('user_role');
      setUser(null);
      setToken(null);
    }
  };

  const setSetupComplete = (complete: boolean) => {
    if (user) {
      setUser({ ...user, setup_complete: complete });
    }
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isLoading,
        login,
        register,
        logout,
        refreshUser,
        setSetupComplete,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
