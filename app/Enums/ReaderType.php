<?php

namespace App\Enums;

/**
 * Kutubxona a'zosi turi. Talaba yoki xodim — isStudent() farqlaydi.
 */
enum ReaderType: string
{
    case Bachelor = 'bachelor';                 // BT — Bakalavr talabasi
    case Master = 'master';                     // MT — Magistr talabasi
    case Doctoral = 'doctoral';                 // DT — Doktorant
    case TechnicumStudent = 'technicum_student'; // TT — Texnikum talabasi
    case Professor = 'professor';               // PO — Professor-o'qituvchi
    case BranchStaff = 'branch_staff';          // FX — Filial xodimi
    case TechnicumTeacher = 'technicum_teacher'; // TO — Texnikum o'qituvchisi
    case TechnicumStaff = 'technicum_staff';    // TX — Texnikum xodimi

    public function label(): string
    {
        return match ($this) {
            self::Bachelor => __('Bakalavr talabasi'),
            self::Master => __('Magistr talabasi'),
            self::Doctoral => __('Doktorant'),
            self::TechnicumStudent => __('Texnikum talabasi'),
            self::Professor => __('Professor-o‘qituvchi'),
            self::BranchStaff => __('Filial xodimi'),
            self::TechnicumTeacher => __('Texnikum o‘qituvchisi'),
            self::TechnicumStaff => __('Texnikum xodimi'),
        };
    }

    /**
     * Talabami (o'qish joyi/mutaxassislik/guruh) yoki xodimmi (ish joyi/bo'lim/lavozim).
     */
    public function isStudent(): bool
    {
        return in_array($this, [
            self::Bachelor,
            self::Master,
            self::Doctoral,
            self::TechnicumStudent,
        ], true);
    }
}
