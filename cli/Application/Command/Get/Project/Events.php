<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Get\Project;

use App\Projects\TrackerProject;
use App\Tracker\Table\ActivitiesTable;

use Application\Command\Get\Project;
use Application\Command\TrackerCommandOption;

use Joomla\Date\Date;

/**
 * Class for retrieving events from GitHub for selected projects
 *
 * @since  1.0
 */
class Events extends Project
{
	/**
	 * Event data from GitHub
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $items = array();

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Retrieve issue events from GitHub.');

		$this->addOption(
			new TrackerCommandOption(
				'issue', '',
				g11n3t('<n> Process only a single issue.')
			)
		)->addOption(
			new TrackerCommandOption(
				'all', '',
				g11n3t('Process all issues.')
			)
		);
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle(g11n3t('Retrieve Events'));

		$this->logOut(g11n3t('Start retrieve Events'))
			->selectProject()
			->setupGitHub()
			->fetchData()
			->processData()
			->out()
			->logOut(g11n3t('Finished.'));
	}

	/**
	 * Set the changed issues.
	 *
	 * @param   array  $changedIssueNumbers  List of changed issue numbers.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function setChangedIssueNumbers(array $changedIssueNumbers)
	{
		$this->changedIssueNumbers = $changedIssueNumbers;

		return $this;
	}

	/**
	 * Set the list of changed issues before they were changed.
	 *
	 * @param   array  $oldIssuesData  List of changed issues before they were changed.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setOldIssuesData(array $oldIssuesData)
	{
		$this->oldIssuesData = $oldIssuesData;

		return $this;
	}

	/**
	 * Method to get the comments on items from GitHub
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function fetchData()
	{
		if (!$this->changedIssueNumbers)
		{
			return $this;
		}

		$this->out(
			sprintf(
				g11n4t(
					'Fetch events for one issue from GitHub...',
					'Fetch events for <b>%d</b> issues from GitHub...',
					count($this->changedIssueNumbers)
				),
				count($this->changedIssueNumbers)
			), false
		);

		$progressBar = $this->getProgressBar(count($this->changedIssueNumbers));

		$this->usePBar ? $this->out() : null;

		foreach ($this->changedIssueNumbers as $count => $issueNumber)
		{
			$this->usePBar
				? $progressBar->update($count + 1)
				: $this->out(
					sprintf(
						'%d/%d - # %d: ', $count + 1, count($this->changedIssueNumbers), $issueNumber
					),
					false
				);

			$page = 0;
			$this->items[$issueNumber] = array();

			do
			{
				$page++;

				$events = $this->github->issues->events->getList(
					$this->project->gh_user, $this->project->gh_project, $issueNumber, $page, 100
				);

				$this->checkGitHubRateLimit($this->github->issues->events->getRateLimitRemaining());

				$count = is_array($events) ? count($events) : 0;

				if ($count)
				{
					$this->items[$issueNumber] = array_merge($this->items[$issueNumber], $events);

						$this->usePBar
							? null
							: $this->out($count . ' ', false);
				}
			}

			while ($count);
		}

		// Retrieved items, report status
		$this->out()
			->outOK();

		return $this;
	}

	/**
	 * Method to process the list of issues and inject into the database as needed
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	protected function processData()
	{
		if (!$this->items)
		{
			$this->logOut(g11n3t('Everything is up to date.'));

			return $this;
		}

		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$query = $db->getQuery(true);

		$this->out(g11n3t('Adding events to the database...'), false);

		$progressBar = $this->getProgressBar(count($this->items));

		$this->usePBar ? $this->out() : null;

		$adds = 0;
		$count = 0;

		// Initialize our ActivitiesTable instance to insert the new record
		$table = new ActivitiesTable($db);

		foreach ($this->items as $issueNumber => $events)
		{
			$this->usePBar
				? null
				: $this->out(sprintf(' #%d (%d/%d)...', $issueNumber, $count + 1, count($this->items)), false);

			foreach ($events as $event)
			{
				switch ($event->event)
				{
					case 'referenced' :
					case 'closed' :
					case 'reopened' :
					case 'assigned' :
					case 'unassigned' :
					case 'merged' :
					case 'head_ref_deleted' :
					case 'head_ref_restored' :
					case 'milestoned' :
					case 'demilestoned' :
					case 'labeled' :
					case 'unlabeled' :
					case 'renamed' :
						$query->clear()
							->select($table->getKeyName())
							->from($db->quoteName('#__activities'))
							->where($db->quoteName('gh_comment_id') . ' = ' . (int) $event->id)
							->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id);

						$db->setQuery($query);

						$id = (int) $db->loadResult();

						$table->reset();
						$table->{$table->getKeyName()} = null;

						if ($id && !$this->force)
						{
							if ($this->force)
							{
								// Force update
								$this->usePBar ? null : $this->out('F', false);

								$table->{$table->getKeyName()} = $id;
							}
							else
							{
								// If we have something already, then move on to the next item
								$this->usePBar ? null : $this->out('-', false);

								continue;
							}
						}
						else
						{
							$this->usePBar ? null : $this->out('+', false);
						}

						// Translate GitHub event names to "our" name schema
						$evTrans = array(
							'referenced' => 'reference', 'closed' => 'close', 'reopened' => 'reopen',
							'assigned' => 'assigned', 'unassigned' => 'unassigned', 'merged' => 'merge',
							'head_ref_deleted' => 'head_ref_deleted', 'head_ref_restored' => 'head_ref_restored',
							'milestoned' => 'change', 'demilestoned' => 'change', 'labeled' => 'change', 'unlabeled' => 'change',
							'renamed' => 'change',
						);

						$table->gh_comment_id = $event->id;
						$table->issue_number  = $issueNumber;
						$table->project_id    = $this->project->project_id;
						$table->user          = $event->actor->login;
						$table->event         = $evTrans[$event->event];

						$table->created_date = (new Date($event->created_at))->format('Y-m-d H:i:s');

						if ('referenced' == $event->event)
						{
							// @todo obtain referenced information

							/*
							$reference = $this->github->issues->events->get(
								$this->project->gh_user, $this->project->gh_project, $event->id
							);

							$this->checkGitHubRateLimit($this->github->issues->events->getRateLimitRemaining());
							*/
						}

						if ('assigned' == $event->event)
						{
							$table->text_raw = 'Assigned to ' . $event->assignee->login;
							$table->text     = $table->text_raw;
						}

						if ('unassigned' == $event->event)
						{
							$table->text_raw = $event->assignee->login . ' was unassigned';
							$table->text     = $table->text_raw;
						}

						$changes = $this->prepareChanges($event, $issueNumber);

						if (!empty($changes))
						{
							$table->text = json_encode($changes);
						}

						$table->store();

						++ $adds;
						break;

					case 'mentioned' :
					case 'subscribed' :
					case 'unsubscribed' :
						continue;

					default:
						$this->logOut(sprintf('ERROR: Unknown Event: %s', $event->event));
						continue;
				}
			}

			++ $count;

			$this->usePBar
				? $progressBar->update($count)
				: null;
		}

		$this->out()
			->outOK()
			->logOut(sprintf(g11n3t('Added %d new issue events to the database'), $adds));

		return $this;
	}

	/**
	 * Method to prepare the changes for saving.
	 *
	 * @param   object   $event        The issue event
	 * @param   integer  $issueNumber  The issue number
	 *
	 * @return  array  The array of changes for activities list
	 *
	 * @since   1.0
	 */
	private function prepareChanges($event, $issueNumber)
	{
		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$query   = $db->getQuery(true);
		$changes = [];

		switch ($event->event)
		{
			case 'milestoned':
				// Get the existing milestone id
				$query->select($db->quoteName('milestone_id'))
					->from($db->quoteName('#__tracker_milestones'))
					->where($db->quoteName('title') . ' = ' . $db->quote($event->milestone->title))
					->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id);

				$db->setQuery($query);

				$milestoneId = $db->loadResult();

				$change = new \stdClass;

				$change->name = 'milestone_id';
				$change->old  = null;
				$change->new  = $milestoneId;
				break;

			case 'demilestoned':
				$change = new \stdClass;

				$change->name = 'milestone_id';
				$change->old  = $this->oldIssuesData[$issueNumber]->milestone_id;
				$change->new  = null;
				break;

			case 'labeled':
				// Get the existing label id
				$query->select($db->quoteName('label_id'))
					->from($db->quoteName('#__tracker_labels'))
					->where($db->quoteName('name') . ' = ' . $db->quote($event->label->name))
					->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id);

				$db->setQuery($query);

				$labelId = $db->loadResult();

				$change = new \stdClass;

				$change->name = 'labels';
				$change->old  = null;
				$change->new  = $labelId;
				break;

			case 'unlabeled' :
				$oldLabelId = $this->oldIssuesData[$issueNumber]->labels;

				$labels = (new TrackerProject($db, $this->project))
					->getLabels();

				// Get the id of removed label
				foreach ($labels as $labelId => $label)
				{
					if ($event->label->name == $label->name)
					{
						$oldLabelId = $labelId;
					}
				}

				$change = new \stdClass;

				$change->name = 'labels';
				$change->old  = $oldLabelId;
				$change->new  = null;
				break;

			case 'renamed':
				$change = new \stdClass;

				$change->name = 'title';
				$change->old  = $event->rename->from;
				$change->new  = $event->rename->to;
				break;

			default :
				$change = null;
		}

		if (null !== $change)
		{
			$changes[] = $change;
		}

		return $changes;
	}
}
