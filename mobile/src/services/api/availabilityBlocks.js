import apiClient from './client';

export const availabilityBlocksApi = {
  list(params) {
    return apiClient.get('/availability-blocks', { params: params || {} });
  },
  create(data) {
    return apiClient.post('/availability-blocks', data);
  },
  delete(id) {
    return apiClient.delete(`/availability-blocks/${id}`);
  },
};
