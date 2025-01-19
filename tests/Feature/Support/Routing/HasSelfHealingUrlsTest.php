<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Routing;

use App\Support\Routing\HasSelfHealingUrls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Tests\TestCase;

class HasSelfHealingUrlsTest extends TestCase
{
    use RefreshDatabase;

    private Model $testModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test model that uses the trait.
        $this->testModel = new class() extends Model {
            use HasSelfHealingUrls;

            protected $table = 'test_models';
            protected $guarded = [];
        };

        // Create the test table
        $this->createTestTable();
    }

    private function createTestModel(string $title, int $id): Model
    {
        return $this->testModel->newQuery()->create([
            'id' => $id,
            'title' => $title,
        ]);
    }

    private function createTestTable(): void
    {
        $this->getConnection()->getSchemaBuilder()->create('test_models', function ($table) {
            $table->id();
            $table->string('title');
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    public function testItGeneratesSlugFromTitleByDefault(): void
    {
        // Arrange
        $model = $this->createTestModel('Super Mario Bros.', 1);

        // Act
        $slug = $model->slug;

        // Assert
        $this->assertEquals('super-mario-bros-1', $slug);
    }

    public function testItGeneratesSlugFromCustomField(): void
    {
        // Arrange
        $model = new class() extends Model {
            use HasSelfHealingUrls;

            protected $table = 'test_models';
            protected $guarded = [];

            protected function getSlugSourceField(): string
            {
                return 'name';
            }
        };

        $model = $model->newQuery()->create([
            'id' => 1,
            'title' => 'Ignored Title',
            'name' => 'Used Name',
        ]);

        // Act
        $slug = $model->slug;

        // Assert
        $this->assertEquals('used-name-1', $slug);
    }

    public function testItRedirectsNumericRouteParameters(): void
    {
        // Arrange
        $model = $this->createTestModel('Super Mario Bros.', 1);

        // Assert
        try {
            $model->resolveRouteBinding('1');
            $this->fail('Expected HttpResponseException was not thrown');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
            $this->assertEquals(302, $response->getStatusCode());

            $this->assertStringContainsString(
                'super-mario-bros-1',
                $response->headers->get('Location')
            );
        }
    }

    public function testItRedirectsIncorrectSlugs(): void
    {
        // Arrange
        $model = $this->createTestModel('Super Mario Bros.', 1);

        // Assert
        try {
            $model->resolveRouteBinding('wrong-slug-1');
            $this->fail('Expected HttpResponseException was not thrown');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
            $this->assertEquals(302, $response->getStatusCode());

            $this->assertStringContainsString(
                'super-mario-bros-1',
                $response->headers->get('Location')
            );
        }
    }

    public function testItHandlesForwardSlashesInTitle(): void
    {
        // Arrange
        $model = $this->createTestModel('USA/Europe', 1);

        // Act
        $slug = $model->slug;

        // Assert
        $this->assertEquals('usa-europe-1', $slug);
    }

    public function testItReturnsModelForCorrectSlug(): void
    {
        // Arrange
        $model = $this->createTestModel('Super Mario Bros.', 1);
        $expectedSlug = 'super-mario-bros-1';

        // Act
        $resolved = $model->resolveRouteBinding($expectedSlug);

        // Assert
        $this->assertTrue($model->is($resolved));
    }

    public function testItUsesSlugAsRouteKey(): void
    {
        // Arrange
        $model = $this->createTestModel('Super Mario Bros.', 1);

        // Act
        $routeKey = $model->getRouteKey();

        // Assert
        $this->assertEquals('super-mario-bros-1', $routeKey);
    }

    public function testItResolvesDeeplyNestedUrls(): void
    {
        // Arrange
        $model = $this->createTestModel('Super Mario Bros.', 1);

        request()->server->set('REQUEST_URI', '/games/1/achievements/create');

        // Assert
        try {
            $model->resolveRouteBinding('1');
            $this->fail('Expected HttpResponseException was not thrown');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
            $this->assertEquals(302, $response->getStatusCode());

            $this->assertStringContainsString(
                '/games/super-mario-bros-1/achievements/create',
                $response->headers->get('Location')
            );
        }
    }
}
