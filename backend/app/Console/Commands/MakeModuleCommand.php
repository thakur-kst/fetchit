<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name} {--api-version=v1} {--full : Create full structure with all folders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module (minimal by default, use --full for complete structure)';

    /**
     * Module name
     *
     * @var string
     */
    protected string $moduleName;

    /**
     * Module name in StudlyCase
     *
     * @var string
     */
    protected string $studlyName;

    /**
     * Module name in kebab-case
     *
     * @var string
     */
    protected string $kebabName;

    /**
     * Module name in snake_case
     *
     * @var string
     */
    protected string $snakeName;

    /**
     * API Version
     *
     * @var string
     */
    protected string $apiVersion;

    /**
     * Module base path
     *
     * @var string
     */
    protected string $modulePath;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->moduleName = $this->argument('name');
        $this->studlyName = Str::studly($this->moduleName);
        $this->kebabName = Str::kebab($this->moduleName);
        $this->snakeName = Str::snake($this->moduleName);
        $this->apiVersion = $this->option('api-version');
        $this->modulePath = app_path("Modules/{$this->studlyName}");

        $isFull = $this->option('full');

        $this->info("Creating module: {$this->studlyName}" . ($isFull ? ' (full structure)' : ' (minimal structure)'));
        $this->line('');

        // Check if module already exists
        if (File::exists($this->modulePath)) {
            $this->error("Module {$this->studlyName} already exists!");
            return self::FAILURE;
        }

        // Create minimal or full structure
        if ($isFull) {
            $this->createFullStructure();
        } else {
            $this->createMinimalStructure();
        }

        $this->line('');
        $this->info("Module {$this->studlyName} created successfully!");
        $this->line('');
        $this->line('Next steps:');
        $this->line("1. Register the service provider in bootstrap/providers.php:");
        $this->line("   Modules\\{$this->studlyName}\\Providers\\{$this->studlyName}ServiceProvider::class,");
        $this->line('');
        $this->line("2. Run composer dump-autoload:");
        $this->line('   composer dump-autoload');
        $this->line('');
        $this->line("3. Test the module endpoint:");
        $this->line("   curl http://localhost/api/{$this->apiVersion}/{$this->kebabName}/test");
        $this->line('');

        if (!$isFull) {
            $this->line('💡 Tip: Use --full flag to create complete structure with all folders:');
            $this->line("   php artisan make:module {$this->studlyName} --full");
            $this->line('');
        }

        return self::SUCCESS;
    }

    /**
     * Create minimal module structure (default)
     */
    protected function createMinimalStructure(): void
    {
        $this->info('Creating minimal module structure...');

        // Only essential directories for basic API
        $directories = [
            'Http/Controllers/Api/V1',
            'Services',
            'routes',
            'Providers',
        ];

        foreach ($directories as $directory) {
            File::makeDirectory("{$this->modulePath}/{$directory}", 0755, true);
        }

        $this->line('  ✓ Module directories created');

        // Create minimal files
        $this->createMinimalController();
        $this->createMinimalService();
        $this->createMinimalRoutes();
        $this->createMinimalServiceProvider();
        $this->createMinimalReadme();
    }

    /**
     * Create full module structure (with --full flag)
     */
    protected function createFullStructure(): void
    {
        $this->info('Creating full module structure...');

        $directories = [
            'DTOs',
            'Services',
            'ValueObjects',
            'Http/Controllers/Api/V1',
            'Http/Middleware',
            'Models',
            'Console',
            'Exceptions',
            'database/Migrations',
            'database/Seeders',
            'database/Factories',
            'routes',
            'config',
            'tests/Feature',
            'tests/Unit',
            'Providers',
        ];

        foreach ($directories as $directory) {
            File::makeDirectory("{$this->modulePath}/{$directory}", 0755, true);
        }

        $this->line('  ✓ Module directories created');

        $this->createDTOs();
        $this->createServices();
        $this->createValueObjects();
        $this->createHttpLayer();
        $this->createModels();
        $this->createExceptions();
        $this->createConsole();
        $this->createRoutes();
        $this->createServiceProvider();
        $this->createComposerJson();
        $this->createConfig();
        $this->createDatabaseFiles();
        $this->createTests();
        $this->createReadme();
    }

    /**
     * Create minimal controller
     */
    protected function createMinimalController(): void
    {
        $this->info('Creating controller...');

        $controllerContent = $this->getMinimalControllerStub();
        File::put("{$this->modulePath}/Http/Controllers/Api/V1/{$this->studlyName}Controller.php", $controllerContent);

        $this->line('  ✓ Controller created');
    }

    /**
     * Create minimal service
     */
    protected function createMinimalService(): void
    {
        $this->info('Creating service...');

        $serviceContent = $this->getMinimalServiceStub();
        File::put("{$this->modulePath}/Services/{$this->studlyName}Service.php", $serviceContent);

        $this->line('  ✓ Service created');
    }

    /**
     * Create minimal routes
     */
    protected function createMinimalRoutes(): void
    {
        $this->info('Creating routes...');

        $routesContent = $this->getMinimalRoutesStub();
        File::put("{$this->modulePath}/routes/api.php", $routesContent);

        $this->line('  ✓ Routes created');
    }

    /**
     * Create minimal service provider
     */
    protected function createMinimalServiceProvider(): void
    {
        $this->info('Creating service provider...');

        $providerContent = $this->getMinimalServiceProviderStub();
        File::put("{$this->modulePath}/Providers/{$this->studlyName}ServiceProvider.php", $providerContent);

        $this->line('  ✓ Service provider created');
    }

    /**
     * Create minimal README
     */
    protected function createMinimalReadme(): void
    {
        $this->info('Creating README...');

        $readmeContent = $this->getMinimalReadmeStub();
        File::put("{$this->modulePath}/README.md", $readmeContent);

        $this->line('  ✓ README created');
    }

    /**
     * Get minimal controller stub
     */
    protected function getMinimalControllerStub(): string
    {
        $versionUpper = strtoupper($this->apiVersion);

        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Http\\Controllers\\Api\\{$versionUpper};

use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Modules\\{$this->studlyName}\\Services\\{$this->studlyName}Service;

/**
 * {$this->studlyName} Controller
 */
class {$this->studlyName}Controller extends Controller
{
    public function __construct(
        private {$this->studlyName}Service \$service
    ) {}

    /**
     * Test endpoint
     */
    public function test(): JsonResponse
    {
        return response()->json([
            'message' => '{$this->studlyName} module is working!',
            'module' => '{$this->studlyName}',
            'version' => '{$this->apiVersion}',
        ]);
    }

    /**
     * List all items
     */
    public function index(): JsonResponse
    {
        \$items = \$this->service->getAll();

        return response()->json([
            'data' => \$items,
        ]);
    }

    /**
     * Create new item
     */
    public function store(Request \$request): JsonResponse
    {
        // TODO: Add validation
        \$item = \$this->service->create(\$request->all());

        return response()->json([
            'message' => 'Created successfully',
            'data' => \$item,
        ], 201);
    }

    /**
     * Show single item
     */
    public function show(string \$id): JsonResponse
    {
        \$item = \$this->service->getById(\$id);

        if (!\$item) {
            return response()->json([
                'message' => 'Not found',
            ], 404);
        }

        return response()->json([
            'data' => \$item,
        ]);
    }

    /**
     * Update item
     */
    public function update(Request \$request, string \$id): JsonResponse
    {
        // TODO: Add validation
        \$item = \$this->service->update(\$id, \$request->all());

        if (!\$item) {
            return response()->json([
                'message' => 'Not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Updated successfully',
            'data' => \$item,
        ]);
    }

    /**
     * Delete item
     */
    public function destroy(string \$id): JsonResponse
    {
        \$deleted = \$this->service->delete(\$id);

        if (!\$deleted) {
            return response()->json([
                'message' => 'Not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Deleted successfully',
        ]);
    }
}

PHP;
    }

    /**
     * Get minimal service stub
     */
    protected function getMinimalServiceStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Services;

/**
 * {$this->studlyName} Service
 *
 * Business logic for {$this->studlyName} module
 */
class {$this->studlyName}Service
{
    /**
     * Get all items
     */
    public function getAll(): array
    {
        // TODO: Implement logic
        return [];
    }

    /**
     * Get item by ID
     */
    public function getById(string \$id): ?array
    {
        // TODO: Implement logic
        return null;
    }

    /**
     * Create new item
     */
    public function create(array \$data): array
    {
        // TODO: Implement logic
        return \$data;
    }

    /**
     * Update item
     */
    public function update(string \$id, array \$data): ?array
    {
        // TODO: Implement logic
        return null;
    }

    /**
     * Delete item
     */
    public function delete(string \$id): bool
    {
        // TODO: Implement logic
        return false;
    }
}

PHP;
    }

    /**
     * Get minimal routes stub
     */
    protected function getMinimalRoutesStub(): string
    {
        return <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;
use Modules\\{$this->studlyName}\\Http\\Controllers\\Api\\V1\\{$this->studlyName}Controller;

/*
|--------------------------------------------------------------------------
| {$this->studlyName} API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('{$this->kebabName}')->group(function () {
    Route::get('/test', [{$this->studlyName}Controller::class, 'test']);
    Route::get('/', [{$this->studlyName}Controller::class, 'index']);
    Route::post('/', [{$this->studlyName}Controller::class, 'store']);
    Route::get('/{{id}}', [{$this->studlyName}Controller::class, 'show']);
    Route::put('/{{id}}', [{$this->studlyName}Controller::class, 'update']);
    Route::delete('/{{id}}', [{$this->studlyName}Controller::class, 'destroy']);
});

PHP;
    }

    /**
     * Get minimal service provider stub
     */
    protected function getMinimalServiceProviderStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Providers;

use Illuminate\\Support\\ServiceProvider;
use Illuminate\\Support\\Facades\\Route;

/**
 * {$this->studlyName} Service Provider
 */
class {$this->studlyName}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \$this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api/{$this->apiVersion}')
            ->name('{$this->apiVersion}.')
            ->middleware(['api', 'shared.rate_limit'])
            ->group(function () {
                \$this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });
    }
}

