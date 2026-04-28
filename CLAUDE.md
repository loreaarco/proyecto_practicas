# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

OSCISA Solutions — Invoice comparison platform for energy contracts (electricity/gas) in Spain.
PHP-only backend, no framework, custom MVC. MySQL on a remote server.

## Architecture

Custom MVC without Composer or external frameworks:

- **`public/index.php`** — Front controller. Single entry point. Registers autoloader, loads config, starts session, dispatches route.
- **`core/`** — Framework primitives: `Router`, `Controller`, `Database` (PDO singleton), `Session`, `Auth`, `Logger`.
- **`app/Controllers/`** — HTTP layer. Calls services/repos, renders views.
- **`app/Services/`** — Business logic: `OpenAIExtractionService` (AI extraction), `ComparadorService` (rule-based comparison), `FacturaUploadService` (secure file upload).
- **`app/Repositories/`** — Data access layer. All SQL lives here, using PDO prepared statements.
- **`views/`** — PHP templates. Layout at `views/layouts/app.php`; `$content` variable holds the path to the inner view file.
- **`routes/web.php`** — All route definitions. Pattern `{id}` = integer only.
- **`config/app.php`** — Loads `.env`, sets constants (`BASE_PATH`, `BASE_URL`, `APP_ENV`), configures error handling.
- **`database/schema.sql`** — Full SQL schema to import once.
- **`storage/facturas/`** — Uploaded invoice files, organized by `cliente_id/`.
- **`storage/logs/`** — Daily log files (`YYYY-MM-DD.log`).

## Running locally (XAMPP)

1. Import `database/schema.sql` into MySQL via phpMyAdmin or CLI.
2. Copy `.env.example` to `.env` and fill in `DB_*` and `OPENAI_API_KEY`.
3. Enable `mod_rewrite` in Apache. The `.htaccess` in `public/` handles all rewrites.
4. Access: `http://localhost/oscisa/public`
5. Default admin: `admin@oscisa.com` / `admin1234` (change immediately).

No build step, no Composer, no npm. Pure PHP served by Apache.

## Key design rules

- **No AI in comparisons.** `ComparadorService` uses only formulas, rate tables, and business rules. OpenAI is only called in `OpenAIExtractionService::extraer()`.
- **All DB access via repositories.** Never write SQL in controllers or views.
- **PDO prepared statements everywhere.** No string concatenation in queries.
- **Autoloader** resolves classes from `core/`, `app/Controllers/`, `app/Services/`, `app/Repositories/`, `app/Models/`, `app/Helpers/`.
- **Roles:** `admin`, `comercial`, `supervisor`. Use `Auth::requireRole()` and `Auth::hasRole()` for access control.
- **Flash messages** via `Session::flash('success'|'error', $msg)` — rendered by the layout automatically.
- **File uploads** stored with a random hex name. Detection via `finfo` (content, not extension).

## Comparison engine (ComparadorService)

Spanish electricity bill formula:
```
Término potencia = Σ(potencia_pi × precio_pi × dias / 365)
Término energía  = Σ(consumo_pi × precio_pi)
Base imponible   = potencia + energía + alquiler_equipos
IEE (5.11269632%) applied to base
IVA (10%) applied to (base + IEE)
Total = base + IEE + IVA
```
To modify formulas: edit `calcularCosteTarifa()`. To add rate types: update `tarifas_oferta` table and `filtrarTarifasCompatibles()`.

## OpenAI integration

- Model: configured via `OPENAI_MODEL` in `.env` (default `gpt-4o`).
- Images sent as base64 `image_url`; PDFs sent as base64 text.
- Response must be pure JSON — the service strips markdown code fences before parsing.
- Max 3 retries with exponential backoff on 429/rate-limit errors.
- All extracted data validated and normalized before saving to `datos_extraidos_factura`.

## Database

Remote MySQL. Connection configured in `.env` (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`).
`Database::getInstance()` returns the PDO singleton. Never create a new PDO directly.

Key tables: `clientes`, `facturas`, `datos_extraidos_factura`, `estudios`, `resultados_comparativa`, `tarifas_oferta`, `comercializadoras`, `estados_comerciales`, `seguimiento_comercial`, `logs_sistema`.

## Logging

`Logger::info/warning/error/critical($context, $message, $data)` — writes to daily file and to `logs_sistema` table for warning+. Context should identify the module (e.g. `'OpenAI'`, `'Comparador'`, `'Auth'`).
