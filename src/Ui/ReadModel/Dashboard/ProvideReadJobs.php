<?php

declare(strict_types=1);

namespace PHPMate\Ui\ReadModel\Dashboard;

use Doctrine\DBAL\Connection;
use Symplify\EasyHydrator\ArrayToValueObjectHydrator;

final class ProvideReadJobs
{
    public function __construct(
        private Connection $connection,
        private ArrayToValueObjectHydrator $hydrator,
    ) {}


    /**
     * @return array<ReadJob>
     */
    public function provide(int $jobsCount): array
    {
        $sql = <<<SQL
SELECT 
    job.job_id, job.project_id, job.task_id, job.task_name, job.started_at, job.succeeded_at, job.failed_at, 
    project.name as project_name,
    SUM(job_process.result_execution_time) as execution_time
FROM job
JOIN project
JOIN task
LEFT JOIN job_process ON job.job_id = job_process.job_id
GROUP BY job_process.job_id
ORDER BY job.scheduled_at DESC
LIMIT ?
SQL;

        $resultSet = $this->connection->executeQuery($sql, [$jobsCount], ['integer']);

        return $this->hydrator->hydrateArrays($resultSet->fetchAllAssociative(), ReadJob::class);
    }
}