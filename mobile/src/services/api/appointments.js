import apiClient from './client';

export const appointmentsApi = {
  list(params) {
    return apiClient.get('/appointments', { params: params || {} });
  },
  get(id) {
    return apiClient.get(`/appointments/${id}`);
  },
  create(data) {
    return apiClient.post('/appointments', data);
  },
  update(id, data) {
    return apiClient.put(`/appointments/${id}`, data);
  },
  delete(id) {
    return apiClient.delete(`/appointments/${id}`);
  },
  getRescheduleQueue() {
    return apiClient.get('/appointments/reschedule-queue');
  },
  updateAttendance(id, status) {
    return apiClient.patch(`/appointments/${id}/attendance`, { status });
  },
};
