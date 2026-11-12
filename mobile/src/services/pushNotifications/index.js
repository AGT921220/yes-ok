import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Notifications from 'expo-notifications';
import * as Device from 'expo-device';
import { Platform } from 'react-native';
import Constants from 'expo-constants';
import { pushTokensApi } from '../api';
import { storage } from '../../storage/storage';
import { notificationsStorage } from '../../features/notifications/notifications.storage';

const PUSH_TOKEN_KEY = 'yes-ok_push_token';
const __DEV__ = typeof global !== 'undefined' && global.__DEV__;

function getProjectId() {
  try {
    const extra = Constants.expoConfig?.extra;
    const eas = Constants.easConfig ?? extra?.eas;
    return extra?.eas?.projectId ?? eas?.projectId ?? null;
  } catch {
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

// Comportamiento al recibir notificación en foreground: mostrar banner y sonido
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: true,
  }),
});

/** ID del canal usado por el backend para citas (debe coincidir con backend). */
export const APPOINTMENTS_CHANNEL_ID = 'appointments_channel';

/**
 * Crea/actualiza el canal de notificaciones Android para citas (sonido custom sound1).
 * Llamar al inicio de la app (p. ej. App.js) para que el canal exista antes de recibir push.
 * En Android 8+: si el canal ya existía sin sonido, el usuario debe desinstalar/reinstalar
 * o se puede usar un canal nuevo (appointments_channel_v2) y actualizar el backend.
 */
export async function ensureAppointmentsChannel() {
  if (Platform.OS !== 'android') return;
  try {
    await Notifications.setNotificationChannelAsync(APPOINTMENTS_CHANNEL_ID, {
      name: 'Citas',
      importance: Notifications.AndroidImportance.MAX,
      sound: 'sound1', // archivo en android/app/src/main/res/raw/sound1.mp3 (sin extensión)
      vibrationPattern: [0, 250, 250, 250],
    });
  } catch (e) {
    if (__DEV__) console.warn('[pushNotifications] ensureAppointmentsChannel failed:', e?.message);
  }
}

/**
 * Solicita permisos y obtiene el token (Expo si hay projectId, sino FCM/APNs).
 * En Android 13+ solicita POST_NOTIFICATIONS.
 */
export async function registerForPushNotificationsAsync() {
  if (!Device.isDevice) return null;

  const { status: existingStatus } = await Notifications.getPermissionsAsync();
  let finalStatus = existingStatus;
  if (existingStatus !== 'granted') {
    const { status } = await Notifications.requestPermissionsAsync();
    finalStatus = status;
    if (finalStatus !== 'granted') return null;
  }

  if (Platform.OS === 'android') {
    await ensureAppointmentsChannel();
  }

  const projectId = getProjectId();
  if (projectId) {
    try {
      const result = await Notifications.getExpoPushTokenAsync({ projectId });
      const token = result?.data ?? null;
      if (token) {
        await AsyncStorage.setItem(PUSH_TOKEN_KEY, token);
        await notificationsStorage.setStoredPushToken(token);
        return { token, platform: Platform.OS };
      }
    } catch (e) {
      if (__DEV__) console.warn('[pushNotifications] getExpoPushTokenAsync failed:', e?.message);
    }
  }

  try {
    const deviceToken = await Notifications.getDevicePushTokenAsync();
    const token = deviceToken?.data ?? null;
    if (token) {
      await AsyncStorage.setItem(PUSH_TOKEN_KEY, token);
      await notificationsStorage.setStoredPushToken(token);
      return { token, platform: Platform.OS };
    }
  } catch (e) {
    if (__DEV__) console.warn('[pushNotifications] getDevicePushTokenAsync failed:', e?.message);
  }
  return null;
}

/**
 * Obtiene el token de push actual (del estado local o re-registrando).
 */
export async function getPushToken() {
  try {
    const result = await registerForPushNotificationsAsync();
    return result?.token ?? null;
  } catch (e) {
    console.warn('getPushToken failed:', e);
    return null;
  }
}

/**
 * Envía el token al backend y lo asocia al usuario logueado (device_name y app_version opcionales).
 */
export async function syncTokenWithBackend() {
  const token = await getPushToken();
  if (!token) return false;
  const authToken = await storage.getToken();
  if (!authToken) return false;
  const deviceName = Device.deviceName ?? null;
  const appVersion = getAppVersion();
  try {
    await pushTokensApi.register(token, Platform.OS, deviceName, appVersion);
    return true;
  } catch (err) {
    if (__DEV__) console.warn('[pushNotifications] syncTokenWithBackend failed:', err?.response?.status, err?.message);
    return false;
  }
}

/**
 * Revoca el token en el backend (p. ej. al cerrar sesión).
 */
export async function revokeTokenOnBackend() {
  try {
    const token = await notificationsStorage.getStoredPushToken().catch(() => null)
      ?? await AsyncStorage.getItem(PUSH_TOKEN_KEY);
    if (!token) return;
    const authToken = await storage.getToken();
    if (!authToken) return;
    await pushTokensApi.revoke(token);
    await notificationsStorage.setStoredPushToken(null);
  } catch (e) {
    if (__DEV__) console.warn('[pushNotifications] revokeTokenOnBackend failed:', e?.message);
  }
}
