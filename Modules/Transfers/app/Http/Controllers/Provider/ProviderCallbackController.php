<?php

namespace Modules\Transfers\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Transfers\DTOs\BankingTransferResultDTO;
use Modules\Transfers\Enums\TransferStatus;
use Modules\Transfers\Interfaces\TransferInterface;
use Modules\Transfers\Services\TransferLifecycleService;
use Modules\Transfers\Support\Money;

class ProviderCallbackController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private TransferInterface $repository,
        private TransferLifecycleService $lifecycle,
    ) {}

    public function __invoke(Request $request)
    {
        $secret = (string) config('transfers.callback_secret');

        if ($secret === '') {
            return $this->failure(__('transfers::messages.callback_not_configured'), 403);
        }

        $signature = (string) $request->header('X-Provider-Signature');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if ($signature === '' || ! hash_equals($expected, $signature)) {
            return $this->failure(__('transfers::messages.invalid_callback_signature'), 403);
        }

        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in([
                TransferStatus::Processing->value,
                TransferStatus::Successful->value,
                TransferStatus::Failed->value,
                TransferStatus::Reversed->value,
                TransferStatus::Refunded->value,
            ])],
            'provider_reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'decimal:0,2'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfer = $this->repository->findForCallback($validated['reference']);

        if (! $transfer) {
            return $this->failure(__('transfers::messages.transfer_not_found'), 404);
        }

        if (isset($validated['amount']) && Money::normalize($validated['amount']) !== Money::normalize($transfer->amount)) {
            throw ValidationException::withMessages([
                'amount' => __('transfers::messages.callback_amount_mismatch'),
            ]);
        }

        $this->lifecycle->recordCallbackReceived($transfer, $validated);

        $updated = $this->lifecycle->applyProviderResult($transfer->id, new BankingTransferResultDTO(
            status: TransferStatus::from($validated['status']),
            providerReference: $validated['provider_reference'] ?? $transfer->provider_reference,
            failureReason: $validated['reason'] ?? null,
            metadata: ['source' => 'provider_callback'],
        ), 'provider_callback');

        return $this->success(__('transfers::messages.callback_processed'), data: [
            'reference' => $updated->reference,
            'status' => $updated->status->value,
        ]);
    }
}
