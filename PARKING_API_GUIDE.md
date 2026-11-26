# Parking API — a beginner pair guide

A step-by-step companion to `1_Parkolo_API_feladatlap.docx`.

It assumes you have **never built an API before**. Every command and file is shown in full,
and each part ends with a checkpoint so you can verify you are on track.

**This guide is written for two people.** You will split the work in half, but you must agree
on the "contract" (routes + JSON shape) together *before* writing any code.

---

## 1. What you are building

A small REST API for a city parking system. Two tables, no login, everything is public JSON.

```
parking_lots (1) ──────────< (N) parkings
   id                          id
   name                        parking_lot_id  (FK)
   address                     license_plate
   hourly_rate                 arrived_at
   free_spaces                 left_at   (null = car is still inside)
                               fee       (null until the car leaves)
```

The one rule that drives the whole exercise:

- **A car arrives** → create a `parking`, set `arrived_at = now()`, and **decrease**
  `parking_lots.free_spaces` by 1.
- **A car leaves** → set `left_at = now()`, calculate `fee`, and **increase** `free_spaces` by 1.

---

## 2. Words you will meet

| Word | Plain meaning |
| --- | --- |
| **Artisan** | Laravel's command-line helper. You run `php artisan ...`. |
| **Migration** | A PHP file that describes one database table. |
| **Model** | A PHP class that represents one table (e.g. `ParkingLot` = `parking_lots`). |
| **Factory** | A class that generates fake rows for tests and seeders. |
| **Seeder** | A class that fills the database with starting data. |
| **Form Request** | A class that holds the validation rules for one endpoint. |
| **API Resource** | A class that decides exactly what JSON a model turns into. |
| **Controller** | The class that receives the request and returns the response. |
| **Route** | The line that connects a URL + HTTP method to a controller method. |
| **N+1** | Running one extra query per row in a list. Fixed with `with(...)` / `withCount(...)`. |

---

## 3. How to work as two people

### 3.1 Roles

| | **Person A — Parking lots** | **Person B — Parkings & logic** |
| --- | --- | --- |
| Owns | `parking_lots` migration, `ParkingLot` model, factory, `ParkingLotSeeder`, `ParkingLotController`, `ParkingLotResource`, `StoreParkingLotRequest`, `UpdateParkingLotRequest` | `parkings` migration, `Parking` model, factory, `routes/api.php`, `bootstrap/app.php`, `ParkingController`, `ParkingResource`, `StoreParkingRequest`, `IndexParkingRequest` |
| Main challenge | Clean CRUD + the 409 delete rule | Transactions, fee calculation, filters, pagination |

**File ownership is the most important rule.** Never edit a file the other person owns.
If you need a change in their file, say it out loud and let them make it.

Shared files (`DatabaseSeeder.php`, tests) are written by one person and reviewed by the other.

### 3.2 Pair rules

1. **Agree on the contract first** (section 4). Ten minutes here saves an hour of merging.
2. **Announce your file**: "I'm editing `ParkingController` now." Two people editing the same
   file is the #1 way to lose work.
3. **Commit at every ✅ checkpoint.** Small commits, clear messages.
4. **Two laptops?** Pull before you start, push at every checkpoint.
   **One laptop?** One drives, one navigates (reads the next step aloud and checks the output).
   Swap at every ✅.
5. At the end, **read the other person's controller out loud**. If you cannot explain a line,
   ask about it. You both need to understand the whole solution.

### 3.3 The 120-minute plan

| Time | Both together | Person A | Person B |
| --- | --- | --- | --- |
| 0–10 | Setup, contract, `/api/ping` | — | — |
| 10–20 | Migrations + models (pair) | writes `parking_lots` | writes `parkings` |
| 20–35 | — | factories + seeder | routes + requests + resources |
| 35–45 | Run the seeder, verify data | — | — |
| 45–85 | — | parking-lot endpoints | parking endpoints (basic) |
| 85–105 | — | 409 rule + lot tests | arrive/leave logic + filters |
| 105–120 | Full test run, checklist | — | — |

---

## 4. The contract (agree before you code)

### 4.1 Routes

| Method | URL | What it does | Success | Owner |
| --- | --- | --- | --- | --- |
| GET | `/api/parking-lots` | all lots, by name, with `parkings_count` | 200 | A |
| POST | `/api/parking-lots` | create a lot | 201 | A |
| GET | `/api/parking-lots/{parking_lot}` | one lot + its `parkings` | 200 | A |
| PUT/PATCH | `/api/parking-lots/{parking_lot}` | update a lot | 200 | A |
| DELETE | `/api/parking-lots/{parking_lot}` | delete (409 if it has an active parking) | 204 | A |
| GET | `/api/parking-lots/{parking_lot}/parkings` | its parkings, newest first, paginated | 200 | A |
| GET | `/api/parkings` | list with filters, sort, pagination | 200 | B |
| POST | `/api/parkings` | arrival (new parking) | 201 | B |
| GET | `/api/parkings/{parking}` | one parking | 200 | B |
| PATCH | `/api/parkings/{parking}/leave` | departure + fee | 200 | B |
| DELETE | `/api/parkings/{parking}` | delete (frees a space if still active) | 204 | B |