PHP;
    }

    /**
     * Get minimal README stub
     */
    protected function getMinimalReadmeStub(): string
    {
        return <<<MD
# {$this->studlyName} Module

Minimal module structure for {$this->studlyName}.

## Structure

```
{$this->studlyName}/
├── Http/Controllers/Api/V1/  # Controllers
├── Services/                 # Business logic
├── routes/                   # Routes
├── Providers/                # Service provider
└── README.md
```

## API Endpoints

- `GET /api/{$this->apiVersion}/{$this->kebabName}/test` - Test endpoint
- `GET /api/{$this->apiVersion}/{$this->kebabName}` - List items
- `POST /api/{$this->apiVersion}/{$this->kebabName}` - Create item
- `GET /api/{$this->apiVersion}/{$this->kebabName}/{{id}}` - Get item
- `PUT /api/{$this->apiVersion}/{$this->kebabName}/{{id}}` - Update item
- `DELETE /api/{$this->apiVersion}/{$this->kebabName}/{{id}}` - Delete item

## Next Steps

Add these as your module grows:

1. **Models** - Add database models when you need persistence
2. **DTOs** - Add Data Transfer Objects for structured data
3. **ValueObjects** - Add domain value objects for type safety
4. **Migrations** - Add database migrations
5. **Tests** - Add feature and unit tests
6. **Middleware** - Add custom middleware if needed
7. **Exceptions** - Add custom exceptions
8. **Console Commands** - Add artisan commands

## Full Structure

To regenerate with full structure including all folders:

```bash
php artisan make:module {$this->studlyName} --full
```

MD;
    }

    // Full structure methods (same as before)...

    protected function createDTOs(): void
    {
        $this->info('Creating DTOs...');
        $dtoContent = $this->getDTOStub();
        File::put("{$this->modulePath}/DTOs/{$this->studlyName}DTO.php", $dtoContent);
        $this->line('  ✓ DTOs created');
    }

    protected function createServices(): void
    {
        $this->info('Creating Services...');
        $serviceContent = $this->getApplicationServiceStub();
        File::put("{$this->modulePath}/Services/{$this->studlyName}ApplicationService.php", $serviceContent);
        $this->line('  ✓ Application Service created');
    }

    protected function createValueObjects(): void
    {
        $this->info('Creating Value Objects...');
        $valueObjectContent = $this->getValueObjectStub();
        File::put("{$this->modulePath}/ValueObjects/{$this->studlyName}Id.php", $valueObjectContent);
        $this->line('  ✓ Value Objects created');
    }

    protected function createHttpLayer(): void
    {
        $this->info('Creating HTTP Layer...');
        $controllerContent = $this->getControllerStub();
        File::put("{$this->modulePath}/Http/Controllers/Api/V1/{$this->studlyName}Controller.php", $controllerContent);
        $middlewareContent = $this->getMiddlewareStub();
        File::put("{$this->modulePath}/Http/Middleware/{$this->studlyName}Middleware.php", $middlewareContent);
        $this->line('  ✓ HTTP Controllers and Middleware created');
    }

    protected function createModels(): void
    {
        $this->info('Creating Models...');
        $modelContent = $this->getModelStub();
        File::put("{$this->modulePath}/Models/{$this->studlyName}.php", $modelContent);
        $this->line('  ✓ Models created');
    }

    protected function createExceptions(): void
    {
        $this->info('Creating Exceptions...');
        $exceptionContent = $this->getExceptionStub();
        File::put("{$this->modulePath}/Exceptions/{$this->studlyName}Exception.php", $exceptionContent);
        $this->line('  ✓ Exceptions created');
    }

    protected function createConsole(): void
    {
        $this->info('Creating Console Commands...');
        $commandContent = $this->getConsoleCommandStub();
        File::put("{$this->modulePath}/Console/{$this->studlyName}Command.php", $commandContent);
        $this->line('  ✓ Console Commands created');
    }

    protected function createRoutes(): void
    {
        $this->info('Creating routes...');
        $apiRoutesContent = $this->getApiRoutesStub();
        File::put("{$this->modulePath}/routes/api.php", $apiRoutesContent);
        $webRoutesContent = $this->getWebRoutesStub();
        File::put("{$this->modulePath}/routes/web.php", $webRoutesContent);
        $consoleRoutesContent = $this->getConsoleRoutesStub();
        File::put("{$this->modulePath}/routes/console.php", $consoleRoutesContent);
        $channelsContent = $this->getChannelsStub();
        File::put("{$this->modulePath}/routes/channels.php", $channelsContent);
        $this->line('  ✓ Routes created');
    }

    protected function createServiceProvider(): void
    {
        $this->info('Creating Service Provider...');
        $providerContent = $this->getServiceProviderStub();
        File::put("{$this->modulePath}/Providers/{$this->studlyName}ServiceProvider.php", $providerContent);
        $this->line('  ✓ Service Provider created');
    }

    protected function createComposerJson(): void
    {
        $this->info('Creating composer.json...');
        $composerContent = $this->getComposerJsonStub();
        File::put("{$this->modulePath}/composer.json", $composerContent);
        $this->line('  ✓ composer.json created');
    }

    protected function createConfig(): void
    {
        $this->info('Creating configuration...');
        $configContent = $this->getConfigStub();
        File::put("{$this->modulePath}/config/{$this->snakeName}.php", $configContent);
        $this->line('  ✓ Configuration file created');
    }

    protected function createDatabaseFiles(): void
    {
        $this->info('Creating database files...');
        $timestamp = date('Y_m_d_His');
        $migrationContent = $this->getMigrationStub();
        File::put("{$this->modulePath}/database/Migrations/{$timestamp}_create_{$this->snakeName}_table.php", $migrationContent);
        $factoryContent = $this->getFactoryStub();
        File::put("{$this->modulePath}/database/Factories/{$this->studlyName}Factory.php", $factoryContent);
        $seederContent = $this->getSeederStub();
        File::put("{$this->modulePath}/database/Seeders/{$this->studlyName}Seeder.php", $seederContent);
        $this->line('  ✓ Database files created');
    }

    protected function createTests(): void
    {
        $this->info('Creating tests...');
        $featureTestContent = $this->getFeatureTestStub();
        File::put("{$this->modulePath}/tests/Feature/{$this->studlyName}Test.php", $featureTestContent);
        $unitTestContent = $this->getUnitTestStub();
        File::put("{$this->modulePath}/tests/Unit/{$this->studlyName}ServiceTest.php", $unitTestContent);
        $this->line('  ✓ Test files created');
    }

    protected function createReadme(): void
    {
        $this->info('Creating README...');
        $readmeContent = $this->getReadmeStub();
        File::put("{$this->modulePath}/README.md", $readmeContent);
        $this->line('  ✓ README.md created');
    }

    // All stub methods from previous implementation...
    // (getDTOStub, getApplicationServiceStub, getValueObjectStub, etc.)
    // I'll include the key ones below:

    protected function getDTOStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\DTOs;

