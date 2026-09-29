<?php
declare(strict_types=1);

/**
 * This file is part of the Poppy Seed Pets API.
 *
 * The Poppy Seed Pets API is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets API is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets API. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Service\StarKindred;

use App\Enum\StarKindredRaceEnum;
use App\Enum\StarKindredSexEnum;
use App\Service\IRandom;

final class StarKindredNames
{
    public static function roll(IRandom $rng, StarKindredRaceEnum $race, ?StarKindredSexEnum $sex): string
    {
        if($race->isAndrogynous() !== ($sex === null))
            throw new \InvalidArgumentException('Androgynous races must not have a sex; all other races must.');

        $givenName = match($race)
        {
            StarKindredRaceEnum::Human =>
                $rng->rngNextFromArray($sex === StarKindredSexEnum::Female ? self::HumanFemale : self::HumanMale),
            StarKindredRaceEnum::Elf =>
                $rng->rngNextFromArray($sex === StarKindredSexEnum::Female ? self::ElfFemale : self::ElfMale),
            StarKindredRaceEnum::Gnome =>
                $rng->rngNextFromArray($sex === StarKindredSexEnum::Female ? self::GnomeFemale : self::GnomeMale),
            StarKindredRaceEnum::Goblin =>
                $rng->rngNextFromArray($sex === StarKindredSexEnum::Female ? self::GoblinFemale : self::GoblinMale),
            StarKindredRaceEnum::HighFae =>
                $rng->rngNextFromArray($sex === StarKindredSexEnum::Female ? self::HighFaeFemale : self::HighFaeMale),
            StarKindredRaceEnum::Emberkin =>
                $rng->rngNextFromArray(self::EmberkinDragons) . '-' . $rng->rngNextFromArray(self::EmberkinDragonSuffixes),
            StarKindredRaceEnum::Dwarf => $rng->rngNextFromArray(self::Dwarf),
            StarKindredRaceEnum::Beastkin => $rng->rngNextFromArray(self::Beastkin),
            StarKindredRaceEnum::Nymph => $rng->rngNextFromArray(self::Nymph),
        };

        $familyName = match($race)
        {
            StarKindredRaceEnum::Human =>
                $rng->rngNextFromArray(self::HumanFamilyPrefixes) . $rng->rngNextFromArray(self::HumanFamilySuffixes),
            StarKindredRaceEnum::Elf => $rng->rngNextFromArray(self::ElfFamily),
            StarKindredRaceEnum::Dwarf => self::toRomanNumerals($rng->rngNextInt(50, 150)),
            StarKindredRaceEnum::Gnome => self::gnomePatronymic($rng->rngNextFromArray(self::GnomeMale), $sex),
            StarKindredRaceEnum::Goblin => $rng->rngNextFromArray(self::GoblinFamily),
            StarKindredRaceEnum::Beastkin => $rng->rngNextFromArray(self::BeastkinFamily),
            StarKindredRaceEnum::Emberkin => null, // Emberkin go by a single name, taken from a great dragon
            StarKindredRaceEnum::HighFae => $rng->rngNextFromArray(self::HighFaeFamily),
            StarKindredRaceEnum::Nymph =>
                'of the ' . $rng->rngNextFromArray(self::NymphFamilyAdjectives) . ' ' . $rng->rngNextFromArray(self::NymphFamilyPlaces),
        };

        if($familyName === null)
            return $givenName;

        $separator = $race === StarKindredRaceEnum::Beastkin ? ', ' : ' ';

        return $givenName . $separator . $familyName;
    }

    /**
     * Scandinavian-style: "Cobble" -> "Cobblesson" / "Cobblesdotter". The genitive "s" is not doubled for names
     * already ending in one ("Pockets" -> "Pocketsson"), as with Swedish "Anders" -> "Andersson".
     */
    private static function gnomePatronymic(string $fatherName, ?StarKindredSexEnum $sex): string
    {
        $genitive = str_ends_with($fatherName, 's') ? $fatherName : $fatherName . 's';

        return $genitive . ($sex === StarKindredSexEnum::Female ? 'dotter' : 'son');
    }

    /**
     * Dwarves are named after an ancestor, and numbered: "Brakka CXIV" is the 114th Brakka of their line.
     */
    private static function toRomanNumerals(int $number): string
    {
        if($number < 1)
            throw new \InvalidArgumentException('Roman numerals cannot represent numbers less than 1.');

        $numerals = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90,
            'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1,
        ];

        $result = '';

        foreach($numerals as $numeral => $value)
        {
            while($number >= $value)
            {
                $result .= $numeral;
                $number -= $value;
            }
        }

        return $result;
    }

    private const array HumanFemale = [
        'Adela', 'Brenna', 'Cecily', 'Delia', 'Edith', 'Fiona', 'Greta', 'Helena', 'Imogen', 'Joan', 'Katrin',
        'Lena', 'Maren', 'Nell', 'Odette', 'Petra', 'Rosalind', 'Sabine', 'Tamsin', 'Wren',
    ];

    private const array HumanMale = [
        'Aldric', 'Bram', 'Cedric', 'Dorian', 'Edmund', 'Felix', 'Gareth', 'Hugo', 'Ivo', 'Jasper', 'Konrad',
        'Leofric', 'Matthias', 'Niall', 'Osric', 'Piers', 'Roland', 'Silas', 'Tobias', 'Walter',
    ];

    private const array HumanFamilyPrefixes = [
        'Ash', 'Black', 'Bright', 'Brook', 'Fair', 'Green', 'Hart', 'Hawk', 'Iron', 'Oak', 'Red', 'Stone', 'Storm',
    ];

    private const array HumanFamilySuffixes = [
        'bridge', 'field', 'ford', 'hill', 'more', 'ridge', 'shaw', 'stead', 'ton', 'wick', 'wood', 'worth',
    ];

    private const array ElfFemale = [
        'Aelira', 'Caelynn', 'Elowen', 'Faelith', 'Ilaria', 'Lyssandra', 'Meliora', 'Nimweth', 'Sariel', 'Sylwen',
        'Thessaly', 'Vaelora',
    ];

    private const array ElfMale = [
        'Aelar', 'Caladrel', 'Eldrin', 'Faelar', 'Ilyndor', 'Lorithan', 'Mirthal', 'Saevel',
        'Thalion', 'Varis', 'Elwyth', 'Quelian',
    ];

    private const array ElfFamily = [
        'Brightleaf', 'Dawnwhisper', 'Evenstar', 'Leafwhisper', 'Mistwalker',
        'Starbloom', 'Sunmantle', 'Windsong', 'Rimefeather', 'Frostpetal', 'Mothwing', 'Mustardseed',
        'of Duskhollow', 'of Thornveil', 'of Willowmere', 'of Lanternwood', 'of Fernhaven', 'of Glimmerholt',
        'of Thistledown', 'of Moonbrook', 'of Silverwood',
    ];

    private const array Dwarf = [
        'Brakka', 'Bruni', 'Dagmar', 'Durra', 'Grimma', 'Halvor', 'Hilde', 'Kettil', 'Magni', 'Orna',
        'Rurik', 'Sigrun', 'Thora', 'Ulfa', 'Yngvar',
    ];

    private const array GnomeFemale = [
        'Bibbet', 'Clementine', 'Dottie', 'Fizzy', 'Lulabelle', 'Minnow', 'Penny', 'Posy', 'Tilly', 'Trinket',
        'Wimbly', 'Zinnia',
    ];

    private const array GnomeMale = [
        'Barnaby', 'Bodkin', 'Cobble', 'Dabbin', 'Fennimore', 'Gizmo', 'Juniper', 'Nimbus', 'Pockets', 'Quill',
        'Tock', 'Widget',
    ];

    private const array GoblinFemale = [
        'Bix', 'Grelda', 'Kizza', 'Mogga', 'Nettle', 'Pip', 'Rikka', 'Snaggle', 'Tizzy', 'Vexa', 'Zilla',
    ];

    private const array GoblinMale = [
        'Blix', 'Drek', 'Gark', 'Grub', 'Krag', 'Nabbit', 'Rusk', 'Skeeb', 'Snik', 'Vrok', 'Zug',
    ];

    private const array GoblinFamily = [
        'Tears', 'Eats', 'Rends', 'Climbs', 'Crawls', 'Gnashes', 'Runs', 'Sprints', 'Swims', 'Fishes', 'Hunts',
        'Prowls', 'Preys', 'Clings', 'Smashes', 'Kicks', 'Bites', 'Sweats', 'Rolls', 'Burns', 'Drowns', 'Stabs',
        'Swears', 'Yells', 'Whispers', 'Sneaks', 'Screeches', 'Yawns', 'Snores', 'Tickles', 'Crushes',
    ];

    private const array Beastkin = [
        'Ash', 'Bramble', 'Crag', 'Fang', 'Fisher', 'Flint', 'Grey', 'Hollow', 'Kestrel', 'Moss', 'River', 'Rook',
        'Rowan', 'Sable', 'Thistle', 'Tibia', 'Tor',
    ];

    private const array BeastkinFamily = [
        'the Fast', 'the Bright', 'the Fierce', 'the Calm', 'the Steady', 'the Reliable', 'the Tough', 'the Loud',
        'the Crazed', 'the Lost', 'the Restless',
    ];

    private const array EmberkinDragons = [
        'Typhon', 'Kulshedra', 'Saraph', 'Aitvaras', 'Smok', 'Tarasque', 'Zhulong', 'Zmeya', 'Srvara', 'Mušḫuššu',
        'Bašmu', 'Mizuchi', 'Jörmungandr',
    ];

    private const array EmberkinDragonSuffixes = [
        'fire', 'eyes', 'wing', 'claw', 'tooth', 'bite', 'glint',
    ];

    private const array HighFaeFemale = [
        'Aurelia', 'Celandine', 'Eirlys', 'Isolde', 'Mab', 'Morgance', 'Niamh', 'Rhiannon', 'Titania', 'Veridia',
    ];

    private const array HighFaeMale = [
        'Aurelian', 'Cadmus', 'Eamon', 'Finvarra', 'Lorcan', 'Midir', 'Oberon', 'Ronan', 'Tamlin', 'Valerian',
    ];

    private const array HighFaeFamily = [
        'of the Autumn Court', 'of the Gilded Hollow', 'of the Silver Court', 'of the Summer Court',
        'of the Twilight Court', 'of the Winter Court', 'of Thornveil',
    ];

    private const array Nymph = [
        'Alder', 'Ash', 'Aster', 'Bay', 'Brook', 'Clover', 'Dew', 'Fern', 'Hazel', 'Iris', 'Juniper', 'Lake', 'Laurel',
        'Linden', 'Marsh', 'Reed', 'River', 'Sage', 'Sorrel', 'Willow',
    ];

    private const array NymphFamilyAdjectives = [
        'Hidden', 'Misty', 'Moonlit', 'Old', 'Singing', 'Still', 'Sunlit', 'Whispering',
    ];

    private const array NymphFamilyPlaces = [
        'Bank', 'Beach', 'Bluff', 'Bog', 'Dell', 'Falls', 'Fen', 'Grove', 'Hollow', 'Marsh', 'Mudflat', 'Peak', 'Pond',
        'Run', 'Spit', 'Spring', 'Spur', 'Tarn', 'Valley',
    ];
}
