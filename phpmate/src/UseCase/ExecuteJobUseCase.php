<?php

declare(strict_types=1);

namespace PHPMate\UseCase;

use PHPMate\Domain\PhpApplication\BuildApplication;
use PHPMate\Domain\PhpApplication\PrepareApplicationGitRepository;
use PHPMate\Domain\Process\ProcessFailed;
use PHPMate\Domain\Tools\Composer\ComposerCommandFailed;
use PHPMate\Domain\PhpApplication\ApplicationDirectoryProvider;
use PHPMate\Domain\Tools\Git\Git;
use PHPMate\Domain\Tools\Git\GitCommandFailed;
use PHPMate\Domain\GitProvider\GitProvider;
use PHPMate\Domain\Job\JobNotFound;
use PHPMate\Domain\Job\JobsCollection;
use PHPMate\Domain\Project\ProjectNotFound;
use PHPMate\Domain\Project\ProjectsCollection;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class ExecuteJobUseCase
{
    public function __construct(
        private JobsCollection $jobsCollection,
        private ProjectsCollection $projects,
        private ApplicationDirectoryProvider $projectDirectoryProvider,
        private PrepareApplicationGitRepository $prepareApplicationGitRepository,
        private BuildApplication $buildApplication,
        private Git $git,
        private GitProvider $gitProvider
    ) {}


    /**
     * @throws JobNotFound
     * @throws ProjectNotFound
     * @throws GitCommandFailed
     * @throws ComposerCommandFailed
     */
    public function handle(ExecuteJob $command): void
    {
        $job = $this->jobsCollection->get($command->jobId);
        $project = $this->projects->get($job->projectId); // TODO: consider to drop dependency on project and pass only what is needed via command

        try {
            $job->start();
            $this->jobsCollection->save($job);

            $remoteGitRepository = $project->remoteGitRepository;
            $localGitRepository = $this->prepareApplicationGitRepository->prepare(
                $remoteGitRepository->getAuthenticatedUri(),
                $job->taskName
            );
            $projectDirectory = $localGitRepository->workingDirectory;

            $this->buildApplication->build($projectDirectory);

            foreach ($job->commands as $jobCommand) {
                // TODO: decouple
                try {
                    $process = Process::fromShellCommandline($jobCommand, $projectDirectory, timeout: 60 * 20);
                    $process->mustRun();
                } catch (ProcessFailedException $processFailedException) {
                    throw new ProcessFailed($processFailedException->getMessage(), previous: $processFailedException);
                } finally {
                    // TODO: log process output somewhere so we can display it on UI
                }
            }

            if ($this->git->hasUncommittedChanges($projectDirectory)) {
                $this->git->commit($projectDirectory, '[PHP Mate] Task ' . $job->taskName);
                $this->git->forcePush($projectDirectory);

                // $this->notifier->notifyAboutNewChanges(); // TODO: add test
                $branchWithChanges = $localGitRepository->jobBranch;

                if ($this->gitProvider->hasMergeRequestForBranch($remoteGitRepository, $branchWithChanges) === false) {
                    // TODO: [optional] assign to random user from provided list
                    // TODO: description with list of provided users
                    $this->gitProvider->openMergeRequest(
                        $remoteGitRepository,
                        $localGitRepository->mainBranch,
                        $branchWithChanges,
                        '[PHP Mate] Task ' . $job->taskName
                    );
                }

                // TODO: consider else (if MR already opened, that new commits were added)
            }

            $job->finish();
            $this->jobsCollection->save($job);
        } catch (\Throwable $throwable) {
            $job->fail();
            $this->jobsCollection->save($job);

            // $this->notifier->notifyAboutFailedCommand($throwable);

            throw $throwable;
        }
    }
}
