<?php

namespace App\Enums;

/**
 * Editorial category of a newspaper/journal article — mainly used for
 * in-house periodical content (branch news, student/staff pieces).
 */
enum ArticleCategory: string
{
    case AboutBranch = 'about_branch';
    case StudentAchievements = 'student_achievements';
    case ProfessorArticle = 'professor_article';
    case StudentArticle = 'student_article';

    public function label(): string
    {
        return match ($this) {
            self::AboutBranch => __('Filialimiz haqida'),
            self::StudentAchievements => __('Talabamiz yutuqlari'),
            self::ProfessorArticle => __('Professor-o‘qituvchimizning maqolasi'),
            self::StudentArticle => __('Talabamizning maqolasi'),
        };
    }
}
