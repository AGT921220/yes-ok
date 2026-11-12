# yes-ok - PRD (Product Requirements Document)

## Original Problem Statement
Sistema para PSIQUIATRA llamado yes-ok. Stack: React (responsive) + FastAPI + PostgreSQL. Sistema funcional de agenda + expediente clínico. Web primero (responsive). Arquitectura SIMPLE, clara y ordenada.

## User Personas
1. **ADMIN (Dr. Psiquiatra)**: Configura sistema, administra usuarios, acceso total
2. **ASISTENTE**: Gestiona citas, registra pacientes, captura notas

## Core Requirements
- Agenda con vista día/semana/mes
- Estados de cita: PENDIENTE, CONFIRMADA, CANCELADA, NO_SHOW, COMPLETADA, REQUIERE_REAGENDAR
- Bloqueos de disponibilidad
- Historia clínica (una vez, se cierra, admin desbloquea)
- Notas SOAP con estados BORRADOR/FIRMADA y addendums
- Reportes con exportación PDF/Excel
- Notificaciones en modo sandbox (logs)
- Documentación para migración a Laravel

## What's Been Implemented (2026-02-05)

### Backend (FastAPI + PostgreSQL)
- ✅ Autenticación JWT
- ✅ CRUD completo de usuarios con roles
- ✅ CRUD de pacientes con búsqueda
- ✅ Gestión de citas con validaciones (empalmes, pasado, bloqueos)
- ✅ Bloqueos de disponibilidad con marcado automático
- ✅ Historia clínica con cierre/desbloqueo
- ✅ Notas SOAP con firma y addendums
- ✅ Reportes y estadísticas
- ✅ Exportación PDF y Excel
- ✅ Logs de notificaciones (sandbox)
- ✅ Sistema de configuración
- ✅ Logs de auditoría

### Frontend (React + Tailwind + Shadcn)
- ✅ Login responsive
- ✅ Dashboard con calendario interactivo
- ✅ Gestión de pacientes
- ✅ Expedientes clínicos con tabs
- ✅ Notas SOAP con panel de edición
- ✅ Bandeja "Por Reagendar"
- ✅ Bloqueos de disponibilidad
- ✅ Reportes con filtros y exportación
- ✅ Gestión de usuarios (admin)
- ✅ Configuración del sistema (admin)

### Documentación
- ✅ /docs/architecture/overview.md
- ✅ /docs/database/schema.md
- ✅ /docs/laravel-migration-guide/README.md
- ✅ /docs/laravel-migration-guide/use-cases.md

## Prioritized Backlog

### P0 (Crítico) - Completado
- [x] Login y autenticación
- [x] Calendario funcional
- [x] CRUD de pacientes y citas
- [x] Expediente clínico básico

### P1 (Alto)
- [ ] Integración real WhatsApp/SMS
- [ ] OTP de verificación de pacientes
- [ ] Buffer automático entre citas
- [ ] Recurrencia en bloqueos

### P2 (Medio)
- [ ] Plantillas de recordatorios personalizables
- [ ] Dashboard con métricas en tiempo real
- [ ] Filtros avanzados en reportes
- [ ] Historial de cambios de citas

### P3 (Bajo)
- [ ] App móvil nativa
- [ ] Integración con calendarios externos
- [ ] Facturación básica
- [ ] Multi-idioma

## Next Tasks
1. Integrar WhatsApp/SMS real (Twilio)
2. Implementar verificación OTP
3. Agregar buffer automático configurable
4. Dashboard con gráficas de tendencias
