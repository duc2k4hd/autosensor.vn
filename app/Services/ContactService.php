<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class ContactService
{
    public function updateStatus(Contact $contact, string $status, ?string $note = null): void
    {
        $contact->fill([
            'status' => $status,
            'is_read' => true,
        ]);

        if ($note !== null) {
            $contact->admin_note = $note;
        }

        $contact->save();
    }

    public function updateNote(Contact $contact, string $note): void
    {
        $contact->admin_note = $note;
        $contact->save();
    }

    public function bulkUpdateStatus(array $ids, string $status): void
    {
        Contact::whereIn('id', $ids)->update([
            'status' => $status,
            'is_read' => true,
        ]);
    }

    public function bulkDelete(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            Contact::whereIn('id', $ids)->delete();
        });
    }
}
