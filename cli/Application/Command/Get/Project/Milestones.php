<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Get\Project;

use App\Projects\Table\MilestonesTable;

use Application\Command\Get\Project;

use Joomla\Date\Date;

/**
 * Class for retrieving milestones from GitHub for selected projects.
 *
 * @since  1.0
 */
class Milestones extends Project
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Retrieve project milestones from GitHub.';
	}

	/**
	 * Execute the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Retrieve Milestones');

		return $this->logOut('Start retrieving Milestones')
			->selectProject()
			->setupGitHub()
			->processMilestones()
			->out()
			->logOut('Finished.');
	}

	/**
	 * Get the project's milestones.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processMilestones()
	{
		$this->out('Fetching milestones...', false);

		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$table = new MilestonesTable($db);

		$milestones = array_merge(
			$this->github->issues->milestones->getList(
				$this->project->gh_user, $this->project->gh_project, 'open'
			),
			$this->github->issues->milestones->getList(
				$this->project->gh_user, $this->project->gh_project, 'closed'
			)
		);

		$titles = [];

		$cntUpdated = 0;
		$cntNew     = 0;

		foreach ($milestones as $milestone)
		{
			try
			{
				$table->milestone_id = null;

				// Check if the milestone exists
				$table->load(
					[
						'project_id'       => $this->project->project_id,
						'milestone_number' => $milestone->number,
					]
				);

				// Values that may have changed
				$table->title       = $milestone->title;
				$table->description = $milestone->description;
				$table->state       = $milestone->state;
				$table->due_on      = $milestone->due_on ? (new Date($milestone->due_on))->format('Y-m-d H:i:s') : null;

				$table->store(true);

				$cntUpdated++;
			}
			catch (\RuntimeException $e)
			{
				// New milestone
				$table->milestone_number = $milestone->number;
				$table->project_id       = $this->project->project_id;
				$table->title            = $milestone->title;
				$table->description      = $milestone->description;
				$table->state            = $milestone->state;
				$table->due_on           = $milestone->due_on ? (new Date($milestone->due_on))->format('Y-m-d H:i:s') : null;

				$table->store(true);

				$cntNew++;
			}

			$titles[] = $milestone->title;
		}

		// Check for deleted milestones
		$ids = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName($table->getTableName()))
				->select('milestone_id')
				->where($db->quoteName('project_id') . ' = ' . $this->project->project_id)
				->where($db->quoteName('title') . ' NOT IN (\'' . implode("', '", $titles) . '\')')
		)->loadRowList();

		if ($ids)
		{
			// Kill the orphans
			$db->setQuery(
				$db->getQuery(true)
					->delete($db->quoteName($table->getTableName()))
					->where($db->quoteName('milestone_id') . ' IN (' . implode(', ', $ids[0]) . ')')
			)->execute();
		}

		$cntDeleted = \count($ids);

		return $this->out('ok')
			->logOut(
				sprintf(
					'Milestones: %1$d new, %2$d updated, %3$d deleted.',
					$cntNew, $cntUpdated, $cntDeleted
				)
			);
	}
}
