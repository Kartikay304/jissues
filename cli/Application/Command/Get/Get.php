<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Get;

use App\Projects\TrackerProject;

use Application\Command\TrackerCommand;
use Application\Command\TrackerCommandOption;

use JTracker\Github\Github;

/**
 * Class for retrieving data from external providers for selected projects
 *
 * @since  1.0
 */
class Get extends TrackerCommand
{
	/**
	 * Joomla! Github object
	 *
	 * @var    Github
	 * @since  1.0
	 */
	protected $github;

	/**
	 * The id of the current bot.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $botId = 0;

	/**
	 * Project object.
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
		$this->description = 'Retrieve Information from various sources.';

		$this
			->addOption(
				new TrackerCommandOption(
					'project',
					'p',
					'Process the project with the given ID.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'noprogress',
					'',
					"Don't use a progress bar."
				)
			);
	}

	/**
	 * Execute the command.
	 *
	 * NOTE: This command must not be executed without parameters !
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		return $this->displayMissingOption(__DIR__);
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

		// Check the rate limit immediately and switch if need be
		$rate = $this->github->authorization->getRateLimit()->resources->core;

		$this->checkGitHubRateLimit($rate->remaining);

		return $this;
	}

	/**
	 * Check the remaining GitHub rate limit.
	 *
	 * @param   integer  $remaining  The remaining count.
	 *
	 * @throws \RuntimeException
	 * @return $this
	 *
	 * @since   1.0
	 */
	protected function checkGitHubRateLimit($remaining)
	{
		// @todo hard coded values..
		$minSwitch = 500;
		$minRemain = 10;

		$this->debugOut(sprintf('Limit check: %1$d -- %2$d / %3$d', $remaining, $minSwitch, $minRemain));

		if ($remaining <= $minSwitch)
		{
			$this->switchGitHubAccount();
		}

		if ($remaining <= $minRemain)
		{
			throw new \RuntimeException(
				sprintf(
					'GitHub remaining rate limit (%1$d) dropped below the minimum (%2$d) for user %3$s.',
					$remaining, $minRemain, $this->github->getOption('api.username')
				)
			);
		}

		return $this;
	}

	/**
	 * Cycle through a list of GitHub accounts for "long running processes".
	 *
	 * @return $this
	 *
	 * @since   1.0
	 * @throws \UnexpectedValueException
	 */
	public function switchGitHubAccount()
	{
		$accounts = $this->github->getOption('api.accounts');

		if (!$accounts)
		{
			return $this;

			// @todo throw new \UnexpectedValueException('No GitHub accounts set in config.');
		}

		// Increase or reset the bot id counter.
		$this->botId = ($this->botId + 1 >= \count($accounts)) ? 0 : $this->botId + 1;

		$username = $accounts[$this->botId]->username;
		$password = $accounts[$this->botId]->password;

		$this->github->setOption('api.username', $username);
		$this->github->setOption('api.password', $password);

		$this->logOut(sprintf('Switched to bot account %s (%d)', $username, $this->botId));

		return $this;
	}
}
