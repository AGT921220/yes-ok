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
import { availabilityBlocksApi } from '../services/api';
import { getApiErrorMessage } from '../utils/apiError';

function normalizeList(res) {
  const data = res.data?.data ?? res.data;
  return Array.isArray(data) ? data : [];
}

export default function AvailabilityBlocksScreen() {
  const navigation = useNavigation();
  const [blocks, setBlocks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchBlocks = useCallback(async () => {
    try {
      const res = await availabilityBlocksApi.list();
      setBlocks(normalizeList(res));
    } catch (err) {
      Alert.alert('Error', getApiErrorMessage(err, 'Error al cargar bloqueos'));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchBlocks();
  }, [fetchBlocks]);

  const renderItem = ({ item }) => {
    const start = item.start_date ? new Date(item.start_date).toLocaleDateString('es-MX') : '—';
    const end = item.end_date ? new Date(item.end_date).toLocaleDateString('es-MX') : '—';
    return (
      <Card style={styles.card}>
        <Text style={styles.title}>{item.title || 'Bloqueo'}</Text>
        <Text style={styles.dates}>{start} – {end}</Text>
        {item.notes ? <Text style={styles.notes}>{item.notes}</Text> : null}
      </Card>
    );
  };

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <Text style={styles.back} onPress={() => navigation.goBack()}>← Volver</Text>
      <View style={styles.header}>
        <Text style={styles.titleHeader}>Bloqueos</Text>
        <Text style={styles.subtitle}>Disponibilidad y bloqueos de agenda</Text>
      </View>
      {loading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={blocks}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderItem}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>No hay bloqueos</Text>
            </View>
          }
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchBlocks(); }} colors={[colors.primary]} />
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
  titleHeader: { fontSize: 28, fontWeight: '700', color: colors.text },
  subtitle: { fontSize: 14, color: colors.textSecondary, marginTop: 4 },
  list: { paddingHorizontal: 24, paddingBottom: 24 },
  card: { marginBottom: 12 },
  title: { fontSize: 17, fontWeight: '600', color: colors.text },
  dates: { fontSize: 14, color: colors.textSecondary, marginTop: 4 },
  notes: { fontSize: 13, color: colors.textMuted, marginTop: 4 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  empty: { paddingVertical: 48, alignItems: 'center' },
  emptyText: { fontSize: 16, color: colors.textMuted },
});
