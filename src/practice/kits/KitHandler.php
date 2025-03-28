<?php

declare(strict_types=1);

namespace practice\kits;

use JsonException;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use practice\game\effects\PracticeEffect;
use practice\PracticeCore;
use practice\PracticeUtil;

class KitHandler{
	/** @var string */
	private string $configPath;
	/** @var Config */
	private Config $kitConfig;

	/* @var Kit[] */
	private array $kits;

	/* @var KitPvPSettings[] */
	private array $pvpSettings;

	/**
	 * @throws JsonException
	 */
	public function __construct(){
		$this->configPath = PracticeCore::getInstance()->getDataFolder() . "kits.yml";
		$this->initConfig();
		$this->initKits();
	}

	/**
	 * @return void
	 * @throws JsonException
	 */
	private function initConfig() : void{

		$this->kitConfig = new Config($this->configPath, Config::YAML, []);

		if(!$this->kitConfig->exists("kits")){
			$this->kitConfig->set("kits", []);
			$this->kitConfig->save();
		}

		if(!$this->kitConfig->exists("pvp")){
			$this->kitConfig->set("pvp", []);
			$this->kitConfig->save();
		}
	}

	/**
	 * @return void
	 * @throws JsonException
	 */
	private function initKits() : void{
		$this->kits = [];

		$this->pvpSettings = [];

		$kitKeys = $this->getConfig()->get("kits");

		$pvpSettings = $this->getConfig()->get("pvp");

		$keys = array_keys($kitKeys);

		foreach($keys as $key){

			if($this->isKitFromCfg($key)){

				$kit = $this->getKitFromCfg($key);
				$this->kits[$key] = $kit;

				$name = $kit->getName();

				if(!$this->hasKitSettingInCfg($name)){
					$kitSettings = new KitPvPSettings();
					$pvpSettings[$name] = $kitSettings->toMap();
					$this->pvpSettings[$name] = $kitSettings;
					$this->getConfig()->set("pvp", $pvpSettings);
					$this->getConfig()->save();
				}else{
					$kitSettings = $this->getKitSettingInCfg($name);
					$this->pvpSettings[$name] = $kitSettings;
				}
			}
		}
	}

