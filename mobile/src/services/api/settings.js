import apiClient from './client';

export const settingsApi = {
  list() {
    return apiClient.get('/settings');
  },
  update(key, value) {
    return apiClient.put(`/settings/${key}`, { value });
  },
};
