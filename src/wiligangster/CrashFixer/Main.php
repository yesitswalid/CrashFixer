<?php
/**
 * Created by PhpStorm.
 * User: Walid
 * Date: 12/04/2019
 * Time: 02:41
 */

namespace wiligangster\CrashFixer;


use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\event\EventPriority;
use pocketmine\utils\TextFormat;

class Main extends PluginBase Implements Listener
{

    public $words;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
        if (!is_file($this->getDataFolder() . "worldlist.yml")) {
            $this->setWords(new Config($this->getDataFolder() . "wordlist.yml", Config::YAML, ["character_banned" => "舾狽ꑺ쑟賞㱎嶈랉昖๵᢫븳뺄◓勔㭫菈ꯚ䔷뎊넿冕㾇灕悫侠섪噮ꆏ镃㭋瀓縇ᛷ혲憹緩읡帼塏緘殣圛벢祘태뱤钿ﯲࠈ不ʨ펯濢꧶훟ᖗ왥䳹퐇卄㿈ꕩ盠ⴁ냚捗籈喝縣툝䖅賓調䵝躳筺열ዽਸ਼죛℈ⴄ෠釧홋퍆ᶾ罭豥⧕꫊븮믪郪橧䡻섧损埳鷎⫒믷鯵�꣼뀋狖�㓐梧ퟑ獼蟱㮒徫끵᭛拟ꖛ龾搣佄फ㴢䠠ഀ饟ဢ桉뢿嫅擭䞶붘জ秬䘻㤌蔀씦삣눝坻瓳䘘꼤䎮붊᝜伷ꑇ﷭폪渕큛䊉餟풼ĺ地쎲愊襆ꜳ٪뵓쬅竞鳇媷綢ↈ뻤횦벱桩삆Ὄ뤖⦗铨ᣜ㢀", "message_prevent" => true, "message" => TextFormat::RED . "It's not allowed to do that !", "kick" => false, "kick_message" => TextFormat::RED . "It's not allowed to do that !"]));
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
        foreach ($needle as $word) {
            for ($i = 0; $i < strlen($word); $i++) {
                if (strpos($this->getWords()->get("character_banned"), $word[$i]) !== false) {
                    $event->setCancelled(true);
                    if($this->getWords()->get("message_prevent")){
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
        for ($i = 0; $i < strlen($words); $i++) {
            if (strpos($this->getWords()->get("character_banned"), $words[$i]) !== false) {
                $event->setCancelled(true);
                if($this->getWords()->get("message_prevent")){
                    $player->sendMessage($this->getWords()->get("message"));
                }
                if ($this->getWords()->get("kick")) {
                    $event->getPlayer()->kick($this->getWords()->get("kick_message"), false);
                }
                break;
            }
        }
    }
}