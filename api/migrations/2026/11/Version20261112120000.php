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

final class Version20261112120000 extends AbstractMigration
{
    // fixed so the survey's URL (/survey/{guid}) is known before deploy, for linking from a news post
    private const string SURVEY_GUID = '55abf4d7-140d-48c7-838c-9bdd6f85fb23';

    /** in display order (questions are shown in insertion order) */
    private const array QUESTIONS = [
        'got any quality-of-life wishes, annoyances, or bugs?',
        'think of a moment in PSP that genuinely delighted or surprised you - something you grinned at, or told someone about. what happened?',
        'is there anything you now do on autopilot, skip, or ignore? or something you used to look forward to, but don\'t anymore? what do you think made it "lose its spark"?',
        'what do you wonder about in PSP\'s world? a place, character, creature, mystery... anything you wish you knew more about, or could explore.',
        'what would you like your pets to get up to? write a few example pet activity logs that would make you laugh, gasp, or go "aww!"',
    ];

    public function getDescription(): string
    {
        return 'Survey: "what do you want to see in PSP?" (Nov 13-19, 2026)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            INSERT INTO survey (title, description, start_date, end_date, guid) VALUES (
                :title,
                :description,
                '2026-11-13 00:00:00',
                '2026-11-19 23:59:59',
                :guid
            )
            ON DUPLICATE KEY UPDATE id = id
            SQL,
            [
                'title' => 'what do you want to see in PSP?',
                'description' => 'I\'m looking for what makes PSP _fun_ - the surprises, stories, and "ooh, neat!" moments - so I can make MORE of them!',
                'guid' => self::SURVEY_GUID,
            ]
        );

        foreach(self::QUESTIONS as $question)
        {
            $this->addSql(
                <<<'SQL'
                INSERT INTO survey_question (survey_id, title, type)
                SELECT s.id, :title, 'text'
                FROM survey s
                WHERE s.guid = :guid
                    AND NOT EXISTS (
                        SELECT 1 FROM survey_question q WHERE q.survey_id = s.id AND q.title = :existingTitle
                    )
                SQL,
                [
                    'title' => $question,
                    'guid' => self::SURVEY_GUID,
                    'existingTitle' => $question,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DELETE a FROM survey_question_answer a JOIN survey_question q ON q.id = a.question_id JOIN survey s ON s.id = q.survey_id WHERE s.guid = :guid',
            [ 'guid' => self::SURVEY_GUID ]
        );

        $this->addSql(
            'DELETE q FROM survey_question q JOIN survey s ON s.id = q.survey_id WHERE s.guid = :guid',
            [ 'guid' => self::SURVEY_GUID ]
        );

        $this->addSql('DELETE FROM survey WHERE guid = :guid', [ 'guid' => self::SURVEY_GUID ]);
    }
}
