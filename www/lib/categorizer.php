<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/groups.php");

class Categorizer
{
	const CAT_GAME_NDS = 1010;
	const CAT_GAME_PSP = 1020;
	const CAT_GAME_WII = 1030;
	const CAT_GAME_XBOX = 1040;
	const CAT_GAME_XBOX360 = 1050;
	const CAT_GAME_WIIWARE = 1060;
	const CAT_GAME_XBOX360DLC = 1070;
	const CAT_GAME_PS3 = 1080;
	const CAT_MOVIE_FOREIGN = 2010;
	const CAT_MOVIE_OTHER = 2020;
	const CAT_MOVIE_SD = 2030;
	const CAT_MOVIE_HD = 2040;
	const CAT_MUSIC_MP3 = 3010;
	const CAT_MUSIC_VIDEO = 3020;
	const CAT_MUSIC_AUDIOBOOK = 3030;
	const CAT_MUSIC_LOSSLESS = 3040;
	const CAT_PC_0DAY = 4010;
	const CAT_PC_ISO = 4020;
	const CAT_PC_MAC = 4030;
	const CAT_PC_PHONE = 4040;
	const CAT_PC_GAMES = 4050;
	const CAT_TV_FOREIGN = 5020;
	const CAT_TV_SD = 5030;
	const CAT_TV_HD = 5040;
	const CAT_TV_OTHER = 5050;
	const CAT_TV_SPORT = 5060;
	const CAT_TV_ANIME = 5070;
	const CAT_XXX_DVD = 6010;
	const CAT_XXX_WMV = 6020;
	const CAT_XXX_XVID = 6030;
	const CAT_XXX_X264 = 6040;
	const CAT_MISC = 7010;
	const CAT_MISC_EBOOK = 7020;
	const CAT_MISC_COMICS = 7030;

	const CAT_PARENT_GAME = 1000;
	const CAT_PARENT_MOVIE = 2000;
	const CAT_PARENT_MUSIC = 3000;
	const CAT_PARENT_PC = 4000;
	const CAT_PARENT_TV = 5000;
	const CAT_PARENT_XXX = 6000;
	const CAT_PARENT_MISC = 7000;

	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_DISABLED = 2;

	private $tmpCat = 0;
	
	//
	// Work out which category is applicable for either a group or a binary.
	// returns -1 if no category is appropriate from the group name.
	//
	public function Categorize($releasename = "", $groupID)
	{     
		//                       
		//Try against all functions, if still nothing, return Cat Misc.
		//
		
		if($this->byGroup($releasename, $groupID)){ return $this->tmpCat; }
		if($this->isPC($releasename)){ return $this->tmpCat; }
		if($this->isTV($releasename)){ return $this->tmpCat; }
		if($this->isMovie($releasename)){ return $this->tmpCat; }
		if($this->isXXX($releasename)){ return $this->tmpCat; }
		if($this->isConsole($releasename)){ return $this->tmpCat; }
		if($this->isMusic($releasename)){ return $this->tmpCat; }
		if($this->isEBook($releasename)){ return $this->tmpCat; }
		if($this->isComic($releasename)){ return $this->tmpCat; }
		if($this->isHashed($releasename)){ return $this->tmpCat; }
		
		return Category::CAT_MISC;
	}
	
	//
	// Beginning of functions to determine category by release name
	//
	
