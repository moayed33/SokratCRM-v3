<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function index(
        Request $request
    ): View {
        $term = trim(
            (string)
            $request->query(
                'q',
                ''
            )
        );

        $query =
            Quotation::query();

        if ($term !== '') {
            $query->where(
                function (
                    $builder
                ) use (
                    $term
                ): void {
                    $builder
                        ->where(
                            'quotation_no',
                            'like',
                            '%'.$term.'%'
                        )
                        ->orWhere(
                            'client_name',
                            'like',
                            '%'.$term.'%'
                        )
                        ->orWhere(
                            'prepared_by',
                            'like',
                            '%'.$term.'%'
                        )
                        ->orWhere(
                            'system_title',
                            'like',
                            '%'.$term.'%'
                        );
                }
            );
        }

        $quotations =
            $query
                ->orderByDesc('id')
                ->paginate(30)
                ->withQueryString();

        return view(
            'quotations.index',
            compact(
                'quotations',
                'term'
            )
        );
    }

    public function create(): View
    {
        return view(
            'quotations.builder'
        );
    }

    public function show(
        Quotation $quotation
    ): View {
        return view(
            'quotations.builder',
            compact(
                'quotation'
            )
        );
    }

    public function store(
        Request $request
    ): JsonResponse {
        $validated =
            $request->validate([
                'clientName' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'location' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'preparedBy' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'quotationNo' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'quoteDate' => [
                    'required',
                    'date',
                ],

                'systemTitle' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'proposalDescription' => [
                    'nullable',
                    'string',
                ],

                'technicalScope' => [
                    'nullable',
                    'string',
                ],

                'terms' => [
                    'nullable',
                    'string',
                ],

                'features' => [
                    'nullable',
                    'string',
                ],

                'financialNote' => [
                    'nullable',
                    'string',
                ],

                'includeProducts' => [
                    'required',
                    'boolean',
                ],

                'includeTechnical' => [
                    'required',
                    'boolean',
                ],

                'includeTerms' => [
                    'required',
                    'boolean',
                ],

                'includeFeatures' => [
                    'required',
                    'boolean',
                ],

                'quoteFontFamily' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'quoteFontSize' => [
                    'required',
                    'integer',
                    'min:85',
                    'max:130',
                ],

                'enableTextColor' => [
                    'required',
                    'boolean',
                ],

                'quoteTextColor' => [
                    'nullable',
                    'string',
                    'max:20',
                ],

                'enableAccentColor' => [
                    'required',
                    'boolean',
                ],

                'quoteAccentColor' => [
                    'nullable',
                    'string',
                    'max:20',
                ],

                'items' => [
                    'required',
                    'array',
                    'min:1',
                    'max:200',
                ],

                'items.*.productId' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'items.*.name' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],

                'items.*.description' => [
                    'nullable',
                    'string',
                ],

                'items.*.qty' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:1000000',
                ],

                'items.*.price' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:999999999.99',
                ],

                'items.*.unit' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'items.*.image' => [
                    'nullable',
                    'string',
                ],

                'items.*.inFinancial' => [
                    'nullable',
                    'boolean',
                ],

                'items.*.showProduct' => [
                    'required',
                    'boolean',
                ],

                'adjustments' => [
                    'nullable',
                    'array',
                    'max:100',
                ],

                'adjustments.*.label' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'adjustments.*.operation' => [
                    'required',
                    'in:add,subtract',
                ],

                'adjustments.*.mode' => [
                    'required',
                    'in:amount,percent',
                ],

                'adjustments.*.value' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:999999999.99',
                ],
            ]);

        $subtotal = 0.0;

        foreach (
            $validated['items']
            as &$item
        ) {
            $item['qty'] =
                (float)
                $item['qty'];

            $item['price'] =
                (float)
                $item['price'];

            $item['inFinancial'] =
                array_key_exists(
                    'inFinancial',
                    $item
                )
                    ? (bool) $item['inFinancial']
                    : true;

            if ($item['inFinancial']) {
                $subtotal +=
                    $item['qty']
                    * $item['price'];
            }
        }

        unset($item);

        $validated['adjustments'] =
            $validated['adjustments'] ?? [];

        $adjustmentTotal = 0.0;

        foreach (
            $validated['adjustments']
            as &$adjustment
        ) {
            $adjustment['value'] =
                (float)
                $adjustment['value'];

            $amount =
                $adjustment['mode'] === 'percent'
                    ? $subtotal * $adjustment['value'] / 100
                    : $adjustment['value'];

            $adjustmentTotal +=
                $adjustment['operation'] === 'subtract'
                    ? -$amount
                    : $amount;
        }

        unset($adjustment);

        $grandTotal = max(
            0,
            $subtotal + $adjustmentTotal
        );

        $quotation =
            Quotation::create([
                'quotation_no' =>
                    $validated[
                        'quotationNo'
                    ],

                'client_name' =>
                    $validated[
                        'clientName'
                    ],

                'location' =>
                    $validated[
                        'location'
                    ] ?? null,

                'prepared_by' =>
                    $validated[
                        'preparedBy'
                    ] ?? null,

                'quote_date' =>
                    $validated[
                        'quoteDate'
                    ],

                'system_title' =>
                    $validated[
                        'systemTitle'
                    ] ?? null,

                'grand_total' =>
                    round(
                        $grandTotal,
                        2
                    ),

                'payload' =>
                    $validated,

                'created_by' =>
                    auth()->user()->name,
                'created_by_user_id' =>
                    $request->user()->id,
            ]);

        return response()->json(
            [
                'ok' => true,

                'id' =>
                    $quotation->id,

                'message' =>
                    'تم حفظ عرض السعر بنجاح.',

                'redirect' =>
                    route(
                        'v2.quotations.show',
                        $quotation
                    ),
            ],
            201
        );
    }
}
