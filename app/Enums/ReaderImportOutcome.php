<?php

namespace App\Enums;

/**
 * What happened to a single row of a reader-import workbook.
 *
 * Skips used to be a single anonymous counter, which is how a real import
 * reported "563 o'tkazildi" and nothing else: the sheet's name column was
 * headed "F.I.Sh.", the alias table did not know that spelling, so every row
 * arrived with an empty name and was dropped as if it were a blank line. The
 * count was accurate and told the librarian nothing. Naming the reason is what
 * turns that into something fixable.
 */
enum ReaderImportOutcome: string
{
    case Imported = 'imported';

    case Updated = 'updated';

    /** Every field is blank — trailing rows Excel keeps after the real data. */
    case SkippedEmptyRow = 'skipped_empty_row';

    /** Has an ID or PINFL but no name: usually an unrecognised name column. */
    case SkippedNoName = 'skipped_no_name';

    /** Named, but with neither an ID number nor a PINFL to deduplicate on. */
    case SkippedNoIdentifier = 'skipped_no_identifier';

    /** A group caption ("3-kurs") sitting in the middle of the data. */
    case SkippedGroupHeader = 'skipped_group_header';

    /** "Ketkenler" only: the ID prefix matches no known reader type. */
    case SkippedUnknownType = 'skipped_unknown_type';

    /** The row threw — details go to the log, not to the screen. */
    case SkippedError = 'skipped_error';

    public function isSkipped(): bool
    {
        return $this !== self::Imported && $this !== self::Updated;
    }

    /**
     * Whether this skip suggests the FILE is wrong rather than the row being
     * legitimately empty. These are the ones worth putting in front of a
     * librarian, because they are the ones a person can act on.
     */
    public function needsAttention(): bool
    {
        return match ($this) {
            self::SkippedNoName, self::SkippedNoIdentifier, self::SkippedError => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Imported => __('Qo\'shildi'),
            self::Updated => __('Yangilandi'),
            self::SkippedEmptyRow => __('Bo\'sh qator'),
            self::SkippedNoName => __('Ism ustuni topilmadi yoki bo\'sh'),
            self::SkippedNoIdentifier => __('ID raqami ham, JSHSHIR ham yo\'q'),
            self::SkippedGroupHeader => __('Guruh sarlavhasi (kurs/bosqich)'),
            self::SkippedUnknownType => __('ID prefiksi tanilmadi'),
            self::SkippedError => __('Qatorda xato (jurnalga yozildi)'),
        };
    }
}
