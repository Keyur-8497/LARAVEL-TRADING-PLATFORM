<?php

namespace App\Http\Controllers\Front;

use App\Services\TradeStrategyPostbackService;
use App\Support\ApplicationLogger;
use App\Support\TradingErrorLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZerodhaPostbackController extends FrontMainController
{
    public function __construct(private readonly TradeStrategyPostbackService $tradeStrategyPostbackService)
    {
        parent::__construct();
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            TradingErrorLogger::write('warning', 'Invalid Zerodha postback payload received.', [
                'body' => $request->getContent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid payload.',
            ], 422);
        }

        $result = $this->tradeStrategyPostbackService->process($payload);

        ApplicationLogger::event('Zerodha postback processed.', [
            'order_id' => $payload['order_id'] ?? null,
            'tradingsymbol' => $payload['tradingsymbol'] ?? null,
            'status' => $payload['status'] ?? null,
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? null,
        ]);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
