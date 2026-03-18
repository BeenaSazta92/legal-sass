<?php

namespace App\Models\Traits;

trait HasRoleAndPermissions
{
    /**
     * Check if user is system admin (at platform or firm level)
     */
    public function isSystemAdmin(): bool
    {
        return $this->role === 'SYSTEM_ADMIN';
    }

    /**
     * Check if user is platform-level system admin
     */
    public function isPlatformAdmin(): bool
    {
        return $this->role === 'SYSTEM_ADMIN' && $this->firm_id === null;
    }

    /**
     * Check if user is firm-level system admin
     */
    public function isFirmSystemAdmin(): bool
    {
        return $this->role === 'SYSTEM_ADMIN' && $this->firm_id !== null;
    }

    /**
     * Check if user is firm admin
     */
    public function isFirmAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    /**
     * Check if user is lawyer
     */
    public function isLawyer(): bool
    {
        return $this->role === 'LAWYER';
    }

    /**
     * Check if user is client
     */
    public function isClient(): bool
    {
        return $this->role === 'CLIENT';
    }

    /**
     * Check if user belongs to a specific firm
     */
    public function belongsToFirm($firmId): bool
    {
        return $this->firm_id === $firmId;
    }

    /**
     * Check if user can manage a firm
     * Platform admin can manage all firms
     * Firm system admin can only manage their firm
     * Firm admin can only manage their firm
     */
    public function canManageFirm($firmId): bool
    {
        // Platform admin can manage any firm
        if ($this->isPlatformAdmin()) {
            return true;
        }

        // Firm system admin or firm admin can manage their firm
        if (($this->isFirmSystemAdmin() || $this->isFirmAdmin()) && $this->firm_id === $firmId) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can manage users in a firm
     */
    public function canManageUsersInFirm($firmId): bool
    {
        // Platform admin can manage users in any firm
        if ($this->isPlatformAdmin()) {
            return true;
        }

        // Firm system admin or firm admin can manage users in their firm
        if (($this->isFirmSystemAdmin() || $this->isFirmAdmin()) && $this->firm_id === $firmId) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can access a document
     */
    public function canAccessDocument($documentFirmId): bool
    {
        // Platform admin can access any document
        if ($this->isPlatformAdmin()) {
            return true;
        }

        // Firm users can only access documents from their firm
        return $this->firm_id === $documentFirmId;
    }

    /**
     * Get user's context (what firm/platform they're working in)
     */
    public function getContext(): array
    {
        $context = [
            'role' => $this->role,
            'firm_id' => $this->firm_id,
            'is_platform_admin' => $this->isPlatformAdmin(),
            'is_firm_system_admin' => $this->isFirmSystemAdmin(),
            'is_firm_admin' => $this->isFirmAdmin() && !$this->isFirmSystemAdmin(),
            'is_lawyer' => $this->isLawyer(),
            'is_client' => $this->isClient(),
        ];

        // Add firm name if user belongs to a firm
        // if ($this->firm_id !== null && $this->firm) {
        //     $context['firm_name'] = $this->firm->name;
        // }
        if ($this->relationLoaded('firm') && $this->firm) {
            $context['firm_name'] = $this->firm->name;
        }

        return $context;
    }

    /**
     * Check if user is system admin for a specific firm
     * 
     * @param mixed $firmId
     * @return bool
     */
    public function isSystemAdminForFirm($firmId): bool
    {
        return $this->isFirmSystemAdmin() && $this->firm_id === $firmId;
    }

    /**
     * Get which firm this user is system admin for
     * Returns the firm object if this user is a firm system admin
     * 
     * @return \App\Models\LawFirm|null
     */
    public function getFirmAsSystemAdmin()
    {
        if ($this->isFirmSystemAdmin()) {
            return $this->firm;
        }

        return null;
    }
}
