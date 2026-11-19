# Changelog

## 8.1 — 2026-11-07

- Enforces some verification before registering module

## 8.0 — 2026-08-03

- Avoid using magic methods for user preferences and blog settings (2.39+)
- Fav Icon

## 7.5 — 2026-05-04

- Restore previous error handler on instance destruction
- Harmonize usage of App::blogWorkspace()::NS_... and App::userWorkspace()::WS_... (and use short version of constant name for types)

## 7.4.2 — 2026-04-15

- count might not be present in serialized data

## 7.4.1 — 2026-04-15

- hash might be not present in serialized data

## 7.4 — 2026-04-09

- DA -&gt; DC

## 7.3 — 2025-09-30

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 7.2 — 2025-09-22

- Use backend services rather than static calls

## 7.1 — 2025-09-07

- DC 2.36 compliance

## 7.0 — 2025-09-03

- Use App::session() rather than $_SESSION, Dotclear 2.36+

## 6.2 — 2025-07-14

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 6.1 — 2025-05-14

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 6.0 — 2025-05-05

- Use Strong(…) rather than Text('strong', …)
- ToDo done

## 5.2 — 2025-02-06

- Switch from jQuery to Vanilla JS

## 5.1 — 2025-02-06

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 5.0 — 2025-01-04

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 4.3 — 2024-09-05

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 4.2 — 2023-12-16

- Take care of context as it may be other than FRONTEND/BACKEND

## 4.1 — 2023-10-27

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 4.0 — 2023-10-24

- Use instanced item constants rather than interface ones
- Use 2.28 Config methods
- 2.28 compliance

## 3.0 — 2023-08-24

- Dotclear 2.27 compliance, PHP 8.1+

## 2.6.2 — 2023-07-10

- dcCore::app()-&gt;adminurl may not be initialized yet

## 2.6.1 — 2023-06-27

- Add local static $init variable

## 2.6 — 2023-06-20

- Use dcCore::app()-&gt;adminurl-&gt;redirect() rather than Http::redirect()

## 2.5 — 2023-06-20

- Use admin URLs

## 2.4 — 2023-06-15

- Add void Install.php

## 2.3 — 2023-06-10

- Use My::id() rather than literal string

## 2.2 — 2023-05-31

- Add a maintenance task to empty errorlogger cache

## 2.1.1 — 2023-05-22

- Minify fix

## 2.1 — 2023-05-18

- Reduce paths (core, theme, plugin) in output

## 2.0 — 2023-05-17

- Switch to namespace
- Prepare 2.26

## 1.0 — 2023-02-03

- Cope with error on auth page (blog not yet defined)
- Minified version
- addNamespace() not more needed

## 0.8 — 2022-11-26

- Dotclear 2.24 compliance
- Prepare 2.24

## 0.7.1 — 2022-09-01

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 0.7 — 2022-08-14

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 0.6.2 — 2021-12-01

- Remove notice on unknown error type
- Update default branch in _define.php for alternative repository
- Add alternative repository

## 0.6.1 — 2021-09-27

- Should use strlen rather than count to get string length ;-)

## 0.6 — 2021-08-06

- Dotclear 2.19 compliance

## 0.5.11 — 2021-02-27

- Keep compatibility with PHP 5.6+ until Dotclear 2.19 is required — closes #2

## 0.5.10 — 2021-02-25

- Retrocompatibility PHP 5.6+ — Closes #1

## 0.5.9 — 2021-02-19

- Cope with date_formats setting now cope as array rather than serialized string - Dotclear 2.9+