use Modules\\{$this->studlyName}\\Models\\{$this->studlyName};

final class {$this->studlyName}DTO
{
    public function __construct(
        public readonly string \$id,
        public readonly string \$name,
        public readonly string \$createdAt
    ) {}

    public static function fromModel({$this->studlyName} \$model): self
    {
        return new self(
            id: \$model->id,
            name: \$model->name,
            createdAt: \$model->created_at->toIso8601String()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => \$this->id,
            'name' => \$this->name,
            'created_at' => \$this->createdAt,
        ];
    }
}

PHP;
    }

    protected function getApplicationServiceStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Services;

use Modules\\{$this->studlyName}\\DTOs\\{$this->studlyName}DTO;
use Modules\\{$this->studlyName}\\Models\\{$this->studlyName};
use Modules\\{$this->studlyName}\\ValueObjects\\{$this->studlyName}Id;

class {$this->studlyName}ApplicationService
{
    public function getAll(): array
    {
        \$items = {$this->studlyName}::all();
        return \$items->map(fn(\$item) => {$this->studlyName}DTO::fromModel(\$item))->toArray();
    }

    public function getById(string \$id): ?{$this->studlyName}DTO
    {
        \$item = {$this->studlyName}::find(\$id);
        return \$item ? {$this->studlyName}DTO::fromModel(\$item) : null;
    }

