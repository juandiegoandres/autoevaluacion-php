CREATE TABLE IF NOT EXISTS docentes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    usuario TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS estudiantes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo TEXT NOT NULL UNIQUE,
    nombre TEXT NOT NULL,
    grado TEXT NOT NULL,
    curso TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS periodos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    activo INTEGER NOT NULL DEFAULT 0,
    formulario_abierto INTEGER NOT NULL DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS autoevaluaciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    estudiante_id INTEGER NOT NULL,
    periodo_id INTEGER NOT NULL,
    respuestas_json TEXT NOT NULL,
    promedio_dimension_1 REAL NOT NULL,
    promedio_dimension_2 REAL NOT NULL,
    promedio_dimension_3 REAL NOT NULL,
    promedio_dimension_4 REAL NOT NULL,
    promedio_dimension_5 REAL NOT NULL,
    nota_final REAL NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY(periodo_id) REFERENCES periodos(id) ON DELETE CASCADE,
    UNIQUE(estudiante_id, periodo_id)
);

CREATE INDEX IF NOT EXISTS idx_estudiantes_curso ON estudiantes(curso);
CREATE INDEX IF NOT EXISTS idx_autoeval_periodo ON autoevaluaciones(periodo_id);
