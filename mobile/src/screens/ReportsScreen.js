import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { colors } from '../theme';
import { reportsApi } from '../services/api';
import { getApiErrorMessage } from '../utils/apiError';

export default function ReportsScreen() {
  const navigation = useNavigation();
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState(null);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const statsRes = await reportsApi.appointmentStats({});
        if (!cancelled) {
          setStats({
            appointmentStats: statsRes.data?.data ?? statsRes.data ?? {},
          });
        }
      } catch (err) {
        if (!cancelled) Alert.alert('Error', getApiErrorMessage(err, 'Error al cargar reportes'));
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => { cancelled = true; };
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

  const total = stats?.appointmentStats?.total ?? 0;

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <Text style={styles.back} onPress={() => navigation.goBack()}>← Volver</Text>
      <View style={styles.header}>
        <Text style={styles.title}>Reportes</Text>
        <Text style={styles.subtitle}>Estadísticas y resumen</Text>
      </View>
      <View style={styles.content}>
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Citas</Text>
          <Text style={styles.cardValue}>{total}</Text>
        </View>
        <Text style={styles.hint}>Más reportes y exportación en la versión web.</Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  back: { fontSize: 16, color: colors.primary, fontWeight: '500', paddingHorizontal: 24, paddingTop: 16 },
  header: { paddingHorizontal: 24, paddingTop: 8, paddingBottom: 8 },
  title: { fontSize: 28, fontWeight: '700', color: colors.text },
  subtitle: { fontSize: 14, color: colors.textSecondary, marginTop: 4 },
  content: { paddingHorizontal: 24, paddingTop: 16 },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 20,
    marginBottom: 16,
  },
  cardTitle: { fontSize: 14, color: colors.textSecondary },
  cardValue: { fontSize: 28, fontWeight: '700', color: colors.primary, marginTop: 4 },
  hint: { fontSize: 14, color: colors.textMuted },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
