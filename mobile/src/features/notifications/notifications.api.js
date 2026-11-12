import apiClient from '../../services/api/client';

const MAX_RETRIES = 2;

/**
 * POST /api/v1/push-tokens — registrar o actualizar token (idempotente).
 * @param {{ token: string, platform: 'ios'|'android', deviceName?: string, appVersion?: string }} payload
 */
export async function registerPushToken(payload) {
  const body = {
    token: payload.token,
    platform: payload.platform,
    device_name: payload.deviceName ?? null,
    app_version: payload.appVersion ?? null,
  };
  let lastErr;
  for (let attempt = 0; attempt <= MAX_RETRIES; attempt++) {
    try {
      const res = await apiClient.post('/push-tokens', body);
      return res.data;
    } catch (err) {
      lastErr = err;
      if (err?.response?.status === 409) {
        throw err;
      }
      if (attempt < MAX_RETRIES && err?.response?.status >= 500) {
        await new Promise((r) => setTimeout(r, 1000 * (attempt + 1)));
        continue;
      }
      throw err;
    }
  }
  throw lastErr;
}

/**
 * DELETE /api/v1/push-tokens — revocar token del usuario actual.
 */
export async function revokePushToken(token) {
  const res = await apiClient.delete('/push-tokens', { data: { token } });
  return res.data;
}