There is **no** general `PUT /api/parkings/{parking}`. A parking is closed only through `leave`.

### 4.2 JSON shape — parking lot

```json
{
    "data": {
        "id": 3,
        "name": "Belvárosi mélygarázs",
        "address": "Győr, Árpád út 12.",
        "hourly_rate": 500,
        "free_spaces": 42,
        "full": false,
        "parkings_count": 7
    }
}
```

- `full` is `true` when `free_spaces` is 0.
- `parkings_count` appears in the list and on a single lot.
- A single lot also contains `"parkings": [ ... ]`.

### 4.3 JSON shape — parking

```json
{
    "data": {
        "id": 39,
        "parking_lot_id": 3,
        "parking_lot_name": "Belvárosi mélygarázs",
        "license_plate": "ABC-123",
        "arrived_at": "2026-11-13 08:12:40",
        "left_at": "2026-11-13 10:31:05",
        "fee": 1500,
        "active": false
    }
}
```

- `active` is `true` while `left_at` is `null`.
- `parking_lot_name` appears in lists, single parking, arrival and departure responses.
- `arrived_at`, `left_at`, `fee` **never** come from the request body.

---

## 5. Step 1 — Project setup (0–10 min, both)

### 5.1 Create the project (skip if you already have it)

The prepared folder is already a Laravel project with SQLite, so **skip this** if you are
working in it. Otherwise:

```bash
composer create-project laravel/laravel parking-api
cd parking-api
```

Check that SQLite is the database:

```bash
php artisan config:show database.default
# sqlite
```

The file `database/database.sqlite` must exist. If it does not:

```bash
# macOS / Linux
touch database/database.sqlite

# Windows PowerShell
New-Item database/database.sqlite -ItemType File
```

### 5.2 Hungarian fake data

Faker generates the test names and addresses. Tell it to use Hungarian in `.env`:

```dotenv
APP_FAKER_LOCALE=hu_HU
```

Then clear the config cache:

```bash
php artisan config:clear
```

### 5.3 Turn on `/api` routes

Laravel's slim skeleton has no `routes/api.php` yet. Create the file and register it.

Create `routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json(['message' => 'pong']));
```

Open `bootstrap/app.php` and add the `api:` line:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',   // <-- add this line
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
```

> **Do not run `php artisan install:api`.** It installs Sanctum, which this exercise does not
> need. Registering the file by hand is one line and keeps the project small.

### 5.4 Start the server

```bash
php artisan serve
```

Open <http://127.0.0.1:8000/api/ping>. You should see:

```json
{"message":"pong"}
```

> If you see HTML instead of JSON, the `api:` line is missing or `routes/api.php` is not saved.

✅ **Checkpoint 1:** `/api/ping` returns JSON.

---

## 6. Step 2 — Database (10–45 min)

### 6.1 Create the files

**Person A runs first** (so the `parking_lots` migration gets an earlier timestamp):

```bash
php artisan make:model ParkingLot -mfs
```

**Person B runs second:**

```bash
php artisan make:model Parking -mf
```

`-m` migration, `-f` factory, `-s` seeder. This creates:

```
app/Models/ParkingLot.php
app/Models/Parking.php
database/migrations/..._create_parking_lots_table.php
database/migrations/..._create_parkings_table.php
database/factories/ParkingLotFactory.php
database/factories/ParkingFactory.php
database/seeders/ParkingLotSeeder.php
```

### 6.2 Migration — parking_lots (Person A)

Open `database/migrations/..._create_parking_lots_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_lots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('address', 255);
            $table->unsignedInteger('hourly_rate');
            $table->unsignedInteger('free_spaces')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_lots');
    }
};
```

### 6.3 Migration — parkings (Person B)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parkings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_lot_id')->constrained()->cascadeOnDelete();
            $table->string('license_plate', 10);
            $table->dateTime('arrived_at');
            $table->dateTime('left_at')->nullable();
            $table->unsignedInteger('fee')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parkings');
    }
};
```

`cascadeOnDelete()` is what makes "delete a lot → its parkings are deleted too" work.

Run both:

```bash
php artisan migrate
```

✅ **Checkpoint 2:** both tables exist.

### 6.4 Model — ParkingLot (Person A)

