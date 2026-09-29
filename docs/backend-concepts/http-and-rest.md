# HTTP and REST

## 1. What is it?

HTTP (HyperText Transfer Protocol) is the request/response protocol a browser and Laravel communicate over. REST is a set of conventions for organizing URLs and HTTP methods around resources (nouns like "projects," "tasks") rather than actions (verbs like "getProject").

## 2. What problem does it solve?

Without a shared protocol, browser and server couldn't communicate at all. Without REST's conventions, every endpoint's naming would be arbitrary and inconsistent — one team's `/getProject?id=5`, another's `/project/fetch/5`, another's `/api/v1/project-details/5`. REST conventions (a resource noun in the URL, the HTTP method conveying the action) make an unfamiliar API's shape predictable.

## 3. How does it work — traced through a real request in this app

**Request**: `GET /projects/7`

```
GET /projects/7 HTTP/1.1
Host: 127.0.0.1:8000
Cookie: laravel-session=eyJpdiI6...
```

- **Method**: `GET` — "give me this resource," no side effects, safe to retry/cache.
- **URL**: `/projects/7` — the resource (a project) identified by its ID, matched by `Route::resource('projects', ProjectController::class)`'s auto-generated `GET /projects/{project}` route.
- **Headers**: `Cookie` carries the session ID, which is how `Auth::user()` resolves who's asking (see `docs/backend-concepts/authentication-and-sessions.md`).

**Response**:
```
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>...
```
- **Status code**: `200 OK` — request succeeded, here's the resource.
- **Body**: the rendered `projects/show.blade.php` HTML.

## 4. HTTP methods actually used in this app, and what each means

| Method | Used for | Example in this app |
|---|---|---|
| `GET` | Retrieve a resource, no side effects | `GET /tasks/{task}` — view a task |
| `POST` | Create a new resource | `POST /projects` — create a project |
| `PUT` | Replace/update an existing resource | `PUT /tasks/{task}` — update a task |
| `DELETE` | Remove a resource | `DELETE /comments/{comment}` — delete a comment |

HTML forms only natively support `GET`/`POST` — this app's `PUT`/`DELETE` actions (every `@method('PUT')`/`@method('DELETE')` directive throughout the `resources/views/` Blade files) use method spoofing: a hidden `_method` input field. Laravel's HTTP Kernel calls Symfony's `Request::enableHttpMethodParameterOverride()` on every request, which makes `$request->getMethod()` return the spoofed value instead of the browser's real `POST` whenever a `_method` field is present — the router then matches the route as if the request had actually arrived as `PUT`/`DELETE`, even though the wire-level HTTP method was `POST` the whole time (confirmable with `curl -v`, which shows the literal `POST` in the request line).

## 5. Status codes actually produced by this app

| Code | Meaning | Produced by |
|---|---|---|
| `200` | OK | Successful `GET` requests |
| `302` | Found (redirect) | Every successful `POST`/`PUT`/`DELETE` in this app redirects afterward (the Post/Redirect/Get pattern — prevents a form resubmission if the user refreshes the resulting page) |
| `403` | Forbidden | `Gate::authorize()`/Form Request `authorize()` returning `false` — see `docs/backend-concepts/authorization-and-policies.md` |
| `404` | Not Found | Undefined route, or `ModelNotFoundException` from route-model binding — see `docs/backend-concepts/error-handling.md` |
| `422` | Unprocessable Entity (implicit, via redirect) | Validation failure — Laravel doesn't render a raw 422 page for standard web requests; it redirects back with flashed errors, which the browser follows as a normal `302`+`GET` |
| `500` | Server Error | Any unhandled exception |

## 6. REST conventions as actually applied in this app

`Route::resource('projects', ProjectController::class)` generates exactly the 7 conventional REST routes/methods (index/create/store/show/edit/update/destroy) documented in `docs/backend-concepts/routing.md`. Not every resource in this app follows the full convention — `CommentController` only has `store`/`edit`/`update`/`destroy` (no `index`/`show`/`create`), because comments have no standalone page; they render inline on their parent task's page. This is a deliberate, documented deviation from strict REST, not an oversight — see `docs/PROJECT-STATE.md`'s Phase 2.5 notes.

## 7. Alternatives

- **GraphQL** — a single endpoint, client specifies exactly what data it wants. Not used here; this app's UI needs are fixed and known ahead of time (each Blade view needs exactly the data its controller already provides), so REST's per-resource endpoints are a natural fit without GraphQL's added complexity.
- **RPC-style APIs** (`/api/getProjectById`, `/api/createProject`) — the pre-REST convention this app deliberately avoids, per the REST conventions described above.

## 8. Trade-offs

Strict REST sometimes forces awkward mappings for actions that aren't naturally CRUD (e.g. "change a task's status" isn't creating/reading/updating/deleting a distinct resource) — this app handles that by treating a status change as a normal `PUT /tasks/{task}` update (the status field is just one of several fields being updated), rather than inventing a non-RESTful `/tasks/{task}/status` endpoint.

## 9. How do I verify it?

```bash
curl -v http://127.0.0.1:8000/tasks/1
```
`-v` shows the full request AND response headers — the exact method, status code, and headers described above, for a real request against the running app.

## 10. Questions for me

1. Why does every successful `POST`/`PUT`/`DELETE` action in this app redirect (302) rather than returning the resulting page directly (200)? What problem does that specifically prevent?
2. HTML forms can't send a native `PUT` or `DELETE` request. What two things does Laravel need in a form for `@method('PUT')` to actually work — and what would happen if only one of them were present?
3. `CommentController` doesn't follow the full REST resource convention (no index/show/create). Is this a violation of REST, or a legitimate adaptation? What's the difference between "not following convention" and "doing REST wrong"?
4. A `GET` request is supposed to have no side effects ("safe"). Does anything in this app's `GET /dashboard` route violate that principle? What would be wrong with a hypothetical `GET /projects/{project}/delete` route, even if it worked?
