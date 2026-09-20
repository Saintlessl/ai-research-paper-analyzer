<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Paper;
use App\Models\User;

class PaperPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(RoleName::Admin) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->primaryRole() !== null;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Researcher);
    }

    public function view(User $user, Paper $paper): bool
    {
        return $this->isResearcherOwner($user, $paper)
            || $this->isAssignedReviewer($user, $paper);
    }

    public function viewAnalysis(User $user, Paper $paper): bool
    {
        return $this->view($user, $paper);
    }

    public function update(User $user, Paper $paper): bool
    {
        return $this->isResearcherOwner($user, $paper);
    }

    public function delete(User $user, Paper $paper): bool
    {
        return $this->isResearcherOwner($user, $paper);
    }

    public function analyze(User $user, Paper $paper): bool
    {
        return $this->isResearcherOwner($user, $paper);
    }

    public function review(User $user, Paper $paper): bool
    {
        return $this->isAssignedReviewer($user, $paper);
    }

    public function askQuestion(User $user, Paper $paper): bool
    {
        return $this->view($user, $paper);
    }

    private function isResearcherOwner(User $user, Paper $paper): bool
    {
        return $user->hasRole(RoleName::Researcher)
            && $paper->uploaded_by === $user->id;
    }

    private function isAssignedReviewer(User $user, Paper $paper): bool
    {
        return $user->hasRole(RoleName::Reviewer)
            && $paper->assignments()
                ->where('reviewer_id', $user->id)
                ->exists();
    }
}
