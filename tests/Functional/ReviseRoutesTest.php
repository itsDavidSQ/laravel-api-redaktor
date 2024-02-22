<?php

declare(strict_types=1);

namespace DSLabs\LaravelApiRedaktor\Tests\Functional;

use DSLabs\LaravelApiRedaktor\ApiRedaktorServiceProvider;
use DSLabs\LaravelApiRedaktor\Tests\Concerns\InteractsWithApplication;
use DSLabs\LaravelApiRedaktor\Tests\Concerns\InteractsWithConfiguration;
use DSLabs\LaravelApiRedaktor\Tests\Concerns\InteractsWithRouting;
use DSLabs\LaravelApiRedaktor\Tests\Doubles\RoutingRevisionStub;
use DSLabs\LaravelApiRedaktor\Tests\Request;
use DSLabs\Redaktor\Revision\RoutingRevision;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use PHPUnit\Framework\TestCase;

final class ReviseRoutesTest extends TestCase
{
    use InteractsWithApplication;
    use InteractsWithExceptionHandling;
    use InteractsWithRouting;
    use InteractsWithConfiguration;

    protected function getServiceProviders(Application $app): array
    {
        return [
            ApiRedaktorServiceProvider::class,
        ];
    }

    public function testAddRoute(): void
    {
        // Arrange
        $revisedRoutes = tap(new RouteCollection(), static function (RouteCollection $routes) {
            $routes->add(
                new Route(
                    'GET',
                    '/foo',
                    static function (): Response {
                        return new Response();
                    }
                )
            );
        });
        $this->withConfig(
            'api-redaktor.revisions',
            [
                '2020-01' => [
                    self::createRoutingRevisionDefinition($revisedRoutes),
                ],
            ]
        );

        // Act
        $okResponse = $this->getKernel()->handle(
            Request::createForVersion('2020-01', '/foo', 'GET')
        );
        $notFoundResponse = $this->getKernel()->handle(
            Request::create('/foo', 'GET')
        );

        // Arrange
        self::assertSame(200, $okResponse->getStatusCode());
        self::assertSame(404, $notFoundResponse->getStatusCode());
    }

    public function testRemoveRoute(): void
    {
        // Arrange
        $this->withConfig(
            'api-redaktor.revisions',
            [
                '2020-01' => [
                    self::createRoutingRevisionDefinition(new RouteCollection()),
                ],
            ]
        );
        $this->addRoute('/foo', 'GET');

        // Act
        $okResponse = $this->getKernel()->handle(
            Request::create('/foo', 'GET')
        );
        $notFoundResponse = $this->getKernel()->handle(
            Request::createForVersion('2020-01', '/foo', 'GET')
        );

        // Arrange
        self::assertSame(200, $okResponse->getStatusCode());
        self::assertSame(404, $notFoundResponse->getStatusCode());
    }

    private static function createRoutingRevisionDefinition(RouteCollection $revisedRoutes): \Closure
    {
        return static function () use ($revisedRoutes): RoutingRevision {
            return new RoutingRevisionStub($revisedRoutes);
        };
    }
}
