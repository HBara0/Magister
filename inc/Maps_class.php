<?php
/*
 * Orkila Central Online System (OCOS)
 * Copyright © 2009 Orkila International Offshore, All Rights Reserved
 * 
 * Maps Class
 * $id: Maps_class.php
 * Created:		@zaher.reda		May 10, 2012 | 06:03 PM
 * Last Update: @zaher.reda		May 10, 2012 | 06:03 PM
 */
 
class Maps {
	private $api_key = 'AIzaSyDwLmEN86FYPso64IjwgCkWfmMErM-QvGQ';
	private $places = array();
	private $options = array();
	
	public function __construct(array $places=array(), array $options=array()) {
		global $headerinc, $template;
		
		$this->places = $places;
		$this->options = $options;
		
		if(!isset($this->options['canvas_name'])) {
			$this->options['canvas_name'] = 'map_canvas';
		}
		
		if(!isset($this->options['mapcenter'])) {
			$this->options['mapcenter'] = '5.362467, 50.039063';	
		}
		
		if(!empty($places)) {
			$places_script .= $this->parse_markers();	
		}
		
		eval("\$headerinc .= \"".$template->get('headerinc_mapsapi')."\";");
	}
	
	private function parse_markers() {
		if(is_array($this->places)) {
			$i = 0;
			foreach($this->places as $id => $place) {
				list($latitude, $longitude) = explode(',', $place['geoLocation']);
				$markers .= 'places['.$i.']={id:"'.$id.'", title:"'.$place['title'].'",otherinfo:"'.$place['otherinfo'].'",lat:'.$latitude.',lng:'.$longitude.',link:"'.$this->parse_link($place['type'], $id).'"};'."\n";
				$i++;
			}
			return $markers;
		}
		return false;
	}
	
	private function parse_link($type, $id) {
		switch($type) {
			case 'affiliateprofile':
				return 'index.php?module=profiles/affiliateprofile&affid='.$id;
				break;
			default: return false;
		}
	}
	
	public function get_map($width=600, $height=400) {
		return '<div id="'.$this->options['canvas_name'].'" style="height: '.$height.'px; width: '.$width.'px;"></div>';	
	}
}
?>