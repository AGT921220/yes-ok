import apiClient from './client';

/** Endpoints para usuario con rol CLIENTE (portal del cliente) */
export const meApi = {
  getProfile() {
    return apiClient.get('/me/profile');
  },
  updateProfile(data) {
    return apiClient.put('/me/profile', data);
  },
  getAppointments(params) {
    return apiClient.get('/me/appointments', { params: params || {} });
  },
  getAppointment(id) {
    return apiClient.get(`/me/appointments/${id}`);
  },
};
