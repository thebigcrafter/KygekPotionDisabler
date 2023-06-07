<?php

/*
 * Unregisters and disables default PocketMine-MP regular and splash potion items
 * Copyright (C) 2021-2023 KygekTeam
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace KygekTeam\KygekPotionDisabler;

use KygekTeam\KygekPotionDisabler\tile\Chest;
use pocketmine\event\Listener;
use pocketmine\event\server\CommandEvent;
use pocketmine\inventory\CraftingManager;
use pocketmine\inventory\CraftingRecipe;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Potion;
use pocketmine\item\SplashPotion;
use pocketmine\lang\TranslationContainer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use ReflectionException;
use ReflectionProperty;
use function array_diff;
use function array_filter;
use function array_walk;
use function basename;
use function explode;
use function in_array;
use function mb_strtolower;
use function scandir;
use function str_ireplace;

class PotionDisabler extends PluginBase implements Listener {

	private const HIGHEST_META = 42;

	/**
	 * @throws ReflectionException
	 */
	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->removePotionFromCreativeInventory();
		$this->removePotionItems();
		$this->removePotionFromInventory();

		$this->removePotionCrafting("shapedRecipes");
		$this->removePotionCrafting("shapelessRecipes");
		Tile::registerTile(Chest::class, ["Chest", "Large Chest"]);
	}

	public function onGiveCommand(CommandEvent $event) {
		if ($event->isCancelled()) return;

		$rawCommand = $event->getCommand();
		$command = explode(" ", mb_strtolower($rawCommand));
		if ($command[0] !== "give" || !isset($command[2])) return;

		$item = explode(":", str_ireplace("minecraft:", "", $command[2]));
		$match = ["potion", (string) ItemIds::POTION, "splash_potion", (string) ItemIds::SPLASH_POTION];
		if (in_array($item[0], $match, true)) {
			$event->setCancelled();
			$event->getSender()->sendMessage(
				new TranslationContainer(TextFormat::RED . "%commands.give.item.notFound", [explode(" ", $rawCommand)[2]])
			);
		}
	}

	private function removePotionFromCreativeInventory() {
		$meta = 0;
		while ($meta <= self::HIGHEST_META) {
			Item::removeCreativeItem(ItemFactory::get(ItemIds::POTION, $meta));
			Item::removeCreativeItem(ItemFactory::get(ItemIds::SPLASH_POTION, $meta));
			$meta++;
		}
	}

	private function removePotionItems() {
		$reflection = new ReflectionProperty(ItemFactory::class, "list");
		$reflection->setAccessible(true);
		$list = $reflection->getValue()->toArray();
		$list = array_filter($list, function ($item) : bool {
			return !$item instanceof Potion && !$item instanceof SplashPotion;
		});
		$reflection->setValue($list);
	}

	/**
	 * @throws ReflectionException
	 */
	private function removePotionCrafting(string $property) {
		$reflection = new ReflectionProperty(CraftingManager::class, $property);
		$reflection->setAccessible(true);
		$recipes = $reflection->getValue(new CraftingManager());
		array_walk($recipes, function (array &$value) {
			$value = array_filter($value, function (CraftingRecipe $recipe) : bool {
				foreach ($recipe->getIngredientList() as $ingredient) {
					if ($ingredient instanceof Potion || $ingredient instanceof SplashPotion) return false;
				}
				foreach ($recipe->getResults() as $result) {
					if ($result instanceof Potion || $result instanceof SplashPotion) return false;
				}
				return true;
			});
		});
		$reflection->setValue(new CraftingManager(), $recipes);
	}

	private function removePotionFromInventory() {
		$server = $this->getServer();
		foreach (array_diff(scandir($server->getDataPath() . "players/"), [".", ".."]) as $playerData) {
			$playerName = basename($playerData, ".dat");
			$player = $server->getOfflinePlayerData($playerName);

			$invTag = $player->getListTag("Inventory");
			$player->setTag(new ListTag("Inventory", array_filter($invTag->getValue(), function (CompoundTag $value) : bool {
				$id = $value->getValue()["id"]->getValue();
				return $id !== ItemIds::POTION && $id !== ItemIds::SPLASH_POTION;
			}), NBT::TAG_Compound));

			$enderChestTag = $player->getListTag("EnderChestInventory");
			$player->setTag(new ListTag("EnderChestInventory", array_filter($enderChestTag->getValue(), function (CompoundTag $value) : bool {
				$id = $value->getValue()["id"]->getValue();
				return $id !== ItemIds::POTION && $id !== ItemIds::SPLASH_POTION;
			}), NBT::TAG_Compound));

			$server->saveOfflinePlayerData($playerName, $player);
		}
	}

}
