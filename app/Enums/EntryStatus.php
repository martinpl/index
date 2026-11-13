<?php

namespace App\Enums;

enum EntryStatus: string
{
    case Publish = 'publish';
    case Draft = 'draft';
    case Trash = 'trash';

    /**
     * Entries used by the site without being its content, like media files: they are left out of the published entries,
     * so they are not served at their URLs nor counted as the entries of their authors.
     */
    case Internal = 'internal';
}
