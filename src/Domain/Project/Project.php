<?php

declare(strict_types=1);

namespace PHPMate\Domain\Project;

use JetBrains\PhpStorm\Immutable;
use PHPMate\Domain\Cookbook\Value\RecipeName;
use PHPMate\Domain\Project\Exception\RecipeAlreadyEnabledForProject;
use PHPMate\Domain\Project\Exception\RecipeNotEnabledForProject;
use PHPMate\Domain\Project\Value\ProjectId;
use PHPMate\Domain\GitProvider\Value\RemoteGitRepository;

class Project
{
    #[Immutable]
    public string $name;

    /**
     * @var array<RecipeName>
     */
    #[Immutable(Immutable::PRIVATE_WRITE_SCOPE)]
    public array $enabledRecipes = [];

    public function __construct(
        public ProjectId $projectId,
        public RemoteGitRepository $remoteGitRepository
    ) {
        $this->name = $this->remoteGitRepository->getProject();
    }


    /**
     * @param array<RecipeName> $recipes
     */
    public function changeRecipes(array $recipes): void
    {
        $this->enabledRecipes = $recipes;
    }


    /**
     * @throws RecipeAlreadyEnabledForProject
     */
    public function enableRecipe(RecipeName $recipe): void
    {
        foreach ($this->enabledRecipes as $enabledRecipe) {
            if ($enabledRecipe->equals($recipe)) {
                throw new RecipeAlreadyEnabledForProject();
            }
        }

        $this->enabledRecipes[] = $recipe;
    }


    /**
     * @throws RecipeNotEnabledForProject
     */
    public function disableRecipe(RecipeName $recipe): void
    {
        foreach ($this->enabledRecipes as $key => $enabledRecipe) {
            if ($enabledRecipe->equals($recipe)) {
                unset($this->enabledRecipes[$key]);
                return;
            }
        }

        throw new RecipeNotEnabledForProject();
    }
}
