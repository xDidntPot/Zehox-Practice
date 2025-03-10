<?php

declare(strict_types=1);

namespace practice\commands\basic;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\player\Player;
use practice\PracticeCore;
use practice\PracticeUtil;

class ExtinguishCommand extends Command{
	public function __construct(){
		parent::__construct("ext", "The extinguish command.", "Usage: /ext [target:player]", []);
		self::setPermission("practice.permission.ext");
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
					$player->extinguish();
					if($player->getName() === $sender->getName()){
						$msg = PracticeUtil::getMessage("general.ext.personal");
					}else{
						$msg = PracticeUtil::getMessage("general.ext.player");
						$msg = strval(str_replace("%player%", $player->getName(), $msg));
						$player->sendMessage(PracticeUtil::getMessage("general.ext.personal"));
					}
				}
			}
		}

		if(!is_null($msg)) $sender->sendMessage($msg);
		return true;
	}
}