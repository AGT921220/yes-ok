import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  Alert,
  Image,
} from 'react-native';
import { useAuth } from '../context/AuthContext';
import { getApiErrorMessage } from '../utils/apiError';
import { authManager } from '../utils/authManager';
import { colors } from '../theme';

// Colores fijos en login para evitar texto blanco en APK release (theme puede variar por entorno)
const LOGIN_TEXT = '#000000';
const LOGIN_PLACEHOLDER = '#666666';
const LOGIN_LABEL_MUTED = '#6B7280';

export default function LoginScreen({ navigation }) {
  const [loginType, setLoginType] = useState('email');
  const [email, setEmail] = useState('admin@yes-ok.com');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('admin123');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();

  const onPhoneChange = (text) => {
    setPhone(text.replace(/\D/g, '').slice(0, 10));
  };

  const handleSubmit = async () => {
    const identifier = loginType === 'email' ? email : phone;
    if (!identifier.trim() || !password.trim()) {
      Alert.alert('Error', 'Completa todos los campos');
      return;
    }
    setLoading(true);
    try {
      await login(identifier, password, loginType);
      authManager.navigateToMain();
    } catch (err) {
      const msg = getApiErrorMessage(err, 'Error al iniciar sesión');
      Alert.alert('Error', msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <View style={styles.card}>
        <Image
          source={require('../../assets/yes-ok.png')}
          style={styles.logo}
          resizeMode="contain"
        />
        <Text style={styles.title}>yes-ok</Text>
        <Text style={styles.subtitle}>Agenda Médica Psiquiátrica</Text>

        <View style={styles.tabs}>
          <TouchableOpacity
            style={[styles.tab, loginType === 'email' && styles.tabActive]}
            onPress={() => setLoginType('email')}
          >
            <Text style={[styles.tabText, loginType === 'email' && styles.tabTextActive]}>Correo</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.tab, loginType === 'phone' && styles.tabActive]}
            onPress={() => setLoginType('phone')}
          >
            <Text style={[styles.tabText, loginType === 'phone' && styles.tabTextActive]}>Teléfono</Text>
          </TouchableOpacity>
        </View>

        {loginType === 'email' ? (
          <TextInput
            style={styles.input}
            placeholder="Correo electrónico"
            placeholderTextColor={LOGIN_PLACEHOLDER}
            value={email}
            onChangeText={setEmail}
            keyboardType="email-address"
            autoCapitalize="none"
          />
        ) : (
          <>
            <TextInput
              style={styles.input}
              placeholder="Teléfono (10 dígitos)"
              placeholderTextColor={LOGIN_PLACEHOLDER}
              value={phone}
              onChangeText={onPhoneChange}
              keyboardType="phone-pad"
              maxLength={10}
            />
            <Text style={styles.hint}>Solo 10 dígitos, sin LADA</Text>
          </>
        )}

        <TextInput
          style={styles.input}
          placeholder="Contraseña"
          placeholderTextColor={LOGIN_PLACEHOLDER}
          value={password}
          onChangeText={setPassword}
          secureTextEntry
        />

        <TouchableOpacity
          style={[styles.button, loading && styles.buttonDisabled]}
          onPress={handleSubmit}
          disabled={loading}
        >
          <Text style={styles.buttonText}>{loading ? 'Iniciando sesión...' : 'Iniciar sesión'}</Text>
        </TouchableOpacity>
      </View>
      <Text style={styles.footer}>Demo: admin@yes-ok.com / admin123</Text>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
    justifyContent: 'center',
    padding: 24,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 16,
    padding: 24,
    borderWidth: 1,
    borderColor: colors.border,
  },
  logo: {
    width: 160,
    height: 160,
    alignSelf: 'center',
    marginBottom: 16,
  },
  title: {
    fontSize: 28,
    fontWeight: '700',
    color: LOGIN_TEXT,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 14,
    color: LOGIN_LABEL_MUTED,
    textAlign: 'center',
    marginTop: 4,
    marginBottom: 24,
  },
  tabs: {
    flexDirection: 'row',
    marginBottom: 16,
    backgroundColor: colors.borderLight,
    borderRadius: 8,
    padding: 4,
  },
  tab: {
    flex: 1,
    paddingVertical: 10,
    alignItems: 'center',
    borderRadius: 6,
  },
  tabActive: {
    backgroundColor: colors.surface,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  tabText: { fontSize: 15, color: LOGIN_LABEL_MUTED },
  tabTextActive: { color: colors.primary, fontWeight: '600' },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontSize: 16,
    marginBottom: 12,
    backgroundColor: colors.surface,
    color: LOGIN_TEXT,
  },
  hint: {
    fontSize: 12,
    color: LOGIN_LABEL_MUTED,
    marginBottom: 12,
  },
  button: {
    backgroundColor: colors.primary,
    borderRadius: 999,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 8,
  },
  buttonDisabled: { opacity: 0.7 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
  footer: {
    textAlign: 'center',
    marginTop: 24,
    fontSize: 12,
    color: LOGIN_LABEL_MUTED,
  },
});