    public function create(string \$name): {$this->studlyName}DTO
    {
        \$item = {$this->studlyName}::create([
            'id' => {$this->studlyName}Id::generate()->value(),
            'name' => \$name,
        ]);
        return {$this->studlyName}DTO::fromModel(\$item);
    }

    public function update(string \$id, string \$name): ?{$this->studlyName}DTO
    {
        \$item = {$this->studlyName}::find(\$id);
        if (!\$item) return null;
        \$item->update(['name' => \$name]);
        return {$this->studlyName}DTO::fromModel(\$item);
    }

    public function delete(string \$id): bool
    {
        \$item = {$this->studlyName}::find(\$id);
        if (!\$item) return false;
        return \$item->delete();
    }

    public function getTestData(): {$this->studlyName}DTO
    {
        return new {$this->studlyName}DTO(
            id: {$this->studlyName}Id::generate()->value(),
            name: 'Test {$this->studlyName}',
            createdAt: now()->toIso8601String()
        );
    }
}

PHP;
    }

    protected function getValueObjectStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\ValueObjects;

use Illuminate\\Support\\Str;

final class {$this->studlyName}Id
{
    private string \$value;

    private function __construct(string \$value)
    {
        if (empty(\$value)) {
            throw new \InvalidArgumentException('{$this->studlyName}Id cannot be empty');
        }
        \$this->value = \$value;
    }

