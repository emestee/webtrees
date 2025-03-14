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

namespace Fisharebest\Webtrees\Services;

use Fisharebest\Webtrees\Gedcom;

/**
 * Utilities for manipulating GEDCOM data.
 */
class GedcomService
{
    // Some applications, such as FTM, use GEDCOM tag names instead of the tags.
    private const array TAG_NAMES = [
        'ABBREVIATION'      => 'ABBR',
        'ADDRESS'           => 'ADDR',
        'ADDRESS1'          => 'ADR1',
        'ADDRESS2'          => 'ADR2',
        'ADDRESS3'          => 'ADR3',
        'ADOPTION'          => 'ADOP',
        'AGENCY'            => 'AGNC',
        'ALIAS'             => 'ALIA',
        'ANCESTORS'         => 'ANCE',
        'ANCES_INTEREST'    => 'ANCI',
        'ANULMENT'          => 'ANUL',
        'ASSOCIATES'        => 'ASSO',
        'AUTHOR'            => 'AUTH',
        'BAPTISM-LDS'       => 'BAPL',
        'BAPTISM'           => 'BAPM',
        'BAR_MITZVAH'       => 'BARM',
        'BAS_MITZVAH'       => 'BASM',
        'BIRTH'             => 'BIRT',
        'BLESSING'          => 'BLES',
        'BURIAL'            => 'BURI',
        'CALL_NUMBER'       => 'CALN',
        'CASTE'             => 'CAST',
        'CAUSE'             => 'CAUS',
        'CENSUS'            => 'CENS',
        'CHANGE'            => 'CHAN',
        'CHARACTER'         => 'CHAR',
        'CHILD'             => 'CHIL',
        'CHRISTENING'       => 'CHR',
        'ADULT_CHRISTENING' => 'CHRA',
        'CONCATENATION'     => 'CONC',
        'CONFIRMATION'      => 'CONF',
        'CONFIRMATION-LDS'  => 'CONL',
        'CONTINUED'         => 'CONT',
        'COPYRIGHT'         => 'COPY',
        'CORPORTATE'        => 'CORP',
        'CREMATION'         => 'CREM',
        'COUNTRY'           => 'CTRY',
        'DEATH'             => 'DEAT',
        'DESCENDANTS'       => 'DESC',
        'DESCENDANTS_INT'   => 'DESI',
        'DESTINATION'       => 'DEST',
        'DIVORCE'           => 'DIV',
        'DIVORCE_FILED'     => 'DIVF',
        'PHY_DESCRIPTION'   => 'DSCR',
        'EDUCATION'         => 'EDUC',
        'EMIGRATION'        => 'EMIG',
        'ENDOWMENT'         => 'ENDL',
        'ENGAGEMENT'        => 'ENGA',
        'EVENT'             => 'EVEN',
        'FAMILY'            => 'FAM',
        'FAMILY_CHILD'      => 'FAMC',
        'FAMILY_FILE'       => 'FAMF',
        'FAMILY_SPOUSE'     => 'FAMS',
        'FACIMILIE'         => 'FAX',
        'FIRST_COMMUNION'   => 'FCOM',
        'FORMAT'            => 'FORM',
        'PHONETIC'          => 'FONE',
        'GEDCOM'            => 'GEDC',
        'GIVEN_NAME'        => 'GIVN',
        'GRADUATION'        => 'GRAD',
        'HEADER'            => 'HEAD',
        'HUSBAND'           => 'HUSB',
        'IDENT_NUMBER'      => 'IDNO',
        'IMMIGRATION'       => 'IMMI',
        'INDIVIDUAL'        => 'INDI',
        'LANGUAGE'          => 'LANG',
        'LATITUDE'          => 'LATI',
        'LONGITUDE'         => 'LONG',
        'MARRIAGE_BANN'     => 'MARB',
        'MARR_CONTRACT'     => 'MARC',
        'MARR_LICENSE'      => 'MARL',
        'MARRIAGE'          => 'MARR',
        'MEDIA'             => 'MEDI',
        'NATIONALITY'       => 'NATI',
        'NATURALIZATION'    => 'NATU',
        'CHILDREN_COUNT'    => 'NCHI',
        'NICKNAME'          => 'NICK',
        'MARRIAGE_COUNT'    => 'NMR',
        'NAME_PREFIX'       => 'NPFX',
        'NAME_SUFFIX'       => 'NSFX',
        'OBJECT'            => 'OBJE',
        'OCCUPATION'        => 'OCCU',
        'ORDINANCE'         => 'ORDI',
        'ORDINATION'        => 'ORDN',
        'PEDIGREE'          => 'PEDI',
        'PHONE'             => 'PHON',
        'PLACE'             => 'PLAC',
        'POSTAL_CODE'       => 'POST',
        'PROBATE'           => 'PROB',
        'PROPERTY'          => 'PROP',
        'PUBLICATION'       => 'PUBL',
        'QUALITY_OF_DATA'   => 'QUAY',
        'REFERENCE'         => 'REFN',
        'RELATIONSHIP'      => 'RELA',
        'RELIGION'          => 'RELI',
        'REPOSITORY'        => 'REPO',
        'RESIDENCE'         => 'RESI',
        'RESTRICTION'       => 'RESN',
        'RETIREMENT'        => 'RETI',
        'REC_FILE_NUMBER'   => 'RFN',
        'REC_ID_NUMBER'     => 'RIN',
        'ROMANIZED'         => 'ROMN',
        'SEALING_CHILD'     => 'SLGC',
        'SEALING_SPOUSE'    => 'SLGS',
        'SOURCE'            => 'SOUR',
        'SURN_PREFIX'       => 'SPFX',
        'SOC_SEC_NUMBER'    => 'SSN',
        'STATE'             => 'STAE',
        'STATUS'            => 'STAT',
        'SUBMITTER'         => 'SUBM',
        'SUBMISSION'        => 'SUBN',
        'SURNAME'           => 'SURN',
        'TEMPLE'            => 'TEMP',
        'TITLE'             => 'TITL',
        'TRAILER'           => 'TRLR',
        'VERSION'           => 'VERS',
        'WEB'               => 'WWW',
        '_DEATH_OF_SPOUSE'  => 'DETS',
        '_DEGREE'           => '_DEG',
        '_MEDICAL'          => '_MCL',
        '_MILITARY_SERVICE' => '_MILT',
    ];

