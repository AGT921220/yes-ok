import React, { useEffect, useRef } from 'react';
import { View, Text, StyleSheet, Animated, Image } from 'react-native';
import { colors } from '../theme';

const INTRO_DURATION_MS = 1800;

/**
 * Pantalla de intro con animación.
 * Si existe assets/intro.gif se usa; si no, se muestra un placeholder.
 * Tras INTRO_DURATION_MS redirige a "Main" o "Auth" según haya token (lo decide el navigator).
 */
export default function IntroScreen({ onFinish }) {
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const scaleAnim = useRef(new Animated.Value(0.9)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 600,
        useNativeDriver: true,
      }),
      Animated.timing(scaleAnim, {
        toValue: 1,
        duration: 600,
        useNativeDriver: true,
      }),
    ]).start();

    const t = setTimeout(() => {
      onFinish?.();
    }, INTRO_DURATION_MS);
    return () => clearTimeout(t);
  }, [fadeAnim, scaleAnim, onFinish]);

  return (
    <View style={styles.container}>
      <Animated.View
        style={[
          styles.box,
          {
            opacity: fadeAnim,
            transform: [{ scale: scaleAnim }],
          },
        ]}
      >
        {/* Placeholder: reemplaza por <Image source={require('../assets/intro.gif')} style={styles.gif} /> cuando exista intro.gif */}
        <View style={styles.placeholder}>
          <Text style={styles.placeholderText}>yes-ok</Text>
          <Text style={styles.placeholderSub}>Agenda Médica</Text>
        </View>
      </Animated.View>
      <Text style={styles.footer}>Desarrollado por Agsoftweb</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
    justifyContent: 'center',
    alignItems: 'center',
  },
  box: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  placeholder: {
    width: 200,
    height: 200,
    borderRadius: 24,
    backgroundColor: colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
  },
  placeholderText: {
    fontSize: 22,
    fontWeight: '700',
    color: '#fff',
  },
  placeholderSub: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.9)',
    marginTop: 4,
  },
  footer: {
    position: 'absolute',
    bottom: 40,
    fontSize: 12,
    color: colors.textMuted,
  },
});
