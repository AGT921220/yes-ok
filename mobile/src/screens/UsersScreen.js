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
import { usersApi } from '../services/api';
import { getApiErrorMessage } from '../utils/apiError';

function normalizeList(res) {
  const data = res.data?.data ?? res.data;
  return Array.isArray(data) ? data : [];
}

export default function UsersScreen() {
  const navigation = useNavigation();
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchUsers = useCallback(async () => {
    try {
      const res = await usersApi.list();
      setUsers(normalizeList(res));
    } catch (err) {
      Alert.alert('Error', getApiErrorMessage(err, 'Error al cargar usuarios'));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchUsers();
  }, [fetchUsers]);

  const renderItem = ({ item }) => (
    <Card style={styles.card}>
      <Text style={styles.name}>{item.name || item.email || 'Usuario'}</Text>
      <Text style={styles.email}>{item.email || '—'}</Text>
      <Text style={styles.role}>{item.role || '—'}</Text>
    </Card>
  );

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <Text style={styles.back} onPress={() => navigation.goBack()}>← Volver</Text>
      <View style={styles.header}>
        <Text style={styles.title}>Usuarios</Text>
        <Text style={styles.subtitle}>Gestión de asistentes (solo admin)</Text>
      </View>
      {loading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={users}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderItem}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>No hay usuarios</Text>
            </View>
          }
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchUsers(); }} colors={[colors.primary]} />
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
  name: { fontSize: 17, fontWeight: '600', color: colors.text },
  email: { fontSize: 14, color: colors.textSecondary, marginTop: 2 },
  role: { fontSize: 12, color: colors.textMuted, marginTop: 2 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  empty: { paddingVertical: 48, alignItems: 'center' },
  emptyText: { fontSize: 16, color: colors.textMuted },
});
