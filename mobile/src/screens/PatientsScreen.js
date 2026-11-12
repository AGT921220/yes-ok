import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TextInput,
  ActivityIndicator,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { colors } from '../theme';
import { Card } from '../components/ui/Card';
import { patientsApi } from '../services/api';
import { getApiErrorMessage } from '../utils/apiError';

function normalizeList(res) {
  const data = res.data?.data ?? res.data;
  return Array.isArray(data) ? data : [];
}

export default function PatientsScreen() {
  const [patients, setPatients] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchPatients = useCallback(async () => {
    try {
      const res = await patientsApi.list(searchTerm || undefined);
      setPatients(normalizeList(res));
    } catch (err) {
      Alert.alert('Error', getApiErrorMessage(err, 'Error al cargar clientes'));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [searchTerm]);

  useEffect(() => {
    setLoading(true);
    fetchPatients();
  }, [fetchPatients]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchPatients();
  };

  const fullName = (p) =>
    [p.first_name, p.paternal_surname, p.maternal_surname].filter(Boolean).join(' ') || p.name || 'Sin nombre';

  const renderItem = ({ item }) => (
    <Card style={styles.itemCard}>
      <View style={styles.avatar}>
        <Text style={styles.avatarText}>
          {(item.first_name?.charAt(0) || 'P') + (item.paternal_surname?.charAt(0) || '')}
        </Text>
      </View>
      <View style={styles.itemBody}>
        <Text style={styles.itemName}>{fullName(item)}</Text>
        <Text style={styles.itemPhone}>{item.whatsapp || item.phone || '—'}</Text>
        {item.email ? <Text style={styles.itemEmail}>{item.email}</Text> : null}
      </View>
    </Card>
  );

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.header}>
        <Text style={styles.title}>Clientes</Text>
        <Text style={styles.subtitle}>Busca y gestiona clientes</Text>
      </View>
      <TextInput
        style={styles.search}
        placeholder="Buscar por nombre o teléfono..."
        placeholderTextColor={colors.textMuted}
        value={searchTerm}
        onChangeText={setSearchTerm}
      />
      {loading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={patients}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderItem}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>No hay clientes</Text>
              <Text style={styles.emptySub}>Registra clientes desde la web o agrega aquí (próximamente)</Text>
            </View>
          }
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />
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
  search: {
    marginHorizontal: 24,
    marginVertical: 12,
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: colors.surface,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.border,
    fontSize: 16,
    color: colors.text,
  },
  list: { paddingHorizontal: 24, paddingBottom: 24 },
  itemCard: { flexDirection: 'row', alignItems: 'center' },
  avatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 14,
  },
  avatarText: { fontSize: 18, fontWeight: '600', color: '#fff' },
  itemBody: { flex: 1 },
  itemName: { fontSize: 17, fontWeight: '600', color: colors.text },
  itemPhone: { fontSize: 14, color: colors.textSecondary, marginTop: 2 },
  itemEmail: { fontSize: 12, color: colors.textMuted, marginTop: 2 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  empty: { paddingVertical: 48, alignItems: 'center' },
  emptyText: { fontSize: 18, fontWeight: '600', color: colors.text },
  emptySub: { fontSize: 14, color: colors.textMuted, marginTop: 8, textAlign: 'center' },
});
