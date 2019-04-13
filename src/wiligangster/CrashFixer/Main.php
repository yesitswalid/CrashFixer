<?php
/**
 * Created by PhpStorm.
 * User: Walid
 * Date: 12/04/2019
 * Time: 02:41
 */

namespace wiligangster\CrashFixer;


use pocketmine\block\Block;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\level\ChunkUnloadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerEditBookEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Sign;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase Implements Listener
{
    public $words;

    const COMMAND_HACK = [
        "/tell",
        "/w",
        "/me",
        "/whisper"
    ];

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
        if (!is_file($this->getDataFolder() . "wordlist.yml")) {
            $this->setWords(new Config($this->getDataFolder() . "wordlist.yml", Config::YAML, ["character_banned" => "๵᢫᝜﷭", "character_allowed" => ['é', 'à', 'ù', 'ç', 'è', '§'], "message_prevent" => true, "message" => TextFormat::RED . "It's not allowed to do that !", "kick" => false, "kick_message" => TextFormat::RED . "It's not allowed to do that !", "commands_suspect" => self::COMMAND_HACK]));
            $this->getLogger()->info("The config has been created !");
        } else {
            $this->setWords(new Config($this->getDataFolder() . "wordlist.yml"));
        }
    }

    /**
     * @return Config
     */
    public function getWords(): Config
    {
        return $this->words;
    }

    /**
     * @param Config $words
     */
    public function setWords(Config $words): void
    {
        $this->words = $words;
    }

    /**
     * @param SignChangeEvent $event
     * @ignoreCancelled true
     * @priority HIGH
     */
    public function onSign(SignChangeEvent $event): void
    {
        $needle = $event->getLines();
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if ($player instanceof Player) {
            foreach ($needle as $words) {
                if ($this->wordFinder($words)) {
                    $event->setCancelled(true);
                    $player->getLevel()->setBlock($block, Block::get(Block::AIR));
                    $player->getLevel()->dropItem($block, $block->getPickedItem());

                    if ($this->getWords()->get("message_prevent")) {
                        $player->sendMessage($this->getWords()->get("message"));

                    }
                    if ($this->getWords()->get("kick")) {
                        $player->kick($this->getWords()->get("kick_message"), false);
                    }
                    break;
                }
            }
        }
    }


    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event): void
    {
        $words = $event->getMessage();
        $player = $event->getPlayer();

        if ($this->wordFinder($words)) {
            $event->setCancelled(true);

            if ($this->getWords()->get("message_prevent")) {
                $player->sendMessage($this->getWords()->get("message"));
            }
            if ($this->getWords()->get("kick")) {
                $player->kick($this->getWords()->get("kick_message"), false);
            }
        }
    }

    /**
     * @param PlayerEditBookEvent $event
     */
    public function onEditBook(PlayerEditBookEvent $event) : void
    {
        $player = $event->getPlayer();
        $book = $event->getNewBook();

        foreach ($book->getPages() as $page) {
            if ($this->wordFinder($page->getString("text"))) {
                $event->setCancelled(true);

                $page->setString("text", "");

                if ($this->getWords()->get("message_prevent")) {
                    $player->sendMessage($this->getWords()->get("message"));
                }
                if ($this->getWords()->get("kick")) {
                    $player->kick($this->getWords()->get("kick_message"), false);
                }
                break;
            }
        }
    }

    /**
     * @param EntityInventoryChangeEvent $event
     */
    public function onEntityInventory(EntityInventoryChangeEvent $event): void
    {
        $item = $event->getNewItem();
        $player = $event->getEntity();

        if ($player instanceof Player) {
            if ($this->wordFinder($item->getCustomName())) {
                $event->setCancelled(true);

                $player->getInventory()->removeItem($item);

                if ($this->getWords()->get("message_prevent")) {
                    $player->sendMessage($this->getWords()->get("message"));
                }
                if ($this->getWords()->get("kick")) {
                    $player->kick($this->getWords()->get("kick_message"), false);
                }
            }
        }
    }


    /**
     * @param PlayerCommandPreprocessEvent $event
     */
    public function onPlayerCommand(PlayerCommandPreprocessEvent $event): void
    {
        $player = $event->getPlayer();
        $message = $event->getMessage();
        $args = explode(" ", $message);
        $cmd = array_shift($args);
        if (in_array($cmd, $this->getWords()->get("commands_suspect"))) {
            if ($this->wordFinder(implode(" ", $args))) {
                $event->setCancelled(true);
                if ($this->getWords()->get("message_prevent")) {
                    $player->sendMessage($this->getWords()->get("message"));
                }
                if ($this->getWords()->get("kick")) {
                    $player->kick($this->getWords()->get("kick_message"), false);
                }
            }
        }
    }


    /**
     * @param ChunkLoadEvent $event
     */
    public function onChunkLoad(ChunkLoadEvent $event) : void
    {
        $chunk = $event->getChunk();
        foreach ($chunk->getTiles() as $tile) {
            if ($tile instanceof Sign) {
                foreach ($tile->getText() as $text) {
                    if ($this->wordFinder($text)) {
                        $tile->setText("", "", "", "");
                    }
                }
            }
        }
    }


    /**
     * @param ChunkUnloadEvent $event
     */
    public function onChunkUnload(ChunkUnloadEvent $event) : void
    {
        $chunk = $event->getChunk();
        foreach ($chunk->getTiles() as $tile) {
            if ($tile instanceof Sign) {
                foreach ($tile->getText() as $text) {
                    if ($this->wordFinder($text)) {
                        $tile->setText("", "", "", "");
                    }
                }
            }
        }
    }


    /**
     * @param string $words
     * @return bool
     */
    public function wordFinder(string $words)
    {
        $find = false;
        $bypass = false;
        $charallowed = false;
        for ($i = 0; $i < strlen($words); $i++) {
            $convert = mb_convert_encoding($words[$i], "", 'UTF-8');
            if ($convert == "?") {
                $find = true;
            }
            if (in_array($words[$i], $this->getWords()->get('character_allowed'))) {
                $charallowed = true;
            }
            if (strpos($this->getWords()->get("character_banned"), $words[$i]) !== false) {
                $bypass = true;
            }
        }
        if ($find && $charallowed || $bypass) {
            return true;
        }
        return false;
    }
}