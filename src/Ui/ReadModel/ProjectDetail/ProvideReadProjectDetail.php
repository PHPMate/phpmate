<?php

declare(strict_types=1);

namespace Peon\Ui\ReadModel\ProjectDetail;

use Doctrine\DBAL\Connection;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Peon\Domain\Cookbook\Value\RecipeName;
use Peon\Domain\GitProvider\Exception\UnknownGitProvider;
use Peon\Domain\GitProvider\Value\GitProviderName;
use Peon\Domain\PhpApplication\Value\PhpApplicationBuildConfiguration;
use Peon\Domain\Project\Exception\ProjectNotFound;
use Peon\Domain\Project\Value\EnabledRecipe;
use Peon\Domain\Project\Value\ProjectId;
use Peon\Domain\Project\Value\RecipeJobConfiguration;
use UXF\Hydrator\ObjectHydrator;

final class ProvideReadProjectDetail
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ObjectHydrator $hydrator,
    ) {}

    /**
     * @throws ProjectNotFound
     */
    public function provide(ProjectId $projectId): ReadProjectDetail
    {
        $sql = <<<SQL
SELECT
       project_id AS "projectId",
       name,
       enabled_recipes AS "enabledRecipes",
       remote_git_repository_repository_uri AS "remoteGitRepositoryUri",
       build_configuration AS "buildConfiguration"
FROM project
WHERE project_id = :projectId
SQL;

        $resultSet = $this->connection->executeQuery($sql, ['projectId' => $projectId->id]);
        $row = $resultSet->fetchAssociative();

        if ($row === false) {
            throw new ProjectNotFound();
        }

        assert(is_string($row['enabledRecipes']));
        assert(is_string($row['buildConfiguration']));

        /**
         * @var array<EnabledRecipe> $enabledRecipes
         */
        $enabledRecipes = [];

        /**
         * @var array<array{recipe_name: string, baseline_hash: string|null, configuration?: array{merge_automatically: bool}}> $enabledRecipesJson
         */
        $enabledRecipesJson = Json::decode($row['enabledRecipes'], Json::FORCE_ARRAY);

        /**
         * @var array{skip_composer_install?: bool} $buildConfiguration
         */
        $buildConfiguration = Json::decode($row['buildConfiguration'], Json::FORCE_ARRAY);

        // TODO: i do not like this :-/ find better way
        $row['skipComposerInstall'] = $buildConfiguration['skip_composer_install'] ?? PhpApplicationBuildConfiguration::DEFAULT_SKIP_COMPOSER_INSTALL_VALUE;

        // TODO: Temporary fix until we have proper hydrator
        foreach ($enabledRecipesJson as $enabledRecipe) {
            $enabledRecipes[] = new EnabledRecipe(
                RecipeName::from($enabledRecipe['recipe_name']),
                $enabledRecipe['baseline_hash'],
                new RecipeJobConfiguration(
                    $enabledRecipe['configuration']['merge_automatically'] ?? RecipeJobConfiguration::DEFAULT_MERGE_AUTOMATICALLY_VALUE,
                ),
            );
        }

        $row['enabledRecipes'] = $enabledRecipes;

        try{
            $row['gitProviderName'] = GitProviderName::determineFromRepositoryUri($row['remoteGitRepositoryUri']);
        } catch (UnknownGitProvider) {
            $row['gitProviderName'] = null;
        }

        return $this->hydrator->hydrateArray($row, ReadProjectDetail::class);
    }
}