`app/Models/ParkingLot.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingLot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'hourly_rate',
        'free_spaces',
    ];

    /**
     * Default value when "free_spaces" is not sent in the request.
     * Without this, a missing value would be inserted as NULL.
     */
    protected $attributes = [
        'free_spaces' => 0,
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'integer',
            'free_spaces' => 'integer',
        ];
    }

    public function parkings(): HasMany
    {
        return $this->hasMany(Parking::class);
    }
}
```

### 6.5 Model — Parking (Person B)

`app/Models/Parking.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parking extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_lot_id',
        'license_plate',
        'arrived_at',
        'left_at',
        'fee',
    ];

    protected function casts(): array
    {
        return [
            'arrived_at' => 'datetime',
            'left_at' => 'datetime',
            'fee' => 'integer',
        ];
    }

    public function parkingLot(): BelongsTo
    {
        return $this->belongsTo(ParkingLot::class);
    }
}
```

The `datetime` casts are important: they let you call `$parking->arrived_at->diffInMinutes(...)`
and format the dates in the resource.

✅ **Checkpoint 3:** `php artisan tinker --execute 'echo App\Models\ParkingLot::count();'` prints `0`.

### 6.6 Factory — ParkingLot (Person A)

`database/factories/ParkingLotFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\ParkingLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParkingLot>
 */
class ParkingLotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Belvárosi mélygarázs',
                'P+R Kossuth tér',
                'Városháza parkolóház',
                'Duna-parti parkoló',
                'Árkád parkolóház',
                'Keleti P+R parkoló',
                'Nyugati pályaudvar parkoló',
                'Széchenyi téri mélygarázs',
                'Fő utcai parkoló',
                'Vasútállomás P+R',
            ]),
            'address' => fake()->streetAddress(),
            'hourly_rate' => fake()->randomElement([300, 400, 500, 600, 800]),
            'free_spaces' => fake()->numberBetween(5, 150),
        ];
    }
}
```

> The seeder below guarantees six **different** names. The factory itself does not, because
> `unique()` in a factory can explode when tests create many rows.

### 6.7 Factory — Parking (Person B)

`database/factories/ParkingFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Parking;
use App\Models\ParkingLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Parking>
 */
class ParkingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parking_lot_id' => ParkingLot::factory(),
            'license_plate' => strtoupper(fake()->bothify('???-###')),
            'arrived_at' => now()
                ->subDays(fake()->numberBetween(0, 10))
                ->setTime(fake()->numberBetween(8, 18), fake()->numberBetween(0, 59)),
            'left_at' => null,
            'fee' => null,
        ];
    }
}
```

### 6.8 Seeder (Person A writes, Person B reviews)

`database/seeders/ParkingLotSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Parking;
use App\Models\ParkingLot;
use Illuminate\Database\Seeder;

class ParkingLotSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Belvárosi mélygarázs',
            'P+R Kossuth tér',
            'Városháza parkolóház',
            'Duna-parti parkoló',
            'Árkád parkolóház',
            'Keleti P+R parkoló',
            'Nyugati pályaudvar parkoló',
            'Széchenyi téri mélygarázs',
            'Fő utcai parkoló',
            'Vasútállomás P+R',
        ];

        // 6 lots, each with a different name.
        $lots = collect($names)
            ->shuffle()
            ->take(6)
            ->map(fn (string $name) => ParkingLot::factory()->create(['name' => $name]));

        // 30 closed parkings.
        for ($i = 0; $i < 30; $i++) {
            $lot = $lots->random();

            $arrivedAt = now()
                ->subDays(fake()->numberBetween(0, 9))
                ->setTime(fake()->numberBetween(8, 18), fake()->numberBetween(0, 59));

            $leftAt = $arrivedAt->copy()->addMinutes(fake()->numberBetween(20, 300));

            $hours = max(1, (int) ceil($arrivedAt->diffInMinutes($leftAt) / 60));

            Parking::factory()->create([
                'parking_lot_id' => $lot->id,
                'arrived_at' => $arrivedAt,
                'left_at' => $leftAt,
                'fee' => $hours * fake()->randomElement([300, 400, 500, 600, 800]),
            ]);
        }

        // 8 active parkings (arrived today, no leaving time, no fee).
        for ($i = 0; $i < 8; $i++) {
            $lot = $lots->random();

            Parking::factory()->create([
                'parking_lot_id' => $lot->id,
                'arrived_at' => now()->subMinutes(fake()->numberBetween(5, 180)),
                'left_at' => null,
                'fee' => null,
            ]);

            // The car is inside, so one space is taken.
            $lot->decrement('free_spaces');
        }
    }
}
```

`database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ParkingLotSeeder::class,
        ]);
    }
}
```

### 6.9 The one-command rebuild

```bash
php artisan migrate:fresh --seed
```

✅ **Checkpoint 4:**

