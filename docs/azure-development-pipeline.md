# Pipeline розробки та активація Azure

Базовий CI зі скриптами PR #940 вже є в `main`. Цей PR додає frontend build,
послідовний запуск reviewer та аналіз технічних вимог. До злиття workflow і trusted
scripts у `main` чинна автоматизація не змінюється.

## Послідовність PR

1. `Domain tests`: `Owned domain suites` (498 вибраних тестів на момент додавання)
   та незалежний `Frontend build` (`npm ci`, `npm run build`). Тестова БД локальна,
   пароль та APP_KEY одноразові. Цей набір не охоплює всі модулі застосунку.
2. Завершення CI викликає `workflow_run` на довіреному main. Gate перевіряє run
   через GitHub API: repository, workflow path, event, SHA, актуальну спробу й
   успішний результат обох обов'язкових jobs. Застарілий або невдалий CI не
   запускає модель. PR має бути ready, спрямований у main та від учасника команди.
3. Main workflow виконує Azure OIDC login та наявне GPT code review.
4. Окремий `Technical requirements review` аналізує явно зазначені пункти ТВ.
   Якщо пунктів немає, check нейтральний, без запиту до моделі й зайвого коментаря.
5. Людина перевіряє результати, виконує UAT за потреби та надає approval.

Ручний запуск Azure review теж вимагає зеленого CI актуального commit; він не є
обходом тестів. Pipeline ніколи не checkout/execute код форку з Azure credentials,
не запускає Composer/npm у привілейованому job і не виконує PR artifacts.

## Як зазначити ТВ

У title або body PR:

```text
ТВ: 3.5.1.3
Closes #123
```

Або `Requirements: 3.5.1.3, 3.7.1.3`. Можна вказати вимоги у пов'язаній issue:
`Closes/Fixes/Resolves #123` читає її title/body. Підтримуються до п'яти таких issues
і до восьми пунктів; ширші вимоги потрібно розбити на конкретні підпункти.
Зміна body PR запускає CI повторно. Після зміни лише issue потрібно вручну
повторити Azure review. Це також спосіб аналізувати старі відкриті PR.

Модель бачить дерево допустимих PHP/Blade-файлів і вибирає до шести релевантних
файлів. Скрипт перевіряє вибір, читає їхні immutable base/head версії та передає
нумеровані рядки. Контекст обмежений 450 рядками/20 KB на версію файла; усі
скорочення позначені. Посилання у відповіді перевіряються проти цих рядків.
Позитивний висновок при скороченому контексті переводиться у `unknown`.

Висновки: поведінка підтверджена наданим кодом, конкретна невідповідність або
потрібна додаткова перевірка. У requirements audit можна повідомляти існуючі
прогалини base; їх потрібно відрізняти від регресій PR. Check засвідчує завершення
аналізу, а не автоматично блокує merge через AI-зауваження.

Результат прив'язаний до head/base, SHA corpus і переліку пунктів. Повторні запити
з тим самим fingerprint не витрачають токени. Застарілі результати не публікуються.
Джерело й оновлення: [корпус ТВ](technical-requirements/README.md).

## Налаштування GitHub після merge

Власник репозиторію в `Settings → Rules → Rulesets → main`:

- Додати required status checks `Owned domain suites`, `Frontend build`,
  `Azure OpenAI PR review`, `Technical requirements review`.
- Вимагати актуальну гілку та людський approval; нові зміни після approval мають
  вимагати повторної перевірки. Команді потрібно окремо погодити bypass policy.
- Обмежити зміну workflows, `.github/scripts`, CI-скриптів і corpus відповідальними
  reviewers через CODEOWNERS/правила. YAML сам не налаштовує ці права.
- Зберегти Azure variables чинного reviewer: `AZURE_CLIENT_ID`, `AZURE_TENANT_ID`,
  `AZURE_OPENAI_ENDPOINT`, `AZURE_OPENAI_DEPLOYMENT`. Довгостроковий ключ API не потрібен.

В Azure reviewer identity достатньо inference role на відповідному Foundry/OpenAI
ресурсі. Привілейований run залишається на main, тому existing branch FIC не
потрібно замінювати на subject форку або pull_request.

## Білінг

Для public repository стандартні GitHub-hosted runners безкоштовні; Azure credits
використовуються моделлю та погодженими Azure deployment resources. Перенесення
цього CI у платний Azure Pipelines не є умовою активації.

- [GitHub Actions billing](https://docs.github.com/en/billing/concepts/product-billing/github-actions).
- [Azure DevOps sponsorship billing FAQ](https://learn.microsoft.com/en-us/azure/devops/organizations/billing/billing-faq?view=azure-devops).

Не знімайте spending limit і не купуйте parallel jobs автоматично: умови
Sponsorship потрібно перевірити з власником перед платною активацією.

## Наступний крок CD

Розгортання хостингу наразі відкладене. CI та обидва reviewer можуть працювати
без staging, production credentials або ключів КЕП.

Deploy не активується лише від наявності Azure credits. До запуску потрібно
погодити конкретні staging/production targets і нинішній спосіб deploy:

- VM чи App Service/Container Apps, resource group, runtime, domain і health endpoint;
- окрема deploy identity, FIC під protected environment, мінімальні RBAC права;
- GitHub environments з approvals, обмеженням main та захистом production;
- незмінний release artifact із commit SHA, staging health check, promotion
  того самого artifact та rollback на попередню версію;
- чинні production APP_KEY/DB/eHealth secrets у сховищі середовища; без генерації
  нової APP_KEY або використання CI install/first-run на production;
- backup і окреме погодження production migrations (schema rollback не дорівнює
  rollback коду). Health check має підтвердити основні залежності.

У цьому PR підготовлено CI та два види рев'ю. Конкретний deploy driver й активація
CD залежать від підтвердження цілі власником; перевірки не є свідченням deploy.

## Тести підписання та живий КЕП

Автоматичні тести вже перевіряють підготовку payload, виклик сервісу підписання,
передачу підписаного контенту та відмову при відсутніх даних КЕП через mock
`SignatureService`. Вони не виконують криптографічне підписання реальним ключем.

Живий інтеграційний/UAT прогін потребує окремо погоджених:

- eHealth preprod endpoints, MIS/client налаштувань, callback URL і доступних
  тестових користувачів/закладів із потрібними ролями;
- сценаріїв і тестових даних, які можна створювати/змінювати у preprod;
- способу тестового підписання, доступного Cipher/test signer і участі власника КЕП.

Особисті ключі та паролі КЕП не передаються reviewer, не зберігаються у Git чи
GitHub artifacts. Для браузерного UAT власник підписує сам. Headless інтеграцію
можна обговорювати лише для спеціально призначених тестових облікових даних і
непривілейованого PR-коду, без особистого або production КЕП.
