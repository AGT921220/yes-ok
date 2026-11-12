import apiClient from './client';

export const patientsApi = {
  list(search) {
    return apiClient.get('/clients', { params: search ? { search } : {} });
  },
  get(id) {
    return apiClient.get(`/clients/${id}`);
  },
  create(data) {
    return apiClient.post('/clients', data);
  },
  update(id, data) {
    return apiClient.put(`/clients/${id}`, data);
  },
  delete(id) {
    return apiClient.delete(`/clients/${id}`);
  },
};
