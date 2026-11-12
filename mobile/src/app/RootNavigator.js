import React, { useEffect } from 'react';
const __DEV__ = typeof global !== 'undefined' && global.__DEV__;
import * as Notifications from 'expo-notifications';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../context/AuthContext';
import { authManager } from '../utils/authManager';
import { syncTokenWithBackend } from '../services/pushNotifications';
import { Loader } from '../components/ui/Loader';
import IntroScreen from '../screens/IntroScreen';
import AuthStack from './AuthStack';
import MainStack from './MainStack';

const Stack = createNativeStackNavigator();

export default function RootNavigator() {
  const { user, loading } = useAuth();

  useEffect(() => {
    const received = Notifications.addNotificationReceivedListener((notification) => {
      if (__DEV__) {
        const data = notification.request.content?.data ?? {};
        console.log('[RootNavigator] Notification received (foreground):', data);
      }
    });
    const response = Notifications.addNotificationResponseReceivedListener((res) => {
      const data = res.notification.request.content?.data || {};
      if (data.type === 'APPOINTMENT_REMINDER' && data.appointment_id) {
        authManager.navigateToAppointmentDetail(data.appointment_id);
      }
      if (__DEV__) console.log('[RootNavigator] Notification response:', data);
    });
    return () => {
      received.remove();
      response.remove();
    };
  }, []);

  useEffect(() => {
    if (user) {
      syncTokenWithBackend();
    }
  }, [user]);

  if (loading) {
    return <Loader message="Cargando..." />;
  }

  return (
    <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName="Intro">
      <Stack.Screen name="Intro">
        {(props) => (
          <IntroScreen
            {...props}
            onFinish={() => {
              props.navigation.replace(user ? 'Main' : 'Auth');
            }}
          />
        )}
      </Stack.Screen>
      <Stack.Screen name="Auth" component={AuthStack} />
      <Stack.Screen name="Main" component={MainStack} />
    </Stack.Navigator>
  );
}
