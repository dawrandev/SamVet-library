<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CopyCondition;
use App\Enums\LoanMaterialType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReturnLoanRequest;
use App\Http\Requests\Admin\StoreLoanRequest;
use App\Models\Loan;
use App\Models\Reader;
use App\Services\LoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class LoanController extends Controller
{
    public function __construct(
        private readonly LoanService $loanService,
    ) {}

    /**
     * List of lent materials (due date monitoring), across all readers.
     */
    public function index(Request $request): View
    {
        $scope = $request->input('scope', 'overdue');

        if (! in_array($scope, ['overdue', 'due_soon', 'active'], true)) {
            $scope = 'overdue';
        }

        $materialType = $request->input('material_type');

        if (! in_array($materialType, array_column(LoanMaterialType::cases(), 'value'), true)) {
            $materialType = null;
        }

        $filters = [
            'scope' => $scope,
            'search' => $request->input('search'),
            'material_type' => $materialType,
        ];

        return view('pages.admin.loans.index', [
            'loans' => $this->loanService->paginate($filters),
            'filters' => $filters,
            'materialTypes' => LoanMaterialType::cases(),
            'overdueCount' => $this->loanService->overdueCount(),
        ]);
    }

    public function store(StoreLoanRequest $request, Reader $reader): RedirectResponse
    {
        try {
            $this->loanService->issueByInventory(
                $reader,
                $request->string('inventory_number')->toString(),
                $request->string('due_at')->toString(),
                $request->input('note'),
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.readers.show', $reader)
                ->withInput()
                ->withErrors(['inventory_number' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Material berildi.'));
    }

    public function return(ReturnLoanRequest $request, Loan $loan): RedirectResponse
    {
        $reader = $loan->reader;

        $condition = $request->filled('returned_condition')
            ? CopyCondition::from($request->string('returned_condition')->toString())
            : null;

        $this->loanService->returnLoan($loan, $condition);

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Material qaytarildi.'));
    }
}
