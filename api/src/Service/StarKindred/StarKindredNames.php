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

        $givenNames = match($race)
        {
            StarKindredRaceEnum::Human => $sex === StarKindredSexEnum::Female ? self::HumanFemale : self::HumanMale,
            StarKindredRaceEnum::Elf => $sex === StarKindredSexEnum::Female ? self::ElfFemale : self::ElfMale,
            StarKindredRaceEnum::Gnome => $sex === StarKindredSexEnum::Female ? self::GnomeFemale : self::GnomeMale,
            StarKindredRaceEnum::Goblin => $sex === StarKindredSexEnum::Female ? self::GoblinFemale : self::GoblinMale,
            StarKindredRaceEnum::Emberkin => $sex === StarKindredSexEnum::Female ? self::EmberkinFemale : self::EmberkinMale,
            StarKindredRaceEnum::HighFae => $sex === StarKindredSexEnum::Female ? self::HighFaeFemale : self::HighFaeMale,
            StarKindredRaceEnum::Dwarf => self::Dwarf,
            StarKindredRaceEnum::Beastkin => self::Beastkin,
            StarKindredRaceEnum::Nymph => self::Nymph,
        };

        $familyName = match($race)
        {
            StarKindredRaceEnum::Human =>
                $rng->rngNextFromArray(self::HumanFamilyPrefixes) . $rng->rngNextFromArray(self::HumanFamilySuffixes),
            StarKindredRaceEnum::Elf => $rng->rngNextFromArray(self::ElfFamily),
            StarKindredRaceEnum::Dwarf => $rng->rngNextFromArray(self::DwarfFamily),
            StarKindredRaceEnum::Gnome => $rng->rngNextFromArray(self::GnomeFamily),
            StarKindredRaceEnum::Goblin => $rng->rngNextFromArray(self::GoblinFamily),
            StarKindredRaceEnum::Beastkin => $rng->rngNextFromArray(self::BeastkinFamily),
            StarKindredRaceEnum::Emberkin => $rng->rngNextFromArray(self::EmberkinFamily),
            StarKindredRaceEnum::HighFae => $rng->rngNextFromArray(self::HighFaeFamily),
            StarKindredRaceEnum::Nymph => $rng->rngNextFromArray(self::NymphFamily),
        };

        return $rng->rngNextFromArray($givenNames) . ' ' . $familyName;
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
        'Brightleaf', 'Dawnwhisper', 'Evenstar', 'Leafwhisper', 'Moonbrook', 'Mistvale',
        'Silverfrond', 'Starbloom', 'Sunmantle', 'Windsong',
    ];

    private const array Dwarf = [
        'Brakka', 'Bruni', 'Dagmar', 'Durra', 'Grimma', 'Halvor', 'Hilde', 'Kettil', 'Magni', 'Orna',
        'Rurik', 'Sigrun', 'Thora', 'Ulfa', 'Yngvar',
    ];

    private const array DwarfFamily = [
        'Anvilsong', 'Coalbraid', 'Deepdelver', 'Fireforge', 'Gemcutter', 'Goldvein', 'Granitehold',
        'Lanternmine', 'Orebinder', 'Stonebraid', 'Tunnelwright',
    ];

    private const array GnomeFemale = [
        'Bibbet', 'Clementine', 'Dottie', 'Fizzy', 'Lulabelle', 'Minnow', 'Penny', 'Posy', 'Tilly', 'Trinket',
        'Wimbly', 'Zinnia',
    ];

    private const array GnomeMale = [
        'Barnaby', 'Bodkin', 'Cobble', 'Dabbin', 'Fennimore', 'Gizmo', 'Juniper', 'Nimbus', 'Pockets', 'Quill',
        'Tock', 'Widget',
    ];

    private const array GnomeFamily = [
        'Bramblecog', 'Cogsworth', 'Fiddlefen', 'Gearwhistle', 'Kettlebottom', 'Puddlejump', 'Sprocketwhistle',
        'Tinkerton', 'Wizzlebang',
    ];

    private const array GoblinFemale = [
        'Bix', 'Grelda', 'Kizza', 'Mogga', 'Nettle', 'Pip', 'Rikka', 'Snaggle', 'Tizzy', 'Vexa', 'Zilla',
    ];

    private const array GoblinMale = [
        'Blix', 'Drek', 'Gark', 'Grub', 'Krag', 'Nabbit', 'Rusk', 'Skeeb', 'Snik', 'Vrok', 'Zug',
    ];

    private const array GoblinFamily = [
        'Bottlecap', 'Candlegrab', 'Coppercrook', 'Mudrunner', 'Quickfingers', 'Rustpocket', 'Sparkfizzle',
        'Tinscrap', 'Wickerwhistle',
    ];

    private const array Beastkin = [
        'Ash', 'Bramble', 'Fang', 'Flint', 'Grey', 'Hollow', 'Kestrel', 'Moss', 'Rook', 'Rowan', 'Sable',
        'Thistle', 'Tor',
    ];

    private const array BeastkinFamily = [
        'Brightpelt', 'Farwander', 'Longstride', 'of the Deep Pines', 'of the High Moors', 'of the Red Cliffs',
        'Stormhowl', 'Swiftclaw', 'Thornhide',
    ];

    private const array EmberkinFemale = [
        'Aitne', 'Brisa', 'Cindra', 'Ignia', 'Kaela', 'Pyra', 'Seraphine', 'Solenne', 'Vesta', 'Zhara',
    ];

    private const array EmberkinMale = [
        'Aidan', 'Brand', 'Cyrus', 'Ignar', 'Kaldor', 'Pyros', 'Soren', 'Tavish', 'Vulcan', 'Zephyr',
    ];

    private const array EmberkinFamily = [
        'Ashbright', 'Candleheart', 'Emberly', 'Flameborn', 'Hearthstone', 'Kilnborn', 'Smolderwick', 'Sunforge',
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
        'Alder', 'Brook', 'Clover', 'Dew', 'Fern', 'Hazel', 'Iris', 'Laurel', 'Linden', 'Marsh', 'Reed', 'Sorrel',
        'Willow',
    ];

    private const array NymphFamily = [
        'of the Hidden Spring', 'of the Misty Fen', 'of the Old Grove', 'of the Singing Falls',
        'of the Still Pond', 'of the Whispering Reeds',
    ];
}
