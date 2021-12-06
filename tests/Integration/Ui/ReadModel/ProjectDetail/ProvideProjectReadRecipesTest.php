<?php
declare(strict_types=1);

namespace PHPMate\Tests\Integration\Ui\ReadModel\ProjectDetail;

use PHPMate\Domain\Cookbook\Value\RecipeName;
use PHPMate\Domain\Project\Value\ProjectId;
use PHPMate\Tests\DataFixtures\DataFixtures;
use PHPMate\Ui\ReadModel\ProjectDetail\ProvideProjectReadRecipes;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProvideProjectReadRecipesTest extends KernelTestCase
{
    private ProvideProjectReadRecipes $provideProjectReadRecipes;


    protected function setUp(): void
    {
        $container = self::getContainer();

        $this->provideProjectReadRecipes = $container->get(ProvideProjectReadRecipes::class);
    }


    public function testItWorks(): void
    {
        /**
         * Data are populated from data fixtures
         * @see \PHPMate\Tests\DataFixtures\DataFixtures
         */
        $projectId = DataFixtures::PROJECT_ID;

        $readRecipes = $this->provideProjectReadRecipes->provide(new ProjectId($projectId));

        self::assertCount(2, $readRecipes);

        $recipe = $readRecipes[0];
        self::assertEquals(RecipeName::TYPED_PROPERTIES, $recipe->recipeName);
        self::assertNull($recipe->lastJobId);
        self::assertNull($recipe->lastJobMergeRequestUrl);

        $recipe = $readRecipes[1];
        self::assertEquals(RecipeName::UNUSED_PRIVATE_METHODS, $recipe->recipeName);
        self::assertNotNull($recipe->lastJobId);
        self::assertNotNull($recipe->lastJobMergeRequestUrl);
    }
}
