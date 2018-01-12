<?php
namespace nagi;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\Server;

class WarpSystem extends PluginBase implements Listener{

	const TAG = "§f[§aWS§f]";

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getLogger()->info(self::TAG."§aWarpSystemを読み込みました");
		if(!file_exists($this->getDataFolder())){
			mkdir($this->getDataFolder(), 0744, true);
		}
		$this->warps = new Config($this->getDataFolder() ."warps.yml", Config::YAML);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!$sender instanceof Player){
			$sender->sendMessage(self::TAG."§cゲーム内で使用して下さい");
			return true;
		}
		switch($command->getName()){
			case "warp":
			if(!isset($args[0])){
				if($sender->isOp()){
					$sender->sendMessage("使い方 : /warp <ワープ地点名> [プレイヤー名]");
				}else{
					$sender->sendMessage("使い方 : /warp <ワープ地点名>");
				}
			}else{
				if($this->warps->exists($args[0])){
					if($sender->isOp()){
						if(isset($args[1])){
							$player = $this->getServer()->getPlayer($args[1]);
							if($player instanceof Player){
								$this->Warp($player, $args[0]);
								$sender->sendMessage(self::TAG."§e".$args[1]."§bを§a".$args[0]."§bにワープさせました");
							}else{
								$sender->sendMessage(self::TAG."§e".$args[1]."§cはサーバー内にいません");
							}
						}else{
							$this->Warp($sender, $args[0]);
							$sender->sendMessage(self::TAG."§a".$args[0]."§bにワープしました");
						}
					}else if($this->isOpenedWarp($args[0])){
						$this->Warp($sender, $args[0]);
						$sender->sendMessage(self::TAG."§a".$args[0]."§bにワープしました");
					}else{
						$sender->sendMessage(self::TAG."§a".$args[0]."§cにワープする権限がありません");
					}
				}else{
					$sender->sendMessage(self::TAG."§a".$args[0]."§cというワープ地点は存在しません");
				}
			}
			return true;

			case "addwarp":
			if(!isset($args[0])){
				$sender->sendMessage("使い方 : /addwarp <ワープ地点名> [<x> <y> <z> <ワールド名>|<プレイヤー名>]");
			}else if(!isset($args[1])){
				$x = $sender->x;
				$y = $sender->y;
				$z = $sender->z;
				$level = $sender->getLevel()->getName();
				if(!$this->warps->exists($args[0])){
					$this->AddWarp($args[0], $x, $y, $z, $level);
					$sender->sendMessage(self::TAG."§bワープ地点§a".$args[0]."§bを作成しました§f(X:".$x." Y:".$y." Z:".$z." ワールド:".$level.")");
				}else{
					$sender->sendMessage(self::TAG."§cワープ地点§a".$args[0]."§cは既に存在します");
				}
			}else if(!isset($args[2])){
				$player = $this->getServer->getPlayer($args[1]);
				if($player instanceof Player){
					$x = $player->x;
					$y = $player->y;
					$z = $player->z;
					$level = $player->getLevel()->getName();
					if(!$this->warps->exists($args[0])){
						$this->AddWarp($args[0], $x, $y, $z, $level);
						$sender->sendMessage(self::TAG."§bワープ地点§a".$args[0]."§bを作成しました§f(X:".$x." Y:".$y." Z:".$z." ワールド:".$level.")");
					}else{
						$sender->sendMessage(self::TAG."§cワープ地点§a".$args[0]."§cは既に存在します");
					}
				}else{
					$sender->sendMessage(self::TAG."§e".$args[1]."§cはサーバー内にいません");
				}
			}else if(!isset($args[4])){
				$sender->sendMessage("使い方 : /addwarp <ワープ地点名> <x> <y> <z> <ワールド名>");
			}else{
				$x = (int) $args[1];
				$y = (int) $args[2];
				$z = (int) $args[3];
				$level = $args[4];
				if(!$this->warps->exists($args[0])){
					if($this->getServer()->loadLevel($level)){
						$this->AddWarp($args[0], $x, $y, $z, $level);
						$sender->sendMessage(self::TAG."§bワープ地点§a".$args[0]."§bを作成しました§f(X:".$x." Y:".$y." Z:".$z." ワールド:".$level.")");
					}else{
						$player->sendMessage(self::TAG."§cワールド§e".$level."§cは存在しません");
					}
				}else{
					$sender->sendMessage(self::TAG."§c座標は数字で入力して下さい");
				}
			}else{
				$sender->sendMessage(self::TAG."§cワープ地点§a".$args[0]."§cは既に存在します");
			}
			return true;

			case "delwarp":
			if(!isset($args[0])){
				$sender->sendMessage("使い方 : /delwarp <ワープ地点名>");
			}else{
				if($this->warps->exists($args[0])){
					$this->DelWarp($args[0]);
					$sender->sendMessage(self::TAG."§bワープ地点§a".$args[0]."§bを§c削除§bしました");
				}else{
					$sender->sendMessage(self::TAG."§bワープ地点§a".$args[0]."§cは存在しません");
				}
			}
			return true;

			case "openwarp":
			if(!isset($args[0])){
				$sender->sendMessage("使い方 : /openwarp <ワープ地点名>");
			}else{
				if($this->warps->exists($args[0])){
					$this->OpenWarp($args[0]);
					$sender->sendMessage(self::TAG."§bワープ地点§a".$args[0]."§bを§e開放§bしました");
				}else{
					$sender->sendMessage(self::TAG."§bワープ地点§a".$args[0]."§cは存在しません");
				}
			}
			return true;

			case "closewarp":
			if(!isset($args[0])){
				$sender->sendMessage("使い方 : /closewarp <ワープ地点名>");
			}else{
				if($this->warps->exists($args[0])){
					$this->CloseWarp($args[0]);
					$sender->sendMessage(self::TAG."§bワープ地点§a".$args[0]."§bを§c閉鎖§bしました");
				}else{
					$sender->sendMessage(self::TAG."§bワープ地点§a".$args[0]."§cは存在しません");
				}
			}
			return true;

			case "listwarps":
			$sender->sendMessage("§bワープ地点リスト---------------------");
			foreach($this->warps->getAll() as $b=>$a){
				$x = $this->warps->getAll()[$b]["x"];
				$y = $this->warps->getAll()[$b]["y"];
				$z = $this->warps->getAll()[$b]["z"];
				$level = $this->warps->getAll()[$b]["level"];
				$public = $this->warps->getAll()[$b]["public"];
				$message = "§f".$b."(X:".$x." Y:".$y." Z:".$z." ワールド:".$level;
				if($sender->isOp()){
					if($public){
						$public = "開放";
					}else{
						$public = "閉鎖";
					}
					$message = $message." 状態:".$public.")";
					$sender->sendMessage($message);
				}else{
					$message = $message.")";
					if($this->warps->getAll()[$b]["public"]){
						$sender->sendMessage($message);
					}
				}
			}
			$sender->sendMessage(" ");
		}
		return true;
	}

	public function Warp($player, $warpname){
		if($this->warps->exists($warpname)){
			$levelname = $this->warps->getAll()[$warpname]["level"];
			if($this->getServer()->loadLevel($levelname)){
				$x = $this->warps->getAll()[$warpname]["x"];
				$y = $this->warps->getAll()[$warpname]["y"];
				$z = $this->warps->getAll()[$warpname]["z"];
				$level = $this->getServer()->getLevelByName($levelname);
				$pos = new Position($x, $y, $z, $level);
				$player->teleport($pos);
				return true;
			}else{
				$player->sendMessage(self::TAG."§cワールド§e".$levelname."§cは存在しません");
				return false;
			}
		}else{
			return false;
		}
	}

	public function AddWarp($warpname, $x, $y, $z, $level){
		if(isset($warpname) && isset($x) && isset($y) && isset($z) && isset($level) && !$this->warps->exists($warpname)){
			$this->warps->set($warpname, array(
				"x"=>$x,
				"y"=>$y,
				"z"=>$z,
				"level"=>$level,
				"public"=>true,
				"metadata"=>array()
			));
			$this->warps->save();
			return true;
		}else{
			return false;
		}
	}

	public function DelWarp($warpname){
		if($this->warps->exists($warpname)){
			$this->warps->remove($warpname);
			$this->warps->save();
			return true;
		}else{
			return false;
		}
	}

	public function OpenWarp($warpname){
		if($this->warps->exists($warpname)){
			$this->warps->set($warpname, array(
				"x"=>$this->warps->getAll()[$warpname]["x"],
				"y"=>$this->warps->getAll()[$warpname]["y"],
				"z"=>$this->warps->getAll()[$warpname]["z"],
				"level"=>$this->warps->getAll()[$warpname]["level"],
				"public"=>true,
				"metadata"=>$this->warps->getAll()[$warpname]["metadata"]
			));
			$this->warps->save();
			return true;
		}else{
			return false;
		}
	}

	public function CloseWarp($warpname){
		if($this->warps->exists($warpname)){
			$this->warps->set($warpname, array(
				"x"=>$this->warps->getAll()[$warpname]["x"],
				"y"=>$this->warps->getAll()[$warpname]["y"],
				"z"=>$this->warps->getAll()[$warpname]["z"],
				"level"=>$this->warps->getAll()[$warpname]["level"],
				"public"=>false,
				"metadata"=>$this->warps->getAll()[$warpname]["metadata"]
			));
			$this->warps->save();
			return true;
		}else{
			return false;
		}
	}

	public function isOpenedWarp($warpname){
		if($this->warps->exists($warpname)){
			if($this->warps->getAll()[$warpname]["public"]){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}
