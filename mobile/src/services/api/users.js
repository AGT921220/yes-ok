import apiClient from './client';

export const usersApi = {
  list() {
    return apiClient.get('/users');
  },
  create(data) {
    return apiClient.post('/users', data);
  },
  update(id, data) {
    return apiClient.put(`/users/${id}`, data);
  },
  delete(id) {
    return apiClient.delete(`/users/${id}`);
  },
};
