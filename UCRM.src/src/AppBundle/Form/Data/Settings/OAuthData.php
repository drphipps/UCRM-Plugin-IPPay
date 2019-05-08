<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Assert\GroupSequence({"IsUploadedFile", "OAuthData"})
 */
final class OAuthData implements SettingsDataInterface
{
    /**
     * @var string|null
     *
     * @Identifier(Option::GOOGLE_OAUTH_SECRET)
     */
    public $googleOAuthSecret;

    /**
     * @var UploadedFile|null
     *
     * Type constraint for UploadedFile must be used to prevent file enumeration attack
     *
     * @Assert\Type(
     *     type="\Symfony\Component\HttpFoundation\File\UploadedFile",
     *     groups={"IsUploadedFile"},
     *     message="Uploaded file is not valid."
     * )
     * @Assert\File(maxSize = "1M")
     */
    public $googleOAuthSecretFile;

    /**
     * @Assert\Callback()
     */
    public function validate(ExecutionContextInterface $context): void
    {
        if (null === $this->googleOAuthSecretFile || ! $this->googleOAuthSecretFile instanceof UploadedFile) {
            return;
        }

        $secret = file_get_contents($this->googleOAuthSecretFile->getPathname());
        try {
            Json::decode($secret);
        } catch (JsonException $exception) {
            $context
                ->buildViolation(
                    'Uploaded file is not valid JSON.'
                )
                ->atPath('googleOAuthSecretFile')
                ->addViolation();
        }
    }
}
