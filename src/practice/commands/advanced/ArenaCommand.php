<?php

namespace practice\commands\advanced;

use JsonException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use practice\arenas\PracticeArena;
use practice\commands\BaseCommand;
use practice\commands\parameters\BaseParameter;
use practice\commands\parameters\Parameter;
use practice\commands\parameters\SimpleParameter;
use practice\PracticeCore;
use practice\PracticeUtil;

class ArenaCommand extends BaseCommand{
	public function __construct(){
		parent::__construct("arena", "The base arena command.", "/arena help");

		$parameters = [
			0 => [
				new BaseParameter("help", Parameter::NO_PERMISSION, "Lists all the kit commands.")
			],
			1 => [
				new BaseParameter("create", $this->getPermission(), "Creates a new arena."),
				new SimpleParameter("arena-name", Parameter::PARAMTYPE_STRING),
				new SimpleParameter("arena-type", Parameter::PARAMTYPE_STRING)
			],
			2 => [
				new BaseParameter("delete", $this->getPermission(), "Deletes an existing arena."),
				new SimpleParameter("arena-name", Parameter::PARAMTYPE_STRING)
			],
			3 => [
				new BaseParameter("list", $this->getPermission(), "Lists all current arenas.", false),
			],
			4 => [
				new BaseParameter("tp", $this->getPermission(), "Teleports a player to an arena.", false),
				new SimpleParameter("arena-name", Parameter::PARAMTYPE_STRING),
				(new SimpleParameter("name...", Parameter::PARAMTYPE_STRING))->setOptional(true),
				(new SimpleParameter("...", Parameter::PARAMTYPE_STRING))->setOptional(true)
			],
			5 => [
				new BaseParameter("kit", $this->getPermission(), "Sets/adds a kit of an arena."),
				new SimpleParameter("arena-name", Parameter::PARAMTYPE_STRING),
				new SimpleParameter("kit-name", Parameter::PARAMTYPE_STRING)
			],
			6 => [
				new BaseParameter("pos1", $this->getPermission(), "Sets the first spawn position for the players in a duel arena."),
				new SimpleParameter("arena-name", Parameter::PARAMTYPE_STRING)
			],
			7 => [
				new BaseParameter("pos2", $this->getPermission(), "Sets the second spawn position for the players in a duel arena."),
				new SimpleParameter("arena-name", Parameter::PARAMTYPE_STRING)
			]
		];
		$this->setParameters($parameters);
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param array         $args
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		$msg = null;

		if(self::canExecute($sender, $args)){
			$name = strval($args[0]);
			switch($name){
				case "help":
					$msg = $this->getFullUsage();
					break;
				case "create":
					$aName = strval($args[1]);
					$aType = strval($args[2]);
					$this->createArena($sender, $aName, $aType);
					break;
				case "delete":
					$aName = strval($args[1]);
					$this->deleteArena($sender, $aName);
					break;
				case "list":
					$msg = $this->listArenas($sender);
					break;
				case "tp":

					$argsCount = count($args) - 1;

					if($argsCount <= 3 and $argsCount > 0){

						$aName = strval($args[1]);

						if($argsCount == 2)
							$aName .= " " . $args[2];
						else if($argsCount === 3)
							$aName .= " " . $args[2] . " " . $args[3];

						$this->teleportToArena($sender, $aName);

					}else $msg = $this->getUsageOf($this->getParamGroupFrom($name), false);
					break;
				case "kit":
					$aName = strval($args[1]);
					$kitName = strval($args[2]);
					$this->setKitOf($sender, $aName, $kitName);
					break;
				case "pos1":
					$aName = strval($args[1]);
					$this->setPosition($sender, $aName, false);
					break;
				case "pos2":
					$aName = strval($args[1]);
					$this->setPosition($sender, $aName, true);
					break;
			}
		}

		if(!is_null($msg)) $sender->sendMessage($msg);
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $arenaName
	 * @param string        $arenaType
	 *
	 * @return void
	 * @throws JsonException
	 */
	private function createArena(CommandSender $sender, string $arenaName, string $arenaType) : void{
		if($sender instanceof Player){
			if(!PracticeCore::getArenaHandler()->doesArenaExist($arenaName)){

				$arenaType = PracticeArena::getType($arenaType);

				if($arenaType === "unknown")
					$msg = TextFormat::RED . "Create Arena Failed. Reason: Unknown Type.\nTypes: duel, ffa, spleef";

				else{
					PracticeCore::getArenaHandler()->createArena($arenaName, $sender->getPosition(), $arenaType);
					$msg = PracticeUtil::getMessage("general.arena.create");
					$msg = strval(str_replace("%arena-name%", $arenaName, $msg));
				}
			}else{
				$msg = PracticeUtil::getMessage("general.arena.arena-exists");
				$msg = strval(str_replace("%arena-name%", $arenaName, $msg));
			}
		}else{
			$msg = PracticeUtil::getMessage("console-usage-command");
		}


		if(!is_null($msg)) $sender->sendMessage($msg);
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $arenaName
	 *
	 * @return void
	 * @throws JsonException
	 */
	private function deleteArena(CommandSender $sender, string $arenaName) : void{
		if(PracticeCore::getArenaHandler()->doesArenaExist($arenaName)){
			PracticeCore::getArenaHandler()->removeArena($arenaName);
			$msg = PracticeUtil::getMessage("general.arena.delete");
		}else{
			$msg = PracticeUtil::getMessage("general.arena.arena-no-exist");
		}
		$msg = strval(str_replace("%arena-name%", $arenaName, $msg));
		if(!is_null($msg)) $sender->sendMessage($msg);
	}

	/**
	 * @param CommandSender $sender
	 *
	 * @return string
	 */
	private function listArenas(CommandSender $sender) : string{
		$listAll = true;

		if($sender instanceof Player) $listAll = PracticeCore::getPlayerHandler()->isOwner($sender->getName());

		return PracticeCore::getArenaHandler()->getArenaList($listAll);
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $arenaName
	 *
	 * @return void
	 */
	private function teleportToArena(CommandSender $sender, string $arenaName) : void{
		$msg = null;
		if($sender instanceof Player){
			if(PracticeCore::getPlayerHandler()->isPlayer($sender)){
				$p = PracticeCore::getPlayerHandler()->getPlayer($sender);
				if($p->canUseCommands(false)){
					$exec = !$p->isInArena();
					if($exec){
						if(PracticeCore::getArenaHandler()->isFFAArena($arenaName)){
							$arena = PracticeCore::getArenaHandler()->getFFAArena($arenaName);
							$p->teleportToFFA($arena);
						}else{
							$msg = PracticeUtil::getMessage("general.arena.arena-no-exist");
							$msg = strval(str_replace("%arena-name%", $arenaName, $msg));
						}
					}else{
						$msg = PracticeUtil::getMessage("general.arena.in-arena");
						$msg = strval(str_replace("%arena-name%", $arenaName, $msg));
					}
				}
			}
		}else{
			$msg = PracticeUtil::getMessage("console-usage-command");
		}

		if(!is_null($msg)) $sender->sendMessage($msg);
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $arena
	 * @param string        $kit
	 *
	 * @return void
	 * @throws JsonException
	 */
	private function setKitOf(CommandSender $sender, string $arena, string $kit){
		$msg = null;

		if(PracticeCore::getArenaHandler()->doesArenaExist($arena)){
			$theArena = PracticeCore::getArenaHandler()->getArena($arena);
			if(PracticeCore::getKitHandler()->isKit($kit)){
				$exec = true;
				if($theArena->getArenaType() !== PracticeArena::DUEL_ARENA){
					$theArena->setKit($kit);
				}else{
					if($theArena->hasKit($kit)){
						$msg = PracticeUtil::getMessage("general.kits.kit-exists");
						$msg = strval(str_replace("%kits%", $kit, $msg));
						$exec = false;
					}else{
						$theArena->addKit($kit);
					}
				}

				if($exec){
					PracticeCore::getArenaHandler()->updateArena($arena, $theArena);

					$execute = PracticeUtil::isMysqlEnabled();

					if($execute === true) PracticeCore::getMysqlHandler()->addEloColumn($kit);
					else PracticeCore::getPlayerHandler()->addEloKit($kit);

					$msg = PracticeUtil::getMessage("general.arena.update");
					$msg = strval(str_replace("%arena-name%", $arena, $msg));
					PracticeCore::getItemHandler()->reload();
				}
			}else{
				$msg = PracticeUtil::getMessage("general.kits.kit-no-exist");
				$msg = strval(str_replace("%kit%", $kit, $msg));
			}
		}else{
			$msg = PracticeUtil::getMessage("general.arena.arena-no-exist");
			$msg = strval(str_replace("%arena-name%", $arena, $msg));
		}

		if(!is_null($msg)) $sender->sendMessage($msg);
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $arena
	 * @param bool          $isOpponentPos
	 *
	 * @return void
	 * @throws JsonException
	 */
	private function setPosition(CommandSender $sender, string $arena, bool $isOpponentPos) : void{
		if($sender instanceof Player){
			if(PracticeCore::getArenaHandler()->doesArenaExist($arena)){
				if(PracticeCore::getArenaHandler()->isDuelArena($arena)){
					$theArena = PracticeCore::getArenaHandler()->getDuelArena($arena);
					$level = $sender->getWorld();

					if(PracticeUtil::areWorldEqual($level, $theArena->getWorld())){
						if($isOpponentPos){
							$theArena->setOpponentPos($sender->getPosition());
						}else{
							$theArena->setPlayerPos($sender->getPosition());
						}
						PracticeCore::getArenaHandler()->updateArena($arena, $theArena);
						$msg = PracticeUtil::getMessage("general.arena.update");
					}else{
						$msg = PracticeUtil::getMessage("general.arena.level");
					}
				}else{
					$msg = PracticeUtil::getMessage("general.arena.not-duel");
				}
			}else{
				$msg = PracticeUtil::getMessage("general.arena.arena-no-exist");
			}
			$msg = strval(str_replace("%arena-name%", $arena, $msg));
		}else{
			$msg = PracticeUtil::getMessage("console-usage-command");
		}

		if(!is_null($msg)) $sender->sendMessage($msg);
	}
}