```bash
php artisan tinker --execute 'echo App\Models\ParkingLot::count()." lots, ".App\Models\Parking::count()." parkings";'
# 6 lots, 38 parkings
```

The doc also mentions phone numbers in the test data, but the data model has no phone column.
Stick to the data model — do not invent a column.

---

## 7. Step 3 — Routes and file skeletons (Person B, ~10 min)

### 7.1 The routes

Replace `routes/api.php`:

```php
<?php

use App\Http\Controllers\ParkingController;
use App\Http\Controllers\ParkingLotController;
use Illuminate\Support\Facades\Route;

Route::apiResource('parking-lots', ParkingLotController::class);

Route::get('parking-lots/{parking_lot}/parkings', [ParkingLotController::class, 'parkings']);

Route::apiResource('parkings', ParkingController::class)->except(['update']);

Route::patch('parkings/{parking}/leave', [ParkingController::class, 'leave']);
```

Check it:

```bash
php artisan route:list --path=api
```

You should see `{parking_lot}` and `{parking}` as the parameter names.

> Why `parking-lots` and not `parking_lots`? URLs use dashes, route parameters use underscores.
> Laravel converts `parking-lots` to `{parking_lot}` automatically.

### 7.2 Generate the empty classes

```bash
php artisan make:controller ParkingLotController --api --model=ParkingLot
php artisan make:controller ParkingController --api --model=Parking

php artisan make:request StoreParkingLotRequest
php artisan make:request UpdateParkingLotRequest
php artisan make:request StoreParkingRequest
php artisan make:request IndexParkingRequest

php artisan make:resource ParkingLotResource
php artisan make:resource ParkingResource
```

Each Form Request contains `authorize(): bool { return false; }`. **Change it to `true`** in all
four files, otherwise every request returns 403.

✅ **Checkpoint 5:** `php artisan route:list --path=api` lists 10 routes.

---

## 8. Step 4 — Validation (Form Requests)

### 8.1 StoreParkingLotRequest (Person A)

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParkingLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'hourly_rate' => ['required', 'integer', 'min:0', 'max:5000'],
            'free_spaces' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
```

### 8.2 UpdateParkingLotRequest (Person A)

Same rules, but every field is optional (`sometimes`), so `PATCH` can send only one field:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParkingLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'hourly_rate' => ['sometimes', 'required', 'integer', 'min:0', 'max:5000'],
            'free_spaces' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
```

### 8.3 StoreParkingRequest (Person B)

This one has the custom license-plate rule. Hungarian plates are either `ABC-123`
(3 letters, dash, 3 digits) or `AA BC-123` (2 letters, space, 2 letters, dash, 3 digits).

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parking_lot_id' => ['required', 'integer', 'exists:parking_lots,id'],
            'license_plate' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Z]{3}-\d{3}$|^[A-Z]{2} [A-Z]{2}-\d{3}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'license_plate.regex' => 'The license plate must be in ABC-123 or AA BC-123 format.',
        ];
    }
}
```

Only `parking_lot_id` and `license_plate` are validated. If the client sends `arrived_at`,
`left_at` or `fee`, they are simply ignored — that is exactly what the exercise asks for.

### 8.4 IndexParkingRequest (Person B)

Query-string validation for `GET /api/parkings`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexParkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parking_lot_id' => ['sometimes', 'integer', 'exists:parking_lots,id'],
            'plate' => ['sometimes', 'string', 'max:10'],
            'active' => ['sometimes', 'boolean'],
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'sort' => ['sometimes', 'in:id,arrived_at,fee,-id,-arrived_at,-fee'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
```

---

## 9. Step 5 — Response format (API Resources)

### 9.1 ParkingLotResource (Person A)

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParkingLotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'hourly_rate' => $this->hourly_rate,
            'free_spaces' => $this->free_spaces,
            'full' => $this->free_spaces <= 0,
            'parkings_count' => $this->whenCounted('parkings'),
            'parkings' => $this->when(
                $this->relationLoaded('parkings'),
                fn () => ParkingResource::collection($this->parkings),
            ),
        ];
    }
}
```

`whenCounted` and `when` are conditional fields: they disappear when the data was not loaded,
so the list endpoint does not accidentally return a huge `parkings` array.

### 9.2 ParkingResource (Person B)

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParkingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parking_lot_id' => $this->parking_lot_id,
            'parking_lot_name' => $this->whenLoaded(
                'parkingLot',
                fn () => $this->parkingLot->name,
            ),
            'license_plate' => $this->license_plate,
            'arrived_at' => $this->arrived_at?->format('Y-m-d H:i:s'),
            'left_at' => $this->left_at?->format('Y-m-d H:i:s'),
            'fee' => $this->fee,
            'active' => $this->left_at === null,
        ];
    }
}
```

