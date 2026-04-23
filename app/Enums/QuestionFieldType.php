<?php

namespace App\Enums;

enum QuestionFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case RankedText5 = 'ranked_text_5';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Select = 'select';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Pole tekstowe',
            self::Textarea => 'Pole tekstowe (długie)',
            self::RankedText5 => 'Ranking 5 miejsc',
            self::Radio => 'Jednokrotny wybór',
            self::Checkbox => 'Wielokrotny wybór',
            self::Select => 'Lista rozwijana',
        };
    }

    public function needsOptions(): bool
    {
        return in_array($this, [self::Radio, self::Checkbox, self::Select], true);
    }
}
