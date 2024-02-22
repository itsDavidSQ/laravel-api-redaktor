<?php

declare(strict_types=1);

namespace DSLabs\LaravelRedaktor\Tests\Concerns;

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

trait InteractsWithApplication
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * Provides a list of Service Providers to be registered.
     */
    abstract protected function getServiceProviders(Application $app): array;

    /**
     * Set up the application and assign it to a class property, so it can be
     * accessed from other concerns and the test itself.
     */
    protected function setUp(): void
    {
        if ($this instanceof TestCase) {
            parent::setUp();
        }

        $this->app = $this->createApplication();
        ResourcePublisher::observe($this->app->get('events'));
    }

    /**
     * Clean up so next test runs in a clean environment.
     */
    protected function tearDown(): void
    {
        if ($this instanceof TestCase) {
            parent::tearDown();
        }

        if ($this->app instanceof Application) {
            ResourcePublisher::revert();

            // Clear cache
            $artisan = $this->app->make(ConsoleKernelContract::class);
            $artisan->call('optimize:clear');
            $artisan->call('clear-compiled');

            $this->app->flush();
        }
        $this->app = null;
    }

    /**
     * Provides the Application instance.
     */
    protected function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * Provides the Http Kernel instance.
     */
    protected function getKernel(): Kernel
    {
        return $this->app->make(Kernel::class);
    }

    /**
     * Creates and bootstraps an Application instance.
     */
    private function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__ . '/../../vendor/laravel/laravel/bootstrap/app.php';
        $this->redefinePackageManifest($app);

        $app->instance('request', new Request());
        $app->make(Kernel::class)->bootstrap();

        foreach ($this->getServiceProviders($app) as $serviceProvider) {
            $app->register($serviceProvider);
        }

        return $app;
    }

    /**
     * Redefine the `PackageManifest` service to use the project root path as the base path,
     * instead of the default laravel/laravel path inside the vendor directory.
     * `PackageManifest` uses the base path to look for the `composer.json` file to identify
     * any Service Providers defined by composer packages.
     */
    private function redefinePackageManifest(Application $app): void
    {
        $app->singleton(PackageManifest::class, function (Application $app) {
            $projectRootPath = __DIR__ . '/../../';

            return new PackageManifest(
                new Filesystem(),
                $projectRootPath,
                $app->getCachedPackagesPath()
            );
        });
    }
}