> The exercise's example uses `Y-m-d H:i:s`. The bonus task asks for `Y-m-d H:i` — change both
> `format()` calls at the end if you do the bonus.

---

## 10. Step 6 — Parking lot endpoints (Person A, 45–85 min)

`app/Http/Controllers/ParkingLotController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParkingLotRequest;
use App\Http\Requests\UpdateParkingLotRequest;
use App\Http\Resources\ParkingLotResource;
use App\Http\Resources\ParkingResource;
use App\Models\ParkingLot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ParkingLotController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $parkingLots = ParkingLot::query()
            ->withCount('parkings')
            ->orderBy('name')
            ->get();

        return ParkingLotResource::collection($parkingLots);
    }

    public function store(StoreParkingLotRequest $request): JsonResponse
    {
        $parkingLot = ParkingLot::create($request->validated());
        $parkingLot->loadCount('parkings');

        return (new ParkingLotResource($parkingLot))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ParkingLot $parkingLot): ParkingLotResource
    {
        $parkingLot
            ->load(['parkings' => fn ($query) => $query->latest('arrived_at')])
            ->loadCount('parkings');

        return new ParkingLotResource($parkingLot);
    }

    public function update(
        UpdateParkingLotRequest $request,
        ParkingLot $parkingLot,
    ): ParkingLotResource {
        $parkingLot->update($request->validated());
        $parkingLot->loadCount('parkings');

        return new ParkingLotResource($parkingLot);
    }

    public function destroy(ParkingLot $parkingLot): Response
    {
        if ($parkingLot->parkings()->whereNull('left_at')->exists()) {
            return response()->json([
                'message' => 'The parking lot cannot be deleted because it has active parkings.',
            ], 409);
        }

        $parkingLot->delete();

        return response()->noContent();
    }

    public function parkings(ParkingLot $parkingLot): AnonymousResourceCollection
    {
        $parkings = $parkingLot->parkings()
            ->with('parkingLot')
            ->latest('arrived_at')
            ->paginate(15)
            ->withQueryString();

        return ParkingResource::collection($parkings);
    }
}
```

What to notice:

- `withCount('parkings')` gives `parkings_count` in **one** extra query, not one per row (no N+1).
- `show()` loads the parkings **and** the count, so both conditional fields appear.
- `destroy()` checks active parkings first, then returns 409 or deletes.
- `paginate()` + `withQueryString()` keeps `?page=` links working.

✅ **Checkpoint 6:** `GET /api/parking-lots` returns 6 lots with `parkings_count`.

---

## 11. Step 7 — Parking endpoints (Person B)

`app/Http/Controllers/ParkingController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexParkingRequest;
use App\Http\Requests\StoreParkingRequest;
use App\Http\Resources\ParkingResource;
use App\Models\Parking;
use App\Models\ParkingLot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParkingController extends Controller
{
    public function index(IndexParkingRequest $request): AnonymousResourceCollection
    {
        $sort = $request->input('sort', '-arrived_at');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        $parkings = Parking::query()
            ->with('parkingLot')
            ->when(
                $request->filled('parking_lot_id'),
                fn ($query) => $query->where('parking_lot_id', $request->integer('parking_lot_id')),
            )
            ->when(
                $request->filled('plate'),
                fn ($query) => $query->where('license_plate', 'like', '%'.$request->input('plate').'%'),
            )
            ->when($request->has('active'), function ($query) use ($request) {
                return $request->boolean('active')
                    ? $query->whereNull('left_at')
                    : $query->whereNotNull('left_at');
            })
            ->when(
                $request->filled('date'),
                fn ($query) => $query->whereDate('arrived_at', $request->input('date')),
            )
            ->orderBy($column, $direction)
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ParkingResource::collection($parkings);
    }

    public function store(StoreParkingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $parking = DB::transaction(function () use ($validated) {
            $parkingLot = ParkingLot::query()->findOrFail($validated['parking_lot_id']);

            if ($parkingLot->free_spaces <= 0) {
                throw ValidationException::withMessages([
                    'parking_lot_id' => 'The parking lot is full.',
                ]);
            }

            $alreadyParked = Parking::query()
                ->where('license_plate', $validated['license_plate'])
                ->whereNull('left_at')
                ->exists();

            if ($alreadyParked) {
                throw ValidationException::withMessages([
                    'license_plate' => 'This license plate is already parked.',
                ]);
            }

            $parking = $parkingLot->parkings()->create([
                'license_plate' => $validated['license_plate'],
                'arrived_at' => now(),
            ]);

            $parkingLot->decrement('free_spaces');

            return $parking;
        });

        $parking->load('parkingLot');

        return (new ParkingResource($parking))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Parking $parking): ParkingResource
    {
        $parking->load('parkingLot');

        return new ParkingResource($parking);
    }

    public function leave(Parking $parking): ParkingResource
    {
        if ($parking->left_at !== null) {
            throw ValidationException::withMessages([
                'parking' => 'This parking has already ended.',
            ]);
        }

        DB::transaction(function () use ($parking) {
            $parking->load('parkingLot');

            $minutes = (int) $parking->arrived_at->diffInMinutes(now());
            $hours = max(1, (int) ceil($minutes / 60));

            $parking->update([
                'left_at' => now(),
                'fee' => $hours * $parking->parkingLot->hourly_rate,
            ]);

            $parking->parkingLot->increment('free_spaces');
        });

        $parking->refresh()->load('parkingLot');

        return new ParkingResource($parking);
    }

    public function destroy(Parking $parking): Response
    {
        DB::transaction(function () use ($parking) {
            if ($parking->left_at === null) {
                $parking->parkingLot->increment('free_spaces');
            }

            $parking->delete();
        });

        return response()->noContent();
    }
}
```

