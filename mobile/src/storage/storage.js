import AsyncStorage from '@react-native-async-storage/async-storage';

const KEYS = {
  TOKEN: 'yes-ok_token',
  USER: 'yes-ok_user',
  PATIENT_TOKEN: 'patient_token',
};

export const storage = {
  async getToken() {
    return AsyncStorage.getItem(KEYS.TOKEN);
  },
  async setToken(value) {
    if (value == null) {
      await AsyncStorage.removeItem(KEYS.TOKEN);
    } else {
      await AsyncStorage.setItem(KEYS.TOKEN, value);
    }
  },
  async getUser() {
    const raw = await AsyncStorage.getItem(KEYS.USER);
    return raw ? JSON.parse(raw) : null;
  },
  async setUser(value) {
    if (value == null) {
      await AsyncStorage.removeItem(KEYS.USER);
    } else {
      await AsyncStorage.setItem(KEYS.USER, JSON.stringify(value));
    }
  },
  async clearAuth() {
    await AsyncStorage.multiRemove([KEYS.TOKEN, KEYS.USER, KEYS.PATIENT_TOKEN]);
  },
};
