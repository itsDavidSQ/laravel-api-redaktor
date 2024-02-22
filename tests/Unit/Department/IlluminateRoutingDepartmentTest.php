<?php

declare(strict_types=1);

namespace DSLabs\LaravelApiRedaktor\Tests\Unit\Department;

use DSLabs\LaravelApiRedaktor\Department\IlluminateRoutingDepartment;
use DSLabs\LaravelApiRedaktor\Department\UnexpectedEditorException;
use DSLabs\LaravelApiRedaktor\Editor\IlluminateRoutingEditor;
use DSLabs\LaravelApiRedaktor\Tests\Doubles\Department\RoutingEditorProviderStub;
use DSLabs\Redaktor\Editor\Brief;
use DSLabs\Redaktor\Editor\EditorInterface;
use DSLabs\Redaktor\Editor\RoutingEditorInterface;
use DSLabs\Redaktor\Revision\Revision;
use DSLabs\Redaktor\Version\Version;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @see IlluminateRoutingDepartment
 */
final class IlluminateRoutingDepartmentTest extends TestCase
{
    use ProphecyTrait;

    public function testProvidesAnIlluminateRoutingEditor(): void
    {
        // Arrange
        $juniorRoutingEditor = $this->prophesize(RoutingEditorInterface::class)
            ->reveal();

        $illuminateRoutingDepartment = new IlluminateRoutingDepartment(
            new RoutingEditorProviderStub($juniorRoutingEditor)
        );

        // Act
        $routingEditor = $illuminateRoutingDepartment->provideEditor(self::createBrief());

        // Assert
        self::assertInstanceOf(IlluminateRoutingEditor::class, $routingEditor);
    }

    public function testRefusesNonRoutingJuniorEditor(): void
    {
        // Arrange
        $juniorGenericEditor = $this->prophesize(EditorInterface::class)->reveal();

        $illuminateRoutingDepartment = new IlluminateRoutingDepartment(
            new RoutingEditorProviderStub($juniorGenericEditor)
        );

        // Assert
        $this->expectException(UnexpectedEditorException::class);

        // Act
        $illuminateRoutingDepartment->provideEditor(self::createBrief());
    }

    public function testDelegatesRetrievingTheBriefedVersionToTheJuniorRoutingEditor(): void
    {
        // Arrange
        $juniorRoutingEditorProphecy = $this->prophesize(RoutingEditorInterface::class);
        $juniorRoutingEditorProphecy->briefedVersion()
            ->willReturn($expectedVersion = new Version('foo'));

        $illuminateRoutingDepartment = new IlluminateRoutingDepartment(
            new RoutingEditorProviderStub($juniorRoutingEditorProphecy->reveal())
        );
        $illuminateRoutingEditor = $illuminateRoutingDepartment->provideEditor(self::createBrief());

        // Act / Assert
        self::assertSame(
            $expectedVersion,
            $illuminateRoutingEditor->briefedVersion()
        );
    }

    public function testDelegatesRetrievingTheBriefedRevisionsToTheJuniorRoutingEditor(): void
    {
        // Arrange
        $juniorRoutingEditorProphecy = $this->prophesize(RoutingEditorInterface::class);
        $juniorRoutingEditorProphecy->briefedRevisions()
            ->willReturn($expectedRevisions = [
                new class () implements Revision {},
            ]);

        $illuminateRoutingDepartment = new IlluminateRoutingDepartment(
            new RoutingEditorProviderStub($juniorRoutingEditorProphecy->reveal())
        );
        $editor = $illuminateRoutingDepartment->provideEditor(self::createBrief());

        // Act / Assert
        self::assertSame(
            $expectedRevisions,
            $editor->briefedRevisions()
        );
    }

    private static function createBrief(): Brief
    {
        return new Brief(
            new Version('foo'),
            []
        );
    }
}
