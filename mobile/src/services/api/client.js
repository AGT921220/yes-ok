import axios from 'axios';
import { storage } from '../../storage/storage';
import { authManager } from '../../utils/authManager';

const rawUrl = process.env.EXPO_PUBLIC_API_URL || '';
const baseURL = rawUrl
  ? `${rawUrl.startsWith('http') ? rawUrl : `http://${rawUrl}`}/api/v1`
  : 'http://localhost/api/v1';
console.log(baseURL)
export const apiClient = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 10000,
});

apiClient.interceptors.request.use(
  async (config) => {
    const token = await storage.getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (err) => Promise.reject(err)
);

apiClient.interceptors.response.use(
  (res) => res,
  async (err) => {
    if (err?.response?.status === 401 || err?.response?.status === 419) {
      authManager.handleAuthError(err);
    }
    return Promise.reject(err);
  }
);

export default apiClient;
