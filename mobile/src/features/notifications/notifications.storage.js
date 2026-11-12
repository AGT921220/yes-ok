import AsyncStorage from '@react-native-async-storage/async-storage';

const KEYS = {
  PUSH_TOKEN: 'yes-ok_push_token',
  PERMISSION_PROMPT_SKIPPED_AT: 'push_permission_prompt_last_skipped_at',
};

const SKIP_COOLDOWN_DAYS = 7;

export const notificationsStorage = {
  async getStoredPushToken() {
    return AsyncStorage.getItem(KEYS.PUSH_TOKEN);
  },

  async setStoredPushToken(value) {
    if (value == null) {
      await AsyncStorage.removeItem(KEYS.PUSH_TOKEN);
    } else {
      await AsyncStorage.setItem(KEYS.PUSH_TOKEN, value);
    }
  },

  async getPermissionPromptSkippedAt() {
    const raw = await AsyncStorage.getItem(KEYS.PERMISSION_PROMPT_SKIPPED_AT);
    return raw ? new Date(raw) : null;
  },

  async setPermissionPromptSkippedNow() {
    await AsyncStorage.setItem(KEYS.PERMISSION_PROMPT_SKIPPED_AT, new Date().toISOString());
  },

  /**
   * True si el usuario eligió "Ahora no" hace menos de SKIP_COOLDOWN_DAYS días.
   */
  async shouldSuppressPrompt() {
    const skippedAt = await this.getPermissionPromptSkippedAt();
    if (!skippedAt) return false;
    const daysSince = (Date.now() - skippedAt.getTime()) / (1000 * 60 * 60 * 24);
    return daysSince < SKIP_COOLDOWN_DAYS;
  },

  async clearPushData() {
    await AsyncStorage.multiRemove([KEYS.PUSH_TOKEN, KEYS.PERMISSION_PROMPT_SKIPPED_AT]);
  },
};
