<?php

namespace App\Features\WhatsApp\Domain\Interfaces;

interface WhatsappTemplate
{
    // public function getBody(): string;

    public static function getName(): string;
    public static function getTwilioName(): string;
    public static function getDisplayName(): string;
    public function getVariables(): array;
    public function getTemplateSid(): string;
}
