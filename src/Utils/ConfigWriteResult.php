<?php

namespace Sazl\LaravelRepokit\Utils;

enum ConfigWriteResult: string
{
    case SUCCESS = 'success';
    case ALREADY_EXISTS = 'already_exists';
    case FILE_NOT_FOUND = 'file_not_found';
    case NOT_WRITABLE = 'not_writable';
}
