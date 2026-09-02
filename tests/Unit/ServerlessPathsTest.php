<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Sur un hébergement serverless, tout est en lecture seule sauf /tmp. Laravel
 * écrit pourtant à deux endroits du projet, et l'un des deux — le manifeste
 * des fournisseurs de services — n'est pas versionné : sans déplacement, la
 * toute première requête tue l'application.
 */
class ServerlessPathsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/smartlink-serverless-'.uniqid();
    }

    protected function tearDown(): void
    {
        foreach (['APP_SERVICES_CACHE', 'APP_PACKAGES_CACHE', 'APP_CONFIG_CACHE', 'APP_ROUTES_CACHE', 'APP_EVENTS_CACHE'] as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        if (is_dir($this->root)) {
            foreach (glob($this->root.'/**/*') ?: [] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            exec('rm -rf '.escapeshellarg($this->root));
        }

        parent::tearDown();
    }

    public function test_it_points_every_bootstrap_cache_file_into_the_writable_root(): void
    {
        $paths = serverless_relocate_bootstrap_cache($this->root.'/bootstrap/cache');

        $this->assertSame([
            'APP_SERVICES_CACHE',
            'APP_PACKAGES_CACHE',
            'APP_CONFIG_CACHE',
            'APP_ROUTES_CACHE',
            'APP_EVENTS_CACHE',
        ], array_keys($paths));

        foreach ($paths as $key => $path) {
            $this->assertStringStartsWith($this->root, $path, "{$key} doit pointer dans le dossier écrivable.");
            $this->assertSame($path, getenv($key), "{$key} doit être posé dans l'environnement.");
            $this->assertSame($path, $_ENV[$key]);
            $this->assertSame($path, $_SERVER[$key]);
        }
    }

    public function test_it_creates_the_destination_directory(): void
    {
        $destination = $this->root.'/bootstrap/cache';

        $this->assertDirectoryDoesNotExist($destination);

        serverless_relocate_bootstrap_cache($destination);

        $this->assertDirectoryExists($destination);
    }

    public function test_it_carries_over_what_the_build_already_produced(): void
    {
        $built = $this->root.'/construit';
        mkdir($built, 0755, true);
        file_put_contents($built.'/packages.php', '<?php return [];');

        serverless_relocate_bootstrap_cache($this->root.'/bootstrap/cache', $built);

        $this->assertFileExists($this->root.'/bootstrap/cache/packages.php');
    }

    public function test_it_prepares_every_directory_laravel_writes_into(): void
    {
        $storage = serverless_storage_path($this->root.'/storage');

        foreach ([
            'app/public',
            'framework/cache/data',
            'framework/sessions',
            'framework/views',
            'logs',
        ] as $expected) {
            $this->assertDirectoryExists($storage.'/'.$expected);
        }
    }
}
