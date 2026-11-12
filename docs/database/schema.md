# yes-ok - Esquema de Base de Datos

## Diagrama de Relaciones

```
users
  │
  ├──< appointments (created_by)
  ├──< availability_blocks (created_by)
  ├──< clinical_histories (closed_by, unlocked_by)
  ├──< soap_notes (created_by, signed_by)
  ├──< soap_note_addendums (created_by)
  ├──< settings (updated_by)
  └──< audit_logs (user_id)

patients
  │
  ├──< appointments
  ├──1 clinical_histories
  ├──< soap_notes
  └──< notification_logs

appointments
  │
  ├──< soap_notes
  └──< notification_logs
```

## Tablas

### users
| Campo | Tipo | Restricciones |
|-------|------|---------------|
| id | SERIAL | PRIMARY KEY |
| email | VARCHAR(255) | UNIQUE, NOT NULL |
| password_hash | VARCHAR(255) | NOT NULL |
| name | VARCHAR(255) | NOT NULL |
| role | ENUM('ADMIN', 'ASISTENTE') | DEFAULT 'ASISTENTE' |
| is_active | BOOLEAN | DEFAULT TRUE |
| created_at | TIMESTAMP | DEFAULT NOW() |
| updated_at | TIMESTAMP | DEFAULT NOW() |

### patients
| Campo | Tipo | Restricciones |
|-------|------|---------------|
| id | SERIAL | PRIMARY KEY |
| name | VARCHAR(255) | NOT NULL |
| whatsapp | VARCHAR(20) | NOT NULL, INDEX |
| email | VARCHAR(255) | NULLABLE |
| consent_notifications | BOOLEAN | DEFAULT TRUE |
| otp_verified | BOOLEAN | DEFAULT FALSE |
| created_at | TIMESTAMP | DEFAULT NOW() |
| updated_at | TIMESTAMP | DEFAULT NOW() |

### appointments
| Campo | Tipo | Restricciones |
|-------|------|---------------|
| id | SERIAL | PRIMARY KEY |
| patient_id | INTEGER | FK patients.id, NOT NULL |
| date | DATE | NOT NULL, INDEX |
| start_time | TIME | NOT NULL |
| end_time | TIME | NOT NULL |
| status | ENUM | DEFAULT 'PENDIENTE' |
| notes | TEXT | NULLABLE |
| reason | VARCHAR(500) | NULLABLE |
| created_by | INTEGER | FK users.id, NOT NULL |
| created_at | TIMESTAMP | DEFAULT NOW() |
| updated_at | TIMESTAMP | DEFAULT NOW() |

Estados de cita: `PENDIENTE`, `CONFIRMADA`, `CANCELADA`, `NO_SHOW`, `COMPLETADA`, `REQUIERE_REAGENDAR`

### availability_blocks
| Campo | Tipo | Restricciones |
|-------|------|---------------|
| id | SERIAL | PRIMARY KEY |
| start_date | DATE | NOT NULL, INDEX |
| end_date | DATE | NOT NULL |
| start_time | TIME | NULLABLE |
| end_time | TIME | NULLABLE |
| is_full_day | BOOLEAN | DEFAULT TRUE |
| reason | VARCHAR(500) | NULLABLE |
| is_recurring | BOOLEAN | DEFAULT FALSE |
| recurrence_pattern | VARCHAR(50) | NULLABLE |
| created_by | INTEGER | FK users.id |
| created_at | TIMESTAMP | DEFAULT NOW() |

