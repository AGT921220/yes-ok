import { useState, useCallback, useEffect } from 'react';
import { Platform, Linking } from 'react-native';
import * as Notifications from 'expo-notifications';
import * as Device from 'expo-device';
import Constants from 'expo-constants';
import { registerPushToken } from './notifications.api';
import { notificationsStorage } from './notifications.storage';

const __DEV__ = typeof global !== 'undefined' && global.__DEV__;

function getProjectId() {
  try {
    const extra = Constants.expoConfig?.extra;
    const eas = Constants.easConfig ?? extra?.eas;
    return extra?.eas?.projectId ?? eas?.projectId ?? null;
  } catch (e) {
    if (__DEV__) console.warn('[usePushNotifications] getProjectId:', e?.message);
    return null;
  }
}

function getAppVersion() {
  try {
    return Constants.expoConfig?.version ?? Constants.manifest?.version ?? null;
  } catch {
    return null;
  }
}

/**
 * Obtiene el token de push: Expo si hay projectId, sino FCM/APNs nativo.
 */
async function getPushTokenAsync() {
  if (!Device.isDevice) {
    if (__DEV__) console.log('[usePushNotifications] Not a physical device, skipping token');
    return null;
  }

  const projectId = getProjectId();
  if (projectId) {
    try {
      const result = await Notifications.getExpoPushTokenAsync({ projectId });
      const token = result?.data ?? null;
      if (__DEV__ && token) console.log('[usePushNotifications] Expo push token obtained');
      return token;
    } catch (e) {
      if (__DEV__) console.warn('[usePushNotifications] getExpoPushTokenAsync failed:', e?.message);
    }
  }

  try {
    const deviceToken = await Notifications.getDevicePushTokenAsync();
    const token = deviceToken?.data ?? null;
    if (__DEV__ && token) console.log('[usePushNotifications] Device push token obtained');
    return token;
  } catch (e) {
    if (__DEV__) console.warn('[usePushNotifications] getDevicePushTokenAsync failed:', e?.message);
    return null;
  }
}

/**
 * Hook: registro de notificaciones push con estado y acciones.
 * - requestPermissionAndRegister(): pide permiso, obtiene token, envía al backend.
 * - skipForNow(): guarda preferencia para no molestar 7 días.
 * - openSettings(): abre ajustes del sistema.
 */
export function usePushNotificationsRegistration() {
  const [status, setStatus] = useState('unknown');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  const refreshPermissionStatus = useCallback(async () => {
    try {
      const { status: s } = await Notifications.getPermissionsAsync();
      setStatus(s);
      return s;
    } catch (e) {
      if (__DEV__) console.warn('[usePushNotifications] getPermissionsAsync:', e?.message);
      setStatus('undetermined');
      return 'undetermined';
    }
  }, []);

  useEffect(() => {
    refreshPermissionStatus();
  }, [refreshPermissionStatus]);

  const requestPermissionAndRegister = useCallback(
    async (options = {}) => {
      const { deviceName = null } = options;
      setError(null);
      setIsLoading(true);
      try {
        if (!Device.isDevice) {
          setError('Solo disponible en dispositivo físico');
          return { ok: false };
        }

        if (Platform.OS === 'android') {
          const { ensureAppointmentsChannel } = await import('../../services/pushNotifications');
          await ensureAppointmentsChannel();
        }

        let permStatus = await Notifications.getPermissionsAsync().then((r) => r.status);
        if (permStatus !== 'granted') {
          const { status: requested } = await Notifications.requestPermissionsAsync();
          permStatus = requested;
          setStatus(permStatus);
        }
        if (permStatus !== 'granted') {
          setError('Permiso denegado');
          return { ok: false };
        }

        const token = await getPushTokenAsync();
        if (!token) {
          setError('No se pudo obtener el token');
          return { ok: false };
        }

        await notificationsStorage.setStoredPushToken(token);
        const platform = Platform.OS;
        const appVersion = getAppVersion();
        await registerPushToken({ token, platform, deviceName, appVersion });
        setStatus('granted');
        if (__DEV__) console.log('[usePushNotifications] Token registered with backend');
        return { ok: true, token };
      } catch (err) {
        const message = err?.response?.data?.message ?? err?.message ?? 'Error al registrar';
        setError(message);
        if (__DEV__) console.warn('[usePushNotifications] requestPermissionAndRegister:', err?.response?.status, message);
        return { ok: false };
      } finally {
        setIsLoading(false);
      }
    },
    []
  );

  const skipForNow = useCallback(async () => {
    setError(null);
    await notificationsStorage.setPermissionPromptSkippedNow();
    if (__DEV__) console.log('[usePushNotifications] Skipped for 7 days');
  }, []);

  const openSettings = useCallback(() => {
    Linking.openSettings();
  }, []);

  return {
    status,
    isLoading,
    error,
    requestPermissionAndRegister,
    skipForNow,
    openSettings,
    refreshPermissionStatus,
  };
}