	/**
	 * @return Config
	 */
	public function getConfig() : Config{
		return $this->kitConfig;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	private function isKitFromCfg(string $name) : bool{
		return !is_null($this->getKitFromCfg($name));
	}

	/**
	 * @param string $name
	 *
	 * @return Kit|null
	 */
	private function getKitFromCfg(string $name) : ?Kit{
		$baseArr = $this->getConfig()->get("kits");

		$kitArmor = null;
		$kitItems = null;
		$kitEffects = null;
		$kitRepItem = VanillaItems::AIR();

		$baseKit = null;

		if(isset($baseArr[$name])){

			$kitArmor = [];
			$kitItems = [];
			$kitEffects = [];

			$kitMap = $baseArr[$name];

			if(is_array($kitMap) and PracticeUtil::arr_contains_keys($kitMap, "armor", "items", "effects", "rep-item")){

				$armor = $kitMap["armor"];

				$items = $kitMap["items"];

				$effects = $kitMap["effects"];

				$repItem = $kitMap["rep-item"];

				if(is_string($repItem)){
					$obj = $repItem;
					$item = PracticeUtil::getItemFromString($obj);
					if(!is_null($item)){
						$kitRepItem = $item;
					}
				}

				if(is_array($items)){

					$size = count($items);

					for($i = 0; $i < $size; $i++){
						$obj = strval($items[$i]);
						$item = PracticeUtil::getItemFromString($obj);
						if(!is_null($item)){
							$kitItems[] = $item;
						}
					}
				}

				if(is_array($armor)){

					$keys = array_keys($armor);

					foreach($keys as $key){
						$obj = $armor[$key];
						if(is_string($obj)){
							$item = PracticeUtil::getItemFromString($obj);
							if(!is_null($item)){
								$kitArmor[$key] = $item;
							}
						}
					}
				}

				if(is_array($effects)){

					$size = count($effects);

					for($i = 0; $i < $size; $i++){
						$obj = strval($effects[$i]);
						$effect = PracticeEffect::getEffectFrom($obj);
						if(!is_null($effect)) $kitEffects[] = $effect;
					}
				}
			}
		}

		if(!is_null($kitArmor) and !is_null($kitItems) and !is_null($kitEffects)){
			$baseKit = new Kit($name, $kitItems, $kitArmor, $kitEffects, $kitRepItem);
		}
		return $baseKit;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	private function hasKitSettingInCfg(string $name) : bool{
		return !is_null($this->getKitSettingInCfg($name));
	}

	/**
	 * @param string $name
	 *
	 * @return KitPvPSettings|null
	 */
	private function getKitSettingInCfg(string $name) : ?KitPvPSettings{
		$baseArr = $this->getConfig()->get("pvp");

		$result = null;

		if(isset($baseArr[$name])){

			$settingsArr = $baseArr[$name];

			if(PracticeUtil::arr_contains_keys($settingsArr, "kb", "attack-delay")){

				$kb = floatval($settingsArr["kb"]);
				$attackDel = intval($settingsArr["attack-delay"]);

				$result = new KitPvPSettings($kb, $attackDel);
			}
		}

		return $result;
	}

	/**
	 * @param string $name
	 * @param Player $player
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function createKit(string $name, Player $player) : void{
		$inv_arr = PracticeUtil::inventoryToArray($player);
		$itemArr = $inv_arr["items"];
		$armorArr = $inv_arr["armor"];

		$kit = new Kit($name, $itemArr, $armorArr);

		$this->kits[$name] = $kit;
		$map = $kit->toMap();
		$kitObj = $this->getConfig()->get("kits");

		if(!isset($kitObj[$name])){
			$kitObj[$name] = $map;
			$this->getConfig()->set("kits", $kitObj);
			$this->getConfig()->save();
		}

		$kitSettings = new KitPvPSettings();
		$settingsMap = $kitSettings->toMap();

		$settingsObj = $this->getConfig()->get("pvp");
		if(!isset($settingsObj[$name])){
			$settingsObj[$name] = $settingsMap;
			$this->getConfig()->set("pvp", $settingsObj);
			$this->getConfig()->save();
		}
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 * @throws JsonException
	 */
	public function removeKit(string $name) : bool{
		$kitObj = $this->getConfig()->get("kits");
		$result = false;

		if(isset($kitObj[$name])){

			unset($kitObj[$name], $this->kits[$name]);
			$result = true;
			$execute = PracticeUtil::isMysqlEnabled();

			if($execute === true) PracticeCore::getMysqlHandler()->removeEloColumn($name);
			else PracticeCore::getPlayerHandler()->removeEloKit($name);

			$this->getConfig()->set("kits", $kitObj);
			$this->getConfig()->save();
		}

		$settingsObj = $this->getConfig()->get("pvp");

		if(isset($settingsObj[$name])){
			unset($settingsObj[$name], $this->pvpSettings[$name]);
			$this->getConfig()->set("pvp", $settingsObj);
			$this->getConfig()->save();
		}

		return $result;
	}

	/**
	 * @param string $name
	 * @param Kit    $kit
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function updateKit(string $name, Kit $kit) : void{
		$obj = $this->getConfig()->get("kits");
		if(isset($obj[$name])){
			$this->kits[$name] = $kit;
			$obj[$name] = $kit->toMap();
			$this->getConfig()->set("kits", $obj);
			$this->getConfig()->save();
		}
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function hasKitSetting(string $name) : bool{
		return !is_null($this->getKitSetting($name));
	}

	/**
	 * @param string $name
	 *
	 * @return KitPvPSettings|null
	 */
	public function getKitSetting(string $name) : ?KitPvPSettings{
		$result = null;

		if(isset($this->pvpSettings[$name]))
			$result = $this->pvpSettings[$name];

		return $result;
	}

	/**
	 * @return string
	 */
	public function getListKitMsg() : string{
		$obj = $this->getConfig()->get("kits");
		$msg = PracticeUtil::getMessage("general.kits.list");
		$replaced = null;
		if(count($obj) > 0){
			$replaced = "";
			$count = 0;
			$len = count($obj) - 1;

			$keys = array_keys($obj);

			foreach($keys as $key){
				$str = strval($key);
				$comma = ($count === $len ? "" : ", ");
				$replaced .= $str . $comma;
				$count++;
			}
		}

		if(is_null($replaced) or strlen($replaced) === 0){
			$replaced = "None";
		}
		return strval(str_replace("%kit%", $replaced, $msg));
	}

	/**
	 * @param string $kit
	 * @param bool   $database
	 *
	 * @return bool
	 */
	public function isDuelKit(string $kit, bool $database = false) : bool{

		$duelKits = $this->getDuelKitNames(false, $database);

		return PracticeUtil::arr_contains_value($kit, $duelKits);
	}

	/**
	 * @param bool $checkForRepItem
	 * @param bool $database
	 * @param bool $withKeys
	 *
	 * @return array
	 */
	public function getDuelKitNames(bool $checkForRepItem = false, bool $database = false, bool $withKeys = false) : array{

		$kits = $this->getDuelKits();

		$result = [];

		foreach($kits as $kit){

			$exec = !($checkForRepItem === true) || $kit->hasRepItem();

			if($exec === true){
				$str = ($database === false) ? $kit->getName() : $kit->getLocalizedName();
				if($withKeys) $result[$str] = $kit->getName();
				else $result[] = $str;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getDuelKits() : array{
		$duelArenas = PracticeCore::getArenaHandler()->getDuelArenas();

		$kits = [];

		foreach($duelArenas as $arena){
			$arenaKits = $arena->getKits();
			foreach($arenaKits as $kit){
				if(is_string($kit) and $this->isKit($kit)){
					$theKit = $this->getKit($kit);
					if(!PracticeUtil::arr_contains_value($theKit, $kits)) $kits[] = $theKit;
				}
			}
		}

		return $kits;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function isKit(string $name) : bool{
		return !is_null($this->getKit($name));
	}

	/**
	 * @param string $name
	 *
	 * @return Kit|null
	 */
	public function getKit(string $name) : ?Kit{
		$result = null;
		if(isset($this->kits[$name]))
			$result = $this->kits[$name];
		return $result;
	}

	/**
	 * @return array
	 */
	public function getFFAArenasWKits() : array{
		$ffaArenas = PracticeCore::getArenaHandler()->getFFAArenas();
		$result = [];

		foreach($ffaArenas as $arena){
			if($arena->doesHaveKit())
				$result[] = $arena;
		}

		return $result;
	}
}