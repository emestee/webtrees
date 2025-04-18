<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2025 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Fisharebest\Webtrees\Elements;

use Fisharebest\Webtrees\I18N;

use function strtoupper;

/**
 * MARR:TYPE
 */
class MarriageType extends AbstractElement
{
    public const string VALUE_CIVIL      = 'CIVIL';
    public const string VALUE_COMMON_LAW = 'COMMON LAW';
    public const string VALUE_PARTNERS  = 'PARTNERS';
    public const string VALUE_RELIGIOUS = 'RELIGIOUS';

    /**
     * Convert a value to a canonical form.
     * GEDCOM 5.5EL uses 'RELI' for 'Religious marriage' (RELIGIOUS)
     */
    public function canonical(string $value): string
    {
        $value = strtoupper(parent::canonical($value));

        $canonical = [
            'RELI' => self::VALUE_RELIGIOUS,
        ];

        return $canonical[$value] ?? $value;
    }

    /**
     * A list of controlled values for this element.
     *
     * @return array<int|string,string>
     */
    public function values(): array
    {
        return [
            ''                     => '',
            self::VALUE_CIVIL      => I18N::translate('Civil marriage'),
            self::VALUE_COMMON_LAW => I18N::translate('Common-law marriage'),
            self::VALUE_PARTNERS   => I18N::translate('Registered partnership'),
            self::VALUE_RELIGIOUS  => I18N::translate('Religious marriage'),
        ];
    }
}
