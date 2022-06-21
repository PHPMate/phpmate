<?php

declare(strict_types=1);

namespace Peon\Ui\Controller;

use Peon\Domain\Project\GetProjectIdentifiers;
use Peon\Domain\User\Value\UserId;
use Peon\Ui\ReadModel\Dashboard\ProvideReadJobs;
use Peon\Ui\ReadModel\Dashboard\ProvideReadProjects;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ProvideReadJobs $provideReadJobs,
        private readonly ProvideReadProjects $provideReadProjects,
        private readonly GetProjectIdentifiers $getProjectIdentifiers,
    ) {}


    #[Route(path: '/', name: 'dashboard', methods: ['GET'])]
    public function __invoke(UserInterface $user): Response
    {
        $accessibleProjectIdentifiers = $this->getProjectIdentifiers->ownedByUser(
            new UserId($user->getUserIdentifier()),
        );

        return $this->render('dashboard.html.twig', [
            'jobs' => $this->provideReadJobs->provide($accessibleProjectIdentifiers, 10),
            'projects' => $this->provideReadProjects->provide($accessibleProjectIdentifiers),
        ]);
    }
}
