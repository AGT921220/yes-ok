/**
 * Extrae mensaje de error de la respuesta API (mismo criterio que frontend web)
 */
export function getApiErrorMessage(error, fallbackMessage) {
  if (!error || !error.response || !error.response.data) {
    return fallbackMessage;
  }
  const data = error.response.data;
  if (typeof data.message === 'string' && data.message.trim().length > 0) {
    return data.message;
  }
  if (data.error?.message) {
    return data.error.message;
  }
  const errors = data.errors || data.error?.details;
  if (errors && typeof errors === 'object') {
    const first = Object.values(errors)[0];
    if (Array.isArray(first) && first.length > 0) return first[0];
    if (typeof first === 'string') return first;
  }
  if (typeof data.detail === 'string' && data.detail.trim().length > 0) {
    return data.detail;
  }
  if (error.message) return error.message;
  return fallbackMessage;
}
