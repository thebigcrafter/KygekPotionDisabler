<h1 align="center">KygekPotionDisabler</h1>

<p align="center">
<a href="https://github.com/thebigcrafter/KygekPotionDisabler#GPL-3.0-1"><img src="https://img.shields.io/github/license/thebigcrafter/KygekPotionDisabler?style=for-the-badge" alt="license" /></a>
<a href="https://discord.gg/PykBfE2TZ9"><img src="https://img.shields.io/discord/1087729577004122112?color=7289DA&label=discord&logo=discord&style=for-the-badge" alt="discord" /></a>
</p>

# 📖 About

Unregisters and disables default PocketMine-MP regular and splash potion items.

# ✅ Requirements

- PocketMine-MP API 5.0.0 or a compatible API 5 release.

The plugin has no commands or configuration file. When enabled, it removes
regular and splash potions from saved offline player inventories and the
creative inventory, removes crafting recipes that use or produce those items,
and cancels matching `/give` command requests. It also skips potion items while
loading the inventory of the plugin's chest tile implementation. Active player
inventories are not overwritten during a plugin reload.

This is an enforcement plugin for the server-side item paths covered above;
it does not replace a permission policy or claim to validate every custom
plugin that creates or transfers potion items.

# ⚠️ Notice

This plugin is not planned to be released on Poggit. This plugin is also still in BETA.

# 🛠️ Development

Install the locked development dependencies and run the repository checks:

```shell
composer install
composer run analyse
```

The GitHub Actions build workflow uses [DevTools](https://github.com/NhanAZ/DevTools) to build and validate the plugin PHAR. Its pinned CI inputs remain in the workflow for reproducible builds.

Before using a build on a server, verify creative inventory contents, potion
crafting, `/give` handling and chest loading in an isolated API 5 test server.
Do not test by changing a production world or its plugin data.

The `1.0.2` release also fixes chest NBT loading for the current API 5 item
format while preserving the existing potion filtering behavior.

# ⚖️ License

Licensed under the [GNU General Public License v3.0 license](https://github.com/thebigcrafter/KygekPotionDisabler#GPL-3.0-1).
