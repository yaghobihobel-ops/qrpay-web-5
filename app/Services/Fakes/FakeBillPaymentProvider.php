<?php

namespace App\Services\Fakes;

use App\Services\Contracts\BillPaymentProvider;
use Illuminate\Support\Str;

class FakeBillPaymentProvider implements BillPaymentProvider
{
    protected FakeScenarioRepository $repository;

    public function __construct(?FakeScenarioRepository $repository = null)
    {
        $this->repository = $repository ?? new FakeScenarioRepository();
    }

    protected function scenario(): array
    {
        return $this->repository->load();
    }

    public function getBillers(array $params = [], bool $cache = false): array
    {
        $billers = $this->scenario()['billers']['content'] ?? [];

        if (isset($params['search']) && $params['search']) {
            $term = mb_strtolower($params['search']);
            $billers = array_values(array_filter($billers, function ($biller) use ($term) {
                return str_contains(mb_strtolower($biller['name']), $term);
            }));
        }

        return [
            'content' => array_values($billers),
        ];
    }

    public function getSingleBiller($id): array
    {
        $billers = $this->scenario()['billers']['content'] ?? [];
        $biller = null;
        foreach ($billers as $item) {
            if ((int) $item['id'] === (int) $id) {
                $biller = $item;
                break;
            }
        }

        if (!$biller) {
            return [
                'status' => false,
                'message' => 'Biller not found in sandbox dataset.',
                'content' => [],
            ];
        }

        return [
            'status' => 'SUCCESSFUL',
            'content' => [$biller],
        ];
    }

    public function payUtilityBill(array $data): array
    {
        $reference = $data['referenceId'] ?? 'SANDBOX';
        $amount = $data['amount'] ?? 0;

        if ($amount <= 0) {
            return [
                'status' => false,
                'message' => 'Invalid amount provided for sandbox transaction.',
            ];
        }

        if (str_contains(strtoupper($reference), 'FAIL')) {
            return [
                'status' => false,
                'message' => 'Sandbox payment intentionally failed.',
            ];
        }

        $transactionId = 'UTL-' . Str::upper(Str::random(6));

        return [
            'status' => 'SUCCESSFUL',
            'transactionId' => $transactionId,
            'referenceId' => $reference,
        ];
    }

    public function getTransaction($id): array
    {
        $transactions = $this->scenario()['bill_transactions'] ?? [];
        if (isset($transactions[$id])) {
            return $transactions[$id];
        }

        return [
            'status' => 'SUCCESSFUL',
            'transactionId' => $id,
        ];
    }
}
