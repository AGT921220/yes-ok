import React, { useEffect } from 'react';
import { Platform } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { NavigationContainer, createNavigationContainerRef } from '@react-navigation/native';
import * as SplashScreen from 'expo-splash-screen';
import { AuthProvider } from './src/context/AuthContext';
import { authManager } from './src/utils/authManager';
import { ensureAppointmentsChannel } from './src/services/pushNotifications';
import RootNavigator from './src/app/RootNavigator';

SplashScreen.preventAutoHideAsync();

const navigationRef = createNavigationContainerRef();

export default function App() {
  useEffect(() => {
    SplashScreen.hideAsync();
  }, []);

  useEffect(() => {
    authManager.setNavigationRef(navigationRef);
  }, []);

  useEffect(() => {
    if (Platform.OS === 'android') {
      ensureAppointmentsChannel();
    }
  }, []);

  return (
    <AuthProvider>
      <NavigationContainer ref={navigationRef}>
        <RootNavigator />
      </NavigationContainer>
      <StatusBar style="dark" />
    </AuthProvider>
  );
}
