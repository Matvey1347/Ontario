# Ontario project rules

## Writable project scope

First-party project code is located in:

- plugins/ontario-plugin
- themes/Ontario
- mu-plugins/ontario-bootstrap.php

Do not modify WordPress core or third-party plugins unless explicitly requested.
Do not modify WP Mail SMTP.

## Localization invariant

English is the source language.

All public user-facing strings must use the Ontario translation layer.
Do not add new hardcoded public UI text directly to PHP templates or JavaScript.

Translation files:

- plugins/ontario-plugin/translations/en.php
- plugins/ontario-plugin/translations/ru.php
- plugins/ontario-plugin/translations/fr.php
- plugins/ontario-plugin/translations/pl.php

Whenever a public string is added, removed, renamed, or changed:

1. Update the English source translation.
2. Update Russian, French, and Polish translations in the same change.
3. Keep the same translation keys in every language file.
4. Do not leave English placeholders inside non-English files.
5. Run:

   php plugins/ontario-plugin/tools/check-translations.php
6. Re-check responsive frontend behavior across the supported languages after the text change so navigation, buttons, forms, modals, and other public UI do not break at narrower widths.

A task that changes public text is incomplete until all translation files and the translation check are updated.

Keep canonical form values sent to the backend and Zoho stable. Translate only the labels shown to visitors unless a backend migration is explicitly required.