### 11.1 Why the transaction matters

`DB::transaction()` means: **all of it succeeds, or none of it does.**

If saving the parking worked but decreasing `free_spaces` failed, the database would be
inconsistent. The transaction rolls the first part back.

### 11.2 The fee formula

```text
fee = max(1, ceil(minutes / 60)) * hourly_rate
```

| Stay | Hours charged | Fee at 500 Ft/hour |
| --- | --- | --- |
| 10 min | 1 | 500 |
| 60 min | 1 | 500 |
| 61 min | 2 | 1000 |
| 150 min | 3 | 1500 |

✅ **Checkpoint 7:** `POST /api/parkings` returns 201 and the lot's `free_spaces` drops by 1.

---

## 12. Step 8 — Errors and 404 (Person B, 5 min)

`bootstrap/app.php` — extend the `withExceptions` block:

```php
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// ...

->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
    );

    $exceptions->render(function (NotFoundHttpException $e, Request $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'The requested resource was not found.',
            ], 404);
        }
    });
})
```

> Use `NotFoundHttpException`, **not** `ModelNotFoundException`. Laravel converts a missing model
> into `NotFoundHttpException` before your callback runs, so a `ModelNotFoundException` callback
> would never fire.

Now `/api/parking-lots/9999` returns:

```json
{"message": "The requested resource was not found."}
```

with status `404`.

The full status-code map:

| Code | When |
| --- | --- |
| 200 | successful read or update |
| 201 | created (parking lot or arrival) |
| 204 | deleted |
| 404 | unknown id (JSON message) |
| 409 | deleting a lot that has an active parking |
| 422 | validation error, full lot, plate already parked, already-ended parking |

---

## 13. Step 9 — Try it by hand (both, 10 min)

Start the server in one terminal:

```bash
php artisan serve
```

The easiest tool is a REST client (Postman, Insomnia, or the Thunder Client VS Code extension).
Create these requests:

| # | Request | Expected |
| --- | --- | --- |
| 1 | `GET /api/parking-lots` | 200, 6 items, sorted by name |
| 2 | `POST /api/parking-lots` with `{"name":"Teszt parkoló","address":"Budapest, Fő utca 1.","hourly_rate":500,"free_spaces":2}` | 201 |
| 3 | `GET /api/parking-lots/1` | 200, has `parkings` and `parkings_count` |
| 4 | `PATCH /api/parking-lots/1` with `{"hourly_rate":600}` | 200, other fields unchanged |
| 5 | `POST /api/parkings` with `{"parking_lot_id":1,"license_plate":"ABC-123","arrived_at":"2000-01-01 00:00:00","fee":999999}` | 201, `arrived_at` is now, `fee` is null |
| 6 | repeat #5 with the same plate | 422, "already parked" |
| 7 | `PATCH /api/parkings/{id}/leave` | 200, `fee` is correct, `active` is false |
| 8 | repeat #7 | 422, "already ended" |
| 9 | `DELETE /api/parking-lots/{lotWithActiveParking}` | 409 |
| 10 | `GET /api/parking-lots/9999` | 404 JSON message |
| 11 | `GET /api/parkings?plate=ABC&active=1&sort=-fee&per_page=5` | 200, filtered and paginated |
| 12 | `GET /api/parkings?per_page=500` | 422 |

### PowerShell examples (Windows)

```powershell
# List parking lots
Invoke-RestMethod http://127.0.0.1:8000/api/parking-lots | ConvertTo-Json -Depth 5

# Arrival
$body = @{ parking_lot_id = 1; license_plate = "ABC-123" } | ConvertTo-Json
Invoke-RestMethod -Method Post `
    -Uri http://127.0.0.1:8000/api/parkings `
    -ContentType "application/json" `
    -Body $body | ConvertTo-Json -Depth 5

# Departure
Invoke-RestMethod -Method Patch http://127.0.0.1:8000/api/parkings/1/leave | ConvertTo-Json -Depth 5
```

