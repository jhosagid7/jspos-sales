<?php

namespace App\Http\Controllers;

use App\Models\DebitNote;
use App\Traits\PdfDebitNoteTrait;
use Illuminate\Http\Request;

class DebitNoteController extends Controller
{
    use PdfDebitNoteTrait;

    public function pdf(DebitNote $note)
    {
        // Check permissions
        if (!auth()->user()->can('manage_debit_notes')) {
            abort(403);
        }

        return $this->generatePdfDebitNote($note);
    }
}
