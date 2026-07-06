<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
     * List of lent books (due date monitoring).
     */
    public function index(Request $request): View
    {
        $scope = $request->input('scope', 'overdue');

        if (! in_array($scope, ['overdue', 'due_soon', 'active'], true)) {
            $scope = 'overdue';
        }

        $filters = [
            'scope' => $scope,
            'search' => $request->input('search'),
        ];

        return view('pages.admin.loans.index', [
            'loans' => $this->loanService->paginate($filters),
            'filters' => $filters,
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
            ->with('success', __('Kitob berildi.'));
    }

    public function return(Loan $loan): RedirectResponse
    {
        $reader = $loan->reader;

        $this->loanService->returnLoan($loan);

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Kitob qaytarildi.'));
    }
}
