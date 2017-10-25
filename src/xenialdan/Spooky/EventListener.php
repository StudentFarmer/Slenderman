<?php

namespace xenialdan\Spooky;

use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use xenialdan\Spooky\entities\Slenderman;
use xenialdan\Spooky\other\GameRule;

class EventListener implements Listener{
	/** @var Loader */
	public $owner;

	public function __construct(Plugin $plugin){
		$this->owner = $plugin;
	}

	public function onPlayerMove(PlayerMoveEvent $ev){
		$player = $ev->getPlayer();
		$from = $ev->getFrom();
		$to = $ev->getTo();
		if ($from->distance($to) < 0.1){
			return;
		}
		$maxDistance = 15;
		$entities = $player->getLevel()->getNearbyEntities($player->getBoundingBox()->grow($maxDistance, $maxDistance, $maxDistance), $player);
		foreach ($entities as $e){
			if (!$e instanceof Slenderman){
				continue;
			}

			$e->lookAt($player->add(0,$player->getEyeHeight()));
			/*
			$pk = new MovePlayerPacket();
			$pk->entityRuntimeId = $e->getId();
			$pk->position = $e->asVector3()->add(0, $e->getEyeHeight(), 0);
			$pk->yaw = $yaw;
			$pk->pitch = $pitch;
			$pk->headYaw = $yaw;
			$pk->onGround = $e->onGround;
			$player->getServer()->broadcastPacket($e->getLevel()->getPlayers(), $pk);
			*/
		}
	}

	public function onPacketReceive(DataPacketReceiveEvent $event){
		/** @var DataPacket $packet */
		if (!($packet = $event->getPacket()) instanceof InteractPacket) return;
		/** @var Player $player */
		if (!($player = $event->getPlayer()) instanceof Player) return;
		/** @var Level $level */
		if (($level = $player->getLevel())->getId() !== Server::getInstance()->getDefaultLevel()->getId()){
			return;
		}
		/** @var InteractPacket $packet */
		if ($packet instanceof InteractPacket){
			if (($entity = Server::getInstance()->findEntity($packet->target, $level)) instanceof Slenderman){
				/** @var Slenderman $entity */
				$entity->triggerTeleport($player);
				$event->setCancelled();
			}
		}
	}

	public function levelChange(EntityLevelChangeEvent $event){
		/** @var Player $player */
		if (!($player = $event->getEntity()) instanceof Player) return;

		$pk = new GameRulesChangedPacket();
		$pk->gameRules = new GameRule('dodaylightcycle', GameRule::TYPE_BOOL, $event->getTarget()->getId() !== Server::getInstance()->getDefaultLevel()->getId());
		$player->dataPacket($pk);
	}
}