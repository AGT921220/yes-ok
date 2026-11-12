import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  ActivityIndicator,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { colors } from '../theme';
import { Card } from '../components/ui/Card';
import { appointmentsApi } from '../services/api';
import { getApiErrorMessage } from '../utils/apiError';

function normalizeList(res) {
  const data = res.data?.data ?? res.data;
  return Array.isArray(data) ? data : [];
}

const STATUS_LABELS = {
  PENDIENTE: 'Pendiente',
  CONFIRMADA: 'Confirmada',
  CANCELADA: 'Cancelada',
  NO_SHOW: 'No asistió',
  COMPLETADA: 'Completada',
  REQUIERE_REAGENDAR: 'Por reagendar',
};

export default function RescheduleQueueScreen() {
  const navigation = useNavigation();
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchQueue = useCallback(async () => {
    try {
      const res = await appointmentsApi.getRescheduleQueue();
      setAppointments(normalizeList(res));
    } catch (err) {
      Alert.alert('Error', getApiErrorMessage(err, 'Error al cargar cola'));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchQueue();
  }, [fetchQueue]);

  const renderItem = ({ item }) => {
    const patientName = item.client?.full_name || item.client?.name || 'Cliente';
    const dateStr = item.date ? new Date(item.date + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' }) : '—';
    return (
      <Card style={styles.card}>
        <Text style={styles.patientName}>{patientName}</Text>
        <Text style={styles.date}>{dateStr} · {item.start_time || '—'} - {item.end_time || '—'}</Text>
        <Text style={styles.status}>{STATUS_LABELS[item.status] || item.status}</Text>
      </Card>
    );
  };

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <Text style={styles.back} onPress={() => navigation.goBack()}>← Volver</Text>
      <View style={styles.header}>
        <Text style={styles.title}>Por Reagendar</Text>
        <Text style={styles.subtitle}>Citas que requieren reagendar</Text>
      </View>
      {loading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={appointments}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderItem}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>No hay citas por reagendar</Text>
            </View>
          }
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchQueue(); }} colors={[colors.primary]} />
          }
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  back: { fontSize: 16, color: colors.primary, fontWeight: '500', paddingHorizontal: 24, paddingTop: 16 },
  header: { paddingHorizontal: 24, paddingTop: 8, paddingBottom: 8 },
  title: { fontSize: 28, fontWeight: '700', color: colors.text },
  subtitle: { fontSize: 14, color: colors.textSecondary, marginTop: 4 },
  list: { paddingHorizontal: 24, paddingBottom: 24 },
  card: { marginBottom: 12 },
  patientName: { fontSize: 17, fontWeight: '600', color: colors.text },
  date: { fontSize: 14, color: colors.textSecondary, marginTop: 4 },
  status: { fontSize: 12, color: colors.primary, marginTop: 4 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  empty: { paddingVertical: 48, alignItems: 'center' },
  emptyText: { fontSize: 16, color: colors.textMuted },
});
