import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme';

import DashboardScreen from '../screens/DashboardScreen';
import PatientsScreen from '../screens/PatientsScreen';
import MoreMenuScreen from '../screens/MoreMenuScreen';
import RescheduleQueueScreen from '../screens/RescheduleQueueScreen';
import AvailabilityBlocksScreen from '../screens/AvailabilityBlocksScreen';
import ReportsScreen from '../screens/ReportsScreen';
import UsersScreen from '../screens/UsersScreen';
import SettingsScreen from '../screens/SettingsScreen';
import SettingsNotificationsScreen from '../screens/SettingsNotificationsScreen';

import PatientHomeScreen from '../screens/PatientHomeScreen';
import PatientAppointmentsScreen from '../screens/PatientAppointmentsScreen';
import PatientProfileScreen from '../screens/PatientProfileScreen';
import PatientAppointmentDetailScreen from '../screens/PatientAppointmentDetailScreen';
import { PermissionPromptModal } from '../features/notifications/PermissionPromptModal';
import { usePushNotificationsRegistration } from '../features/notifications/usePushNotificationsRegistration';
import { notificationsStorage } from '../features/notifications/notifications.storage';

const Tab = createBottomTabNavigator();
const Stack = createNativeStackNavigator();

function StaffTabs() {
  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
      }}
    >
      <Tab.Screen
        name="Agenda"
        component={DashboardScreen}
        options={{
          tabBarLabel: 'Agenda',
          tabBarIcon: ({ color, size }) => <Ionicons name="calendar-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="Clientes"
        component={PatientsScreen}
        options={{
          tabBarLabel: 'Clientes',
          tabBarIcon: ({ color, size }) => <Ionicons name="people-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="Más"
        component={MoreMenuScreen}
        options={{
          tabBarLabel: 'Más',
          tabBarIcon: ({ color, size }) => <Ionicons name="menu-outline" size={size} color={color} />,
        }}
      />
    </Tab.Navigator>
  );
}

function PatientTabs() {
  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
      }}
    >
      <Tab.Screen
        name="Inicio"
        component={PatientHomeScreen}
        options={{
          tabBarLabel: 'Inicio',
          tabBarIcon: ({ color, size }) => <Ionicons name="home-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="MisCitas"
        component={PatientAppointmentsScreen}
        options={{
          tabBarLabel: 'Mis Citas',
          tabBarIcon: ({ color, size }) => <Ionicons name="calendar-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="MiCuenta"
        component={PatientProfileScreen}
        options={{
          tabBarLabel: 'Mi Cuenta',
          tabBarIcon: ({ color, size }) => <Ionicons name="person-outline" size={size} color={color} />,
        }}
      />
    </Tab.Navigator>
  );
}

function StaffStack() {
  return (
    <Stack.Navigator
      screenOptions={{
        headerShown: false,
      }}
      initialRouteName="StaffTabs"
    >
      <Stack.Screen name="StaffTabs" component={StaffTabs} />
      <Stack.Screen name="RescheduleQueue" component={RescheduleQueueScreen} />
      <Stack.Screen name="AvailabilityBlocks" component={AvailabilityBlocksScreen} />
      <Stack.Screen name="Reports" component={ReportsScreen} />
      <Stack.Screen name="Users" component={UsersScreen} />
      <Stack.Screen name="Settings" component={SettingsScreen} />
      <Stack.Screen name="SettingsNotifications" component={SettingsNotificationsScreen} />
    </Stack.Navigator>
  );
}

function PatientStack() {
  return (
    <Stack.Navigator
      screenOptions={{
        headerShown: false,
      }}
      initialRouteName="PatientTabs"
    >
      <Stack.Screen name="PatientTabs" component={PatientTabs} />
      <Stack.Screen name="PatientAppointmentDetail" component={PatientAppointmentDetailScreen} />
      <Stack.Screen name="SettingsNotifications" component={SettingsNotificationsScreen} />
    </Stack.Navigator>
  );
}

function PushPermissionPromptGate({ children }) {
  const [showModal, setShowModal] = React.useState(false);
  const [suppressChecked, setSuppressChecked] = React.useState(false);
  const {
    status,
    isLoading,
    error,
    requestPermissionAndRegister,
    skipForNow: skip,
    refreshPermissionStatus,
  } = usePushNotificationsRegistration();

  React.useEffect(() => {
    let mounted = true;
    (async () => {
      const suppressed = await notificationsStorage.shouldSuppressPrompt();
      if (!mounted) return;
      setSuppressChecked(true);
      if (status !== 'granted' && !suppressed) setShowModal(true);
    })();
    return () => { mounted = false; };
  }, [status]);

  const handleAllow = async () => {
    const result = await requestPermissionAndRegister();
    if (result?.ok) {
      setShowModal(false);
      refreshPermissionStatus();
    }
  };

  const handleSkip = async () => {
    await skip();
    setShowModal(false);
  };

  return (
    <>
      {children}
      {suppressChecked && (
        <PermissionPromptModal
          visible={showModal}
          onAllow={handleAllow}
          onSkip={handleSkip}
          isLoading={isLoading}
          error={error}
        />
      )}
    </>
  );
}

export default function MainStack() {
  const { isPatient } = useAuth();
  const Stack = isPatient ? PatientStack : StaffStack;
  return (
    <PushPermissionPromptGate>
      <Stack />
    </PushPermissionPromptGate>
  );
}
