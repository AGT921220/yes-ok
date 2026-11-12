import apiClient from './client';

export const pushTokensApi = {
  register(token, platform = 'android', deviceName = null, appVersion = null) {
    return apiClient.post('/push-tokens', {
      token,
      platform,
      device_name: deviceName ?? undefined,
      app_version: appVersion ?? undefined,
    });
  },
  revoke(token) {
    return apiClient.delete('/push-tokens', { data: { token } });
  },
};
