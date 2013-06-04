<?php
/*
 * Copyright © 2013 Orkila International Offshore, All Rights Reserved
 *
 * Leave Types Class
 * $id: Leavetypes_class.php
 * Created:        @tony.assaad    May 29, 2013 | 3:39:18 PM
 * Last Update:    @tony.assaad    May 29, 2013 | 3:39:18 PM
 */

class Leavetypes {
	private $leavetype = array();

	public function __construct($ltid = 0, $simple = true) {
		global $db;
		if(isset($ltid) && !empty($ltid)) {
			$this->leavetype = $this->read($ltid, $simple);
		}
	}

	public function has_expenses($id = '') {
		global $db;

		if(!empty($this->leavetype['ltid']) && empty($id)) {
			$id = $this->leavetype['ltid'];
		}

		if(value_exists('attendance_leavetypes_exptypes', 'ltid', $db->escape_string($id))) {

			return true;
		}
		else {
			return false;
		}
	}

	public function get_expenses($id = '') {
		global $db;

		if(!empty($this->leavetype['ltid']) && empty($id)) {
			$id = $this->leavetype['ltid'];
		}

		$leavetypeexp_query = $db->query('SELECT * FROM '.Tprefix.'attendance_leavetypes_exptypes WHERE ltid='.$db->escape_string($id));
		if($db->num_rows($leavetypeexp_query) > 0) {
			while($leavetype_expense = $db->fetch_assoc($leavetypeexp_query)) {
				$leavetypeexpenses[$leavetype_expense['alteid']] = $leavetype_expense;
			}
			if(is_array($leavetypeexpenses)) {
				return $leavetypeexpenses;
			}
			return false;
		}
		return false;
	}

	public function parse_expensesfields(array $expensestype, array $leaveexpenses = array()) {
		global $db, $template;
		if($expensestype['isRequired'] == 1) {
			$expenses_output_required = '<span class="red_text">*</span>';
			$expenses_output_requiredattr = ' required="required"';
		}

		if(isset($lang->{$expensestype['name']})) {
			$expensestype['title'] = $lang->{$expensestype['name']};
		}

		eval("\$requestleaveexpenses = \"".$template->get('attendance_requestleave_expsection_fields')."\";");
		return $requestleaveexpenses;
	}

	private function read($id, $simple = true) {
		global $db;
		if(empty($id)) {
			return false;
		}
		$query_select = '*';
		if($simple == true) {
			$query_select = 'ltid, name, title, description, toApprove';
		}
		return $db->fetch_assoc($db->query('SELECT '.$query_select.' FROM '.Tprefix.'leavetypes WHERE ltid='.$db->escape_string($id)));
	}

	public function get() {
		return $this->leavetype;
	}

}
?>
