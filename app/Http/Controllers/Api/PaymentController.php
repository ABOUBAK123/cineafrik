<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Models\Transaction;
use App\Models\UserAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private const SUPPORTED_METHODS = [
        'CI' => ['cinetpay', 'wave', 'orange_money', 'mtn_momo'],
        'SN' => ['wave', 'orange_money', 'fedapay'],
        'NG' => ['paystack', 'mtn_momo'],
        'GH' => ['paystack', 'mtn_momo'],
        'BF' => ['cinetpay', 'orange_money'],
    ];

    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'film_id' => 'required|integer|exists:films,id',
            'payment_method' => 'required|string',
            'phone' => 'required|string|max:20',
        ]);

        $user = $request->user();
        $film = Film::published()->with('prices')->findOrFail($request->film_id);

        if ($user->hasAccessToFilm($film->id)) {
            return response()->json(['message' => 'Vous avez déjà accès à ce film.'], 409);
        }

        $price = $film->getPriceForCountry($user->country);
        if (!$price) {
            return response()->json(['message' => 'Film non disponible dans votre pays.'], 404);
        }

        $allowedMethods = self::SUPPORTED_METHODS[$user->country] ?? [];
        if (!in_array($request->payment_method, $allowedMethods)) {
            return response()->json([
                'message' => 'Méthode de paiement non supportée dans votre pays.',
                'available_methods' => $allowedMethods,
            ], 422);
        }

        $transaction = DB::transaction(function () use ($user, $film, $price, $request) {
            return Transaction::create([
                'user_id' => $user->id,
                'film_id' => $film->id,
                'amount' => $price->amount,
                'currency' => $price->currency,
                'payment_method' => $request->payment_method,
                'country' => $user->country,
                'phone' => $request->phone,
                'status' => 'pending',
            ]);
        });

        // Appel fournisseur paiement
        $providerResponse = $this->callPaymentProvider($transaction, $request->payment_method);

        if (!$providerResponse['success']) {
            $transaction->update(['status' => 'failed', 'provider_response' => $providerResponse]);
            return response()->json([
                'message' => 'Échec d\'initiation du paiement.',
                'error' => $providerResponse['error'] ?? null,
            ], 502);
        }

        $transaction->update([
            'provider_transaction_id' => $providerResponse['transaction_id'] ?? null,
            'provider_response' => $providerResponse,
        ]);

        return response()->json([
            'transaction_reference' => $transaction->reference,
            'status' => 'pending',
            'payment_url' => $providerResponse['payment_url'] ?? null,
            'instructions' => $providerResponse['instructions'] ?? null,
        ], 202);
    }

    public function status(Request $request, string $reference): JsonResponse
    {
        $transaction = Transaction::where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->with('film:id,title,slug,thumbnail')
            ->firstOrFail();

        return response()->json([
            'reference' => $transaction->reference,
            'status' => $transaction->status,
            'film' => $transaction->film,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'paid_at' => $transaction->paid_at,
        ]);
    }

    public function webhook(Request $request, string $provider): JsonResponse
    {
        // Vérification signature webhook par provider
        if (!$this->verifyWebhookSignature($request, $provider)) {
            Log::warning("Webhook signature invalide pour $provider", $request->all());
            return response()->json(['message' => 'Signature invalide.'], 401);
        }

        $payload = $request->all();
        Log::info("Webhook $provider reçu", $payload);

        $providerTransactionId = $this->extractProviderTransactionId($payload, $provider);
        if (!$providerTransactionId) {
            return response()->json(['message' => 'OK']);
        }

        $transaction = Transaction::where('provider_transaction_id', $providerTransactionId)->first();
        if (!$transaction || $transaction->isCompleted()) {
            return response()->json(['message' => 'OK']);
        }

        $status = $this->extractPaymentStatus($payload, $provider);

        if ($status === 'completed') {
            DB::transaction(function () use ($transaction, $payload) {
                $transaction->update([
                    'status' => 'completed',
                    'provider_response' => array_merge($transaction->provider_response ?? [], $payload),
                    'paid_at' => now(),
                ]);

                UserAccess::updateOrCreate(
                    ['user_id' => $transaction->user_id, 'film_id' => $transaction->film_id],
                    ['transaction_id' => $transaction->id]
                );
            });
        } elseif ($status === 'failed') {
            $transaction->update([
                'status' => 'failed',
                'provider_response' => array_merge($transaction->provider_response ?? [], $payload),
            ]);
        }

        return response()->json(['message' => 'OK']);
    }

    public function history(Request $request): JsonResponse
    {
        $transactions = $request->user()
            ->transactions()
            ->with('film:id,title,slug,thumbnail')
            ->latest()
            ->paginate(20);

        return response()->json($transactions);
    }

    private function callPaymentProvider(Transaction $transaction, string $method): array
    {
        // Stub — à remplacer par les vraies intégrations
        return match ($method) {
            'cinetpay' => $this->cinetpayInitiate($transaction),
            'wave' => $this->waveInitiate($transaction),
            'orange_money' => $this->orangeMoneyInitiate($transaction),
            'mtn_momo' => $this->mtnMomoInitiate($transaction),
            'fedapay' => $this->fedapayInitiate($transaction),
            'paystack' => $this->paystackInitiate($transaction),
            default => ['success' => false, 'error' => 'Méthode inconnue'],
        };
    }

    private function cinetpayInitiate(Transaction $t): array
    {
        // TODO: Intégrer CinetPay API réelle
        return [
            'success' => true,
            'transaction_id' => 'CINET_' . $t->id,
            'payment_url' => null,
            'instructions' => "Composez *144# pour valider le paiement de {$t->amount} {$t->currency}",
        ];
    }

    private function waveInitiate(Transaction $t): array
    {
        return ['success' => true, 'transaction_id' => 'WAVE_' . $t->id, 'payment_url' => null];
    }

    private function orangeMoneyInitiate(Transaction $t): array
    {
        return ['success' => true, 'transaction_id' => 'OM_' . $t->id, 'payment_url' => null];
    }

    private function mtnMomoInitiate(Transaction $t): array
    {
        return ['success' => true, 'transaction_id' => 'MTN_' . $t->id, 'payment_url' => null];
    }

    private function fedapayInitiate(Transaction $t): array
    {
        return ['success' => true, 'transaction_id' => 'FEDA_' . $t->id, 'payment_url' => null];
    }

    private function paystackInitiate(Transaction $t): array
    {
        return ['success' => true, 'transaction_id' => 'PSK_' . $t->id, 'payment_url' => null];
    }

    private function verifyWebhookSignature(Request $request, string $provider): bool
    {
        // TODO: Implémenter vérification HMAC par provider
        return true;
    }

    private function extractProviderTransactionId(array $payload, string $provider): ?string
    {
        return match ($provider) {
            'cinetpay' => $payload['cpm_trans_id'] ?? null,
            'wave' => $payload['id'] ?? null,
            'orange_money' => $payload['txnid'] ?? null,
            'mtn_momo' => $payload['financialTransactionId'] ?? null,
            'fedapay' => $payload['entity']['id'] ?? null,
            'paystack' => $payload['data']['reference'] ?? null,
            default => null,
        };
    }

    private function extractPaymentStatus(array $payload, string $provider): string
    {
        return match ($provider) {
            'cinetpay' => ($payload['cpm_result'] ?? '') === '00' ? 'completed' : 'failed',
            'wave' => ($payload['status'] ?? '') === 'succeeded' ? 'completed' : 'failed',
            'orange_money' => ($payload['status'] ?? '') === 'SUCCESSFULL' ? 'completed' : 'failed',
            'mtn_momo' => ($payload['status'] ?? '') === 'SUCCESSFUL' ? 'completed' : 'failed',
            'fedapay' => ($payload['entity']['status'] ?? '') === 'approved' ? 'completed' : 'failed',
            'paystack' => ($payload['data']['status'] ?? '') === 'success' ? 'completed' : 'failed',
            default => 'failed',
        };
    }
}