    public static function generate(): self
    {
        return new self((string) Str::uuid());
    }

    public static function fromString(string \$value): self
    {
        return new self(\$value);
    }

    public function value(): string
    {
        return \$this->value;
    }

    public function equals({$this->studlyName}Id \$other): bool
    {
        return \$this->value === \$other->value;
    }

    public function __toString(): string
    {
        return \$this->value;
    }
}

PHP;
    }

    protected function getControllerStub(): string
    {
        $versionUpper = strtoupper($this->apiVersion);
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Http\\Controllers\\Api\\{$versionUpper};

use Modules\\{$this->studlyName}\\Services\\{$this->studlyName}ApplicationService;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class {$this->studlyName}Controller extends Controller
{
    public function __construct(
        private {$this->studlyName}ApplicationService \$service
    ) {}

    public function test(): JsonResponse
    {
        \$data = \$this->service->getTestData();
        return response()->json([
            'message' => '{$this->studlyName} module is working!',
            'module' => '{$this->studlyName}',
            'version' => '{$this->apiVersion}',
            'data' => \$data->toArray(),
        ]);
    }

    public function index(): JsonResponse
    {
        \$items = \$this->service->getAll();
        return response()->json(['data' => \$items]);
    }

    public function store(Request \$request): JsonResponse
    {
        \$validated = \$request->validate(['name' => 'required|string|max:255']);
        \$item = \$this->service->create(\$validated['name']);
        return response()->json([
            'message' => '{$this->studlyName} created successfully',
            'data' => \$item->toArray(),
        ], 201);
    }

    public function show(string \$id): JsonResponse
    {
        \$item = \$this->service->getById(\$id);
        if (!\$item) {
            return response()->json(['message' => '{$this->studlyName} not found'], 404);
        }
        return response()->json(['data' => \$item->toArray()]);
    }

    public function update(Request \$request, string \$id): JsonResponse
    {
        \$validated = \$request->validate(['name' => 'required|string|max:255']);
        \$item = \$this->service->update(\$id, \$validated['name']);
        if (!\$item) {
            return response()->json(['message' => '{$this->studlyName} not found'], 404);
        }
        return response()->json([
            'message' => '{$this->studlyName} updated successfully',
            'data' => \$item->toArray(),
        ]);
    }

    public function destroy(string \$id): JsonResponse
    {
        \$deleted = \$this->service->delete(\$id);
        if (!\$deleted) {
            return response()->json(['message' => '{$this->studlyName} not found'], 404);
        }
        return response()->json(['message' => '{$this->studlyName} deleted successfully']);
    }
}

PHP;
    }

    protected function getMiddlewareStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Http\\Middleware;