	public function byGroup($releasename, $groupID)
	{
		$groups = new groups();
		$groupRes = $groups->getByID($groupID);
		
		foreach ($groupRes as $groupRows)
		{
			if (preg_match('/alt\.binaries\.0day\.stuffz/', $groupRes["name"]))
			{
				if($this->isEBook($releasename)){ return $this->tmpCat; }
				if($this->isPC($releasename)){ return $this->tmpCat; }
				if($this->isHashed($releasename)){ return $this->tmpCat; }
				$this->tmpCat = Category::CAT_PC_0DAY;
				return $this->tmpCat;
			}
			
			if (preg_match('/alt\.binaries\.audio\.warez/', $groupRes["name"]))
			{
				if($this->isHashed($releasename)){ return $this->tmpCat; }
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}
			
			if (preg_match('/alt\.binaries\.(multimedia\.)?anime(\.(highspeed|repost))?/', $groupRes["name"]))
			{
				if($this->isHashed($releasename)){ return $this->tmpCat; }
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}
			
			if (preg_match('/alt\.binaries\.cd\.lossless/', $groupRes["name"]))
			{
				if($this->isHashed($releasename)){ return $this->tmpCat; }
				$this->tmpCat =  Category::CAT_MUSIC_LOSSLESS;
				return true;
			}
		}
	}

	//
	// TV
	//
	public function isTV2($releasename, $assumeTV=TRUE)
	{

		// check if it looks like TV
		$looksLikeTV = preg_match('/\W+((S\d[\dE._ -])|(\d\d?x)|(\d{4}\W+\d\d\W+\d\d)|((part|pt)[\._ -]?(\d|[ivx])(?!.*(19|20)\d{2}))|(Season\W+\d+\W+)|(E(p?(isode)?[\._ -]*?)\d+\W+)).*/i', $releasename);

		// anything dsr|pdtv,
		$knownTVSources = preg_match('/(dsr|pdtv)/i', $releasename);

		// hdtv, commonly also movies
		$possibleTVSources = preg_match('/hdtv/i', $releasename);

		// if it looks like a TV episode
		// or if it's from a TV source
		// or if it's possibly TV and we have reason to believe it really is
		if ($looksLikeTV || $knownTVSources || ($possibleTVSources && ($assumeTV || $looksLikeTV)))
		{
			if($this->isForeignTV($releasename)){ return true; }
			if($this->isSportTV($releasename)){ return true; }
			if($this->isHDTV($releasename)){ return true; }
			if($this->isSDTV($releasename)){ return true; }
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}

		return false;
	}
	
