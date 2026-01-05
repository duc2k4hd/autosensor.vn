<?php

namespace App\Services\Media;

use App\Models\Account;
use Illuminate\Support\Facades\Auth;

class PermissionService
{
    /**
     * Check if user can perform action on media
     */
    public function can(string $action, ?Account $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return false;
        }

        return match ($action) {
            'view' => $this->canView($user),
            'upload' => $this->canUpload($user),
            'edit' => $this->canEdit($user),
            'delete' => $this->canDelete($user),
            'manage_folders' => $this->canManageFolders($user),
            default => false,
        };
    }

    /**
     * Check view permission
     */
    public function canView(Account $user): bool
    {
        // All authenticated users can view
        return in_array($user->role, [
            Account::ROLE_ADMIN,
            Account::ROLE_WRITER,
            Account::ROLE_USER,
        ]);
    }

    /**
     * Check upload permission
     */
    public function canUpload(Account $user): bool
    {
        return in_array($user->role, [
            Account::ROLE_ADMIN,
            Account::ROLE_WRITER,
        ]);
    }

    /**
     * Check edit permission
     */
    public function canEdit(Account $user): bool
    {
        return in_array($user->role, [
            Account::ROLE_ADMIN,
            Account::ROLE_WRITER,
        ]);
    }

    /**
     * Check delete permission
     */
    public function canDelete(Account $user): bool
    {
        return in_array($user->role, [
            Account::ROLE_ADMIN,
            Account::ROLE_WRITER,
        ]);
    }

    /**
     * Check folder management permission
     */
    public function canManageFolders(Account $user): bool
    {
        // Only admin can manage folders
        return $user->role === Account::ROLE_ADMIN;
    }

    /**
     * Check if user can delete root folder
     */
    public function canDeleteRootFolder(Account $user): bool
    {
        // Only superadmin can delete root folders (if you have superadmin role)
        // For now, only admin
        return $user->role === Account::ROLE_ADMIN;
    }
}