use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

class {$this->studlyName}Middleware
{
    public function handle(Request \$request, Closure \$next): Response
    {
        // Add middleware logic here
        return \$next(\$request);
    }
}

PHP;
    }

    protected function getModelStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Modules\\{$this->studlyName}\\Database\\Factories\\{$this->studlyName}Factory;

class {$this->studlyName} extends Model
{
    use HasFactory;

    protected \$table = '{$this->snakeName}';
    protected \$fillable = ['id', 'name'];
    protected \$casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public \$incrementing = false;
    protected \$keyType = 'string';

    protected static function newFactory(): {$this->studlyName}Factory
    {
        return {$this->studlyName}Factory::new();
    }
}

PHP;
    }

    protected function getExceptionStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Exceptions;

use Exception;

class {$this->studlyName}Exception extends Exception
{
    public static function notFound(string \$id): self
    {
        return new self("{$this->studlyName} with ID {\$id} not found");
    }

    public static function invalidData(string \$message): self
    {
        return new self("Invalid {$this->studlyName} data: {\$message}");
    }
}

PHP;
    }

    protected function getConsoleCommandStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Console;

use Illuminate\\Console\\Command;

class {$this->studlyName}Command extends Command
{
    protected \$signature = '{$this->kebabName}:process';
    protected \$description = 'Process {$this->studlyName} operations';

    public function handle(): int
    {
        \$this->info('Processing {$this->studlyName}...');
        // Add command logic here
        \$this->info('{$this->studlyName} processing completed!');
        return self::SUCCESS;
    }
}

