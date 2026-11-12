import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  ScrollView,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { useAuth } from '../context/AuthContext';
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
  COMPLETADA: 'Completada',
  REQUIERE_REAGENDAR: 'Por reagendar',
};

export default function PatientHomeScreen() {
  const navigation = useNavigation();
  const { user } = useAuth();
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      try {
        const res = await meApi.getAppointments({});
        const list = normalizeList(res);
        const today = new Date().toISOString().slice(0, 10);
        const future = list
          .filter((a) => a.status !== 'CANCELADA' && (a.date || '') >= today)
          .sort((a, b) => (a.date + (a.start_time || '')).localeCompare(b.date + (b.start_time || '')))
          .slice(0, 5);
        setAppointments(future);
      } catch (err) {
        Alert.alert('Error', getApiErrorMessage(err, 'Error al cargar citas'));
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr + 'T12:00:00').toLocaleDateString('es-MX', {
      weekday: 'short',
      day: 'numeric',
      month: 'short',
    });
  };

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.header}>
        <Text style={styles.title}>Mi espacio</Text>
        <Text style={styles.subtitle}>Hola, {user?.name || 'Cliente'}</Text>
      </View>

      <ScrollView style={styles.scroll} contentContainerStyle={styles.content}>
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Próximas citas</Text>
          {loading ? (
            <View style={styles.loaderWrap}>
              <ActivityIndicator size="small" color={colors.primary} />
            </View>
          ) : appointments.length === 0 ? (
            <Card style={styles.placeholderCard}>
              <Text style={styles.placeholderText}>No tienes citas próximas</Text>
              <Text style={styles.placeholderSub}>Revisa "Mis Citas" para ver tu historial</Text>
            </Card>
          ) : (
            appointments.map((apt) => (
              <Card
                key={apt.id}
                style={styles.aptCard}
                onPress={() => navigation.navigate('PatientAppointmentDetail', { appointmentId: apt.id })}
              >
                <View style={styles.aptLeft}>
                  <Text style={styles.aptDate}>{formatDate(apt.date)}</Text>
                  <Text style={styles.aptTime}>{apt.start_time || '—'} - {apt.end_time || '—'}</Text>
                </View>
                <View style={styles.aptRight}>
                  <Text style={styles.aptStatus}>{STATUS_LABELS[apt.status] || apt.status}</Text>
                  <Text style={styles.aptArrow}>→</Text>
                </View>
              </Card>
            ))
          )}
        </View>

        <TouchableOpacity
          style={styles.linkButton}
          onPress={() => navigation.navigate('MisCitas')}
        >
          <Text style={styles.linkButtonText}>Ver todas mis citas</Text>
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  header: { paddingHorizontal: 24, paddingTop: 16, paddingBottom: 8 },
  title: { fontSize: 28, fontWeight: '700', color: colors.text },
  subtitle: { fontSize: 14, color: colors.textSecondary, marginTop: 4 },
  scroll: { flex: 1 },
  content: { paddingHorizontal: 24, paddingBottom: 24 },
  section: { marginTop: 20 },
  sectionTitle: { fontSize: 18, fontWeight: '600', color: colors.text, marginBottom: 12 },
  loaderWrap: { paddingVertical: 24, alignItems: 'center' },
  placeholderCard: { padding: 20 },
  placeholderText: { fontSize: 16, color: colors.textSecondary },
  placeholderSub: { fontSize: 14, color: colors.textMuted, marginTop: 4 },
  aptCard: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  aptLeft: { flex: 1 },
  aptDate: { fontSize: 16, fontWeight: '600', color: colors.text },
  aptTime: { fontSize: 14, color: colors.textSecondary, marginTop: 2 },
  aptRight: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  aptStatus: { fontSize: 12, fontWeight: '600', color: colors.primary },
  aptArrow: { fontSize: 16, color: colors.textMuted },
  linkButton: {
    marginTop: 24,
    paddingVertical: 14,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.primary,
    borderRadius: 10,
  },
  linkButtonText: { fontSize: 16, fontWeight: '600', color: colors.primary },
});
