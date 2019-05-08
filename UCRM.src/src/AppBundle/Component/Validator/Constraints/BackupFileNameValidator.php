<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Validator\Constraints;

use AppBundle\FileManager\BackupFileManager;
use Nette\Utils\Strings;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class BackupFileNameValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (! $constraint instanceof BackupFileName) {
            throw new UnexpectedTypeException($constraint, BackupFileName::class);
        }

        $filename = null;
        if ($value instanceof UploadedFile) {
            $filename = $value->getClientOriginalName();
        } elseif ($value instanceof File) {
            $filename = $value->getFilename();
        }
        if (! $filename) {
            return;
        }

        if (! Strings::match($filename, BackupFileManager::BACKUP_DATABASE_REGEX)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