### curl examples (macOS / Linux / Git Bash)

```bash
curl -s http://127.0.0.1:8000/api/parking-lots

curl -s -X POST http://127.0.0.1:8000/api/parkings \
  -H "Content-Type: application/json" \
  -d '{"parking_lot_id":1,"license_plate":"ABC-123"}'

curl -s -X PATCH http://127.0.0.1:8000/api/parkings/1/leave
```

✅ **Checkpoint 8:** all 12 requests behave as expected.

---

## 14. Step 10 — Automated tests (both, 110–120 min)

### 14.1 Turn on the test database

`tests/Pest.php` has this line commented out. Uncomment it:

```php
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)   // <-- remove the // in front
    ->in('Feature');
```

`RefreshDatabase` gives every test a clean database, so tests never see the seeded data.

### 14.2 Person A — parking lot tests

```bash
php artisan make:test --pest ParkingLotApiTest
```

`tests/Feature/ParkingLotApiTest.php`:

```php
<?php

use App\Models\Parking;
use App\Models\ParkingLot;

it('lists parking lots by name with a parking count', function () {
    ParkingLot::factory()->create(['name' => 'Zebra parkoló']);
    ParkingLot::factory()->create(['name' => 'Alfa parkoló']);

    $this->getJson('/api/parking-lots')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Alfa parkoló')
        ->assertJsonPath('data.1.name', 'Zebra parkoló');
});

it('creates a parking lot and defaults free_spaces to zero', function () {
    $this->postJson('/api/parking-lots', [
        'name' => 'Új parkoló',
        'address' => 'Budapest, Fő utca 1.',
        'hourly_rate' => 500,
    ])
        ->assertCreated()
        ->assertJsonPath('data.free_spaces', 0)
        ->assertJsonPath('data.full', true);
});

it('does not delete a parking lot with an active parking', function () {
    $lot = ParkingLot::factory()->create();
    Parking::factory()->create(['parking_lot_id' => $lot->id, 'left_at' => null]);

    $this->deleteJson("/api/parking-lots/{$lot->id}")
        ->assertStatus(409);
});

it('returns a json 404 for an unknown parking lot', function () {
    $this->getJson('/api/parking-lots/9999')
        ->assertNotFound()
        ->assertJsonPath('message', 'The requested resource was not found.');
});
```

### 14.3 Person B — parking tests

```bash
php artisan make:test --pest ParkingApiTest
```

`tests/Feature/ParkingApiTest.php`:

```php
<?php

use App\Models\Parking;
use App\Models\ParkingLot;

it('decreases free spaces when a car arrives and ignores arrived_at and fee', function () {
    $lot = ParkingLot::factory()->create(['free_spaces' => 10]);

    $this->postJson('/api/parkings', [
        'parking_lot_id' => $lot->id,
        'license_plate' => 'ABC-123',
        'arrived_at' => '2000-01-01 00:00:00',
        'fee' => 999999,
    ])
        ->assertCreated()
        ->assertJsonPath('data.active', true)
        ->assertJsonPath('data.fee', null)
        ->assertJsonPath('data.parking_lot_name', $lot->name);

    expect($lot->fresh()->free_spaces)->toBe(9);
});

it('rejects an arrival when the parking lot is full', function () {
    $lot = ParkingLot::factory()->create(['free_spaces' => 0]);

    $this->postJson('/api/parkings', [
        'parking_lot_id' => $lot->id,
        'license_plate' => 'ABC-123',
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.parking_lot_id.0', 'The parking lot is full.');
});

it('rejects a second active parking with the same plate', function () {
    $lot = ParkingLot::factory()->create(['free_spaces' => 10]);
    Parking::factory()->create(['license_plate' => 'ABC-123', 'left_at' => null]);

    $this->postJson('/api/parkings', [
        'parking_lot_id' => $lot->id,
        'license_plate' => 'ABC-123',
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.license_plate.0', 'This license plate is already parked.');
});

it('calculates the fee and frees a space when the car leaves', function () {
    $lot = ParkingLot::factory()->create(['hourly_rate' => 500, 'free_spaces' => 5]);

    $parking = Parking::factory()->create([
        'parking_lot_id' => $lot->id,
        'arrived_at' => now()->subMinutes(61),
        'left_at' => null,
    ]);

    $this->patchJson("/api/parkings/{$parking->id}/leave")
        ->assertOk()
        ->assertJsonPath('data.fee', 1000)
        ->assertJsonPath('data.active', false);

    expect($lot->fresh()->free_spaces)->toBe(6);
});

it('rejects leaving twice', function () {
    $parking = Parking::factory()->create([
        'left_at' => now(),
        'fee' => 500,
    ]);

    $this->patchJson("/api/parkings/{$parking->id}/leave")
        ->assertStatus(422);
});

it('frees a space when an active parking is deleted', function () {
    $lot = ParkingLot::factory()->create(['free_spaces' => 3]);
    $parking = Parking::factory()->create([
        'parking_lot_id' => $lot->id,
        'left_at' => null,
    ]);

    $this->deleteJson("/api/parkings/{$parking->id}")
        ->assertNoContent();

    expect($lot->fresh()->free_spaces)->toBe(4);
});
```