### clinical_histories
| Campo | Tipo | Restricciones |
|-------|------|---------------|
| id | SERIAL | PRIMARY KEY |
| patient_id | INTEGER | FK patients.id, UNIQUE |
| status | ENUM('ABIERTA', 'CERRADA') | DEFAULT 'ABIERTA' |
| chief_complaint | TEXT | NULLABLE |
| history_present_illness | TEXT | NULLABLE |
| past_psychiatric_history | TEXT | NULLABLE |
| past_medical_history | TEXT | NULLABLE |
| family_history | TEXT | NULLABLE |
| social_history | TEXT | NULLABLE |
| medications | TEXT | NULLABLE |
| allergies | TEXT | NULLABLE |
| mental_status_exam | TEXT | NULLABLE |
| diagnosis | TEXT | NULLABLE |
| treatment_plan | TEXT | NULLABLE |
| closed_at | TIMESTAMP | NULLABLE |
| closed_by | INTEGER | FK users.id, NULLABLE |
| unlock_reason | TEXT | NULLABLE |
| unlocked_at | TIMESTAMP | NULLABLE |
| unlocked_by | INTEGER | FK users.id, NULLABLE |
| created_at | TIMESTAMP | DEFAULT NOW() |
| updated_at | TIMESTAMP | DEFAULT NOW() |

### soap_notes
| Campo | Tipo | Restricciones |
|-------|------|---------------|
| id | SERIAL | PRIMARY KEY |
| patient_id | INTEGER | FK patients.id, NOT NULL |
| appointment_id | INTEGER | FK appointments.id, NULLABLE |
| subjective | TEXT | NULLABLE |
| objective | TEXT | NULLABLE |
| assessment | TEXT | NULLABLE |
| plan | TEXT | NULLABLE |
| status | ENUM('BORRADOR', 'FIRMADA') | DEFAULT 'BORRADOR' |
| signed_at | TIMESTAMP | NULLABLE |
| signed_by | INTEGER | FK users.id, NULLABLE |
| created_at | TIMESTAMP | DEFAULT NOW() |
| updated_at | TIMESTAMP | DEFAULT NOW() |
| created_by | INTEGER | FK users.id, NOT NULL |

### soap_note_addendums
| Campo | Tipo | Restricciones |
|-------|------|---------------|
| id | SERIAL | PRIMARY KEY |
| soap_note_id | INTEGER | FK soap_notes.id, NOT NULL |
| content | TEXT | NOT NULL |
| created_at | TIMESTAMP | DEFAULT NOW() |
| created_by | INTEGER | FK users.id, NOT NULL |

### notification_logs
| Campo | Tipo | Restricciones |
|-------|------|---------------|
| id | SERIAL | PRIMARY KEY |
| patient_id | INTEGER | FK patients.id, NOT NULL |
| appointment_id | INTEGER | FK appointments.id, NULLABLE |
| channel | VARCHAR(20) | NOT NULL |
| template_name | VARCHAR(100) | NULLABLE |
| message | TEXT | NOT NULL |
| status | VARCHAR(20) | DEFAULT 'SANDBOX' |
| sent_at | TIMESTAMP | DEFAULT NOW() |
| error_message | TEXT | NULLABLE |

### settings
| Campo | Tipo | Restricciones |
|-------|------|---------------|
| id | SERIAL | PRIMARY KEY |
| key | VARCHAR(100) | UNIQUE, NOT NULL |
| value | TEXT | NOT NULL |
| updated_at | TIMESTAMP | DEFAULT NOW() |
| updated_by | INTEGER | FK users.id, NULLABLE |

### audit_logs
| Campo | Tipo | Restricciones |
|-------|------|---------------|
| id | SERIAL | PRIMARY KEY |
| user_id | INTEGER | FK users.id, NOT NULL |
| action | VARCHAR(100) | NOT NULL |
| entity_type | VARCHAR(50) | NOT NULL |
| entity_id | INTEGER | NOT NULL |
| details | TEXT | NULLABLE |
| created_at | TIMESTAMP | DEFAULT NOW() |

## Índices Recomendados

```sql
CREATE INDEX idx_appointments_date ON appointments(date);
CREATE INDEX idx_appointments_patient ON appointments(patient_id);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_patients_whatsapp ON patients(whatsapp);
CREATE INDEX idx_availability_blocks_dates ON availability_blocks(start_date, end_date);
CREATE INDEX idx_soap_notes_patient ON soap_notes(patient_id);
CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
```

## Configuraciones Importantes

- **Timezone**: America/Monterrey (GMT-6)
- **Formato fechas**: dd/mm/yyyy 24h
- **SOAP interval**: Configurable (default: cada 3 citas)
