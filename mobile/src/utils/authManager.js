import { CommonActions } from '@react-navigation/native';
import { storage } from '../storage/storage';

/**
 * Gestor centralizado de logout para uso desde interceptores (sin depender de React).
 * En RN no hay window.location; el redirect se hace vía navigation ref (registrado por la app).
 * Usa createNavigationContainerRef() en App y dispatch(CommonActions.reset(...)) para redirigir.
 */
class AuthManager {
  constructor() {
    this.logoutCallbacks = [];
    this.navigationRef = null;
    this.isLoggingOut = false;
  }

  setNavigationRef(ref) {
    this.navigationRef = ref;
  }

  getNav() {
    return this.navigationRef?.current ?? this.navigationRef;
  }

  navigateToMain() {
    const nav = this.getNav();
    if (nav?.dispatch) {
      nav.dispatch(CommonActions.reset({ index: 0, routes: [{ name: 'Main' }] }));
    }
  }

  /**
   * Navegar al detalle de cita (cliente). Abre Main con PatientStack y muestra PatientAppointmentDetail.
   */
  navigateToAppointmentDetail(appointmentId) {
    const nav = this.getNav();
    if (!nav?.dispatch || !appointmentId) return;
    nav.dispatch(
      CommonActions.reset({
        index: 0,
        routes: [
          {
            name: 'Main',
            state: {
              routes: [
                { name: 'PatientTabs' },
                { name: 'PatientAppointmentDetail', params: { appointmentId: String(appointmentId) } },
              ],
              index: 1,
            },
          },
        ],
      })
    );
  }

  registerLogoutCallback(callback) {
    if (typeof callback === 'function') {
      this.logoutCallbacks.push(callback);
    }
  }

  unregisterLogoutCallback(callback) {
    this.logoutCallbacks = this.logoutCallbacks.filter((cb) => cb !== callback);
  }

  async logout(options = {}) {
    const { redirect = true, force = false } = options;
    if (this.isLoggingOut && !force) return;
    this.isLoggingOut = true;
    try {
      await storage.clearAuth();
      this.logoutCallbacks.forEach((cb) => {
        try {
          cb();
        } catch (e) {
          console.error('Logout callback error:', e);
        }
      });
      const nav = this.getNav();
      if (redirect && nav?.dispatch) {
        nav.dispatch(CommonActions.reset({ index: 0, routes: [{ name: 'Auth' }] }));
      }
    } catch (e) {
      console.error('Logout error:', e);
    } finally {
      setTimeout(() => {
        this.isLoggingOut = false;
      }, 500);
    }
  }

  handleAuthError(error) {
    console.log('handleAuthError')
    console.log(error)
    const status = error?.response?.status;
    if (status === 401 || status === 419) {
      this.logout({ redirect: true });
    }
  }
}

export const authManager = new AuthManager();