Run everything:

```bash
php artisan test --compact
```

✅ **Checkpoint 9:** all tests green.

---

## 15. Bonus tasks (only if the clock allows)

### 15.1 Revenue endpoint

Add to `routes/api.php`:

```php
Route::get('parking-lots/{parking_lot}/revenue', [ParkingLotController::class, 'revenue']);
```

Add to `ParkingLotController`:

```php
public function revenue(ParkingLot $parkingLot): JsonResponse
{
    $closed = $parkingLot->parkings()->whereNotNull('left_at');

    return response()->json([
        'data' => [
            'parkings_count' => $closed->count(),
            'total_revenue' => $closed->sum('fee'),
        ],
    ]);
}
```

### 15.2 `duration_minutes`

In `ParkingResource`:

```php
'duration_minutes' => $this->left_at === null
    ? null
    : (int) $this->arrived_at->diffInMinutes($this->left_at),
```

### 15.3 Accept lowercase plates

In `StoreParkingRequest`:

```php
protected function prepareForValidation(): void
{
    if ($this->has('license_plate')) {
        $this->merge([
            'license_plate' => strtoupper($this->input('license_plate')),
        ]);
    }
}
```

### 15.4 One date format everywhere

Change both `format('Y-m-d H:i:s')` calls in `ParkingResource` to `format('Y-m-d H:i')`.

---

## 16. Final checklist (do this together)

Read each line out loud and check it in the API or in the tests.

- [ ] `php artisan migrate:fresh --seed` works with one command.
- [ ] 6 lots with different Hungarian names, 30 closed + 8 active parkings.
- [ ] Every response is JSON, including errors.
- [ ] Validation lives in Form Requests; JSON lives in API Resources.
- [ ] No N+1: lists use `with(...)` or `withCount(...)`.
- [ ] Arrival decreases `free_spaces` by exactly 1.
- [ ] Departure increases it by exactly 1.
- [ ] Fee: 10 min at 500 → 500; 61 min at 500 → 1000.
- [ ] The same plate cannot have two active parkings.
- [ ] A full lot returns 422 with `{"errors":{"parking_lot_id":["The parking lot is full."]}}`.
- [ ] `arrived_at` and `fee` are ignored even if the client sends them.
- [ ] A lot with an active parking cannot be deleted → 409.
- [ ] A lot with only closed parkings is deleted together with them.
- [ ] Unknown id → 404 with `"The requested resource was not found."`
- [ ] `GET /api/parkings` supports `parking_lot_id`, `plate`, `active`, `date`, `sort`, `per_page`.
- [ ] Pagination links keep the filters (`withQueryString`).
- [ ] `php artisan test --compact` is green.

---

## 17. Common beginner errors

| Symptom | Cause | Fix |
| --- | --- | --- |
| Every request returns 403 | `authorize()` still returns `false` | Return `true` in the Form Request |
| HTML error page instead of JSON | `api:` route file not registered | Add it in `bootstrap/app.php` |
| `free_spaces` is `null` | No default | `->default(0)` in the migration **and** `$attributes` in the model |
| `Call to a member function diffInMinutes()` | `arrived_at` is a string | Add the `datetime` cast |
| Fee is always 1 hour | Wrong rounding | `max(1, ceil($minutes / 60))` |
| `free_spaces` goes negative | Check happens after the insert | Check before creating, inside the transaction |
| `parking_lot_name` missing | Relation not eager loaded | `->with('parkingLot')` |
| `parking_lot_name` triggers many queries | Lazy loading in a loop | Eager load, never access `$parking->parkingLot` inside a loop |
| Route 404 but the method exists | Wrong parameter name | `{parking_lot}` must match `ParkingLot $parkingLot` |
| Test data from the seeder appears in tests | `RefreshDatabase` still commented out | Uncomment it in `tests/Pest.php` |
| `SQLSTATE[HY000]: General error: 1 no such table` | Migrations not run | `php artisan migrate` |

---

## 18. One-sentence summary of the whole exercise

Two tables, eleven endpoints, validation in Form Requests, JSON in API Resources, and **one
transactional rule**: a car taking a space and a car freeing a space must always keep
`free_spaces` in sync with the active parkings.
