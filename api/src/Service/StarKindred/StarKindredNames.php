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

final class StarKindredNames
{
    // these names were copied from the StarKindred API code on 2022-08-28
    public const array GivenNames = [
        "Adaddu-Shalum", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Aho",
        "Akitu", // Babylonian New Year holiday
        "Albazi",
        "Amar-Sin", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Amata",
        "Amba-El", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Anshar", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Appan-Il",
        "Ardorach",
        "Arwia",
        "Ashlutum",
        "Asmaro",
        "Athra",
        "Balashi",
        "Barsawme",
        "Banunu",
        "Bel", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Beletsunu",
        "Belshazzar", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Belshimikka", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Bel-Shum-Usur", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Berosus",
        "Biridis", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Boram", // copilot-suggested
        "Caifas",
        "Celestia", // copilot-suggested
        "Cerasus-El", // copilot-suggested
        "Curus", // copilot-suggested
        "Dabra", // copilot-suggested
        "Dabra-Ea", // copilot-suggested w/ personal modification
        "Diimeritia",
        "Din-Turul", // I made this up; Din + -Turul suffix seen elsewhere
        "Dinu", // copilot-suggested
        "Doz", // copilot-suggested
        "Dwura",
        "Eannatum",
        "Ebru", // copilot-suggested
        "Ecna", // copilot-suggested
        "Eesho",
        "Edra", // copilot-suggested
        "Efra", // copilot-suggested
        "Ekka", // copilot-suggested
        "Ekran", // copilot-suggested
        "El", // copilot-suggested
        "Emmita",
        "Enheduana",
        "Enn", // copilot-suggested
        "Ettu",
        "Ezra", // copilot-suggested
        "Fara", // copilot-suggested
        "Fenra", // copilot-suggested
        "Fenra-Sin", // previous, plus a suffix I've seen before
        "Fenu", // copilot-suggested
        "Fenya", // copilot-suggested
        "Finna", // copilot-suggested
        "Firas", // copilot-suggested
        "Gabbara",
        "Gadatas",
        "Gemekaa",
        "Gewargis",
        "Goda", // copilot-suggested
        "Gomera", // copilot-suggested
        "Goram", // copilot-suggested
        "Gubaru",
        "Hammurabi",
        "Hann", // copilot-suggested
        "Hanuno",
        "Hara", // copilot-suggested
        "Hara-El", // copilot-suggested
        "Hebron", // copilot-suggested
        "Hemera", // copilot-suggested
        "Hesed", // copilot-suggested
        "Hisa", // copilot-suggested
        "Hod", // copilot-suggested
        "Hormuzd",
        "Hushmend", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Ia",
        "Iatum", // I made this one up
        "Ibbi-Adad",
        "Ibi", // extracted from a textsynth suggestion
        "Ibi-Atsi", // concatenated from two textsynth suggestions
        "Ibne", // copilot-suggested
        "Igal", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Igara", // copilot-suggested
        "Ili", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Ishep-Ana", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Ishme-Dagan", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Ishme-Ea",
        "Isimud", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Issavi",
        "Iwartas", // I made this one up
        "Izla",
        "Jabal", // copilot-suggested (and also biblical :P)
        "Jaram", // copilot-suggested
        "Jasen", // copilot-suggested
        "Jasen-El", // copilot-suggested
        "Jebe", // copilot-suggested
        "Jebre", // copilot-suggested
        "Job", // copilot-suggested (and also biblical :P)
        "Jod", // copilot-suggested
        "Jod-Aho", // copilot-suggested
        "Joshe", // copilot-suggested
        "Kabu", // copilot-suggested
        "Kalumtum",
        "Kan", // copilot-suggested
        "Khannah",
        "Khoshaba",
        "Ki", // copilot-suggested
        "Ko", // copilot-suggested
        "Ku-Aya",
        "Kugalis", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Laliya",
        "Lar-Aho", // copilot-suggested
        "Lilis",
        "Lilorach", // Lilis+ -orach suffix seen elsewhere
        "Lumiya", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Makara", // copilot-suggested
        "Malko",
        "Mazra", // copilot-suggested
        "Mekka", // copilot-suggested
        "Mylitta",
        "Nabu", // copilot-suggested
        "Nabua", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Nahrin",
        "Nahtum", // copilot-suggested
        "Nanshe-Kalum", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Naram-Sin",
        "Nazir",
        "Nebo", // copilot-suggested
        "Nebuchadnezzar",
        "Nektum", // copilot-suggested
        "Nesha", // copilot-suggested
        "Ninkurra", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Ninsun", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Nintu", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Nutesh",
        "Nur-Aya",
        "Odur", // copilot-suggested
        "Omarosa",
        "Oshana",
        "Pahtum", // copilot-suggested
        "Palkha",
        "Pardeeshur", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Puabi",
        "Puabu-Aya", // copilot-suggested
        "Rabbu",
        "Reshlutum", // I totally made this one up
        "Rimush",
        "Rishon", // copilot-suggested
        "Saba", // copilot-suggested
        "Samsi-Addu",
        "Samsu-Iluna", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Sarami-Zu", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Sarsurimutu", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Semiramis",
        "Shala-Kin", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Shalimoon",
        "Shamshi", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Shamsi-Adad", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Shu-Turul",
        "Sybella",
        "Tahira", // copilot-suggested
        "Takhana",
        "Tashlutum",
        "Teba", // copilot-suggested
        "Tebi", // copilot-suggested
        "Toram", // copilot-suggested
        "Tora", // copilot-suggested
        "Tu-Aya", // copilot-suggested
        "Ubbi-Adad", // copilot-suggested
        "Udun", // copilot-suggested
        "Ukubu",
        "Uru-Amurri", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Urukat", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Urukki", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Ushan", // copilot-suggested
        "Ushara", // copilot-suggested
        "Utu-Anu", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Uzuri", // from textsynth.com, with a prompt for Babylonian and Assyrian names
        "Waru", // I made this one up
        "Winhana", // I made this one up,
        "Yahatti-Il",
        "Yahtum", // copilot-suggested
        "Yonita",
        "Younan",
        "Zaia",
        "Zaiamoon", // I just combined Zaia + -moon from Shalimoon
        "Zaidu",
        "Zakiti",
        "Zakkum", // copilot-suggested
        "Zamir", // copilot-suggested
        "Zarai", // copilot-suggested
        "Zarath", // copilot-suggested
        "Zarath-Sin", // copilot-suggested
    ];

    public const array FamilyNamePrefixes = [
        'Star', 'Moon', 'Sun', 'Oak', 'Ash', 'Stone', 'River', 'Frost', 'Ember', 'Thorn', 'Gold', 'Silver',
        'Storm', 'Wind', 'Night', 'Dawn', 'Iron', 'Mist', 'Bright', 'Fern', 'Cloud', 'Glimmer', 'Amber', 'Brook',
    ];

    public const array FamilyNameSuffixes = [
        'song', 'walker', 'heart', 'brook', 'shield', 'whisper', 'fall', 'mantle', 'ward', 'field', 'born',
        'weaver', 'dancer', 'bloom', 'forge', 'light', 'runner', 'keeper', 'wood', 'glen',
    ];
}