	public function isTV($releasename, $assumeTV=TRUE)
	{

		// check if it looks like TV
		$looksLikeTV = preg_match('/[\.\-_ ](\dx\d\d|s\d{1,2}[.-_ ]?e\d{1,2}|DSR|(D|H|P)DTV|WEB\-DL)[\.\-_ ]/i', $releasename);
		
		// Sports
		$looksLikeSportTV = preg_match('/[\.\-_ ]((19|20)\d\d[\.\-_ ]\d{1,2}[\.\-_ ]\d{1,2}[\.\-_ ]VHSRip|(Per|Post)\-Show|PPV|WrestleMania|WEB[\.\-_ ]HD|WWE[\.\-_ ](Monday|NXT|RAW|Smackdown|Superstars|WrestleMania))[\.\-_ ]/i', $releasename);

		//Anime
		//$looksLikeAnimeTV = preg_match('/[\.\-_ ]placeholder[\.\-_ ]/i', $releasename);
		
		if ($looksLikeTV)
		{
			if($this->isForeignTV($releasename)){ return true; }
			if($this->isSportTV($releasename)){ return true; }
			if($this->isHDTV($releasename)){ return true; }
			if($this->isSDTV($releasename)){ return true; }
			if($this->isAnimeTV($releasename)){ return true; }
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		if ($looksLikeSportTV)
		{
			if($this->isSportTV($releasename)){ return true; }
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		/*if ($looksLikeAnimeTV)
		{
			if($this->isAnimeTV($releasename)){ return true; }
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}*/

		return false;
	}
	
	public function isForeignTV($releasename)
	{
		if(preg_match('/[\.\-_ ](chinese|dk|fin|french|ger|heb|ita|jap|kor|nor|nordic|nl|pl|swe)[\.\-_ ]?(sub|dub)(ed|bed|s)?[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_FOREIGN;
			return true;
		}
		if(preg_match('/[\.\-_ ](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish).+(DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Seizoen|Sub(bed|s)?|Web[\.\-_ ]?Rip)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_FOREIGN;
			return true;
		}
		if(preg_match('/[\.\-_ ](DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Seizoen|Sub(bed|s)?|Web[\.\-_ ]?Rip).+(brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_FOREIGN;
			return true;
		}
		if(preg_match('/(S\d\dE\d\d|DOCU(MENTAIRE)?|TV)?[\.\-_ ](FRENCH|German|Dutch)[\.\-_ ](720p|dv(b|d)r(ip)?|LD|HD\-?TV|TV[\.\-_ ]?RIP|x264)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_FOREIGN;
			return true;
		}
		if(preg_match('/[\.\-_ ]FastSUB|NL|nlvlaams|patrfa|slosinh|Videomann|Vostfr|xslidian[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_FOREIGN;
			return true;
		}
		
		return false;
	}

	public function isSportTV($releasename)
	{
		if(preg_match('/s\d{1,2}[.-_ ]?e\d{1,2}/i', $releasename))
		{
			return false;
		}
		if(preg_match('/[\.\-_ ]?(Bellator|bundesliga|EPL|ESPN|FIA|la[\.\-_ ]liga|MMA|motogp|NFL|NCAA|PGA|red[\.\-_ ]bull.+race|Sengoku|Strikeforce|supercup|uefa|UFC|wtcc|WWE)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_SPORT;
			return true;
		}
		if(preg_match('/[\.\-_ ]?(DTM|FIFA|formula[\.\-_ ]1|indycar|Rugby|NASCAR|NBA|NHL|NRL|netball[\.\-_ ]anz|ROH|SBK|Superleague|The[\.\-_ ]Ultimate[\.\-_ ]Fighter|TNA|V8[\.\-_ ]Supercars|WBA|WrestleMania)[\.\-_ ]/i', $releasename))
		{	
			$this->tmpCat = Category::CAT_TV_SPORT;
			return true;
		}
		if(preg_match('/[\.\-_ ]?(AFL|Grand Prix|(iMPACT|Smoky[\.\-_ ]Mountain)[\.\-_ ]Wrestling|Poker|PWX|Rugby|WCW)[\.\-_ ]/i', $releasename))
		{	
			$this->tmpCat = Category::CAT_TV_SPORT;
			return true;
		}
		if(preg_match('/[\.\-_ ]?(Horse)[\.\-_ ]Racing[\.\-_ ]/i', $releasename))
		{	
			$this->tmpCat = Category::CAT_TV_SPORT;
			return true;
		}
		
		return false;
	}

	public function isHDTV($releasename)
	{
		if (preg_match('/1080(i|p)|720|h[\.\-_ ]264|web[\.\-_ ]dl/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_HD;
			return true;
		}
		
		return false;
	}

	public function isSDTV($releasename)
	{
		if (preg_match('/480p|dvdr|dvd5|dvd9|SD[\.\-_ ]TV|xvid/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}
		if (preg_match('/((H|P)D[\.\-_ ]?TV|DSR|WebRip)[\.\-_ ]x264/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}
		if (preg_match('/s\d{1,2}[.-_ ]?e\d{1,2}|\s\d{3,4}\s/i', $releasename))
		{
			if (preg_match('/(H|P)D[\.\-_ ]?TV|BDRip[\.\-_ ]x264/i', $releasename))
			{
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}
		}
		
		return false;
	}
	
	public function isAnimeTV($releasename)
	{
		if (preg_match('/^\(\[AST\]\s|\[HorribleSubs\]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_ANIME;
			return true;
		}
		
		return false;
	}


	//
	//  Movie
	//
	public function isMovie($releasename)
	{
		$looksLikeMovie = preg_match('/(B|H)(D|R)RIP|Bluray|BD[\.\-_ ]?(25|50)?|BR|DIVX|DVDRip|XVID/i', $releasename);
		
		if($looksLikeMovie)
		{
			if($this->isMovieForeign($releasename)){ return true; }
			if($this->isMovieSD($releasename)){ return true; }
			if($this->isMovieHD($releasename)){ return true; }
		}
		
		return false;
	}

	public function isMovieForeign($releasename)
	{
		if(preg_match('/(danish|flemish|Deutsch|dutch|nl\.?subbed|nl\.?sub|\.NL|norwegian|swedish|swesub|french|german|spanish)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		if(preg_match('/ der |Deutsch|\d{4} German|\(German\)|NLSubs|NL\-Subs|NLSub/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		} 
		
		return false;
	}

	public function isMovieHD($releasename)
	{
		if(preg_match('/x264|bluray\-|wmvhd|web\-dl|bd?25|bd?50|blu-ray|VC1|VC\-1|AVC|XvidHD/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MOVIE_HD;
			return true;
		}
		
		return false;
	}

	public function isMovieSD($releasename)
	{
		if(preg_match('/(xvid|dvdscr|extrascene|dvdrip|r5|\.CAM|dvdr|dvd9|dvd5|divx)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MOVIE_SD;
			return true;
		}
		
		return false;
	}


	//
	//  PC
	//
	public function isPC($releasename)
	{
		if(!preg_match('/PDTV|x264/i', $releasename))
		{
			if($this->isPhone($releasename)){ return true; }
			if($this->isMac($releasename)){ return true; }
			if($this->isPCGame($releasename)){ return true; }
			if($this->is0day($releasename)){ return true; }
		}
		
		return false;
	}

	public function isPhone($releasename)
	{
		if (preg_match('/[\.\-_ ]?(IPHONE|ITOUCH|ANDROID|COREPDA|symbian|xscale)[\.\-_ ]?/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_PHONE;
			return true;
		}
		if (preg_match('/IPHONE|ITOUCH|IPAD|ANDROID|COREPDA|symbian|xscale|wm5|wm6/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_PHONE;
			return true;
		}
		return false;
	}

	public function is0day($releasename)
	{
		if(preg_match('/[\.\-_ ](32bit|64bit|x32|x64|x86|win64|winnt|win9x|win2k|winxp|winnt2k2003serv|win9xnt|win9xme|winnt2kxp|win2kxp|win2kxp2k3|keygen|regged|keymaker|winall|win32|template|Patch|GAMEGUiDE|unix|irix|solaris|freebsd|hpux|linux|windows|multilingual|software|Pro v\d{1,3})[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}
		else if (preg_match('/\-SUNiSO|Adobe|CYGNUS|GERMAN\-|v\d{1,3}.*?Pro|v\d{1,3}.*?\-TE|MULTiLANGUAGE|Cracked|lz0|\-BEAN|MultiOS|\-iNViSiBLE|\-SPYRAL|WinAll|Keymaker|Keygen|Lynda\.com|FOSI|Keyfilemaker|DIGERATI|\-UNION/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}
		return false;
	}

	public function isMac($releasename)
	{
		if(preg_match('/mac(\.|\s)?osx/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_MAC;
			return true;
		}
		return false;
	}

	public function isPCGame($releasename)
	{
		if (preg_match('/FASDOX|games|PC GAME|RIP\-unleashed|Razor1911/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_GAMES;
			return true;
		}
		if (preg_match('/\-(0x0007|ALiAS|BACKLASH|BAT|CPY|FASiSO|FLTCOGENT|GENESIS|HI2U|JAGUAR|MAZE|MONEY|OUTLAWS|PPTCLASSiCS|PROPHET|RAiN|RELOADED|RiTUELYPOGEiOS|SKIDROW|TiNYiSO)/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_GAMES;
			return true;
		}
		return false;
	}


	//
	//   XXX
	//
	public function isXxx($releasename)
	{
		if(preg_match('/XXX|PORNOLATiON/', $releasename))
		{
			if($this->isXxx264($releasename)){ return true; }
			if($this->isXxxXvid($releasename)){ return true; }
			if($this->isXxxWMV($releasename)){ return true; }
			if($this->isXxxDVD($releasename)){ return true; }
			$this->tmpCat = Category::CAT_XXX_XVID;
			return true;
		}
		return false;
	}

	public function isXxx264($releasename)
	{
		if (preg_match('/x264/i', $releasename))
		{
			$this->tmpCat = Category::CAT_XXX_X264;
			return true;
		}
		return false;
	}

	public function isXxxXvid($releasename)
	{
		if (preg_match('/xvid|dvdrip|bdrip|brrip|pornolation|swe6|nympho|detoxication|tesoro/i', $releasename))
		{
			$this->tmpCat = Category::CAT_XXX_XVID;
			return true;
		}
		
		return false;
	}

	public function isXxxWMV($releasename)
	{
		if (preg_match('/wmv|pack\-|mp4|f4v|flv|mov|mpeg|isom|realmedia|multiformat|(e\d{2,})|(\d{2}\.\d{2}\.\d{2})|uhq|(issue\.\d{2,})/i', $releasename))
		{
			$this->tmpCat = Category::CAT_XXX_WMV;
			return true;
		}
		
		return false;
	}

	public function isXxxDVD($releasename)
	{
		if (preg_match('/dvdr[^ip]|dvd5|dvd9/i', $releasename))
		{
			$this->tmpCat = Category::CAT_XXX_DVD;
			return true;
		}
		
		return false;
	}

	//
	//  Console
	//
	public function isConsole($releasename)
	{
		if($this->isGameNDS($releasename)){return true;}
		if($this->isGamePS3($releasename)){ return true; }
		if($this->isGamePSP($releasename)){ return true; }
		if($this->isGameWiiWare($releasename)){ return true; }
		if($this->isGameWii($releasename)){ return true; }
		if($this->isGameXBOX360DLC($releasename)){ return true; }
		if($this->isGameXBOX360($releasename)){ return true; }
		if($this->isGameXBOX($releasename)){ return true; }
		
		return false;
	}

	public function isGameNDS($releasename)
	{
		if (preg_match('/NDS|\.nds|nintendo.+3ds/', $releasename))
		{
			if(preg_match('/\((DE|DSi(\sEnhanched)?|EUR?|FR|GAME|HOL|JP|NL|NTSC|PAL|KS|USA?)\)/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}
		}
		return false;
	}

	public function isGamePS3($releasename)
	{
		if (preg_match('/PS3/i', $releasename))
		{
			if (preg_match('/ANTiDOTE|DLC|DUPLEX|EUR?|Googlecus|GOTY|\-HR|iNSOMNi|JPN|KONDIOS|PSN/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
			if (preg_match('/AGENCY|APATHY|Caravan|MULTi|NRP|NTSC|PAL|SPLiT|STRiKE|USA?|ZRY/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
		}
		return false;
	}

	public function isGamePSP($releasename)
	{
		if (preg_match('/PSP/i', $releasename))
		{
			if (preg_match('/BAHAMUT|Caravan|EBOOT|EMiNENT|EUR?|GAME|Googlecus|\-HR|JPN|KOR|NTSC|PAL/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}
			if (preg_match('/LIGHTFORCE|MiRiBS|(PLAY)?ASiA|PSN|SUXXORS|UMD(RIP)?|USA?/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}
		}
		return false;
	}

	public function isGameWiiWare($releasename)
	{
		if (preg_match('/WIIWARE|WII.*?VC|VC.*?WII|WII.*?DLC|DLC.*?WII|WII.*?CONSOLE|CONSOLE.*?WII/i', $releasename))
		{
			$this->tmpCat = Category::CAT_GAME_WIIWARE;
			return true;
		}
		return false;
	}

	public function isGameWii($releasename)
	{
		if (preg_match('/WII/i', $releasename))
		{
			if (preg_match('/Allstars|BiOSHOCK|dumpTruck|DNi|iCON|JAP|NTSC|PAL|ProCiSiON|PROPER|RANT|REV0|SUNSHiNE|SUSHi|TMD|USA?/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
			if (preg_match('/APATHY|BAHAMUT|DMZ|ERD|GAME|JPN|LoCAL|MULTi|NAGGERS|OneUp|PLAYME|PONS|Scrubbed|VORTEX|ZARD|ZER0/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
			if (preg_match('/ALMoST|AMBITION|Caravan|CLiiCHE|DRYB|HaZMaT|LOADER|MARVEL|PROMiNENT|LaKiTu|LOCAL|QwiiF|RANT/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
		}
		return false;
	}

	public function isGameXBOX360DLC($releasename)
	{
		if (preg_match('/(DLC.*?xbox360|xbox360.*?DLC|XBLA.*?xbox360|xbox360.*?XBLA)/i', $releasename))
		{
			$this->tmpCat = Category::CAT_GAME_XBOX360DLC;
			return true;
		}
		return false;
	}

	public function isGameXBOX360($releasename)
	{
		if (preg_match('/XBOX360/i', $releasename))
		{
			$this->tmpCat = Category::CAT_GAME_XBOX360;
			return true;
		}
		if (preg_match('/x360/i', $releasename))
		{
			if (preg_match('/Allstars|ASiA|CCCLX|COMPLEX|DAGGER|GLoBAL|iMARS|JAP|JPN|MULTi|NTSC|PAL|REPACK|RRoD|RF|SWAG|USA?/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}
			if (preg_match('/DAMNATION|GERMAN|GOTY|iNT|iTA|JTAG|KINECT|MARVEL|MUX360|RANT|SPARE|SPANISH|VATOS|XGD/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}
		}
		return false;
	}

	public function isGameXBOX($releasename)
	{
		if (preg_match('/XBOX/i', $releasename))
		{
			$this->tmpCat = Category::CAT_GAME_XBOX;
			return true;
		}
		return false;
	}

	//
	// Music
	//
	public function isMusic($releasename)
	{
		if($this->isMusicVideo($releasename)){ return true; }
		if($this->isMusicLossless($releasename)){ return true; }
		if($this->isMusicMP3($releasename)){ return true; }
		
		return false;
	}

	public function isMusicVideo($releasename)
	{
		if (preg_match('/(720P|x264)\-(19|20)\d\d\-[a-z0-9]{1,12}/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MUSIC_VIDEO;
			return true;
		}
		if (preg_match('/[a-z0-9]{1,12}\-(19|20)\d\d\-(720P|x264)/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MUSIC_VIDEO;
			return true;
		}
		
		return false;
	}
	public function isMusicLossless($releasename)
	{
		if (preg_match('/FLAC\-(19|20)\d\d\-[a-z0-9]{1,12}/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
			return true;
		}
		
		return false;
	}
	public function isMusicMP3($releasename)
	{
		if (preg_match('/[a-z0-9]{1,12}\-(19|20)\d\d\-[a-z0-9]{1,12}|(320|cd|eac|vbr).+mp3|(cd|eac|mp3|vbr).+320/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MUSIC_MP3;
			return true;
		}
		// hits[\.\-_ ]volume
		return false;
	}
	
	//
	// Ebooks
	// 
	public function isEBook($releasename)
	{
		if (preg_match('/Ebook|E?\-book|\) WW|Publishing|\[Springer\]|Service\s?Manual|(\(|\[)(epub|html|mobi|pdf|rtf|tif|txt)(\)|\])|\.(epub|mobi)/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MISC_EBOOK;
			return true;
		}

		return false;
	}
	
	//
	// Comics
	// 
	public function isComic($releasename)
	{
		if (preg_match('/\.(cbr|cbz)|\(c2c\)/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MISC_COMICS;
			return true;
		}

		return false;
	}
	
	//
	// Hashed - all hashed go in other misc.
	//
	public function isHashed($releasename)
	{
		if (preg_match('/[a-z0-9]{25,}/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MISC;
			return true;
		}

		return false;
	}
}
?>
