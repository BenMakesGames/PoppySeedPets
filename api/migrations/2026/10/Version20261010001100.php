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

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261010001100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Telephone: dial any number (1-800-PIZZA, 1-800-CURRIES, 1-800-ONIGIRI)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE phone_number (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(20) NOT NULL, label VARCHAR(40) NOT NULL, cost INT NOT NULL, message LONGTEXT NOT NULL, loot JSON NOT NULL, UNIQUE INDEX number_idx (number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user_phone_number (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, phone_number_id INT NOT NULL, INDEX IDX_EBCDC059A76ED395 (user_id), INDEX IDX_EBCDC05939DFD528 (phone_number_id), UNIQUE INDEX user_id_phone_number_id_idx (user_id, phone_number_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user_phone_number ADD CONSTRAINT FK_EBCDC059A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_phone_number ADD CONSTRAINT FK_EBCDC05939DFD528 FOREIGN KEY (phone_number_id) REFERENCES phone_number (id)');

        $this->addSql(<<<'SQL'
            INSERT INTO phone_number (id, number, label, cost, message, loot) VALUES (
                1,
                '180074992',
                '1-800-PIZZA',
                45,
                'You ordered some pizza over the telephone. It\'s on its way-- no, wait, it\'s already here! (So speedy and so smart!)',
                '[{"pick": 3, "from": ["Slice of Cheese Pizza", "Slice of Chicken BBQ Pizza", "Slice of Mixed Mushroom Pizza", "Slice of Pineapple Pizza", "Slice of Spicy Calamari Pizza"]}]'
            )
            ON DUPLICATE KEY UPDATE id = id
            SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO phone_number (id, number, label, cost, message, loot) VALUES (
                2,
                '18002877437',
                '1-800-CURRIES',
                32,
                'Your hot, fresh curry is on its way-- no, wait, it\'s already here! (So speedy and so smart!)',
                '[{"pick": 1, "from": ["Papadum"]}, {"pick": 1, "from": ["Aloo Gobi", "Mushroom Broccoli Krahi"]}, {"pick": 1, "from": ["Blackberry Lassi", "Blueberry Lassi", "Gulab Jamun", "Mango Lassi", "Mango Pudding", "Rice Puddin\'"]}]'
            )
            ON DUPLICATE KEY UPDATE id = id
            SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO phone_number (id, number, label, cost, message, loot) VALUES (
                3,
                '18006644474',
                '1-800-ONIGIRI',
                28,
                'You ordered some seaweed-wrapped rice, and more, and omg: it\'s already here! (So speedy and so smart!)',
                '[{"pick": 2, "from": ["Fermented Fish Onigiri", "Fish Onigiri", "Mini Melowatern Onigiri", "Onigiri", "Tentacle Onigiri", "Yaki Onigiri"]}, {"pick": 1, "from": ["Nigiri", "Simple Sushi", "Tamago Sushi", "Tomato \\"Sushi\\"", "Miso Soup", "Pan-fried Tofu", "Takoyaki", "Shoyu Tamago", "Soy-ginger Fish", "TKG"]}]'
            )
            ON DUPLICATE KEY UPDATE id = id
            SQL);

        $this->addSql(<<<'SQL'
            UPDATE item SET use_actions = '[["Make a Call", "telephone", "page"]]' WHERE id = 1249
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_phone_number DROP FOREIGN KEY FK_EBCDC059A76ED395');
        $this->addSql('ALTER TABLE user_phone_number DROP FOREIGN KEY FK_EBCDC05939DFD528');
        $this->addSql('DROP TABLE phone_number');
        $this->addSql('DROP TABLE user_phone_number');
    }
}
