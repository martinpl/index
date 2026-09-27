<?php

namespace App\Enums;

enum EntryStatus: string
{
    case Publish = 'publish';
    case Draft = 'draft';
    case Trash = 'trash';
}
