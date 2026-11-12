import React, { createContext, useContext, useState, useEffect, useRef } from 'react';
import { authApi } from '../services/api/auth';
import { storage } from '../storage/storage';
import { authManager } from '../utils/authManager';
import { revokeTokenOnBackend } from '../services/pushNotifications';

const AuthContext = createContext(null);

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const ref = useRef(null);

  useEffect(() => {
    let mounted = true;
    const check = async () => {
      const token = await storage.getToken();
      if (!token) {
        if (mounted) setLoading(false);
        return;
      }
      try {
        const res = await authApi.getMe();
        // Backend puede devolver { data: { id, ... } } (Resource) o el user directo
        const userData = res.data?.data ?? res.data;
        if (mounted) setUser(userData);
      } catch (err) {
        if (err?.response?.status !== 401 && mounted) {
          await storage.clearAuth();
          setUser(null);
        }
      }
      if (mounted) setLoading(false);
    };
    check();
    return () => { mounted = false; };
  }, []);

  useEffect(() => {
    const onLogout = () => setUser(null);
    ref.current = onLogout;
    authManager.registerLogoutCallback(onLogout);
    return () => authManager.unregisterLogoutCallback(ref.current);
  }, []);

  const login = async (identifier, password, type = 'email') => {
    const res = await authApi.login(identifier, password, type);
    const data = res.data || {};
    const token = data.access_token;
    // Backend: { access_token, token_type, user } — user puede venir anidado en .data
    const userData = data.user?.data ?? data.user;
    if (!token || !userData) {
      throw new Error(data.message || 'Respuesta de login inválida');
    }
    await storage.setToken(token);
    await storage.setUser(userData);
    setUser(userData);
    return userData;
  };

  const logout = async () => {
    await revokeTokenOnBackend();
    authManager.logout({ redirect: true });
  };

  const value = {
    user,
    loading,
    login,
    logout,
    isAdmin: user?.role === 'ADMIN',
    isPatient: user?.role === 'CLIENTE',
    isAssistant: user?.role === 'ASISTENTE',
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
