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
use pocketmine\block\tile\TileFactory;
use pocketmine\crafting\CraftingManager;
use pocketmine\crafting\CraftingRecipe;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\crafting\ShapelessRecipe;
use pocketmine\data\bedrock\PotionTypeIdMap;
use pocketmine\event\Listener;
use pocketmine\event\server\CommandEvent;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\Potion;
use pocketmine\item\SplashPotion;
use pocketmine\item\VanillaItems;
use pocketmine\lang\Translatable;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use ReflectionException;
use ReflectionProperty;
use function array_filter;
use function array_walk;
use function explode;
use function in_array;
use function is_array;
use function mb_strtolower;
use function str_ireplace;

class PotionDisabler extends PluginBase implements Listener {
	private const HIGHEST_META = 42;

	/**
	 * @throws ReflectionException
	 */
	public function onEnable() : void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->removePotionFromCreativeInventory();

		$this->removePotionCrafting("shapedRecipes");
		$this->removePotionCrafting("shapelessRecipes");
		$this->removePotionCraftingIndex();

		TileFactory::getInstance()->register(Chest::class, ["Chest", "Large Chest"]);
	}

	public function onGiveCommand(CommandEvent $event) : void {
		if ($event->isCancelled())
			return;

		$rawCommand = $event->getCommand();
		$command = explode(" ", mb_strtolower($rawCommand));
		if ($command[0] !== "give" || !isset($command[2]))
			return;

		$item = explode(":", str_ireplace("minecraft:", "", $command[2]));
		$match = ["potion", (string) ItemTypeIds::POTION, "splash_potion", (string) ItemTypeIds::SPLASH_POTION];
		if (in_array($item[0], $match, true)) {
			$event->cancel();
			$event->getSender()->sendMessage(
				new Translatable(TextFormat::RED . "%commands.give.item.notFound", [explode(" ", $rawCommand)[2]])
			);
		}
	}

	private function removePotionFromCreativeInventory() : void {
		$creativeInv = CreativeInventory::getInstance();

		$meta = 0;
		while ($meta <= self::HIGHEST_META) {
			$potionType = PotionTypeIdMap::getInstance()->fromId($meta);
			if ($potionType !== null) {
				$creativeInv->remove(VanillaItems::POTION()->setType($potionType));
				$creativeInv->remove(VanillaItems::SPLASH_POTION()->setType($potionType));
			}
			$meta++;
		}
	}

	/**
	 * @throws ReflectionException
	 */
	private function removePotionCrafting(string $property) : void {
		$craftingManager = $this->getServer()->getCraftingManager();
		$reflection = new ReflectionProperty(CraftingManager::class, $property);
		$reflection->setAccessible(true);
		$recipes = $reflection->getValue($craftingManager);
		if (!is_array($recipes)) {
			return;
		}
		array_walk($recipes, static function (mixed &$value) : void {
			if (!is_array($value)) {
				$value = [];
				return;
			}
			$value = array_filter($value, static fn (mixed $recipe) : bool => !self::isPotionRecipe($recipe));
		});
		$reflection->setValue($craftingManager, $recipes);
	}

	/**
	 * Removes potion recipes from the index used to build the client's crafting data cache.
	 *
	 * @throws ReflectionException
	 */
	private function removePotionCraftingIndex() : void {
		$craftingManager = $this->getServer()->getCraftingManager();
		$reflection = new ReflectionProperty(CraftingManager::class, "craftingRecipeIndex");
		$reflection->setAccessible(true);
		$recipes = $reflection->getValue($craftingManager);
		if (!is_array($recipes)) {
			return;
		}
		$reflection->setValue($craftingManager, array_filter(
			$recipes,
			static fn (mixed $recipe) : bool => !self::isPotionRecipe($recipe)
		));
	}

	private static function isPotionRecipe(mixed $recipe) : bool {
		if (!$recipe instanceof CraftingRecipe) {
			return false;
		}
		foreach ($recipe->getIngredientList() as $ingredient) {
			if ($ingredient instanceof Potion || $ingredient instanceof SplashPotion) {
				return true;
			}
		}
		$results = match (true) {
			$recipe instanceof ShapedRecipe => $recipe->getResults(),
			$recipe instanceof ShapelessRecipe => $recipe->getResults(),
			default => []
		};
		foreach ($results as $result) {
			if ($result instanceof Potion || $result instanceof SplashPotion) {
				return true;
			}
		}
		return false;
	}
}
