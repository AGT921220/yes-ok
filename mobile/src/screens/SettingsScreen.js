import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { colors } from '../theme';
import { settingsApi } from '../services/api';
import { getApiErrorMessage } from '../utils/apiError';

function normalizeList(res) {
  const data = res.data?.data ?? res.data;
  return data && typeof data === 'object' && !Array.isArray(data) ? data : {};
}

export default function SettingsScreen() {
  const navigation = useNavigation();
  const [loading, setLoading] = useState(true);
  const [settings, setSettings] = useState({});

  useEffect(() => {
    (async () => {
      try {
        const res = await settingsApi.list();
        setSettings(normalizeList(res));
      } catch (err) {
        Alert.alert('Error', getApiErrorMessage(err, 'Error al cargar configuración'));
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  if (loading) {
    return (
      <SafeAreaView style={styles.container} edges={['top']}>
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      </SafeAreaView>
    );
  }

  const entries = Object.entries(settings);

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <Text style={styles.back} onPress={() => navigation.goBack()}>← Volver</Text>
      <View style={styles.header}>
        <Text style={styles.title}>Configuración</Text>
        <Text style={styles.subtitle}>Ajustes del sistema (solo admin)</Text>
      </View>
      <ScrollView style={styles.scroll} contentContainerStyle={styles.content}>
        {entries.length === 0 ? (
          <Text style={styles.empty}>No hay configuración cargada.</Text>
        ) : (
          entries.map(([key, value]) => (
            <View key={key} style={styles.row}>
              <Text style={styles.key}>{key}</Text>
              <Text style={styles.value}>{String(value)}</Text>
            </View>
          ))
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  back: { fontSize: 16, color: colors.primary, fontWeight: '500', paddingHorizontal: 24, paddingTop: 16 },
  header: { paddingHorizontal: 24, paddingTop: 8, paddingBottom: 8 },
  title: { fontSize: 28, fontWeight: '700', color: colors.text },
  subtitle: { fontSize: 14, color: colors.textSecondary, marginTop: 4 },
  scroll: { flex: 1 },
  content: { paddingHorizontal: 24, paddingBottom: 24 },
  row: {
    backgroundColor: colors.surface,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 16,
    marginBottom: 12,
  },
  key: { fontSize: 14, color: colors.textSecondary },
  value: { fontSize: 16, fontWeight: '600', color: colors.text, marginTop: 4 },
  empty: { fontSize: 16, color: colors.textMuted },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
