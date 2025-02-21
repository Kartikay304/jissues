<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Controller\Ajax\Hooks;

use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to modify webhooks on the GitHub repository.
 *
 * @since  1.0
 */
class Modify extends AbstractAjaxController
{
	/**
	 * GitHub object
	 *
	 * @var    \Joomla\Github\Github
	 * @since  1.0
	 */
	protected $github;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->github = $this->getContainer()->get('gitHub');
	}

	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function prepareResponse()
	{
		$this->getContainer()->get('app')->getUser()->authorize('admin');

		$action = $this->getContainer()->get('app')->input->getCmd('action');
		$hookId = $this->getContainer()->get('app')->input->getInt('hook_id');

		$project = $this->getContainer()->get('app')->getProject();

		// Get a valid hook object
		$hook = $this->getHook($hookId);

		if ($action == 'delete')
		{
			// Delete the hook
			$this->github->repositories->hooks->delete($project->gh_user, $project->gh_project, $hookId);
		}
		else
		{
			// Process other actions
			$this->processAction($action, $hook);
		}

		// Get the current hooks list.
		$this->response->data = $this->github->repositories->hooks->getList($project->gh_user, $project->gh_project);
	}

	/**
	 * Process an action.
	 *
	 * @param   string  $action  The action to perform.
	 * @param   object  $hook    The hook object.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function processAction($action, $hook)
	{
		$project = $this->getContainer()->get('app')->getProject();

		switch ($action)
		{
			case 'activate' :
				$hook->active = true;

				break;

			case 'deactivate' :
				$hook->active = false;

				break;

			default :
				throw new \RuntimeException('Invalid action');

				break;
		}

		// Create the hook.
		$this->github->repositories->hooks->edit(
			$project->gh_user,
			$project->gh_project,
			$hook->id,
			$hook->name,
			$hook->config,
			$hook->events,
			[],
			[],
			$hook->active
		);

		return $this;
	}

	/**
	 * Get a valid hook.
	 *
	 * @param   integer  $hookId  The hook id.
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function getHook($hookId)
	{
		$project = $this->getContainer()->get('app')->getProject();

		$hooks = $this->github->repositories->hooks->getList($project->gh_user, $project->gh_project);

		if (!$hooks)
		{
			throw new \RuntimeException('No hooks found in repository');
		}

		foreach ($hooks as $hook)
		{
			if ($hook->id == $hookId)
			{
				return $hook;
			}
		}

		throw new \RuntimeException('Unknown hook');
	}
}
