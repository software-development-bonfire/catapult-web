<?php

namespace App\Services\TMG;

use App\Repositories\TMG\TmgRepository;
use App\Repositories\Contracts\CDISKitchenUserRepository;
use App\Traits\TokenResponsesJson;
use Illuminate\Support\Facades\Log;

class TmgService
{
    use TokenResponsesJson;

    protected $repository;

    public function __construct(TmgRepository $repository)
    {
        $this->repository = $repository;
    }

    public function login(string $passcode, ?string $deviceUid, string $kitchenStationBid): array
    {
        try {
            $userRepo = app()->make(CDISKitchenUserRepository::class);
            $result = $userRepo->authenticateByPasscode($passcode);

            if (!$result) {
                return ['success' => false, 'message' => 'Invalid passcode.', 'data' => null];
            }

            $station = $this->repository->getKitchenStation($kitchenStationBid);
            if (!$station) {
                return ['success' => false, 'message' => 'Kitchen station not found.', 'data' => null];
            }

            return [
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'user' => $result['user'],
                    'branch_bid' => $result['branch_bid'],
                    'kitchen_station' => $station,
                    'device_uid' => $deviceUid,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('TmgService::login', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Login failed.', 'data' => null];
        }
    }

    public function logout(?string $token): void
    {
        if ($token) {
            \Laravel\Passport\Token::where('id', $token)->delete();
        }
    }

    public function getTransactions(?string $kitchenStationBid, ?string $status = null, ?string $orderType = null): array
    {
        return $this->repository->getTransactions($kitchenStationBid, $status, $orderType);
    }

    public function getTransactionDetail(string $headBid): ?array
    {
        return $this->repository->getTransactionDetail($headBid);
    }

    public function handleAction(string $action, array $payload): array
    {
        try {
            switch ($action) {
                case 'prepare':
                    return $this->repository->prepareItem($payload);
                case 'bump':
                    return $this->repository->bumpItem($payload);
                case 'undo':
                    return $this->repository->undoItem($payload);
                default:
                    return ['success' => false, 'message' => "Invalid action: $action", 'data' => null];
            }
        } catch (\Exception $e) {
            Log::error('TmgService::handleAction', ['action' => $action, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Action failed.', 'data' => null];
        }
    }

    public function getKitchenStations(): array
    {
        return $this->repository->getAllKitchenStations();
    }

    public function getSummary(?string $kitchenStationBid): array
    {
        return $this->repository->getSummary($kitchenStationBid);
    }
}
