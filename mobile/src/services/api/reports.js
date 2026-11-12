import apiClient from './client';

export const reportsApi = {
  appointmentStats(params) {
    return apiClient.get('/reports/appointments-stats', { params: params || {} });
  },
};
