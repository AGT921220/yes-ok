import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TextInput,
  ActivityIndicator,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme';
import { meApi } from '../services/api';
import { getApiErrorMessage } from '../utils/apiError';
import { useAuth } from '../context/AuthContext';

export default function PatientProfileScreen() {
  const navigation = useNavigation();
  const { logout } = useAuth();
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [edit, setEdit] = useState(false);
  const [form, setForm] = useState({ email: '', consent_notifications: true });

  useEffect(() => {
    (async () => {
      try {
        const res = await meApi.getProfile();
        const data = res.data?.data ?? res.data;
        setProfile(data);
        setForm({
          email: data?.email || '',
          consent_notifications: data?.consent_notifications ?? true,
        });
      } catch (err) {
        Alert.alert('Error', getApiErrorMessage(err, 'Error al cargar perfil'));
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const handleSave = async () => {
    setSaving(true);
    try {
      const res = await meApi.updateProfile(form);
      setProfile(res.data?.data ?? res.data);
      setEdit(false);
      Alert.alert('Listo', 'Perfil actualizado');
    } catch (err) {
      Alert.alert('Error', getApiErrorMessage(err, 'Error al guardar'));
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.container} edges={['top']}>
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      </SafeAreaView>
    );
  }

  if (!profile) {
    return (
      <SafeAreaView style={styles.container} edges={['top']}>
        <Text style={styles.empty}>No se pudo cargar tu perfil.</Text>
      </SafeAreaView>
    );
  }

  const displayName = profile.full_name || profile.name || [profile.first_name, profile.paternal_surname].filter(Boolean).join(' ') || 'Cliente';

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.header}>
        <Text style={styles.title}>Mi cuenta</Text>
        <Text style={styles.subtitle}>Datos de tu perfil (correo y notificaciones)</Text>
      </View>
      <ScrollView style={styles.scroll} contentContainerStyle={styles.content}>
        <View style={styles.card}>
          <Text style={styles.label}>Nombre</Text>
          <Text style={styles.value}>{displayName}</Text>
        </View>
        <View style={styles.card}>
          <Text style={styles.label}>Teléfono / WhatsApp</Text>
          <Text style={styles.value}>{profile.whatsapp || profile.phone || '—'}</Text>
        </View>
        {edit ? (
          <>
            <View style={styles.card}>
              <Text style={styles.label}>Correo</Text>
              <TextInput
                style={styles.input}
                value={form.email}
                onChangeText={(t) => setForm({ ...form, email: t })}
                placeholder="Correo"
                placeholderTextColor={colors.textMuted}
                keyboardType="email-address"
                autoCapitalize="none"
              />
            </View>
            <TouchableOpacity
              style={[styles.button, saving && styles.buttonDisabled]}
              onPress={handleSave}
              disabled={saving}
            >
              <Text style={styles.buttonText}>{saving ? 'Guardando...' : 'Guardar'}</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.buttonSecondary} onPress={() => setEdit(false)}>
              <Text style={styles.buttonSecondaryText}>Cancelar</Text>
            </TouchableOpacity>
          </>
        ) : (
          <>
            <View style={styles.card}>
              <Text style={styles.label}>Correo</Text>
              <Text style={styles.value}>{profile.email || '—'}</Text>
            </View>
            <View style={styles.card}>
              <Text style={styles.label}>Notificaciones</Text>
              <Text style={styles.value}>{profile.consent_notifications ? 'Sí' : 'No'}</Text>
            </View>
            <TouchableOpacity style={styles.button} onPress={() => setEdit(true)}>
              <Text style={styles.buttonText}>Editar correo / notificaciones</Text>
            </TouchableOpacity>
          </>
        )}
        <TouchableOpacity
          style={styles.settingsRow}
          onPress={() => navigation.navigate('SettingsNotifications')}
        >
          <Ionicons name="notifications-outline" size={22} color={colors.text} />
          <Text style={styles.settingsRowText}>Notificaciones push</Text>
          <Ionicons name="chevron-forward" size={20} color={colors.textMuted} />
        </TouchableOpacity>
        <TouchableOpacity
          style={styles.logoutButton}
          onPress={() => {
            Alert.alert(
              'Cerrar sesión',
              '¿Seguro que quieres salir?',
              [
                { text: 'Cancelar', style: 'cancel' },
                { text: 'Cerrar sesión', style: 'destructive', onPress: () => logout() },
              ]
            );
          }}
        >
          <Ionicons name="log-out-outline" size={22} color={colors.error || '#b91c1c'} />
          <Text style={styles.logoutText}>Cerrar sesión</Text>
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
  card: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 16,
    marginBottom: 12,
  },
  label: { fontSize: 12, color: colors.textMuted },
  value: { fontSize: 16, fontWeight: '500', color: colors.text, marginTop: 4 },
  input: {
    marginTop: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    backgroundColor: colors.background,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.border,
    fontSize: 16,
    color: colors.text,
  },
  button: {
    backgroundColor: colors.primary,
    paddingVertical: 14,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 8,
  },
  buttonDisabled: { opacity: 0.6 },
  buttonText: { fontSize: 16, fontWeight: '600', color: '#fff' },
  buttonSecondary: {
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 8,
  },
  buttonSecondaryText: { fontSize: 16, color: colors.primary },
  settingsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 16,
    marginTop: 16,
    gap: 12,
  },
  settingsRowText: { fontSize: 16, color: colors.text, flex: 1 },
  logoutButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    marginTop: 24,
    paddingVertical: 14,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.error || '#b91c1c',
  },
  logoutText: { fontSize: 16, fontWeight: '600', color: colors.error || '#b91c1c' },
  empty: { padding: 24, fontSize: 16, color: colors.textMuted },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
