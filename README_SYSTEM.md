# ⚽ World Cup Pool System

Sistema de **quiniela del Mundial** desarrollado en **Laravel** que permite gestionar torneos, registrar partidos, calcular tablas de grupos automáticamente y administrar predicciones de usuarios. Diseñado específicamente para soportar el **formato del Mundial 2026 (48 equipos)**.

## 📋 Tabla de Contenidos
- [Características Principales](#características-principales)
- [Arquitectura](#arquitectura)
- [Estructura del Torneo](#estructura-del-torneo)
- [Base de Datos](#base-de-datos)
- [Servicios del Sistema](#servicios-del-sistema)
- [Flujo de Trabajo](#flujo-de-trabajo)
- [Instalación](#instalación)
- [Tecnologías](#tecnologías)
- [Próximos Desarrollos](#próximos-desarrollos)
- [Autor](#autor)

---

## ✨ Características Principales

- ✅ Gestión completa de torneos mundialistas
- ✅ Cálculo automático de tablas de grupos
- ✅ Resolución dinámica de brackets de eliminación directa
- ✅ Sistema de predicciones con puntuación automática
- ✅ Soporte para 48 equipos (formato Mundial 2026)
- ✅ Importación automática de calendario desde Excel

---

## 🏗️ Arquitectura

El sistema está dividido en tres módulos principales:

1. **Gestión del Torneo** - Administración de equipos, grupos y partidos
2. **Resolución del Bracket** - Avance automático en fases eliminatorias
3. **Sistema de Quiniela** - Predicciones y puntuación de usuarios

---

## 🏆 Estructura del Torneo

El torneo contempla las siguientes fases:

| Fase | Stage | Descripción |
|------|-------|-------------|
| 🟢 | group | Fase de grupos |
| 🔵 | round_32 | Dieciseisavos de final |
| 🔵 | round_16 | Octavos de final |
| 🟡 | quarter | Cuartos de final |
| 🟠 | semi | Semifinales |
| 🥉 | third_place | Tercer lugar |
| 🏆 | final | Final |

---

# Estructura de Base de Datos

## tournaments
Contiene los torneos disponibles.
```sql
id | name | year | created_at | updated_at

---
## groups

Grupos del torneo.

id | tournament_id | name (A-L) | created_at | updated_at

---

## teams

Equipos participantes.

id | country_id | group_id | name | group_position | type | created_at | updated_at

---

## games

Partidos del torneo.

id | tournament_id | match_number | home_team_id | away_team_id | home_slot | away_slot | stage | venue | match_date | match_time | home_score | away_score | winner_team_id | status | created_at | updated_at

---

### Ejemplo de slots

| Slot | Significado |
|------|-------------|
| 1A | Primer lugar del grupo A |
| 2B | Segundo lugar del grupo B |
| W74 | Ganador del partido 74 |
| RU101 | Perdedor del partido 101 |
| 3-ABCDF | Mejor tercer lugar entre grupos A, B, C, D, F |

---

## group_standings

Tabla de posiciones de cada grupo.

id | tournament_id | group_id | team_id | played | wins | draws | losses | gf | ga | gd | points | position | created_at | updated_at

---

## pool_entries

Participaciones de usuarios en la quiniela.

id | user_id | tournament_id | created_at | updated_at

---

## predictions

Predicciones de los usuarios.

id | pool_entry_id | game_id | home_score | away_score | points | created_at | updated_at

---

# Servicios del Sistema

La lógica principal del sistema se maneja mediante **services**.

## GroupStandingsService

Calcula automáticamente la tabla de grupos cuando se registra un resultado.

Criterios de orden:

1. Points
2. Goal Difference
3. Goals For

---

## BracketResolverService

Resuelve dinámicamente los slots del bracket.

Ejemplos:

1A → primer lugar del grupo A
2B → segundo lugar del grupo B
W74 → ganador del partido 74
RU101 → perdedor del partido 101
3-ABCDF → mejor tercer lugar entre grupos A,B,C,D,F



---

## BracketProgressionService

Avanza automáticamente los equipos en el bracket.

Cuando termina un partido:


---

## BracketProgressionService

Avanza automáticamente los equipos en el bracket.

Cuando termina un partido:


Esto permite que el bracket se actualice automáticamente.

---

## PredictionScoringService

Calcula los puntos de cada predicción.

### Sistema de puntuación

| Resultado | Puntos |
|-----------|-------|
Marcador exacto | 5 |
Ganador correcto | 3 |
Empate correcto | 1 |
Incorrecto | 0 |

---

# Flujo del Sistema

Cuando un administrador registra un resultado:

Update Game Score
↓
GroupStandingsService
↓
BracketProgressionService
↓
PredictionScoringService


Esto permite que el sistema actualice:

- tabla de grupos
- bracket
- puntos de usuarios

---

# Seeder del Mundial

El sistema incluye un **seeder que importa el calendario del Mundial desde Excel**.

Seeder:


Esto permite que el sistema actualice:

- tabla de grupos
- bracket
- puntos de usuarios

---

# Seeder del Mundial

El sistema incluye un **seeder que importa el calendario del Mundial desde Excel**.

Seeder: WorldCupSeeder



Fuente de datos: storage/app/WCup_2026_4.0_en2.xlsx


Este seeder crea automáticamente:

- grupos
- equipos
- partidos
- estructura del bracket

---

# Tecnologías

- Laravel
- MySQL
- Eloquent ORM
- PhpSpreadsheet

---

# Próximos desarrollos

- Panel administrativo
- Interfaz de predicciones de usuario
- Ranking de quiniela
- Estadísticas del torneo

---

# Autor

Miguel Angel Cornielly

