import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRoute, useNavigation } from '@react-navigation/native';
import { colors } from '../theme';
import { meApi } from '../services/api';
import { getApiErrorMessage } from '../utils/apiError';

const STATUS_LABELS = {
  PENDIENTE: 'Pendiente',
  CONFIRMADA: 'Confirmada',
  CANCELADA: 'Cancelada',
  NO_SHOW: 'No asistió',
  COMPLETADA: 'Completada',
  REQUIERE_REAGENDAR: 'Por reagendar',
};

export default function PatientAppointmentDetailScreen() {
  const route = useRoute();
  const navigation = useNavigation();
  const appointmentId = route.params?.appointmentId;
  const [appointment, setAppointment] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!appointmentId) {
      setLoading(false);
      return;
    }
    (async () => {
      try {
        const res = await meApi.getAppointment(appointmentId);
        setAppointment(res.data?.data ?? res.data);
      } catch (err) {
        Alert.alert('Error', getApiErrorMessage(err, 'Error al cargar la cita'));
      } finally {
        setLoading(false);
      }
    })();
  }, [appointmentId]);

  if (loading) {
    return (
      <SafeAreaView style={styles.container} edges={['top']}>
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      </SafeAreaView>
    );
  }

  if (!appointment) {
    return (
      <SafeAreaView style={styles.container} edges={['top']}>
        <Text style={styles.back} onPress={() => navigation.goBack()}>← Volver a mis citas</Text>
        <Text style={styles.empty}>No se encontró la cita.</Text>
      </SafeAreaView>
    );
  }

  const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('es-MX', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  };

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <Text style={styles.back} onPress={() => navigation.goBack()}>← Volver a mis citas</Text>
      <ScrollView style={styles.scroll} contentContainerStyle={styles.content}>
        <View style={styles.card}>
          <Text style={styles.label}>Fecha</Text>
          <Text style={styles.value}>{formatDate(appointment.date)}</Text>
        </View>
        <View style={styles.card}>
          <Text style={styles.label}>Horario</Text>
          <Text style={styles.value}>{appointment.start_time || '—'} - {appointment.end_time || '—'}</Text>
        </View>
        <View style={styles.card}>
          <Text style={styles.label}>Estado</Text>
          <Text style={styles.value}>{STATUS_LABELS[appointment.status] || appointment.status}</Text>
        </View>
        {appointment.reason ? (
          <View style={styles.card}>
            <Text style={styles.label}>Motivo</Text>
            <Text style={styles.value}>{appointment.reason}</Text>
          </View>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  back: { fontSize: 16, color: colors.primary, fontWeight: '500', paddingHorizontal: 24, paddingTop: 16 },
  scroll: { flex: 1 },
  content: { padding: 24, paddingTop: 16 },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 16,
    marginBottom: 12,
  },
  label: { fontSize: 12, color: colors.textMuted },
  value: { fontSize: 16, fontWeight: '600', color: colors.text, marginTop: 4 },
  empty: { padding: 24, fontSize: 16, color: colors.textMuted },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
