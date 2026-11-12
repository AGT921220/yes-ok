import apiClient from './client';

export const authApi = {
  login(identifier, password, type = 'email') {
    const payload = { password };
    if (type === 'email') payload.email = identifier;
    else payload.phone = identifier;

    
    return apiClient.post('/auth/login', payload);
  },
  getMe() {
    return apiClient.get('/auth/me');
  },
};
