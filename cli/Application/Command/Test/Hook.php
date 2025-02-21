<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Test;

use App\Projects\TrackerProject;

use Application\Command\TrackerCommandOption;
use Application\Exception\AbortException;

use Joomla\Github\Github;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Class for testing web hooks.
 *
 * @since  1.0
 */
class Hook extends Test
{
	/**
	 * Hook controller
	 *
	 * @var    \App\Tracker\Controller\AbstractHookController
	 * @since  1.0
	 */
	protected $controller;

	/**
	 * Joomla! Github object
	 *
	 * @var    Github
	 * @since  1.0
	 */
	protected $github;

	/**
	 * The project object.
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	protected $project;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Tests web hooks';

		$this->addOption(
			new TrackerCommandOption(
				'project',
				'p',
				'Process the project with the given ID.'
			)
		);
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Test Hooks');

		$this->logOut('Start testing hook');

		$this->selectProject()->selectHook();

		$this->getApplication()->input->set('project', $this->project->project_id);

		$this->setupGitHub();

		$result = $this->controller->execute();
		$this->logOut($result);
	}

	/**
	 * Select the hook.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  AbortException
	 */
	protected function selectHook()
	{
		$paths = (new Filesystem(new Local(JPATH_ROOT . '/src/App/Tracker/Controller/Hooks')))->listContents();
		$hooks = [];

		foreach ($paths as $path)
		{
			if ($path['type'] == 'file')
			{
				$hooks[] = str_replace(['Receive', 'Hook'], '', $path['filename']);
			}
		}

		$this->out()
			->out('<b>Available hooks:</b>')
			->out();

		$cnt = 1;

		$checks = [];

		foreach ($hooks as $hook)
		{
			$this->out('  <b>' . $cnt . '</b> ' . $hook);
			$checks[$cnt] = $hook;
			$cnt++;
		}

		$this->out()
			->out('<question>Select a hook:</question> ', false);

		$resp = (int) trim($this->getApplication()->in());

		if (!$resp)
		{
			throw new AbortException('Aborted');
		}

		if (\array_key_exists($resp, $checks) === false)
		{
			throw new AbortException('Invalid hook');
		}

		$classname = '\\App\\Tracker\\Controller\\Hooks\\Receive' . $checks[$resp] . 'Hook';

		// Initialize the hook controller
		$this->controller = new $classname;
		$this->controller->setContainer($this->getContainer());

		if ($this->project->project_id == 1 && $resp === 4)
		{
			$this->getApplication()->input->post->set('payload', file_get_contents(__DIR__ . '/data/cms-pull.json'));
		}

		$this->controller->initialize();

		return $this;
	}

	/**
	 * Select the project.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  AbortException
	 */
	protected function selectProject()
	{
		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$projects = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName('#__tracker_projects'))
				->select(['project_id', 'title', 'gh_user', 'gh_project'])
		)->loadObjectList();

		$id = (integer) $this->getOption('project');

		if (!$id)
		{
			$this->out()
				->out('<b>Available projects:</b>')
				->out();

			$cnt = 1;

			$checks = [];

			foreach ($projects as $project)
			{
				if ($project->gh_user && $project->gh_project)
				{
					$this->out('  <b>' . $cnt . '</b> (id: ' . $project->project_id . ') ' . $project->title);
					$checks[$cnt] = $project;
					$cnt++;
				}
			}

			$this->out()
				->out('<question>Select a project:</question> ', false);

			$resp = (int) trim($this->getApplication()->in());

			if (!$resp)
			{
				throw new AbortException('Aborted');
			}

			if (\array_key_exists($resp, $checks) === false)
			{
				throw new AbortException('Invalid project');
			}

			$this->project = $checks[$resp];
		}
		else
		{
			foreach ($projects as $project)
			{
				if ($project->project_id == $id)
				{
					$this->project = $project;

					break;
				}
			}

			if ($this->project === null)
			{
				throw new AbortException('Invalid project');
			}
		}

		$this->logOut('Processing project: <info>' . $this->project->title . '</info>');

		return $this;
	}

	/**
	 * Setup the Github object.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function setupGitHub()
	{
		$this->github = $this->getContainer()->get('gitHub');

		return $this;
	}
}
