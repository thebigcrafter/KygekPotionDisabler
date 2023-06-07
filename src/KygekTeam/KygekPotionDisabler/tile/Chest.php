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

namespace KygekTeam\KygekPotionDisabler\tile;

use pocketmine\block\tile\Container;
use pocketmine\block\tile\ContainerTrait;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\nbt\tag\CompoundTag;


class Chest extends \pocketmine\block\tile\Chest {

	use ContainerTrait;

	protected function loadItems(CompoundTag $tag) : void {
		if ($tag->getTag(Container::TAG_ITEMS) !== null) {
			$inventoryTag = $tag->getListTag(Container::TAG_ITEMS);

			$inventory = $this->getRealInventory();
			/** @var CompoundTag $itemNBT */
			foreach ($inventoryTag as $itemNBT) {
				$id = $itemNBT->getValue()["id"]->getValue();
				if ($id === ItemTypeIds::POTION || $id === ItemTypeIds::SPLASH_POTION) continue;
				$inventory->setItem($itemNBT->getByte("Slot"), Item::nbtDeserialize($itemNBT));
			}
		}

		if ($tag->getTag(Container::TAG_LOCK) !== null) {
			$this->lock = $tag->getString(Container::TAG_LOCK);
		}
	}

}
