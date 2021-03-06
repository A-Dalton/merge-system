<?php
/**
 * MyBB 1.8 Merge System
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/download/merge-system/license/
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

/** @property IPB4_Converter $board */
class IPB4_Converter_Module_Usergroups extends Converter_Module_Usergroups {

	var $settings = array(
		'friendly_name' => 'usergroups',
		'progress_column' => 'g_id',
		'default_per_screen' => 1000,
	);

	function import()
	{
		global $import_session;

		// Get only non-staff groups.
		$query = $this->old_db->simple_select("core_groups", "*", "g_id > 6", array('limit_start' => $this->trackers['start_usergroups'], 'limit' => $import_session['usergroups_per_screen']));
		while($group = $this->old_db->fetch_array($query))
		{
			$this->insert($group);
		}
	}

	function convert_data($data)
	{
		$insert_data = array();

		// Invision Power Board 4 values
		$insert_data['import_gid'] = $data['g_id'];
		$insert_data['title'] = $this->board->getLanguageString("core_group_{$data['g_id']}");
		$insert_data['pmquota'] = $data['g_max_messages'];
		$insert_data['maxpmrecipients'] = $data['g_max_mass_pm'];
		$insert_data['attachquota'] = $data['g_attach_max'];
		$insert_data['caneditposts'] = $data['g_edit_posts'];
		$insert_data['candeleteposts'] = $data['g_delete_own_posts'];
		$insert_data['canpostpolls'] = $data['g_post_polls'];
		$insert_data['canvotepolls'] = $data['g_vote_polls'];
		$insert_data['canusepms'] = $data['g_use_pm'];
		$insert_data['cancp'] = $data['g_access_cp'];
		$insert_data['issupermod'] = intval($data['g_is_supmod']);
		$insert_data['cansearch'] = $data['g_use_search'];
		$insert_data['canview'] = $data['g_view_board'];
		$insert_data['canviewprofiles'] = $data['g_mem_info'];

		// -1 is disabled
		if($insert_data['attachquota'] < 0)
		{
			$insert_data['attachquota'] = 0;
			$insert_data['canpostattachments'] = 0;
		}

		return $insert_data;
	}

	function fetch_total()
	{
		global $import_session;

		// Get number of usergroups
		if(!isset($import_session['total_usergroups']))
		{
			$query = $this->old_db->simple_select("core_groups", "COUNT(*) as count", "g_id > 6");
			$import_session['total_usergroups'] = $this->old_db->fetch_field($query, 'count');
			$this->old_db->free_result($query);
		}

		return $import_session['total_usergroups'];
	}
}


