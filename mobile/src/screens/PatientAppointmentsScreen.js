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
import { meApi } from '../services/api';
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

export default function PatientAppointmentsScreen() {
  const navigation = useNavigation();
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchAppointments = useCallback(async () => {
    try {
      const res = await meApi.getAppointments({});
      setAppointments(normalizeList(res));
    } catch (err) {
      Alert.alert('Error', getApiErrorMessage(err, 'Error al cargar citas'));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchAppointments();
  }, [fetchAppointments]);

  const sorted = [...appointments].sort((a, b) => {
    const d = (x) => (x.date || '') + (x.start_time || '');
    return d(b).localeCompare(d(a));
  });

  const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('es-MX', {
      weekday: 'short',
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  };

  const renderItem = ({ item }) => (
    <Card
      onPress={() => navigation.navigate('PatientAppointmentDetail', { appointmentId: item.id })}
      style={styles.card}
    >
      <View style={styles.dateBox}>
        <Text style={styles.dateDay}>
          {item.date ? new Date(item.date + 'T00:00:00').getDate() : '—'}
        </Text>
        <Text style={styles.dateMonth}>
          {item.date ? new Date(item.date + 'T00:00:00').toLocaleDateString('es-MX', { month: 'short' }) : ''}
        </Text>
      </View>
      <View style={styles.body}>
        <Text style={styles.dateFull}>{formatDate(item.date)}</Text>
        <Text style={styles.time}>{item.start_time || '—'} - {item.end_time || '—'}</Text>
        {item.reason ? <Text style={styles.reason} numberOfLines={1}>{item.reason}</Text> : null}
      </View>
      <View style={styles.badge}>
        <Text style={styles.badgeText}>{STATUS_LABELS[item.status] || item.status}</Text>
      </View>
    </Card>
  );

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.header}>
        <Text style={styles.title}>Mis citas</Text>
        <Text style={styles.subtitle}>Historial y próximas citas</Text>
      </View>
      {loading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={sorted}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderItem}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>No tienes citas registradas</Text>
            </View>
          }
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchAppointments(); }} colors={[colors.primary]} />
          }
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  header: { paddingHorizontal: 24, paddingTop: 16, paddingBottom: 8 },
  title: { fontSize: 28, fontWeight: '700', color: colors.text },
  subtitle: { fontSize: 14, color: colors.textSecondary, marginTop: 4 },
  list: { paddingHorizontal: 24, paddingBottom: 24 },
  card: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  dateBox: {
    width: 56,
    alignItems: 'center',
    marginRight: 14,
  },
  dateDay: { fontSize: 22, fontWeight: '700', color: colors.primary },
  dateMonth: { fontSize: 12, color: colors.textMuted, textTransform: 'uppercase' },
  body: { flex: 1 },
  dateFull: { fontSize: 15, fontWeight: '600', color: colors.text },
  time: { fontSize: 14, color: colors.textSecondary, marginTop: 2 },
  reason: { fontSize: 12, color: colors.textMuted, marginTop: 2 },
  badge: {
    backgroundColor: colors.primary + '20',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
  },
  badgeText: { fontSize: 12, fontWeight: '600', color: colors.primary },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  empty: { paddingVertical: 48, alignItems: 'center' },
  emptyText: { fontSize: 16, color: colors.textMuted },
});
