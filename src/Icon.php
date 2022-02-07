<?php
namespace Whoyasha\Teenyicons;

class Icon
{
	private static $instance = null;

	private static $icons = [];
	private static $type;
	private static $path;
	private static $default_size = "15";
	private static $hidden_toggle = false;

	const HIDDEN_STYLE = "style=\"display:none;\"";
	const HIDDEN_CLASS = "class=\"visualy-hidden\"";

	public function Set(&$content) : string
	{

		static::$path = (is_dir(ICONS_PATH) && ICONS_PATH != $_SERVER["DOCUMENT_ROOT"]) ? ICONS_PATH : __DIR__ . "/icons";
		
		if(!is_dir(static::$path)) 
		{
			ShowError("Icons dir not found");
			return $content;
		}
		
		static::$type = DEFAULT_ICONS_TYPE ? DEFAULT_ICONS_TYPE : false;
		
		$content = static::GetIcons($content);
		
		if(!preg_match("/(bitrix)/", $GLOBALS["APPLICATION"]->GetCurDir()) && !$GLOBALS["USER"]->IsAdmin())
			$content = static::stylePreload($content);
		
		return $content;
	}
	
	protected function stylePreload($content)
	{
		$pattern = "rel=\"stylesheet\"";
		$replacement = "rel=\"preload\" as=\"style\"";
		$content = str_replace($pattern, $replacement, $content);
		
		return $content;
	}
	
	protected static function GetIcons($content)
	{
		preg_match_all("/{{?([A-Za-z0-9\-\_]+)}?(([0-9]+),([0-9]+)}|(([0-9]+)})|()})/", $content, $matches);
		
		$params = array_map('static::PrepParams', $matches[2]);

		foreach($matches[0] as $id => $icon) {
			
			if($matches[1][$id]) {
				static::$icons[] = [
					"ICON" => $matches[1][$id],
					"SIZE" => $params[$id][0] == 0 ? "100%" : $params[$id][0],
					"BOTH" => $params[$id][1] == 0 ? false : true
				];
			}
		}
		
		if($icons = static::GetSvgFromFiles())
			$content = str_replace($matches[0], $icons, $content);
		
		return $content;
	}
	
	protected static function PrepParams($icon)
	{
		$parse = explode(",", str_replace("}", "", $icon));
		$parse = array_map('trim', $parse);
		
		return [
			(int) $parse[0],
			(int) $parse[1]
		];
	}
	
	protected static function GetSvgFromFiles() {
		
		foreach(static::$icons as $id => $icon)
		{
			$type = !static::$type ? "" : "/" . static::$type  . "/";

			$path = ["MAIN" => static::$path . $type];

			if(static::$type && $icon["BOTH"]) {
				$tooggle_type = static::$type == "outline" ? "solid" : "outline";
				$path["TOGGLE"] = static::$path . "/" . $tooggle_type  . "/";
			}
			
			foreach($path as $code => $item)
			{
				if($svg = static::GetFilePath($item, $icon["ICON"]))
				{
					if($code == "TOGGLE" && static::$hidden_toggle)
						$svg = str_replace("<svg", "<svg " . static::HIDDEN_STYLE, $svg);

					if(!preg_match("/(width=\"" . static::$default_size . "\" height=\"" . static::$default_size . "\")/", $svg))
					{
						$svg = str_replace("<svg", "<svg width=\"" . static::$default_size . "\" height=\"" . static::$default_size . "\"", $svg);
					}
					
					if((float) $icon["SIZE"] > 0)
						$svg = str_replace(
							"width=\"" . static::$default_size . "\" height=\"" . static::$default_size . "\"", 
							"width=\"{$icon["SIZE"]}\" height=\"{$icon["SIZE"]}\"", 
							$svg
						);
					
					preg_match_all("/(stroke=\"?([A-Za-z0-9\#]+))/", $svg, $stroke);
					preg_match_all("/(fill=\"?([A-Za-z0-9\#]+))/", $svg, $fill);
					
					$stroke = implode("|", array_diff(array_unique($stroke[2]), ["none", "currentColor"]));
					$fill = implode("|", array_diff(array_unique($fill[2]), ["none", "currentColor"]));

					if(!empty($stroke))
						$svg = preg_replace("/(stroke=\"?" . $stroke . "\")/", "stroke=\"currentColor\"", $svg);
						
					if(!empty($fill))
						$svg = preg_replace("/(fill=\"?" . $fill . "\")/", "fill=\"currentColor\"", $svg);

					$svgs[$id][] = $svg;
				} 

			}
			
			if($svgs[$id])
				$svgs[$id] = implode('', $svgs[$id]);
		}
		
		return $svgs;
	}
	
	protected static function GetFilePath($path, $icon) : string
	{
		$result = $path. $icon . ".svg";
		
		if(file_exists($result))
		{
			return file_get_contents($result);
		}
		
		return "&nbsp;";
	}
}