PHP;
    }

    protected function getApiRoutesStub(): string
    {
        return <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;
use Modules\\{$this->studlyName}\\Http\\Controllers\\Api\\V1\\{$this->studlyName}Controller;

Route::prefix('{$this->kebabName}')->group(function () {
    Route::get('/test', [{$this->studlyName}Controller::class, 'test'])->name('{$this->kebabName}.test');
    Route::get('/', [{$this->studlyName}Controller::class, 'index'])->name('{$this->kebabName}.index');
    Route::post('/', [{$this->studlyName}Controller::class, 'store'])->name('{$this->kebabName}.store');
    Route::get('/{{id}}', [{$this->studlyName}Controller::class, 'show'])->name('{$this->kebabName}.show');
    Route::put('/{{id}}', [{$this->studlyName}Controller::class, 'update'])->name('{$this->kebabName}.update');
    Route::delete('/{{id}}', [{$this->studlyName}Controller::class, 'destroy'])->name('{$this->kebabName}.destroy');
});

PHP;
    }

    protected function getWebRoutesStub(): string
    {
        return <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;

// Add web routes here if needed

PHP;
    }

    protected function getConsoleRoutesStub(): string
    {
        return <<<PHP
<?php

use Illuminate\\Support\\Facades\\Schedule;

// Register scheduled tasks here if needed
// Schedule::command('{$this->kebabName}:process')->daily();

PHP;
    }

    protected function getChannelsStub(): string
    {
        return <<<PHP
<?php

use Illuminate\\Support\\Facades\\Broadcast;

// Register broadcast channels here if needed

PHP;
    }

    protected function getServiceProviderStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Providers;

use Modules\\{$this->studlyName}\\Http\\Middleware\\{$this->studlyName}Middleware;
use Illuminate\\Routing\\Router;
use Illuminate\\Support\\ServiceProvider;
use Illuminate\\Support\\Facades\\Route;

class {$this->studlyName}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        \$this->mergeConfigFrom(__DIR__ . '/../config/{$this->snakeName}.php', '{$this->snakeName}');
    }

    public function boot(): void
    {
        \$this->registerConfig();
        \$this->registerMiddleware();
        \$this->registerRoutes();
        \$this->registerMigrations();
        \$this->registerCommands();
    }

    protected function registerConfig(): void
    {
        \$this->publishes([
            __DIR__ . '/../config/{$this->snakeName}.php' => config_path('{$this->snakeName}.php'),
        ], '{$this->kebabName}-config');
    }

    protected function registerMiddleware(): void
    {
        \$router = \$this->app->make(Router::class);
        \$router->aliasMiddleware('{$this->kebabName}.middleware', {$this->studlyName}Middleware::class);
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api/{$this->apiVersion}')
            ->name('{$this->apiVersion}.')
            ->middleware(['api', 'shared.rate_limit', '{$this->kebabName}.middleware'])
            ->group(function () {
                \$this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });

        \$this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        \$this->loadRoutesFrom(__DIR__ . '/../routes/console.php');
        \$this->loadRoutesFrom(__DIR__ . '/../routes/channels.php');
    }

    protected function registerMigrations(): void
    {
        \$this->loadMigrationsFrom(__DIR__ . '/../database/Migrations');
    }

    protected function registerCommands(): void
    {
        if (\$this->app->runningInConsole()) {
            \$this->commands([
                \\Modules\\{$this->studlyName}\\Console\\{$this->studlyName}Command::class,
            ]);
        }
    }
}

PHP;
    }

    protected function getComposerJsonStub(): string
    {
        return <<<JSON
{
    "name": "modules/{$this->kebabName}",
    "description": "{$this->studlyName} Module",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.3",
        "illuminate/support": "^12.0"
    },
    "autoload": {
        "psr-4": {
            "Modules\\\\{$this->studlyName}\\\\": "./"
        }
    }
}

JSON;
    }

    protected function getConfigStub(): string
    {
        return <<<PHP
<?php

return [
    'enabled' => env('{$this->snakeName}_enabled', true),
    'version' => '1.0.0',
];

PHP;
    }

    protected function getMigrationStub(): string
    {
        return <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$this->snakeName}', function (Blueprint \$table) {
            \$table->uuid('id')->primary();
            \$table->string('name');
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$this->snakeName}');
    }
};

PHP;
    }

    protected function getFactoryStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Database\\Factories;

