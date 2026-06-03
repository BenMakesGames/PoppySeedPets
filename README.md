<img src="docs/psp.svg" style="width:100%" alt="Poppy Seed Pets" />

This is the source code for Poppy Seed Pets. It is available under the [GPL 3.0 license](COPYING).

## Quickstart

You only need [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed — Docker handles PHP, Node, Composer, MySQL, and Redis for you.

1. Clone this repo.
2. Drop your `proprietary-assets/` folder at the repo root (game images & fonts — the only thing not bundled with the repo).
3. Run `docker compose up`.
4. Open <https://localhost:4200> in your browser and accept the dev-certificate warning.

The first boot takes a few minutes (installing PHP and Node dependencies). Subsequent boots take about 15 seconds. Edits to PHP and TypeScript files hot-reload — no rebuild needed.

For the long form (native install, prod notes, troubleshooting), see [docs/Installing and Running.md](docs/Installing%20and%20Running.md).

## More

More info about this project can be found in the `docs/` directory:

* [How to Contribute](docs/how%20to/How%20to%20Contribute.md)
* [Installing and Running](docs/Installing%20and%20Running.md)
* [TODO](docs/TODO.md)
* [Architecture Decisions](docs/Architecture%20Decisions.md)

### More?

* [Play the game! (poppyseedpets.com)](https://poppyseedpets.com)
* [Find us on the Everyone Makes Stuff Discord server](https://discord.gg/HfTDdQzYrY)
* [Check out BenMakesGames' other games, on Steam](https://store.steampowered.com/search/?publisher=benmakesgames.com)
* [Support BenMakesGames on Patreon](https://www.patreon.com/BenMakesGames)
  * hosting PSP isn't free... but this also isn't my job; I'm good; def take care of yourself first!
* [Learn more about the GPLv3 license](https://www.gnu.org/licenses/quick-guide-gplv3.html)

### Recommended reading/viewing

* "The Art of Readable Code" - https://docslib.org/doc/3480439/the-art-of-readable-code-pdf
* Some YouTube videos - https://www.youtube.com/playlist?list=PLMzQZ9sF5S2HecRCVCF9pmdqfZK2HCbxN

### No coding experience? Try an agentic AI tool!

I've personally used and optimized for Claude, but others should work, including open-source tools such as [OpenCode](https://opencode.ai/).

#### 1. Use `/write-ticket` to hash out the details

Example: `/write-ticket add a new item called Sticky Buns that can be cooked by players`

The `/write-ticket` skill will research the codebase, ask you clarifying questions (in the example above you might be asked what the ingredients are, what item graphic to use, etc), and document the results.

If you change your mind about something but the AI already wrote the ticket, just ask it to change it!

#### 2. Use `/implement-ticket` to make the changes

Example: `/implement-ticket add sticky buns` (or whatever title the AI gave your ticket in the first step)

Besides implementing the named ticket, `/implement-ticket` will:

* Ask any additional clarifying questions and perform any additional needed research
* Check for syntax & build errors
* Document pain points during development (to be used to improve the codebase as a whole)
