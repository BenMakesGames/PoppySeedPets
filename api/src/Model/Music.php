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

namespace App\Model;

class Music
{
    /**
     * @var string[]
     */
    const array Lyrics = [
        // U Can't Touch This
        'Go with the flow; it is said: that if you can\'t groove to this then you are probably dead!',

        // Katamari Damacy
        'Na naaaaaaa... na-na-na-na-na, na naaaaa... na-na na na-na naaaaaaa...',
        'Every day, every night, let\'s do the royal rainbow - yes! The cosmic message of looove!',
        'I love you, iki ga tomaru kurai sou... I miss you, tsuyoku dakishimete itsumademo...',
        'Your sound, your wave, your thought, your spark, your touch, your wall, your sense...',

        // Sesame Street
        'Manha-manha - do dooooo d-do-do.... manha-manha - do do-do do...',
        '1! 2! 3! 4! 1-2-3-4! 1-2-3-4! 1, 2, I love counting, whatever the amount! Haha!',

        // Tom's Diner
        'There\'s a woman, on the outside, looking inside; does she see me? No she does not really see me, \'cause she sees her own reflection...',

        // It's the End of the World As We Know It
        'Speed it up a notch, speed, grunt, no strength - the ladder starts to clatter with a fear of height-- down-- height!',

        // Things That Don't Exist
        'Perfect circles, three-sided squares, and two nested pairs with just one number; Issac Newton\'s fourth law of motion; rivers and oceans on the mooooon...',

        // Nano Nano
        'Nano naaanooo, naaaanoooo - what a wonderful surpriiise! The ordinary is extraordinary when you make it nano siiize!',

        // Homestar Runner
        'I don\'t know... who it is... but it probably is Fhqwhgaaaaads. I asked my friend Joe; I asked my friend Jake: they said it was Fhqwhgads!',

        // Portal
        'This was a triumph. I\'m making a note here: huge success. It\'s haarrd to ooooverstaaaate my saaaatisfaction...',

        // B-52s
        'Everybody\'s movin\'; everybody\'s groovin\', baby! Folks linin\' up outside just to get down!',

        // Weird Al
        'Eeaaat it! Eeaaat it! Open up your mouth and feed it! Have a banana-- have a whole bunch! It doesn\'t matter what you had for lunch! Just eat it! (Eat it, eat it, eat it...)',
        'I think I\'m a clone now... there\'s always two of me just a-hangin\' arou-ou-ound...',

        // Sonic (the Hedgehog)
        'Rollin\' around at the speed of sou-ound... got places to go - gotta\' follow my rain-bow!',
        'Moooonliiight all around. Shaaadooows on the ground. Will there ever be an eeend? Forever running from this lunacyyy...',
        'We can show the world what we can do. You are next to me, and I\'m next to you. Pushing on through until the battle\'s wooooon...',

        // Beck, Timebomb
        'We\'re going sideways! Highways! Riding on an elevator - cold just like an alligator - now my baby\'s out of date!',

        // Benassi Bros, Light
        'Love is all and love is round. Like a precious flower. I\'m a spirit floating down. In the universe.',

        // Katzenjammer, Demon Kitty Rag
        'I\'ll be your nightmare mirror! Dubba-do-what you did do-do to meeeee - ahahahaha! I\'ll be your nightmare mirorrrrr-oh-oh-or... colder than a steel blade, yeah...',

        // Pogo, Data & Picard, and Boy & Bear
        'Incredibly unbroken sentence; moving from topic to topic; no one had a chance to interrupt. It was quite hypnotic.',
        'Dum-dee-dum deeee dum-dum. Dum-dee-dum deeee dum-dum. When I\'m with you, I\'m with you.',

        // Pokémon, anime american opening song
        'I wanna be the very best, like no one ever was... to catch them is my real test; to train them is my cause!',

        // Dragostea Din Tei
        'Vrei să pleci dar nu mă, nu mă iei! Nu mă, nu mă iei! Nu mă, nu mă, nu mă iei!',

        // The Hamsterdance
        'All right, everybody, now here we go! It\'s a brand new version of the do-see-do!',

        // Skrillex, Seventeen
        'I want to write you a nooooote... that you\'ll never read. My friends keep telling meeeee... I shouldn\'t beg and plead...',

        // Zedd
        'Let\'s get looooo-o-o-o-o-ooost... at sea-ea-ea-ea, ea-ea-ea, ea! Where theeyyy will neeever find us! Got staarrs at niiight to guide us!',
        'Fadiiing. So slooowww. Black hooole - I feel it slipping awaaay. Here we\'re allll we\'ve gooot... if you\'re looost, I\'m diving in after yo-ooou...',

        // System of a Down
        'Sooooomewhere! Between the saaacred silence and sleep! Disorder! Disorder! Disooooor-o-o-orrrrr-derrrrrrrrr!',

        // Infected Mushroom
        'At night. I sit by your side. Waiting for you... to give me a sign. I\'m counting the daaaay-ays... and have nothing to say-ay...',
        'When I\'m hi-i-iding... amid theeee throng... but nowhere is safe... from the ancient sooo-ooo-ooooong!',
        'I want to move, to lose, to take the grooves... and to give it all back...',

        // Mario Kart Lovesong
        'No one will touch us if we pick up a star. And if you spin out, you can ride in my car. When we slide together we generate sparks in our wheels, and our hearts...',

        // Caravan Palace
        'Act like a brother (every day is a miracle). Help one another (connect back with the people). Give it to your lover (and all the people you miss). Let\'s go, already...',
        'Every night... sym-pho-ny... oh, rock it for me... then beat it out in a minor key...',
        'You bother me (woop!) You\'re ahead of me (woop!) How can you be so placid, when you disagree? You\'re fault-free. Steppin\' up for me. I\'m the hero\'s sidekick (oui, c\'est la vie).',

        // Klingon Victory Song
        'yIja\'Qo\', Bagh Da tuH mogh, ChojaH Duh rHo... yIjah, Qey\' \'oH! yIjah, Qey\' \'oH! yIjah, Qey\' \'oH!',

        // Fresh Prince of Bel-Air
        'Chillin\' out, maxin\', relaxin\', all cool... and all shootin\' some b-ball outside of the school...',

        // Mitternaaaaacht!
        'Loca in ferna in nocte... loca in ferna in nocte... animae in nebula... Mitternaaaaacht!',

        // Aqua, Doctor Jones
        'Doctor Jones, Jones, calling Doctor Jones... Doctor Jones, Doctor Jones, get up now (wake up now)...',

        // They Might Be Giants
        'I found my miiiiind... on the ground belooowww... I was looking dooowwwn... it was looking baaaaack... I was in the sky, all dressed in black!',
        'Placental, the sister of her brother ma-arsupiaaal... their cousin called Monotreme; dead uncle alotheeerrriaaan...',
        'They revaaamped the airpoorrt completely, now it looks just like a nightcluuub... everyone\'s exciteeed and confuu-uused...',
        'I heard they have a space program - when they sing you can\'t hear, there\'s no air. Sometiiimes I think I kind of like that and other times I think I\'m already there...',

        // Alan Parsons Project, Eye in the Sky
        'I am the eye in the sky... looking at yoo-oou... I can read your mind. I am the maker of rules, dealing with foo-ools... I can cheat you blind.',

        // Alanis Morissette
        'And I\'m he-ere! To remind you! Of the mess you left when you went away!',

        // The Cranberries, Dreams
        'Ohhh, myyy, liiife... is changing every daaay... in every possible way-ay...',

        // Walk Like an Egyptian
        'Slide your feet up street, bend your back, shift your arm, then you pull it back. Life is hard, you know - oh-way-oh! - so strike a pose on a Cadillac...',

        // The Scatman
        'While you\'re still sleeping, the saints are still weeping, \'cause things you called dead haven\'t yet had the chance to be be born...',

        // Gangum Style
        'Heeeeeyyyyy, sexy la-dy! 오, 오 오 오! 오빤 강남스타일!',

        // Courtney Barnett
        'My hands are shaaaky; my knees are weeaak; I can\'t seem to stand on my own two feeet...',

        // Chumbawumba
        'I get knocked down! But I get up again! You\'re never gonna keep me down!',
        'She\'s a clueless social climber; likes the wrong side of the bed. She\'s a pick-me-up, and she\'s a drink-to-me in the company of friends...',

        // Monty Python
        'For life is quite absurd, and death\'s the final word; you must always face the curtain with a booww...',
        'Just remember that you\'re standing... on a planet... that\'s evolving... and revolving at nine-hundred miles an hour...',

        // Tim Minchin
        'I know in the past my outlook has been limited... I couldn\'t see examples of where life had been definitive...',

        // Feel Good Inc., by Gorillaz
        'You got a new horizon, it\'s ephemeral style... a melancholy town where we never smile...',

        // Fatboy Slim, Weapon of Choice
        'Halfway between the gutter, and, the stars. Yeah. Halfway between the gutter, and, the stars...',

        // Flobots, Handlebars
        'Me and my friend saw a platypus; me and my friend made a comic book. And guess how long it took? I can do anything that I want, \'cause, look...',

        // Rolling Stones
        'The floods is threat\'ning my very life today. Gimme-- gimme shelter, or I\'m gonna fade away...',

        // Bad Reputation
        'I don\'t give a damn \'bout my reputation! ... I\'ve never been afraid of any deviation! ... And I don\'t really care if you think I\'m strange; I ain\'t gonna change!',

        // I Monster, Daydream in Blue
        'Daydream. I dream of you amid the flowers. For a couple of hours. Such a beautiful daaaayyyy...',

        // Eiffel 64, Blue
        'I\'m blue, da-ba-dee, da-ba-die! A da-ba-dee... da-ba-die, a da-ba-dee, da-ba-die...',

        // Mary Poppins
        'Nowhere is there a more happier crew... than thems that sing chim chim cher-ee, chim cher-oo...',

        // Sayonara Wild Hearts
        'And all the things I need to say... and all the big words seem to stay... on the insiiide... on the insi-i-iiide!',
        'I don\'t know... where to start... in the search of the beat of your heart... a sooouuund in the deeeaaafening silence...',

        // Studio Killers, Friday Night Gurus
        'Theeyy\'ve got a soouund... serious-ly obese in the base frequencies; peerrfectly round, like spiiirals iiin their DNAaa...',

        // Portugal, Feel It Still
        'Ooh-woo, I\'m a rebel just for kicks, now... I been feeling it since 1966, now...',

        // Dirty Vegas, Days Go By
        'You. You are still a whisper on my lips... a feeling at my fingertips... that\'s pulling at my skin...',

        // Todrick Hall, Nails, Hair, Hips, Heels
        'Girl, I don\'t dance, I work; I don\'t play, I slay; I don\'t walk, I strut, strut, strut, and then sashay...',

        // from Jet Set Radio
        'I\'m trying to get to-- I\'m trying to get to sleep! Playing with that-- playing with that-- I\'m, I\'m, I\'m... aaaahhh!',
        'The most important part of dance is music. So now let us listen to the music, and identify the beats. One... ... two... ... three... ... but that was too soft.',

        // Faster than Light (from Stellaris)
        'Stars in the skyyy... Floating in darrrkneeess.... Soooon... I will flyyy... faaasterrr thaaan liiiiight...',

        // As The Rush Comes
        'Traveling somewherrre; could be aaanywhere. There\'s a coldness in the air... but I don\'t ca-arrre...',

        // Pendulum
        'It\'s 9,000 miii-iles back to yooo-ooou. (Nooo-ooo...) I still feeeel like hooooome is in... yoouur aarrms...',

        // Venus Hum, Hummingbirds
        'Some of my faaa-aaavourite colours in the world... beat against my eyelids with the blues of green hummingbirds...',

        // Freezepop
        'The music is loud. The night is so young. All over the world. We wanna have fun.',
        'You tell just half the truth, you\'re pulling strings and pushing buttons. Wheels are turning in your head; I know that you are up to something...',

        // Phoenix
        'No, I gotta be someone else. These days it comes, it comes, it comes, it comes, it comes and goes...',
        'Woo-ha! Singing hallelu-jah! Run for your life, cover your eyes, alpha zulu - hey, hey!',

        // I:Scintilla, The Bells
        'The florrrescent lightiiing does nothiiing to keep you from hiiiiiidiiiiiiiiiiiiiii i-i-iyeaaaaaaaaahah-ah, ah-aaahhh...!',

        // Group Love, Tongue Tied
        'Don\'t take me tongue tied... don\'t wave no goodbye... dooooooooooon\'t BREAK! (One, two, three, four...)',

        // Boom Boom Satellites, Shut Up and Explode
        'Running free, running free, driving me insane... shut it down, shut it down, it\'s about to explode... run away, run away, run away, run away, run away, run away, run away, run away, run away, run away, run away, run away, run away, run away, run away, run away...',

        // Fall Out Boy, I Don't Care
        'I! Don\'t! Care what you think, as long as it\'s abouuut me; the best of us can find happiness in mi-i-i-isery...',

        // Lush, De-Luxe
        'Some say I\'m vaaauuuge... and I\'d easily faaade... foolish paraaade ooof faaantaaasyyyy...',

        // The Seatbelts
        'In the dream that pulls you along, won\'t go carry a jelly bean. In your dream they\'re never on top. Let\'s get funky, Pumpkinheeeeeaaaaad! Yo, Pumpkinheeeeeaaaaad! Yo, Pumpkinheeeeeaaaaad! Yo, Pumpkinheeeeeaaaaad!',
        'La la la la la la la laaa laaaaa... la la la la la la la la laaaaa... la la la la laaa laaa! La la la la laaa laaa la la! We are the doggy-doggy dogs! We are the doggy-doggy dogs!',
        'I like you like he like she likes chick-en booone... everyone looks like a crazy chick-en booone...',

        // Don't Bother None, by Mai Yamane
        'Reading my paper in Ray\'s cafeee... the old guy next to me is loud as dayyy... rimble and ramble, while eatin\' his piiie... he dropped his wallet - now it\'s miii-ine...',

        // Birthday Massacre
        'I know we\'re just pretending; there\'s no window for mistakes. I know you see right through me; there\'s no promise left to brea-eak.',
        'Nails clawing splinters from the ceiling and floor... shrieking like the witches \'til his stitches are sore...',

        // Qemists, S.W.A.G.
        'Don\'t hide the feeling deep inside - take it in your stride, at least you know you tried. Jump back and get back a little more.',

        // Linkin Park:
        'The colors confliiiiiiiiiicteeeed, as the flames climbed into the clouds...',
        'I don\'t know what\'s worth fighting for, or why I have to screeaam. But now I have some clarity, to show you what I meeaan...',

        // Jayme Gutierrez, Like a Tiger
        'And now it sounds like I\'m straining my voice, but I\'m only really singing very soooftly. I sing the melody right over the beat to distract from any monotonicality-y.',

        // Junkie XL, Beauty Never Fades
        'Each step I take the shadows grow looongerrr... padded footfalls in the dark I waaanderrr...',

        // GitHub CoPilot??
        'I\'m not afraid of the future; I\'m not afraid of the past. I\'m not afraid of letting go, and letting my illusions last.',

        // Imogen Heap
        'Ransom notes keep falling out your mouth mid-sweet talk, newspaper word cut-outs...',
        'Inside out. Upside-down, twisting beside myself. Stop that now - you\'re as close as it gets without touching me...',

        // Darren Korb, Setting Sail/Coming Home
        'I dig my hole, you build a wall. I dig my hole, you build a wall. One day that wall is gonna fa-a-all.',

        // MINMI, Song of Four Seasons
        'Haru wo tsuge! Odoridasu sansai... Natsu wo miru uji! Nohara karakusa kawaku wa...',

        // Daft Punk
        'Music\'s got me feeling so free, we\'re gonna celebrate - celebrate and dance so free. One more time...',
        'More than. Hour. Hour. Never. Ever. After. Work is. Over...',

        // Moldy Peaches
        'I kiss you on the brain in the shadow of a train; kiss you all starry-eyed, my body\'s swingin\' from side to side. I don\'t see what anyone can see in anyone else... bu-ut you...',

        // Sesame Street
        'One-two-three four-five, six-seven-eight nine-ten, eleven twelve... twelve!',

        // Hey Ya!, by OutKast
        'You think you\'ve got it - oh, you think you\'ve got it - but "got it" just don\'t get it when there\'s nothin\'t at aaa a-aaa a-aaa a-aaa a-a-all!',

        // Rebecca Sugar
        'The odds are against us, it won\'t be easy, but: we\'re not gonna do it alone!',
        'Look at you go, I just adore you; I wish that I knew... what makes... you think I\'m so speeecial.',

        // Out of my Mind, by Jamie Berry
        'I keep thinkin\' \'bou-- \'bou-- \'bou-- \'bou-- \'bou-- \'bou-- out of my mi-- mi-- mi-- mi-- mi-- mi-- I keep thinkin\'!',

        // Delerium
        'So truuuuuuly, if there is liiight then I wanna see-ee-ee i-i-it... nooowww that I know what I am lookin\' fooorrr...',
        'She\'s tired smiling madlyyy... until silence becomes very silently, a noise in her miiind...',

        // Danger! (High Voltage)
        'Danger! Danger! High voltage! When we touch; when we kiss; when we touch; when we kiss!!',

        // Ciao, Ciao
        'Con le mani, con le mani, con le mani, ciao-ciao! Con i piedi, con i piedi, con i piedi, ciao-ciao!',

        // High, by Polygon
        'Answers... passing by. Lasers... super-fly. Question... question-mark. Dot, dot, dot, dot...',

        // Paper Booklet, by Pola & Bryson
        'Bam, boom-boom-boom-boom... ... ... ... *clap* *clap* *clap* *clap* *clap* *clap* *clap* *clap*... baaaam, boooooom-boom!',

        // We Love, by Ramses B
        'We love. We-- ah, we love (we love). We (we) love-- ah, we love (love)... we-- we... we love... (\'cause you know how... \'cause you know how...)',

        // Time, by Jungle
        'Say it again! Ooooooh, just hold on tight. Don\'t let it in. Yeeeaaahhh, I\'ll run all night - don\'t let me!',

        // Razor Sharp
        'Unh! ... Unh! ... ... RAZOR SHARP!',

        // Under the Sun, by Seba
        'We are the stars under the Suuuuuuuuuuun... riding the wave of life as one; taking our time to feel the love.',

        // Wash My Hands, by Kormac
        'Gonna wash my haaands of you... wash my haaands of you... when you\'ve got me in your power, your kisses turn all sour, oh! I\'m gonna wash my hands... of you...',

        // I Am Not a Robot, by Marina and the Diamonds
        'It\'s okay to say you\'ve got a weak spot. You don\'t always have to be. on. top... Better to be haaated... then lo-o-oved for what you\'re not.',

        // No Doubt
        'The waaaves keep on crashing on me for some reasooonnn... but your looove keeps on coming like a thunderrrbooolllt...',

        // 6 Underground, by Sneaker Pimps
        'Overgrooouuund... watch this spaaaace... I\'m opeeen... to fallin\' from gra-ace...',

        // Dumb Ways to Die
        'Get your toast out... with a fork. Do your ooown electrical work. Teach yourself how to flyyy... eat a two-week-old unrefrigerated pie...',

        // Teardrop, by Massive Attack
        'Love, love is a verb; love is aaa doing word... feeaarless on my... bre-e-eath...',

        // Starships, by Nicki Minaj
        'Starships... were meant to flyyy-y-y... hands up, and touch the skyyy-y-y... let\'s do this one more tiii-i-ime... can\'t stop--',

        // Papercut, by Oohyo
        'Papercut you gave me... just a papercut you left me... papercut you gave me... just a papercut you left me...',

        // Crystal Gems
        'If you\'re evil and you\'re on the rise... you can count on the four of us takin\' you down...',

        // Polo & Pan
        'Jungle sauvaaage ouvre tes bras... il en faut peuuu-u pour toi et moi...',
        'I\'ll take my chaaance, take a glaaance at supernovas, like a magic shooow. I think it\'s time to gooo...',

        // Moon (And It Went Like)
        'And it went like: mm-mm... mm-mm... mm-hm-m-m-mm-mm mm-mm... mm-mm... mm-mm, mm...',

        // Imogen Heap
        'It\'s not meant to beee liiike thiiis... not what I plaaaned aaat allll... I don\'t want to feeel liiike thiiis... yea-aay-ah...',
        'Hiiiiiide aaaaand seeeeeeeeeee--... traaaaiins aaaaand seeewing ma-chiiiiines...',

        // Lifelight, Andy Hunter
        'It\'s you - the star that guides when I\'m lost at night. So light up the sky - a ray of life to every eye... life to every eeeeyee!',

        // Nao Sei Parar
        'Ba-da ba ba, ba ba-da... ba-da-d\'loooo. Ba-da ba ba, ba ba-da... ba-da-d\'looooooooooooooooooooooooooooooooooooooooooooooooooooooo...!-- Eu não sei quando paraaar... só quando você voltaaar... ba-da-d\'loo...',

        // Prelude, by TheFatRat
        'Ohmygod... ... ... ... GO!',

        // If I Could, by Tut Tut Child
        'If. I. Could. I wou-ould... If. I. Could. I wou-ould... oo-oh-ohhh...',

        // I Remember, by Culture Shock
        'I remember... (I remember... (I remember...)) ... I-i-i-i! I remember, I remember!',

        // Seeing What's Next, by Hollywood Principle
        'Now is the right time - I\'m making my move. I\'m... on a high now. You... can\'t bring me down.',

        // Agitations tropicales, by L'Impératrice
        'Monarque ingénue et fière... elle seule domine. L\'assemblée, docile, chemine... vers son parfum... thérémine.',

        // To Let Myself Go, by The Avener & Ane Brun
        'To let myself go, to let myself flow... is the only way of being...',

        // Black to White, by Felix Cartal
        'Big sound... break down... feel-innnn\' right. I\'m those... emooo-tioooons...',

        // Hikari, by Hikaru Utada
        'Suizhuka niiiii... deguchi niiiii... tatte. Kurayamiiii nii-ii... hikaaaari wooo... ute (ute, ute, ute)',

        // Mr Blue Sky, by ELO
        'Hey there, Mr. Blue. We\'re so pleased to be with you. Look around, see what you do. Everybody smiles at you!',

        // Shooting Stars, by Bag Raiders
        'Gave my love to a shooooting star, but she moooves so fast, that I can\'t keep up - I\'m chasing...',

        // D.A.N.C.E., by Justice
        'Do the D.A.N.C.E. 1, 2, 3, 4, fight! Stick to the B.E.A.T. Get ready to ignite!',

        // Let Go, by Frou Frou
        'Yeah, let go. Just get in. Oh, it\'s soooo amaaazing here. It\'s alright. \'Cause there\'s beauuutyyy in the breakdown.',

        // Sing Along, by Blue Man Group
        'If I sing a song, will you... sing along? If I sing a song will you... sing along? Should I just keep singing right here, by myself?',

        // Cowgirl (Eraser of Love), by Underworld
        'I wanna give you everything, I wanna give you energy, I wanna give a good thing, I wanna give you everything...',

        // Is You, by D.I.M. (Le Castle Vania Remix, maybe - it hardly matters with lyrics like this :P)
        'Is you, you, you, you... is you, you, you, you... is you, you, you, you... is yoouu, yooouu-- y-ouuu-- y----ooo-- --...',

        // We're Back, by Heartbreak
        'So. You\'ve hearrrd it allll befoorre. Well we\'re baaack. From the disco tooo theee raaadiooo... whoa-oh, yeah!',

        // The Spark, by Kabin Crew
        'Think you can stop what we do - I doubt it! We got the energy, we\'ll tell ya all about it!',

        // Disco Snails, by Vulf
        'There\'s traffic on the freeway, for the snails are on the loose. Watch your step, they\'re only 1 inch tall atop their platform shoes.',

        // Your Reality, by Dan Salvato (Doki Doki Literature Club)
        'Every day, I imagine a future where I can be with you. In my hand... is a pen that will write a po-em of me and you...',

        // ❅ LAST MINUTE ❅, by kitty
        'I\'m up at night over shit I said and forgot about. You\'re the spider I\'m tired of being waterspout; you\'re too tiny to climb me...',

        // Cobrastyle, by Teddybers
        'My style is di bom digi bom di deng di deng digi-digi (oh-oh, oh-oh, oh-oh-oh-o-oh!)',

        // Dresden Dolls
        'I am not so serious, this passion is a plagiarism. I might join your century, but only on a rare occasion...',
        'Coin. Operated boy. Sitting on the shelf. He is just a toy. But I turn him on. And he comes to life. Automatic joy. That is why I want. A. Coin. Operated boy.',

        // Kick It, by The Breakfastaz
        'Ki-- ki-- kick it now... Kick it, kick it now... Ki-- ki-- kick it now... Kick it, kick it now...',

        // Something Good, by Utah Saints
        'Ooo-ooh-oy, I just knooww that something good is gonna happeeennn... ooo-ooh-oy!',

        // Mario Kart Love Song, by Sam Hart
        'No one will touch us if we pick up a star, and if you spin out, you can ride in my car...',

        // Dusk till Dawn, by Ladyhawke
        'When you sense you\'re not alone, and the darkness starts to moan-- who\'s there? Shadows all around, but: you don\'t make a sound...',
        'Stop! Playing with my delirium... \'cause I\'m outta my head, and outta my self-controool...',

        // Upside Down, by Paloma Faith
        'I tell you what. What I have fooouuund. That I\'m no foo-ool. I\'m just upside down.',

        // Fireflies, by Owl City
        'Why do I tire of counting sheep (please take me away from here), when I\'m far too tired to fall asleep?',

        // Kami-sama no Iu Toori, by Yakushimaru Etsuko
        'Tengoku iku tame... mainichi kosotto. Naisho de ii koto shiteta... kedo. Namida hitohira. Atama kurakura...',

        // Chicane
        'Oh mi i-chidan-da muh munh, shu ken geth che de. Nuka-i machi nanga-dei...',
        'And somedaaayyy... the spirit of the hearrrt. To fiiind - to feeeel you calling your waaayyy...',

        // Close to Me, by Sabrepulse
        'Close to me-e! Close to me-e! Close to me-e! Close to me-e! Close to me! Close to me-e! Close to me-e! Close to meeeeeeeeeeeeeeeeeeeeeeeee...!!',

        // I Really Like You, by Carly Rae Jepsen
        'But I need to tell you somethin\' - I really, really, really, really, really, really like you!',

        // Akihabara Cruise, by flippy 19XX
        'Let me show you all the glamor and the gold... seeecret fantasies in the show biz world...',

        // Back to Me, by KSHMR
        'Paaaiiint my roses red, riiight before the end, I will let the raindrop caarry me...',

        // The Grump Variations, by M. Bulteau
        'GO! D-d-d-doo, d-d-d-doo, d-d-d-do-do-do! D-d-d-d d-d-d-d d-d-d-do-do-do! D-d-d-doo, d-d-d-doo, d-d-d-do-do-do-- AUGHGHGHGH!!',

        // That Look, by Justin Hawkes
        'EYES... I... I... I... I... I... I... I... look upon your-- EYES...',

        // Transformations, by Maduk
        'Someway, somehow... think I\'ve seen it before. Someway, somehow... think I\'ve seen it before...',

        // Eli Eli (Maduk Remix), by Misun
        'Ah and I miss it, and I kiss it, and I try, and I hold it \'till it falls into the ocean or the sky-y-y...',

        // Wild Wild Life, by Talking Heads
        'I\'m wearing... a fur pajamas. I ride a... hot potato. It\'s tickling... my fancy. Speak up! I can\'t hear you.',

        // Starscapes, by TwoThirds & Feint
        'Hard to saaaaaaaaaaaaaaaaaaaaaaaave... hard to sa-a, a a-a, a a-a, a a-a, a a-a, a a-a, a a-a, a a-a...',

        // Snake Eyes, by Feint
        'Caaan\'t you seee... eeeverythiiing... iiis a meeess... when you hiiide... all the liiies... that you thoouught... you could buuurrryyy...',

        // Makeba
        'Ooo-ee! Makeba, Makeba ma qué bella, can I get a "ooo-ee!" Makeba, makes my body dance for yoouu.',

        // We Are One, by Rameses B
        'COME to me; don\'t be shy, I want LOVE, truly. Something that\'ll make SENSE to me. RUSH upon me and SAY something - BREAK something.',

        // A Little Something for the Weekend, Franc Moody
        'The key to life ... Divine inspiration ... Stimulation~ ... The true purpose ... A little pick me up!',

        // Put a Banana in your Ear
        'Puuut aaa bananaaa in your eeeaaarrr! (A banana in my ear?) Put a ripe banana right into your favorite ear!',

        // Abracadabra, by Lady Gaga
        'Abracadabra, amor-oo-nana! Abracadabra, morta-oo-gaga!',

        // The Walker, by Fitz and the Tantrums
        'OH! Here we gooo! Feel it in my soul! Really need it - need it - so GO!',

        // Danger! High Voltage, by Electric Six
        'Danger, danger! High voltage-- when we touch! When we kiss! When we touch! When we kiss! Lover!',

        // Strangers, by Kenya Grace
        'Always eeends the same. When it was me and yoo-ou. But every time I meet somebody new. It\'s like déjà vu (déjà vu (déjà vu))...',

        // The Fox, by Ylvis
        'Fraka-kaka-kaka-kaka-kow! Fraka-kaka-kaka-kaka-kow! Fraka-kaka-kaka-kaka-kow! What the fox say?!',

        // Yeah Yeah Yeahs
        'Off-- off with your head! Dance-- dance \'til your dead! Dead! Heads will roll! Heads will roll! Heads will roll... on the floor!',

        // Foreign Language, by Flight Facilities
        'Boy, it\'s just a puzzle, and the pieces they have scattered on the floor (ooh-ooh.. nah ooh-ooh...)',

        // Destroy Everything You Touch, by Ladytron
        'Everything you touch, you don\'t feel. Do not know... what you steal. Shakes your hand... takes your gun. Walks you out... of the sun.',

        // Hybrid
        'A hybrid of hundreds of troubles... people felt us connect, and ran for shelter...',
        'I\'ll learn to forgeeet the criiime, if I surviiiiive. But I swear you\'re going down if I surviiiiiiiiiiiiiiiiive!',
        'It\'s drifting in and ouuuuuut iiiiin waaaaves... we\'re living in the straaaaangeeeeeeest daaaze...',

        // Want It, by Pola & Bryson & IYAMAH
        'It\'s insightful how I break the cycle. Now this is final. I\'mma take this like a test; brush you off with the cobwebs.',

        // Yakusoku wa Iranai (Escaflowne theme)
        'Kimi wo, kimi wo, shiiiinjiteru samui yoru moooooo... \*random bagpipe\*',

        // R U Guna Move
        'If you say sooo... iiit\'s irrelevaaant. (Are you gonna move? Are you gonna-- are you gonna-- are you gonna move?)',

        // Our Lips Are Sealed, by the Go-Go's
        'Can you hear theeem? They talk about us. Telling liiees, well that\'s no surprise...',

        // If It Makes You Happy, by Sheryl Crow
        'If it makes you haaapyyyyy... it can\'t be that baaaaa-a-a-aaad... if it makes you haaapyyyyy... then why the hell are you sooo saaad...',

        // The Fate of Ophelia, by Taylor Swift
        '\'Tis locked inside my memory, and only you possess the key. No longer drowning and deceived, all because you came for me...',

        // Power, by Kove
        'You gotta\' feel the power! Taking you up - in your soul! Just feel the music! Lifting you up from inside! Play it with me now!',

        // Kokokara, kokokara
        'Koko karaaa hajime yo-oooouuuu... sekaiiii wo mi ni yukoouuuu...',

        // Young Blood, by The Naked and Famous
        'We lie beneath the stars at niiight... our hands gripping each other tiiight... you keep my secrets hope to diiie... promises - swear them to the skyyy!',

        // Low Rider, by War
        'Low. Ri. Der don\'t use no gas, now... Low. Ri. Der don\'t drive too fast...',

        // Still Alive, by Solar Fields
        'I\'ve learned to looose, I\'ve learned to wiiin, I turn my faaace against the wiiind...',
    ];
}
