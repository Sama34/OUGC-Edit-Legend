<?php 

/***************************************************************************
 *
 *   OUGC Guests See Only First Post plugin (/inc/plugins/ougc_showgivedrep.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2012 Omar Gonzalez
 *   
 *   Website: http://community.mybb.com/user-25096.html
 *
 *   Stops guests from seeing more that the first post of each thread.
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

// Run the required hooks.
if(!defined('IN_ADMINCP'))
{
	$plugins->add_hook('forumdisplay_start', 'oucg_gsofp_hack');
	$plugins->add_hook('showthread_start', 'oucg_gsofp_hack');
	$plugins->add_hook('printthread_start', 'oucg_gsofp_hack');
	$plugins->add_hook('archive_start', 'oucg_gsofp_hack');
}

// Array of information about the plugin.
function oucg_gsofp_info()
{
	return array(
		'name'			=> 'OUGC Guests See Only First Post',
		'description'	=> 'Stops guests from seeing more that the first post of each thread.',
		'website'		=> 'http://mods.mybb.com/view/ougc-guests-see-only-first-post',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://community.mybb.com/user-25096.html',
		'version'		=> '1.1',
		'guid'			=> '40f0cb3fb3dffdfd093375e8482d14af',
		'compatibility' => '16*'
	);
}

// Removes the {$thread['multipage']} from forumdisplay
function oucg_gsofp_hack()
{
	global $mybb;

	if(!$mybb->user['uid'])
	{
		global $plugins;
		$mybb->settings['postsperpage'] = 999*999;

		if(THIS_SCRIPT == 'showthread.php')
		{
			// "Hide" the psotbit contents
			$plugins->add_hook('postbit', create_function('&$post', 'global $thread;	if($post[\'pid\'] != $thread[\'firstpost\']){global $templates;	$templates->cache[\'postbit\'] = $templates->cache[\'postbit_classic\'] = \'\';} global $plugins;	$plugins->remove_hook(\'postbit\', __FUNCTION__);'));
		}
		elseif(THIS_SCRIPT == 'printthread.php')
		{
			// Hide from print thread.
			$plugins->add_hook('printthread_post', create_function('', 'global $postrow, $thread;	if($postrow[\'pid\'] != $thread[\'firstpost\']){global $templates;	$templates->cache[\'printthread_post\'] = \'\';} global $plugins;	$plugins->remove_hook(\'printthread_post\', __FUNCTION__);'));
		}
		elseif(defined('IN_ARCHIVE'))
		{
			// Hide from archive
			// Seems like the best and nicer way of doing it...
			$plugins->add_hook('archive_thread_post', create_function('', 'global $post, $thread;	if($post[\'pid\'] != $thread[\'firstpost\']){archive_error_no_permission();}'));
			#$plugins->add_hook('archive_end', 'oucg_gsofp_hack_archive_end');
		}
	}
}