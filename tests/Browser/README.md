# Drawer regression test

Install PHP dependencies with `composer install`, JavaScript dependencies with
`npm ci`, and Chromium with `npx playwright install chromium` (on Linux CI use
`npx playwright install --with-deps chromium`). Run `npm run test:drawers`.

Optional environment variables: `PHP_BINARY` selects the PHP executable;
`PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH` selects an already installed Chromium/Chrome.

The test renders the production drawer components and reads the actual Care Plan
form/search opening tags, cancel handlers and Alpine reopen handlers. It runs real
Alpine UI and focus in a headless browser with synthetic form/search controls.
Device reopening and selecting a result simulate asynchronous server transitions.
No database, patient data or eHealth API access is required.

For services, medication and medical devices it checks repeated reopening, clicks,
typing/Enter, filter toggles, pagination, cancellation, Escape, backdrop dismissal,
selection, retained form values, focus return and subsequent parent dismissal.
The service backdrop has the same width as its panel and is fully covered, so its
click is dispatched directly. Clicking the uncovered drawer root uses a real mouse
for all three types. The other two drawers also use a real backdrop click.

This is a component behavior test. Full Livewire hydration, API search results,
authorization, saving and KEP signing still require an authenticated application
session; they are not exercised by this fixture.
