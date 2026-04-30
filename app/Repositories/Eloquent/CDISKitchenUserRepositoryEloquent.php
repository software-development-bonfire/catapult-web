<?php

namespace App\Repositories\Eloquent;

use App\Entities\CDISBranch;
use App\Entities\CDISKitchenUser;
use App\Enums\Status;
use App\Repositories\Contracts\CDISKitchenUserRepository;
use Illuminate\Support\Facades\Hash;

class CDISKitchenUserRepositoryEloquent extends BaseEloquent implements CDISKitchenUserRepository
{
    public function model()
    {
        return CDISKitchenUser::class;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticateByPasscode(string $passcode): ?array
    {
        $user = $this->where(['passcode' => $passcode])->first();

        if (!$user || $user->status !== Status::ACTIVE) {
            return null;
        }

        return $this->validateBranchAccess($user);
    }

    /**
     * {@inheritdoc}
     */
    public function authenticateByCredentials(string $username, string $password): ?array
    {
        $user = $this->where(['username' => $username])->first();

        if (!$user || !Hash::check($password, $user->password) || $user->status !== Status::ACTIVE) {
            return null;
        }

        return $this->validateBranchAccess($user);
    }

    /**
     * Validate that the user has access to the configured branch.
     *
     * @param  CDISKitchenUser $user
     * @return array|null
     */
    private function validateBranchAccess(CDISKitchenUser $user): ?array
    {
        $branchBid = CDISBranch::where('code', config('configuration.branch_code'))
            ->whereNull('deleted_at')
            ->value('bid');

        if ($branchBid && !$user->accessibleBranches()->where('cdis_branch.bid', $branchBid)->exists()) {
            return null;
        }

        return [
            'user' => $user,
            'branch_bid' => $branchBid,
            'allowed_branches' => $user->accessibleBranches()->get(['cdis_branch.bid', 'cdis_branch.name']),
        ];
    }
}
