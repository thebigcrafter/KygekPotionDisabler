<?php

/*
 * Unregisters and disables default PocketMine-MP regular and splash potion items
 * Copyright (C) 2021 KygekTeam
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace KygekTeam\KygekPotionDisabler\tile;

use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Chest as PChest;
use pocketmine\tile\Container;
use pocketmine\tile\ContainerTrait;

class Chest extends PChest {

    use ContainerTrait;

    protected function loadItems(CompoundTag $tag) : void {
        if ($tag->hasTag(Container::TAG_ITEMS, ListTag::class)) {
            $inventoryTag = $tag->getListTag(Container::TAG_ITEMS);

            $inventory = $this->getRealInventory();
            /** @var CompoundTag $itemNBT */
            foreach ($inventoryTag as $itemNBT) {
                $id = $itemNBT->getValue()["id"]->getValue();
                if ($id === Item::POTION || $id === Item::SPLASH_POTION) continue;
                $inventory->setItem($itemNBT->getByte("Slot"), Item::nbtDeserialize($itemNBT));
            }
        }

        if ($tag->hasTag(Container::TAG_LOCK, StringTag::class)) {
            $this->lock = $tag->getString(Container::TAG_LOCK);
        }
    }

}