use Modules\\{$this->studlyName}\\Models\\{$this->studlyName};
use Illuminate\\Database\\Eloquent\\Factories\\Factory;

class {$this->studlyName}Factory extends Factory
{
    protected \$model = {$this->studlyName}::class;

    public function definition(): array
    {
        return [
            'id' => \$this->faker->uuid(),
            'name' => \$this->faker->words(3, true),
        ];
    }
}

PHP;
    }

    protected function getSeederStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Database\\Seeders;

use Illuminate\\Database\\Seeder;
use Modules\\{$this->studlyName}\\Models\\{$this->studlyName};

class {$this->studlyName}Seeder extends Seeder
{
    public function run(): void
    {
        {$this->studlyName}::factory()->count(10)->create();
    }
}

PHP;
    }

    protected function getFeatureTestStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Tests\\Feature;

use Tests\\TestCase;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;

class {$this->studlyName}Test extends TestCase
{
    use RefreshDatabase;

    public function test_{$this->snakeName}_test_endpoint_works(): void
    {
        \$response = \$this->getJson('/api/{$this->apiVersion}/{$this->kebabName}/test');
        \$response->assertStatus(200)->assertJson([
            'message' => '{$this->studlyName} module is working!',
            'module' => '{$this->studlyName}',
        ]);
    }

    public function test_{$this->snakeName}_crud_operations(): void
    {
        // Index
        \$response = \$this->getJson('/api/{$this->apiVersion}/{$this->kebabName}');
        \$response->assertStatus(200);

        // Store
        \$response = \$this->postJson('/api/{$this->apiVersion}/{$this->kebabName}', ['name' => 'Test']);
        \$response->assertStatus(201);

        // Show
        \$item = \\Modules\\{$this->studlyName}\\Models\\{$this->studlyName}::factory()->create();
        \$response = \$this->getJson('/api/{$this->apiVersion}/{$this->kebabName}/' . \$item->id);
        \$response->assertStatus(200);

        // Update
        \$response = \$this->putJson('/api/{$this->apiVersion}/{$this->kebabName}/' . \$item->id, ['name' => 'Updated']);
        \$response->assertStatus(200);

        // Delete
        \$response = \$this->deleteJson('/api/{$this->apiVersion}/{$this->kebabName}/' . \$item->id);
        \$response->assertStatus(200);
    }
}

PHP;
    }

    protected function getUnitTestStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->studlyName}\\Tests\\Unit;

use Tests\\TestCase;
use Modules\\{$this->studlyName}\\Services\\{$this->studlyName}ApplicationService;
use Modules\\{$this->studlyName}\\ValueObjects\\{$this->studlyName}Id;

class {$this->studlyName}ServiceTest extends TestCase
{
    private {$this->studlyName}ApplicationService \$service;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->service = new {$this->studlyName}ApplicationService();
    }

    public function test_get_test_data_returns_valid_dto(): void
    {
        \$dto = \$this->service->getTestData();
        \$this->assertNotEmpty(\$dto->id);
        \$this->assertEquals('Test {$this->studlyName}', \$dto->name);
    }

    public function test_{$this->snakeName}_id_can_be_generated(): void
    {
        \$id = {$this->studlyName}Id::generate();
        \$this->assertNotEmpty(\$id->value());
    }
}

PHP;
    }

    protected function getReadmeStub(): string
    {
        return <<<MD
# {$this->studlyName} Module

Complete module structure for {$this->studlyName}.

## API Endpoints

- `GET /api/{$this->apiVersion}/{$this->kebabName}/test` - Test endpoint
- `GET /api/{$this->apiVersion}/{$this->kebabName}` - List items
- `POST /api/{$this->apiVersion}/{$this->kebabName}` - Create item
- `GET /api/{$this->apiVersion}/{$this->kebabName}/{{id}}` - Get item
- `PUT /api/{$this->apiVersion}/{$this->kebabName}/{{id}}` - Update item
- `DELETE /api/{$this->apiVersion}/{$this->kebabName}/{{id}}` - Delete item

## Testing

```bash
php artisan test --filter={$this->studlyName}
```

MD;
    }
}
