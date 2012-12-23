<?php

/***************************************************************************
 *
 *   OUGC Edit Legend plugin (/inc/plugins/ougc_editlengend.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2012 Omar Gonzalez
 *   
 *   Website: http://community.mybb.com/user-25096.html
 *
 *   Stop moderators from editing administrator posts.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('This file cannot be accessed directly.');

// Run our hooks
if(!defined('IN_ADMINCP'))
{
	$plugins->add_hook('editpost_start', 'ougc_editlegend');
	$plugins->add_hook('xmlhttp', 'ougc_editlegend');
	$plugins->add_hook('postbit', 'ougc_editlegend_postbit');
}

// Necessary plugin information for the ACP plugin manager.
function ougc_editlegend_info()
{
	return array(
		'name'			=> 'OUGC Edit Legend',
		'description'	=> 'Stop moderators from editing administrator posts.',
		'website'		=> 'http://mods.mybb.com/profile/25096',
		'author'		=> 'Omar Gonzalez',
		'authorsite'	=> 'http://community.mybb.com/user-25096.html',
		'version'		=> '1.0',
		'compatibility'	=> '16*',
		'guid' 			=> ''
	);
}

// Check when editing posts.
function ougc_editlegend()
{
	global $mybb;

	// Dont run unnecesary queries
	if(THIS_SCRIPT == 'xmlhttp.php' && $mybb->input['action'] != 'edit_post')
	{
		return;
	}

	// Turn ajax on, for error()
	if(THIS_SCRIPT == 'xmlhttp.php')
	{
		$mybb->input['ajax'] = true;
	}

	if(!$mybb->user['uid'])
	{
		(THIS_SCRIPT == 'xmlhttp.php') or error_no_permission();
		error();
	}

	// Return if user can access is an administrator
	if($mybb->usergroup['cancp'])
	{
		return;
	}

	global $db, $lang;

	$query = $db->query("
		SELECT p.pid, t.tid, p.uid, u.usergroup, u.additionalgroups
		FROM ".TABLE_PREFIX."posts p
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
		WHERE p.pid='".intval($mybb->input['pid'])."'
	");

	// Post seems to no exists, show error and evoid the mybb query
	if(!($post = $db->fetch_array($query)))
	{
		error($lang->error_invalidpost);
	}

	// Thread seems to no exists, show error and evoid the mybb query
	if(!($thread = get_thread($post['tid'])))
	{
		error($lang->error_invalidthread);
	}

	// Get thread author usergroup permissions
	$gids = $post['usergroup'];
	if($post['additionalgroups'])
	{
		$usergroup .= ','.$gids['additionalgroups'];
	}
	$usergroup = usergroup_permissions($gids);

	// Check permissions..
	if(!ougc_editlegend_canedit($usergroup['cancp'], $usergroup['issupermod']))
	{
		isset($lang->no_permission_edit_post) or $lang->load('xmlhttp');

		error($lang->no_permission_edit_post);
	}
}

// Remove the edit/delete buttons.
function ougc_editlegend_postbit(&$post)
{
	global $mybb;

	// Admins can do whatever they want anyways
	if($mybb->usergroup['cancp'])
	{
		global $plugins;

		$plugins->remove_hook('postbit', 'ougc_editlegend_postbit');
		return;
	}

	if($post['button_edit'] || $post['button_quickdelete'])
	{
		// Get post author usergroup permissions
		static $permissions = array();
		if(!isset($permissions[$post['uid']]))
		{
			$gids = $post['usergroup'];
			if($post['additionalgroups'])
			{
				$usergroup .= ','.$gids['additionalgroups'];
			}
			$permissions[$post['uid']] = usergroup_permissions($gids);
		}
		$usergroup = $permissions[$post['uid']];

		// Check permissions..
		if(!ougc_editlegend_canedit($usergroup['cancp'], $usergroup['issupermod']))
		{
			$post['button_edit'] = $post['button_quickdelete'] = '';
		}
	}
}

// Quick function
function ougc_editlegend_canedit($cancp, $issupermod)
{
	global $mybb;

	if(!$mybb->usergroup['cancp'])
	{
		if($cancp)
		{
			return false;
		}

		if(!$mybb->usergroup['issupermod'])
		{
			if($issupermod)
			{
				return false;
			}
		}
	}

	return true;
}