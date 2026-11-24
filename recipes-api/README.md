# Recipes API — Laravel 11/12 + Sanctum + Reverb

Готовое API для SPA «Кухни мира»: регистрация/вход по токену, рецепты (CRUD, сортировка, конструктор по ингредиентам),
WebSocket-уведомление о новом рецепте.

## 1. Подключение к Laravel (пошагово)

```bash
# 1. Новый проект (PHP 8.2+, Composer, MySQL/SQLite)
composer create-project laravel/laravel recipes-api
cd recipes-api

# 2. Sanctum (токены) и Reverb (WebSocket) — на вопросы отвечайте yes
php artisan install:api
php artisan install:broadcasting
```

**3. Скопируйте файлы из архива в проект** (с заменой существующих):
`app/`, `database/`, `routes/api.php`, `tests/`. Файл `client/` — для фронтенда, в Laravel он не нужен.
Миграцию `users` и миграцию токенов не трогайте.

**4. `bootstrap/app.php`** — чтобы любые ошибки API (401, 403, 404, 422) отдавались JSON, а не HTML:
```php
use Illuminate\Http\Request;

->withExceptions(function (Exceptions $exceptions) {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
    );
})
```
Убедитесь, что в `->withRouting(...)` есть `api: __DIR__.'/../routes/api.php'` (добавляет `install:api`)
и `channels: __DIR__.'/../routes/channels.php'` (добавляет `install:broadcasting`).

**5. `.env`** (БД + broadcasting; Reverb-ключи обычно уже выставил `install:broadcasting`):
```
APP_URL=http://127.0.0.1:8000
APP_LOCALE=ru
DB_CONNECTION=sqlite            # или mysql + DB_DATABASE/DB_USERNAME/DB_PASSWORD

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local
REVERB_APP_KEY=localkey
REVERB_APP_SECRET=localsecret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```
`APP_URL` важен: по нему строятся ссылки на картинки.

**6. Миграции, данные, запуск**
```bash
php artisan migrate --seed       # 2 пользователя, 12 ингредиентов, 8 рецептов с картинками
php artisan storage:link         # без этого картинки /storage/... отдают 404
php artisan serve                # API: http://127.0.0.1:8000/api
php artisan reverb:start         # в ОТДЕЛЬНОМ терминале: WebSocket на :8080
```
Тестовые пользователи: `user@example.com`, `ivan@example.com`, пароль `password123`.

**7. Проверка**
```bash
curl -H "Accept: application/json" "http://127.0.0.1:8000/api/recipes?sort=calories&order=asc"
php artisan test                  # feature-тесты из tests/Feature/RecipeApiTest.php
```
Или откройте `requests.http` (VS Code REST Client / PhpStorm HTTP Client).

## 2. Подключение фронтенда (SPA)

1. Скопируйте `client/api.js` в проект SPA. `npm i laravel-echo pusher-js`.
2. Задайте адреса (Vite `.env`) или поправьте константы в начале `api.js`:
   ```
   VITE_API_URL=http://127.0.0.1:8000/api
   VITE_CLIENT=ваш_логин            # уходит в заголовок Client
   VITE_REVERB_APP_KEY=localkey
   VITE_REVERB_HOST=127.0.0.1
   VITE_REVERB_PORT=8080
   ```
3. CORS настраивать не нужно: в Laravel 11+ для `api/*` он включён и разрешает любые origin/заголовки
   (в т.ч. `Client` и `Authorization`). Если ограничивали — `php artisan config:publish cors`.
4. Какая страница какой метод `api` вызывает:

| Страница шаблона | Вызов |
|---|---|
| register.html | `api.register({email, name, password})` → затем редирект на login.html |
| login.html | `api.login({email, password})` (токен сохранится в localStorage) |
| index.html | `api.recipes({sort, order})` + `listenNewRecipes(cb)` для тостов |
| constructor.html | `api.ingredients()` для списка, `api.recipes({ingredients:[id,…]})` при каждом изменении |
| recipe.html | `api.recipe(id)` |
| my-recipes.html | `api.myRecipes()`, удаление — `api.deleteRecipe(id)` после подтверждения в модалке |
| recipe-form / recipe-edit | `api.ingredients()`, `api.createRecipe(...)` / `api.updateRecipe(id, ...)` |
| шапка | показывать «Мои рецепты / Выйти», если `token.get()` есть; «Выйти» → `api.logout()` |

5. Ошибки валидации: перехватывайте `catch (err)` → `showErrors(form, err.errors)` — функция сама вешает
   `has-error` и `.error-text` под нужным полем (разметка совпадает с html-шаблонами).
   Для входа при `err.status === 401` покажите `err.message` в блоке `.form-alert`.

## 3. Эндпоинты (префикс `/api`)

| Метод | URL | Доступ | Описание |
|---|---|---|---|
| POST | /register | гость | email, name, password → 201 (токена нет) |
| POST | /login | гость | → `{ token, user }`; неверные данные → 401 `{message}` |
| POST | /logout | токен | 204 |
| GET | /me | токен | текущий пользователь |
| GET | /ingredients | все | `{data:[{id,name}]}` |
| GET | /recipes | все | `sort=date\|time\|calories`, `order=asc\|desc` (по умолчанию date desc), `ingredients[]=id` — только рецепты, где есть ВСЕ выбранные |
| GET | /recipes/{id} | все | один рецепт |
| GET | /my/recipes | токен | свои рецепты (sort/order те же) |
| POST | /recipes | токен | multipart: title, description, calories, cooking_time, image, ingredients[] → 201, рассылка по WebSocket |
| PUT | /recipes/{id} | владелец | как POST, image необязателен. С файлом: `POST` + поле `_method=PUT` (это делает `api.updateRecipe`) |
| DELETE | /recipes/{id} | владелец | 204; чужой → 403 |

Заголовки: `Accept: application/json`, `Client: <логин>`, для защищённых `Authorization: Bearer <token>`.

Валидация (422): `{ "message": "...", "errors": { "title": ["Укажите название рецепта."], "ingredients": ["..."] } }`.
Рецепт: `id, title, description, calories, cooking_time, image (полный URL), created_at, author{id,name}, ingredients[{id,name}], is_mine`.
Списки приходят в обёртке `{ "data": [...] }`.

## 4. WebSocket
Публичный канал `recipes`, событие `.recipe.created` (слушают и гости). Payload: `id, title, description, calories, cooking_time, image, created_at, author`.
Событие отправляется сразу (`ShouldBroadcastNow`, воркер очереди не нужен). Если Reverb выключен, создание рецепта всё равно проходит.

## 5. Частые проблемы
| Симптом | Причина / решение |
|---|---|
| Ответ — HTML-страница вместо JSON | нет `Accept: application/json` или не добавлен `shouldRenderJsonWhen` (п. 4 выше) |
| Картинки не открываются (404) | `php artisan storage:link` и правильный `APP_URL` |
| 401 на защищённых | нет/устарел `Authorization: Bearer …` |
| При редактировании с файлом поля пустые | multipart+PUT: слать POST с `_method=PUT` |
| Тосты не приходят | запущен ли `php artisan reverb:start`, совпадают ли ключ/порт в SPA и `.env`; слушайте `.recipe.created` с точкой |
| `Class HasApiTokens not found` | не выполнен `php artisan install:api` |

> В задании сказано, что документацию API выдадут на чемпионате. Пути и форматы здесь — моя реализация;
> на экзамене переименуйте поля и URL под выданную документацию (меняется только `routes/api.php`, запросы, ресурсы и `client/api.js`).