    // Custom GEDCOM tags used by other applications, with direct synonyms
    private const array TAG_SYNONYMS = [
        // Convert PhpGedView tag to webtrees
        '_PGVU'     => '_WT_USER',
        '_PGV_OBJS' => '_WT_OBJE_SORT',
    ];

    /**
     * Convert a GEDCOM tag to a canonical form.
     *
     * @param string $tag
     *
     * @return string
     */
    public function canonicalTag(string $tag): string
    {
        $tag = strtoupper($tag);

        $tag = self::TAG_NAMES[$tag] ?? self::TAG_SYNONYMS[$tag] ?? $tag;

        return $tag;
    }

    public function readLatitude(string $text): float|null
    {
        return $this->readDegrees($text, Gedcom::LATITUDE_NORTH, Gedcom::LATITUDE_SOUTH);
    }

    public function readLongitude(string $text): float|null
    {
        return $this->readDegrees($text, Gedcom::LONGITUDE_EAST, Gedcom::LONGITUDE_WEST);
    }

    private function readDegrees(string $text, string $positive, string $negative): float|null
    {
        $text       = trim($text);
        $hemisphere = substr($text, 0, 1);
        $degrees    = substr($text, 1);

        // Match a valid GEDCOM format
        if (is_numeric($degrees)) {
            $hemisphere = strtoupper($hemisphere);
            $degrees    = (float) $degrees;

            if ($hemisphere === $positive) {
                return $degrees;
            }

            if ($hemisphere === $negative) {
                return -$degrees;
            }
        }

        // Just a number?
        if (is_numeric($text)) {
            return (float) $text;
        }

        // Can't match anything.
        return null;
    }
}
