<?php

namespace practice\commands\basic;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\player\Player;
use practice\PracticeCore;
use practice\PracticeUtil;

class HealCommand extends Command{
	public function __construct(){
		parent::__construct("heal", "Heals yourself or another player.", "Usage: /heal [target:player]", []);
		parent::setPermission("practice.permission.heal");
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param string[]      $args
	 *
	 * @return bool
	 * @throws CommandException
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		$msg = null;
		if(PracticeUtil::canExecBasicCommand($sender)){

			if(PracticeUtil::testPermission($sender, $this->getPermissions()[0])){

				$len = count($args);
				$player = null;

				if($len === 0){
					if($sender instanceof Player){
						$player = $sender;
					}else{
						$msg = PracticeUtil::getMessage("console-usage-command");
					}
				}else if($len === 1){
					$name = $args[0];
					if(PracticeCore::getPlayerHandler()->isPlayerOnline($name)){
						$player = PracticeCore::getPlayerHandler()->getPlayer($name)->getPlayer();
					}else{
						$msg = PracticeUtil::getMessage("not-online");
						$msg = strval(str_replace("%player-name%", $name, $msg));
					}
				}else{
					$msg = $this->getUsage();
				}

				if(!is_null($player)){

					$player->setHealth($player->getMaxHealth());

					if($player->getName() === $sender->getName()){
						$msg = PracticeUtil::getMessage("general.heal.success-direct");
					}else{
						$msg = PracticeUtil::getMessage("general.heal.success-op");
						$msg = strval(str_replace("%player%", $player->getName(), $msg));
						$player->sendMessage(PracticeUtil::getMessage("general.heal.success-direct"));
					}
				}
			}
		}

		if(!is_null($msg)) $sender->sendMessage($msg);
		return true;
	}
}