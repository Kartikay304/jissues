<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Controller\Ajax;

use JTracker\Authentication\Database\TableUsers;
use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to assign users to groups
 *
 * @since  1.0
 */
class Assign extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	protected function prepareResponse()
	{
		if ($this->getContainer()->get('app')->getUser()->check('manage') === false)
		{
			throw new \Exception('You are not authorized');
		}

		$input = $this->getContainer()->get('app')->input;
		$db    = $this->getContainer()->get('db');

		$user    = $input->getCmd('user');
		$groupId = $input->getInt('group_id');
		$assign  = $input->getInt('assign');

		if (!$groupId)
		{
			throw new \Exception('Missing group id');
		}

		$tableUsers = new TableUsers($this->getContainer()->get('db'));

		$tableUsers->loadByUserName($user);

		if (!$tableUsers->id)
		{
			throw new \Exception('User not found');
		}

		$check = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName('#__user_accessgroup_map', 'm'))
				->select('COUNT(*)')
				->where($db->quoteName('group_id') . ' = ' . (int) $groupId)
				->where($db->quoteName('user_id') . ' = ' . (int) $tableUsers->id)
		)->loadResult();

		if ($assign)
		{
			if ($check)
			{
				throw new \Exception('The user is already assigned to this group.');
			}

			$this->assign($tableUsers->id, $groupId);

			$this->response->data->message = 'The user has been assigned.';
		}
		else
		{
			if (!$check)
			{
				throw new \Exception('The user is not assigned to this group.');
			}

			$this->unAssign($tableUsers->id, $groupId);

			$this->response->data->message = 'The user has been unassigned.';
		}
	}

	/**
	 * Add a user to a group.
	 *
	 * @param   integer  $userId   The user id.
	 * @param   integer  $groupId  The group id.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	private function assign($userId, $groupId)
	{
		$db = $this->getContainer()->get('db');

		$data = [
			$db->quoteName('user_id')  => (int) $userId,
			$db->quoteName('group_id') => (int) $groupId,
		];

		$db->setQuery(
			$db->getQuery(true)
				->insert($db->quoteName('#__user_accessgroup_map'))
				->columns(array_keys($data))
				->values(implode(',', $data))
		)->execute();

		return $this;
	}

	/**
	 * Remove a user from a group.
	 *
	 * @param   integer  $userId   The user id.
	 * @param   integer  $groupId  The group id.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	private function unAssign($userId, $groupId)
	{
		$db = $this->getContainer()->get('db');

		$db->setQuery(
			$db->getQuery(true)
				->delete($db->quoteName('#__user_accessgroup_map'))
				->where($db->quoteName('user_id') . ' = ' . (int) $userId)
				->where($db->quoteName('group_id') . ' = ' . (int) $groupId)
		)->execute();

		return $this;
	}
}
