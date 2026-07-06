<?php

namespace App\Enums;

/**
 * Library member type. Student or staff — distinguished by isStudent().
 */
enum ReaderType: string
{
    case Bachelor = 'bachelor';                 // BT — Bachelor student
    case Master = 'master';                     // MT — Master's student
    case Doctoral = 'doctoral';                 // DT — Doctoral candidate
    case TechnicumStudent = 'technicum_student'; // TT — Technicum student
    case Professor = 'professor';               // PO — Professor / teacher
    case BranchStaff = 'branch_staff';          // FX — Branch staff member
    case TechnicumTeacher = 'technicum_teacher'; // TO — Technicum teacher
    case TechnicumStaff = 'technicum_staff';    // TX — Technicum staff member

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
     * Whether a student (place of study/specialty/group) or staff (workplace/department/position).
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
