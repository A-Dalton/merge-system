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

class WBB3_Converter_Module_Usergroups extends Converter_Module_Usergroups {

	var $settings = array(
		'friendly_name' => 'usergroups',
		'progress_column' => 'groupID',
		'default_per_screen' => 1000,
	);

	var $options = array(
		"admin.general.canUseAcp",
		"user.profile.canView",
		"user.profile.rank.canEditUserTitle",
		"user.membersList.canView",
		"user.pm.canUsePm",
		"user.board.canStartThread",
		"user.board.canReplyThread",
		"user.board.canEditOwnPost",
		"user.board.canDeleteOwnPost",
		"user.board.canDownloadAttachment",
		"user.board.canUploadAttachment",
		"user.board.canVotePoll",
		"user.board.canStartPoll",
		"mod.board.isSuperMod",
	);

	var $nice_options;

	function pre_setup()
	{
		global $import_session;

		// We need the ID's for the nice names above
		if(!isset($import_session['nice_options']))
		{
			$query = $this->old_db->simple_select(WCF_PREFIX."group_option", "optionID, optionName", "optionName IN ('".implode("','", $this->options)."')");
			while($option = $this->old_db->fetch_array($query))
			{
				$this->nice_options[$option['optionID']] = $option['optionName'];
			}
			$this->old_db->free_result($query);

			$import_session['nice_options'] = $this->nice_options;
		}
		else
		{
			$this->nice_options = $import_session['nice_options'];
		}
	}

	function finish()
	{
		global $import_session;

		unset($import_session['enc_options']);
		unset($import_session['nice_options']);
	}

	function import()
	{
		global $import_session;

		// Get only non-standard groups.
		$query = $this->old_db->simple_select(WCF_PREFIX."group", "*", "groupID > 6", array('limit_start' => $this->trackers['start_usergroups'], 'limit' => $import_session['usergroups_per_screen']));
		while($group = $this->old_db->fetch_array($query))
		{
			// Fetch the options for that group
			$oquery = $this->old_db->simple_select(WCF_PREFIX."group_option_value", "optionID, optionValue", "groupID='{$group['groupID']}' AND optionID IN ('".implode("','", array_keys($this->nice_options))."')");
			while($opt = $this->old_db->fetch_array($oquery))
			{
				// This will get the last part, eg "admin.general.canUseAcp" becomes "canUseAcp"
				// If we can't use the nice name we use the complete
				$nicename = substr($this->nice_options[$opt['optionID']], strrpos($this->nice_options[$opt['optionID']], ".")+1);
				if(!isset($group[$nicename]))
				{
					$group[$nicename] = $opt['optionValue'];
				}
				else
				{
					$group[$this->nice_options[$opt['optionID']]] = $opt['optionValue'];
				}
			}
			$this->old_db->free_result($oquery);

			$this->insert($group);
		}
	}

	function convert_data($data)
	{
		$insert_data = array();

		// WBB 3 values
		$insert_data['import_gid'] = $data['groupID'];
		$insert_data['type'] = 2; // Custom usergroup
		$insert_data['title'] = $data['groupName'];
		$insert_data['description'] = 'WBB Lite 2 imported group';

		$insert_data['cancp'] = $data['canUseAcp'];
		$insert_data['canviewprofiles'] = $data['canView'];
		$insert_data['cancustomtitle'] = $data['canEditUserTitle'];
		$insert_data['canviewmemberlist'] = $data['user.membersList.canView'];
		$insert_data['canusepms'] = $data['canUsePm'];
		$insert_data['cansendpms'] = $data['canUsePm'];
		$insert_data['canpostthreads'] = $data['canStartThread'];
		$insert_data['canpostreplys'] = $data['canReplyThread'];
		$insert_data['caneditposts'] = $data['canEditOwnPost'];
		$insert_data['candeleteposts'] = $data['canDeleteOwnPost'];
		$insert_data['candlattachments'] = $data['canDownloadAttachment'];
		$insert_data['canpostattachments'] = $data['canUploadAttachment'];
		$insert_data['canvotepolls'] = $data['canVotePoll'];
		$insert_data['canpostpolls'] = $data['canStartPoll'];
		$insert_data['issupermod'] = $data['isSuperMod'];

		// These values are only available on WBB 3
		if(isset($data['showOnTeamPage']))
		{
			$insert_data['showforumteam'] = $data['showOnTeamPage'];
			$insert_data['namestyle'] = str_replace("%s", "{username}", $data['userOnlineMarking']);
			
			if(!empty($data['groupDescription']))
			{
				$insert_data['description'] = $data['groupDescription'];
			}
		}

		return $insert_data;
	}

	function fetch_total()
	{
		global $import_session;

		// Get number of usergroups
		if(!isset($import_session['total_usergroups']))
		{
			$query = $this->old_db->simple_select(WCF_PREFIX."group", "COUNT(*) as count", "groupID > 6");
			$import_session['total_usergroups'] = $this->old_db->fetch_field($query, 'count');
			$this->old_db->free_result($query);
		}

		return $import_session['total_usergroups'];
	}
}


