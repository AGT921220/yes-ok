import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme';
import { Card } from '../components/ui/Card';

const STAFF_ITEMS = [
  { key: 'RescheduleQueue', title: 'Por Reagendar', subtitle: 'Cola de citas por reagendar' },
  { key: 'AvailabilityBlocks', title: 'Bloqueos', subtitle: 'Disponibilidad y bloqueos' },
  { key: 'Reports', title: 'Reportes', subtitle: 'Estadísticas y reportes' },
  { key: 'SettingsNotifications', title: 'Notificaciones', subtitle: 'Activar o desactivar avisos push' },
];

const ADMIN_ITEMS = [
  { key: 'Users', title: 'Usuarios', subtitle: 'Gestión de asistentes' },
  { key: 'Settings', title: 'Configuración', subtitle: 'Ajustes del sistema' },
];

export default function MoreMenuScreen() {
  const navigation = useNavigation();
  const { isAdmin, logout } = useAuth();

  const handleLogout = () => {
    Alert.alert(
      'Cerrar sesión',
      '¿Seguro que quieres salir?',
      [
        { text: 'Cancelar', style: 'cancel' },
        { text: 'Cerrar sesión', style: 'destructive', onPress: () => logout() },
      ]
    );
  };

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.header}>
        <Text style={styles.title}>Más</Text>
        <Text style={styles.subtitle}>Reagendar, bloqueos, reportes y configuración</Text>
      </View>
      <View style={styles.list}>
        {STAFF_ITEMS.map((item) => (
          <Card
            key={item.key}
            onPress={() => navigation.navigate(item.key)}
            style={styles.menuCard}
          >
            <Text style={styles.menuTitle}>{item.title}</Text>
            <Text style={styles.menuSubtitle}>{item.subtitle}</Text>
          </Card>
        ))}
        {isAdmin && ADMIN_ITEMS.map((item) => (
          <Card
            key={item.key}
            onPress={() => navigation.navigate(item.key)}
            style={styles.menuCard}
          >
            <Text style={styles.menuTitle}>{item.title}</Text>
            <Text style={styles.menuSubtitle}>{item.subtitle}</Text>
          </Card>
        ))}
        <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
          <Ionicons name="log-out-outline" size={22} color={colors.error || '#b91c1c'} />
          <Text style={styles.logoutText}>Cerrar sesión</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  header: { paddingHorizontal: 24, paddingTop: 16, paddingBottom: 8 },
  title: { fontSize: 28, fontWeight: '700', color: colors.text },
  subtitle: { fontSize: 14, color: colors.textSecondary, marginTop: 4 },
  list: { paddingHorizontal: 24, paddingTop: 16 },
  menuCard: { marginBottom: 12 },
  menuTitle: { fontSize: 17, fontWeight: '600', color: colors.text },
  menuSubtitle: { fontSize: 14, color: colors.textMuted, marginTop: 4 },
  logoutButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    marginTop: 24,
    paddingVertical: 14,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.error || '#b91c1c',
  },
  logoutText: { fontSize: 16, fontWeight: '600', color: colors.error || '#b91c1c' },
});
