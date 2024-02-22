<?php

declare(strict_types=1);

namespace DSLabs\LaravelApiRedaktor\Tests\Functional;

use DSLabs\LaravelApiRedaktor\ApiRedaktorServiceProvider;
use DSLabs\LaravelApiRedaktor\Tests\Concerns\InteractsWithApplication;
use DSLabs\LaravelApiRedaktor\Tests\Concerns\InteractsWithConfiguration;
use DSLabs\LaravelApiRedaktor\Tests\Concerns\InteractsWithRouting;
use DSLabs\LaravelApiRedaktor\Tests\Doubles\ResponseRevisionStub;
use DSLabs\LaravelApiRedaktor\Tests\Request;
use DSLabs\Redaktor\Revision\ResponseRevision;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

final class ReviseResponseTest extends TestCase
{
    use InteractsWithApplication;
    use InteractsWithRouting;
    use InteractsWithConfiguration;

    public function getServiceProviders(Application $app): array
    {
        return [
            ApiRedaktorServiceProvider::class,
        ];
    }

    public function testResponseWithNoRevisionsRegistered(): void
    {
        // Arrange
        $originalResponse = new Response();
        $this->addRoute(
            '/foo',
            'GET',
            static function () use ($originalResponse): object {
                return $originalResponse;
            }
        );

        // Act
        $response = $this->getKernel()->handle(
            Request::create('/foo')
        );

        // Assert
        self::assertSame($originalResponse, $response);
    }

    public function testRevisedResponseIsReturnedBack(): void
    {
        // Arrange
        $this->withConfig(
            'api-redaktor.revisions',
            [
                '2020-01' => [
                    self::createResponseRevisionDefinition($revisedResponse = new Response()),
                ],
            ]
        );
        $this->addRoute(
            '/foo',
            'GET'
        );

        // Act
        $response = $this->getKernel()->handle(
            Request::createForVersion('2020-01', '/foo')
        );

        // Assert
        self::assertSame($revisedResponse, $response);
    }

    public function testOriginalResponseIsReturnedIfNoVersionIsSpecified(): void
    {
        // Arrange
        $this->withConfig(
            'api-redaktor.revisions',
            [
                '2020-01' => [
                    self::createResponseRevisionDefinition($revisedResponse = new Response()),
                ],
            ]
        );

        $originalResponse = new Response();
        $this->addRoute(
            '/foo',
            'GET',
            static function () use ($originalResponse): object {
                return $originalResponse;
            }
        );

        // Act
        $response = $this->getKernel()->handle(
            Request::create('/foo')
        );

        // Assert
        self::assertSame($originalResponse, $response);
    }

    private static function createResponseRevisionDefinition(Response $revisedResponse): \Closure
    {
        return static function () use ($revisedResponse): ResponseRevision {
            return new ResponseRevisionStub($revisedResponse);
        };
    }
}
