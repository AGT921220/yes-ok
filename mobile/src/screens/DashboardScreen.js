import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme';
import { Card } from '../components/ui/Card';
import { appointmentsApi } from '../services/api';
import { getApiErrorMessage } from '../utils/apiError';

function normalizeList(res) {
  const data = res.data?.data ?? res.data;
  return Array.isArray(data) ? data : [];
}

function formatDateKey(d) {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function formatDateLabel(dateStr) {
  const d = new Date(dateStr + 'T12:00:00');
  const today = formatDateKey(new Date());
  if (dateStr === today) return 'Hoy';
  return d.toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' });
}

const STATUS_LABELS = {
  PENDIENTE: 'Pendiente',
  CONFIRMADA: 'Confirmada',
  CANCELADA: 'Cancelada',
  NO_SHOW: 'No asistió',
  COMPLETADA: 'Completada',
  REQUIERE_REAGENDAR: 'Por reagendar',
};

export default function DashboardScreen() {
  const { user } = useAuth();
  const [selectedDate, setSelectedDate] = useState(formatDateKey(new Date()));
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchDay = useCallback(async () => {
    try {
      const res = await appointmentsApi.list({
        start_date: selectedDate,
        end_date: selectedDate,
      });
      const list = normalizeList(res);
      setAppointments(list.sort((a, b) => (a.start_time || '').localeCompare(b.start_time || '')));
    } catch (err) {
      Alert.alert('Error', getApiErrorMessage(err, 'Error al cargar agenda'));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [selectedDate]);

  useEffect(() => {
    setLoading(true);
    fetchDay();
  }, [fetchDay]);

  const onPrevDay = () => {
    const d = new Date(selectedDate + 'T12:00:00');
    d.setDate(d.getDate() - 1);
    setSelectedDate(formatDateKey(d));
  };

  const onNextDay = () => {
    const d = new Date(selectedDate + 'T12:00:00');
    d.setDate(d.getDate() + 1);
    setSelectedDate(formatDateKey(d));
  };

  const onToday = () => {
    setSelectedDate(formatDateKey(new Date()));
  };

  const patientName = (apt) =>
    apt.client?.full_name || [apt.client?.first_name, apt.client?.paternal_surname].filter(Boolean).join(' ') || 'Sin cliente';

  const renderItem = ({ item }) => (
    <Card style={styles.aptCard}>
      <View style={styles.aptTime}>
        <Text style={styles.aptTimeText}>{item.start_time || '—'}</Text>
        <Text style={styles.aptTimeEnd}>{item.end_time || ''}</Text>
      </View>
      <View style={styles.aptBody}>
        <Text style={styles.aptPatient}>{patientName(item)}</Text>
        {item.reason ? <Text style={styles.aptReason} numberOfLines={1}>{item.reason}</Text> : null}
      </View>
      <View style={styles.badge}>
        <Text style={styles.badgeText}>{STATUS_LABELS[item.status] || item.status}</Text>
      </View>
    </Card>
  );

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.header}>
        <Text style={styles.title}>Agenda</Text>
        <Text style={styles.subtitle}>Hola, {user?.name || 'Usuario'}</Text>
      </View>

      <View style={styles.dateBar}>
        <TouchableOpacity onPress={onPrevDay} style={styles.dateBtn}>
          <Text style={styles.dateBtnText}>←</Text>
        </TouchableOpacity>
        <TouchableOpacity onPress={onToday} style={styles.dateLabelWrap}>
          <Text style={styles.dateLabel}>{formatDateLabel(selectedDate)}</Text>
        </TouchableOpacity>
        <TouchableOpacity onPress={onNextDay} style={styles.dateBtn}>
          <Text style={styles.dateBtnText}>→</Text>
        </TouchableOpacity>
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
              <Text style={styles.emptyText}>No hay citas este día</Text>
              <Text style={styles.emptySub}>Usa la web para crear y editar citas</Text>
            </View>
          }
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => { setRefreshing(true); fetchDay(); }}
              colors={[colors.primary]}
            />
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
  dateBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginHorizontal: 24,
    marginVertical: 12,
    backgroundColor: colors.surface,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    paddingVertical: 8,
    paddingHorizontal: 8,
  },
  dateBtn: { padding: 12 },
  dateBtnText: { fontSize: 20, color: colors.primary, fontWeight: '600' },
  dateLabelWrap: { flex: 1, alignItems: 'center' },
  dateLabel: { fontSize: 16, fontWeight: '600', color: colors.text },
  list: { paddingHorizontal: 24, paddingBottom: 24 },
  aptCard: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  aptTime: { width: 64, marginRight: 12 },
  aptTimeText: { fontSize: 16, fontWeight: '700', color: colors.primary },
  aptTimeEnd: { fontSize: 12, color: colors.textMuted },
  aptBody: { flex: 1 },
  aptPatient: { fontSize: 16, fontWeight: '600', color: colors.text },
  aptReason: { fontSize: 13, color: colors.textSecondary, marginTop: 2 },
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
  emptySub: { fontSize: 14, color: colors.textMuted, marginTop: 8 },
});
