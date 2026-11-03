<?php

namespace App\Constants;

final class RegexPatterns
{
    public const PHONE_DIGITS               = '/^[0-9]+$/';

    public const PHONE_DIGITS_WITH_PLUS     = '/^\+?[0-9]{9,20}$/';

    public const HUMAN_NAME                 = "/^[\p{L}\s'\-]+$/u";

    public const NAME_WITH_REQUIRED_LETTER  = "/^(?=.*\p{L})[\p{L}\d\s'-]+$/u";

    public const EMAIL_NOT_NUMERIC_PREFIX   = '/^(?![0-9]+@)/';

    public const ENGLISH_LEADING_TEXT       = '/^[A-Za-z]+[0-9A-Za-z _&,.\-\/()\'"]*$/u';

    public const ARABIC_LEADING_TEXT        = '/^[\p{Arabic}]+[0-9\p{Arabic}A-Za-z _&,.\-\/()\'"]*$/u';

    public const ARABIC_ENGLISH_LEADING_TEXT = '/^[\p{Arabic}A-Za-z]+[0-9\p{Arabic}A-Za-z _&,.\-\/()\'"]*$/u';

    public const HAS_UPPERCASE              = '@[A-Z]@';

    public const HAS_LOWERCASE              = '@[a-z]@';

    public const HAS_NUMBER                 = '@[0-9]@';

    public const HAS_SPECIAL_CHARACTER      = '@[^\w]@';

    public const SAUDI_MOBILE_NUMBER        = '/^\+9665[0-9]{8}$/';

    public const COMPANY_NAME               = "/^(?=.*\p{L})[\p{L}\p{N}\s&.,'()\/\-]+$/u";

    public const INDIVIDUAL_NAME            = "/^(?=.*\p{L})[\p{L}\p{N}\s&.,'()\/\-]+$/u";

    private function __construct() {}
}
