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

namespace App\Service;

use App\Entity\Beehive;
use App\Entity\Inventory;
use App\Entity\User;
use App\Enum\BeehiveSpaceTypeEnum;
use App\Enum\HolidayEnum;
use App\Enum\LocationEnum;
use App\Exceptions\PSPNotUnlockedException;
use App\Functions\InventoryHelpers;
use App\Model\BeehiveSpace;
use App\Model\ExtraItemTiers;
use Doctrine\ORM\EntityManagerInterface;

class BeehiveService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly IRandom $rng,
        private readonly Clock $clock,
    )
    {
    }

    public function createBeehive(User $user): void
    {
        if($user->getBeehive())
            throw new \Exception('Player #' . $user->getId() . ' already has a beehive!');

        $beehive = new Beehive(
            user: $user,
            name: $this->rng->rngNextFromArray(self::QueenNames),
            spaces: $this->rollSpaces(),
        );

        $this->em->persist($beehive);

        $user->setBeehive($beehive);
    }

    /**
     * What every beehive endpoint returns: the hive, plus whether the player can afford to re-roll its spaces.
     *
     * @return array{beehive: Beehive, canReroll: bool}
     */
    public function getResponseData(User $user): array
    {
        return [
            'beehive' => $user->getBeehive() ?? throw new PSPNotUnlockedException('Beehive'),
            'canReroll' => InventoryHelpers::findOneToConsume($this->em, $user, 'Gold Compass') !== null,
        ];
    }

    /**
     * Re-rolls every space's terrain type; harvested flags are untouched.
     */
    public function rerollSpaceTypes(Beehive $beehive): void
    {
        $beehive->setSpaces(array_map(
            fn(BeehiveSpace $space) => new BeehiveSpace(BeehiveSpaceTypeEnum::roll($this->rng), $space->harvested),
            $beehive->getSpaces()
        ));
    }

    /**
     * 19 fresh, unharvested spaces, each an independent weighted draw of terrain type.
     *
     * @return BeehiveSpace[]
     */
    public function rollSpaces(): array
    {
        $spaces = [];

        for($i = 0; $i < BeehiveSpace::Count; $i++)
            $spaces[] = new BeehiveSpace(BeehiveSpaceTypeEnum::roll($this->rng));

        return $spaces;
    }

    /**
     * @return string[] item names; duplicates are extra weight
     */
    public function getGoodsForTerrain(BeehiveSpaceTypeEnum $type): array
    {
        return match($type)
        {
            BeehiveSpaceTypeEnum::Jungle => $this->getJungleGoods(),
            BeehiveSpaceTypeEnum::Beach => $this->getBeachGoods(),
            BeehiveSpaceTypeEnum::Grassy => $this->getGrassyGoods(),
            BeehiveSpaceTypeEnum::Rocky => $this->getRockyGoods(),
        };
    }

    /**
     * @return string[]
     */
    private function getJungleGoods(): array
    {
        $goods = [ 'Sugar', 'Glue', 'Crooked Stick', 'Honeycomb', 'Antenna', 'Cacao Fruit', 'Chanterelle' ];

        if(WeatherService::getWeather($this->clock->now)->isHoliday(HolidayEnum::ApricotFestival))
            $goods[] = 'Apricot';

        return $goods;
    }

    /**
     * @return string[]
     */
    private function getBeachGoods(): array
    {
        return [ 'Sand Dollar', 'Feathers', 'Seaweed', 'Crooked Stick', 'Scales' ];
    }

    /**
     * @return string[]
     */
    private function getGrassyGoods(): array
    {
        $goods = [ 'Sugar', 'Sweet Beet', 'Honeycomb', 'Fluff', 'Moth', 'Rosemary' ];

        if(WeatherService::getWeather($this->clock->now)->isHoliday(HolidayEnum::SaintPatricks))
            $goods[] = '1-leaf Clover';

        return $goods;
    }

    /**
     * @return string[]
     */
    private function getRockyGoods(): array
    {
        return [ 'Silica Grounds', 'Crooked Stick', 'Rock Candy' ];
    }

    /**
     * What a helper pet brings back when it goes gathering on a space of this terrain. Naner (Jungle only) awards the
     * BeeNana badge.
     */
    public function getHelperGatherTiers(BeehiveSpaceTypeEnum $type): ExtraItemTiers
    {
        return match($type)
        {
            BeehiveSpaceTypeEnum::Jungle => $this->getHelperJungleGatherTiers(),
            BeehiveSpaceTypeEnum::Beach => $this->getHelperBeachGatherTiers(),
            BeehiveSpaceTypeEnum::Grassy => $this->getHelperGrassyGatherTiers(),
            BeehiveSpaceTypeEnum::Rocky => $this->getHelperRockyGatherTiers(),
        };
    }

    /**
     * What a helper pet brings back when it goes hunting on a space of this terrain.
     */
    public function getHelperHuntTiers(BeehiveSpaceTypeEnum $type): ExtraItemTiers
    {
        return match($type)
        {
            BeehiveSpaceTypeEnum::Jungle => $this->getHelperJungleHuntTiers(),
            BeehiveSpaceTypeEnum::Beach => $this->getHelperBeachHuntTiers(),
            BeehiveSpaceTypeEnum::Grassy => $this->getHelperGrassyHuntTiers(),
            BeehiveSpaceTypeEnum::Rocky => $this->getHelperRockyHuntTiers(),
        };
    }

    private function getHelperJungleGatherTiers(): ExtraItemTiers
    {
        return new ExtraItemTiers(
            base: [ 'Naner', 'Orange' ],
            medium: [ 'Paper', 'Cacao Fruit', 'Coriander Flower', 'Spicy Peps' ],
            high: [ 'Apricot', 'Chanterelle', 'Mango', 'Pineapple' ],
            superHigh: [ 'Goodberries', 'Honeycomb' ],
        );
    }

    private function getHelperJungleHuntTiers(): ExtraItemTiers
    {
        return new ExtraItemTiers(
            base: [ 'Feathers', 'Egg', 'Fluff' ],
            medium: [ 'Talon', 'Toad Legs' ],
            high: [ 'Jar of Fireflies', 'Scales' ],
            superHigh: [ 'Quintessence', 'Dark Scales' ],
        );
    }

    private function getHelperBeachGatherTiers(): ExtraItemTiers
    {
        return new ExtraItemTiers(
            base: [ 'Silica Grounds', 'Crooked Stick', 'Seaweed', 'Paper Boat' ],
            medium: [ 'Feathers', 'Sand Dollar', 'Coconut' ],
            high: [ 'Plastic Bottle', 'Glass', 'Yeast' ],
            superHigh: [ 'Silver Ore', 'Gold Ore', 'Mermaid Egg' ],
        );
    }

    private function getHelperBeachHuntTiers(): ExtraItemTiers
    {
        return new ExtraItemTiers(
            base: [ 'Scales', 'Silica Grounds', 'Fish' ],
            medium: [ 'Talon', 'Feathers', 'Egg' ],
            high: [ 'Tentacle', 'Jellyfish Jelly' ],
            superHigh: [ 'Quintessence', 'Little Strongbox' ],
        );
    }

    private function getHelperGrassyGatherTiers(): ExtraItemTiers
    {
        return new ExtraItemTiers(
            base: [ 'Tea Leaves', 'Agrimony', 'Blueberries', 'Blackberries', 'Crooked Stick', 'Red' ],
            medium: [ 'Onion', 'Tomato', 'Sweet Beet', 'Beans', 'Celery' ],
            high: [ 'Rosemary', 'Melowatern', 'Honeydont', 'Grass Jelly' ],
            superHigh: [ 'Goodberries', 'Honeycomb' ],
        );
    }

    private function getHelperGrassyHuntTiers(): ExtraItemTiers
    {
        return new ExtraItemTiers(
            base: [ 'Feathers', 'Egg', 'Snail Shell', 'Fluff' ],
            medium: [ 'Creamy Milk', 'Toad Legs' ],
            high: [ 'Jar of Fireflies', 'Moth' ],
            superHigh: [ 'Quintessence' ],
        );
    }

    private function getHelperRockyGatherTiers(): ExtraItemTiers
    {
        return new ExtraItemTiers(
            base: [ 'Grandparoot', 'Silica Grounds', 'Crooked Stick', 'Toadstool', 'Tea Leaves', 'Cobweb' ],
            medium: [ 'Iron Ore', 'Rock Candy', 'Limestone', 'Blueberries' ],
            high: [ 'Gypsum', 'Silver Ore', 'Rock' ],
            superHigh: [ 'Iris', 'Gold Ore', 'Liquid-hot Magma', 'Everice', 'Blackonite' ],
        );
    }

    private function getHelperRockyHuntTiers(): ExtraItemTiers
    {
        return new ExtraItemTiers(
            base: [ 'Scales', 'Egg', 'Fluff', 'Feathers' ],
            medium: [ 'Talon', 'Creamy Milk', 'Toad Legs' ],
            high: [ 'Tiny Scroll of Resources', 'Gold Bar', 'Silver Bar' ],
            superHigh: [ 'Lightning in a Bottle', 'Quintessence' ],
        );
    }

    /**
     * @return Inventory[]
     */
    public function findFlowers(User $user, ?array $inventoryIds = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb
            ->select('i')->from(Inventory::class, 'i')
            ->andWhere('i.owner=:owner')
            ->andWhere('i.location IN (:home)')
            ->leftJoin('i.item', 'item')
            ->leftJoin('item.food', 'food')
            ->andWhere($qb->expr()->orX(
                'food.floral>0',
                'food.fruity>0',
                'food.planty>0'
            ))
            ->addOrderBy('item.name', 'ASC')
            ->setParameter('owner', $user->getId())
            ->setParameter('home', LocationEnum::Home)
        ;

        if($inventoryIds)
        {
            $qb
                ->andWhere('i.id IN (:inventoryIds)')
                ->setParameter('inventoryIds', $inventoryIds)
            ;
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    public static function computeFlowerPower(Inventory $i): int
    {
        return
            $i->getItem()->getFood()->getFloral() * 25 +
            $i->getItem()->getFood()->getPlanty() * 10 +
            $i->getItem()->getFood()->getFruity() * 10 +
            $i->getItem()->getFood()->getLove() * 5 +
            (
                $i->getSpice()?->getEffects() === null ? 0 : (
                    $i->getSpice()->getEffects()->getFloral() * 25 +
                    $i->getSpice()->getEffects()->getPlanty() * 10 +
                    $i->getSpice()->getEffects()->getFruity() * 10 +
                    $i->getSpice()->getEffects()->getLove() * 5
                )
            )
        ;
    }

    public static function mapFlowerPowerToRating(int $flowerPower): string
    {
        return match(true) {
            $flowerPower >= 135 => 'Wow!',
            $flowerPower >= 120 => 'S++',
            $flowerPower >= 105 => 'S+',
            $flowerPower >= 90 => 'S',
            $flowerPower >= 75 => 'A+',
            $flowerPower >= 60 => 'A',
            $flowerPower >= 45 => 'B',
            $flowerPower >= 30 => 'C',
            $flowerPower >= 15 => 'D',
            default => 'F',
        };
    }

    // a couple of these are princesses; sorry about the non-semantic variable name:
    public const array QueenNames = [
        'Acropolitissa', 'Adelaide', 'Adélina', 'Adosinda', 'Ædgyth', 'Ælfthryth', 'Aénor', 'Afzan', 'Agafiya',
        'Allogia', 'Amalia', 'Anglesia', 'Andregoto', 'Anka', 'Ansi', 'Aphainuchit', 'Aregund', 'Aremburga',
        'Argentaela', 'Argyra', 'Ashina', 'Aspasia', 'Astrid', 'Aud', 'Austerchild',

        'Babukhan', 'Bainun', 'Bao Si', 'Barbara', 'Bartolomea', 'Bé Fáil', 'Berengaria', 'Bertechildis', 'Bian',
        'Bilichild', 'Biltrude', 'Blanche', 'Blotstulka', 'Bonne', 'Božena', 'Brynhildr',

        'Charito', 'Cheng\'ai', 'Chengmu', 'Chen Jiao', 'Chittrawadi', 'Chlothsind', 'Cixilo', 'Claudia', 'Claudine',
        'Clementia', 'Clotilde', 'Cunigunde', 'Cymburgis',

        'Danashiri', 'Darejan', 'Darinka', 'Dauphine', 'Debsirindra', 'Doubravka', 'Draginja', 'Drahomíra',
        'Dubchoblaig', 'Dunlaith',

        'Eilika', 'Eindama', 'Eishō', 'Eithne', 'Ekaterina', 'Elisenda', 'Elvira', 'Emilienne', 'Ermengarde',
        'Ermesinde', 'Erzhu', 'Eschive', 'Esclaramunda', 'Ethelinde', 'Eudokia', 'Euphrosyne', 'Eupraxia', 'Eustachie',

        'Faileube', 'Fara', 'Fastrada', 'Fauziah', 'Findelb', 'Folchiade', 'Françoise', 'Froiliuba', 'Frozza',

        'Galswintha', 'Garsinda', 'Genmei', 'Gerperga', 'Ghadana', 'Gisela', 'Goiswintha', 'Gomentrude', 'Gongsi',
        'Gormflaith', 'Go-Sakuramachi', 'Grimhild', 'Grzymislawa', 'Guanglie', 'Gulkhana', 'Gunhilda', 'Gyrid',

        'Haminah', 'Hanthawaddy', 'Hedwig', 'Hellicha', 'Helvis', 'Hildegard', 'Hiltrud', 'Hortense', 'Huansi', 'Huyan',

        'Ikbal', 'Imma', 'Immilla', 'Iñiguez', 'Ingeborg', 'Inoe', 'Isabelle',

        'Jadwiga', 'Jemaah', 'Jiāng', 'Jing', 'Jiajak', 'Jigda-Khatun', 'Jimena', 'Jingū', 'Jitō', 'Joan', 'Juana',
        'Junshi', 'Jutta',

        'Kamāmalu', 'Kantakouzena', 'Kapi\'olani', 'Katranide', 'Ketevan', 'Kezuhun', 'Khongirad', 'Khorashan',
        'Kira Maria', 'Kōgyoku', 'Konchaka-Agafia', 'Kōken', 'Komnina', 'Kujava', 'Kunigunda', 'Kurshiah',

        'Li', 'Lingsi', 'Liutperga', 'Ljubica', 'Lotitia', 'Ludmilla', 'Lutgard', 'Lü Zhi',

        'Maddalena', 'Maedhbh', 'Máel Muire', 'Manisanda', 'Marcatrude', 'Marguerite', 'Marmohec', 'Marozia',
        'Mathesuentha', 'Mechtild', 'Meishō', 'Melisende', 'Messalina', 'Milica', 'Mingdao', 'Mingyuan', 'Minkhaung',
        'Min Pyan', 'Monomachina', 'Morphia', 'Mù', 'Muirenn', 'Murong', 'Musbah', 'Muzhang', 'Myauk',

        'Najihah', 'Nambui', 'Nanmadaw', 'Nanthild', 'Nestan-Darejan',

        'Oljath', 'Oreguen', 'Órlaith', 'Ostrogotha', 'Ota', 'Otehime',

        'Phannarai', 'Phokaina', 'Phuntsho', 'Piroska', 'Plaisance', 'Polyxena', 'Poppaea', 'Prathuma', 'Prisca',
        'Pwadawgyi',

        'Qiang',

        'Rachanurak', 'Radnashiri', 'Regelinda', 'Regintrude', 'Renata', 'Richardis', 'Richenza', 'Rogneda', 'Roscille',
        'Rusudan', 'Ryksa',

        'Sagdukht', 'Sallustia', 'Sālote', 'Sancha', 'Sangwan', 'Sawatdi', 'Saw Sala', 'Saw Thanda', 'Saw Yin', 'Seishi',
        'Shi', 'Shin Saw', 'Shunlie', 'Sibylla', 'Sigrid', 'Sineenat', 'Siti Aishah', 'Smiltsena', 'Soe Min', 'Song',
        'Statilia', 'Suavegotha', 'Suiko', 'Supayagyi', 'Suriyawongsa', 'Synadena', 'Swanhilde', 'Świętosława',

        'Tai Si', 'Taiwu', 'Taung', 'Teri\'itaria', 'Tetua', 'Thanbula', 'Theodelinda', 'Thermantia', 'Thiri Thuriya',
        'Thonlula', 'Thukomma', 'Thupaba', 'Thyra', 'Tōchi', 'Tove', 'Tsundue',

        'Ulanara', 'Ulvhild', 'Urraca', 'Usaukpan',

        'Vénérande', 'Violant', 'Viridis', 'Vitača', 'Voisava',

        'Wadanthika', 'Waldrada', 'Weluwaddy', 'Wilhelmina', 'Wisigard', 'Wisutkasat', 'Wizala', 'Wu', 'Wulfefundis',
        'Wulfhilde', 'Wuwei', 'Wuxiao', 'Wyszesława',

        'Xia', 'Xianlie', 'Xianmu', 'Xianwen', 'Xiaocheng', 'Xiaojing', 'Xin', 'Xu', 'Xunying',

        'Yadana', 'Yadanabon', 'Yaroslavna', 'Yaza Dewi', 'Yazakumari', 'Yin', 'Yixian', 'Yolanda', 'Yuanfei', 'Yujiulü',
        'Yukiko',

        'Zanariah', 'Zbyslava', 'Zhang', 'Zhangde', 'Zhaoxin', 'Zhejue',
    ];
}
