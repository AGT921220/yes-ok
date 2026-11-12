import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  Linking,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme';
import { usePushNotificationsRegistration } from '../features/notifications/usePushNotificationsRegistration';
import { revokePushToken } from '../features/notifications/notifications.api';
import { notificationsStorage } from '../features/notifications/notifications.storage';

const STATUS_LABELS = {
  granted: 'Activadas',
  denied: 'Desactivadas',
  undetermined: 'No decidido',
  unknown: 'Comprobando…',
};

export default function SettingsNotificationsScreen() {
  const navigation = useNavigation();
  const [storedToken, setStoredToken] = useState(null);
  const {
    status,
    isLoading,
    requestPermissionAndRegister,
    openSettings,
    refreshPermissionStatus,
  } = usePushNotificationsRegistration();

  useEffect(() => {
    notificationsStorage.getStoredPushToken().then(setStoredToken);
  }, []);

  const handleActivate = async () => {
    if (status === 'denied') {
      Alert.alert(
        'Abrir ajustes',
        'Para activar notificaciones hay que permitirlo en los ajustes del sistema.',
        [
          { text: 'Cancelar', style: 'cancel' },
          { text: 'Abrir ajustes', onPress: openSettings },
        ]
      );
      return;
    }
    const result = await requestPermissionAndRegister();
    if (result?.ok) {
      setStoredToken(result.token);
    }
  };

  const handleDisable = () => {
    if (!storedToken) {
      Alert.alert('Sin token', 'No hay token registrado en este dispositivo.');
      return;
    }
    Alert.alert(
      'Desactivar notificaciones',
      'Dejarás de recibir notificaciones en este dispositivo. Puedes activarlas de nuevo cuando quieras.',
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Desactivar',
          style: 'destructive',
          onPress: async () => {
            try {
              await revokePushToken(storedToken);
              await notificationsStorage.setStoredPushToken(null);
              setStoredToken(null);
              await refreshPermissionStatus();
            } catch (e) {
              Alert.alert('Error', e?.response?.data?.message ?? e?.message ?? 'No se pudo desactivar.');
            }
          },
        },
      ]
    );
  };

  const statusLabel = STATUS_LABELS[status] ?? status;

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backRow}>
        <Ionicons name="arrow-back" size={24} color={colors.primary} />
        <Text style={styles.backText}>Volver</Text>
      </TouchableOpacity>
      <View style={styles.header}>
        <Text style={styles.title}>Notificaciones</Text>
        <Text style={styles.subtitle}>Gestiona los avisos push en este dispositivo</Text>
      </View>
      <View style={styles.section}>
        <Text style={styles.label}>Estado</Text>
        <View style={styles.statusRow}>
          <Ionicons
            name={status === 'granted' ? 'notifications' : 'notifications-off'}
            size={22}
            color={status === 'granted' ? colors.success : colors.textMuted}
          />
          <Text style={styles.statusText}>{statusLabel}</Text>
          {isLoading && <ActivityIndicator size="small" color={colors.primary} style={styles.loader} />}
        </View>
      </View>
      <View style={styles.actions}>
        <TouchableOpacity
          style={[styles.button, styles.primaryButton]}
          onPress={handleActivate}
          disabled={isLoading}
        >
          <Ionicons name="notifications" size={20} color="#fff" />
          <Text style={styles.primaryButtonText}>Activar</Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.button, styles.secondaryButton]}
          onPress={handleDisable}
          disabled={isLoading || !storedToken}
        >
          <Ionicons name="notifications-off" size={20} color={colors.text} />
          <Text style={styles.secondaryButtonText}>Desactivar</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  backRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 24,
    paddingVertical: 16,
  },
  backText: { fontSize: 16, color: colors.primary, fontWeight: '500', marginLeft: 8 },
  header: { paddingHorizontal: 24, paddingBottom: 24 },
  title: { fontSize: 28, fontWeight: '700', color: colors.text },
  subtitle: { fontSize: 14, color: colors.textSecondary, marginTop: 4 },
  section: {
    backgroundColor: colors.surface,
    marginHorizontal: 24,
    padding: 16,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 24,
  },
  label: { fontSize: 12, color: colors.textMuted, marginBottom: 8 },
  statusRow: { flexDirection: 'row', alignItems: 'center' },
  statusText: { fontSize: 16, color: colors.text, marginLeft: 10, flex: 1 },
  loader: { marginLeft: 8 },
  actions: { paddingHorizontal: 24, gap: 12 },
  button: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 14,
    borderRadius: 12,
    gap: 8,
  },
  primaryButton: { backgroundColor: colors.primary },
  primaryButtonText: { fontSize: 16, fontWeight: '600', color: '#fff' },
  secondaryButton: { backgroundColor: colors.surface, borderWidth: 1, borderColor: colors.border },
  secondaryButtonText: { fontSize: 16, fontWeight: '600', color: colors.text },
});
