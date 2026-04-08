# Autoevaluación SPA - Instituto Técnico Santo Tomás

Aplicación web en PHP + SQLite (portable a MySQL) para autoevaluación estudiantil con panel de docente y experiencia SPA ligera.

## Requisitos
- PHP 8.1+
- Extensiones: `pdo_sqlite`, `zip`, `mbstring`, `gd`
- Composer

## Instalación
1. Copiar entorno:
   ```bash
   cp .env.example .env
   ```
2. Instalar dependencias:
   ```bash
   composer install
   ```
3. Inicializar base de datos:
   ```bash
   php scripts/init_db.php
   ```
4. Levantar servidor local:
   ```bash
   php -S localhost:8000 -t public
   ```

## Accesos
- Docente: `admin` / `admin123` (cambiable en `.env` antes de inicializar)
- Estudiante: requiere importación CSV y período activo

## CSV de estudiantes
Columnas esperadas en orden:
1. `codigo`
2. `nombre`
3. `grado`
4. `curso`

## Arquitectura
- `app/Config`: configuración y estructura centralizada del formulario
- `app/Core`: bootstrap, sesión, conexión PDO
- `app/Repositories`: acceso a datos con consultas preparadas
- `app/Services`: reglas de negocio (auth, evaluación, import, export, PDF)
- `app/Controllers`: endpoints API
- `public`: SPA (HTML/CSS/JS) y rutas de entrada
- `database/migrations`: esquema SQL
- `scripts`: utilidades CLI

## Migración a MySQL
En `.env` cambiar:
- `DB_DRIVER=mysql`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

La capa PDO ya selecciona el driver por configuración.
