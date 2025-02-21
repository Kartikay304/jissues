<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application;

use App\Projects\TrackerProject;
use Application\Cli\CliInput;

use Application\Cli\CliOutput;
use Application\Cli\Output\Stdout;
use Application\Command\Help\Help;
use Application\Command\TrackerCommand;
use Application\Command\TrackerCommandOption;
use Application\Exception\AbortException;

use Elkuku\Console\Helper\ConsoleProgressBar;

use Joomla\Application\AbstractApplication;
use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Input;
use Joomla\Registry\Registry;

use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Input\Cli;

/**
 * CLI application for installing the tracker application
 *
 * @since  1.0
 */
class Application extends AbstractApplication implements ContainerAwareInterface, DispatcherAwareInterface
{
	use ContainerAwareTrait, DispatcherAwareTrait;

	/**
	 * Output object
	 *
	 * @var    CliOutput
	 * @since  1.0
	 */
	protected $output;

	/**
	 * CLI Input object
	 *
	 * @var    CliInput
	 * @since  1.6.0
	 */
	protected $cliInput;

	/**
	 * Quiet mode - no output.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	private $quiet = false;

	/**
	 * Verbose mode - debug output.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	private $verbose = false;

	/**
	 * Use the progress bar.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $usePBar;

	/**
	 * Progress bar format.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $pBarFormat = '[%bar%] %fraction% %elapsed% ETA: %estimate%';

	/**
	 * Array of TrackerCommandOption objects
	 *
	 * @var    TrackerCommandOption[]
	 * @since  1.0
	 */
	protected $commandOptions = [];

	/**
	 * The application input object.
	 *
	 * @var    Cli
	 * @since  1.0
	 */
	public $input;

	/**
	 * Class constructor.
	 *
	 * @param   Input\Cli   $input     An optional argument to provide dependency injection for the application's input object.  If the
	 *                                 argument is an Input\Cli object that object will become the application's input object, otherwise
	 *                                 a default input object is created.
	 * @param   Registry    $config    An optional argument to provide dependency injection for the application's config object.  If the
	 *                                 argument is a Registry object that object will become the application's config object, otherwise
	 *                                 a default config object is created.
	 * @param   CliOutput   $output    An optional argument to provide dependency injection for the application's output object.  If the
	 *                                 argument is a CliOutput object that object will become the application's input object, otherwise
	 *                                 a default output object is created.
	 * @param   CliInput    $cliInput  An optional argument to provide dependency injection for the application's CLI input object.  If the
	 *                                 argument is a CliInput object that object will become the application's input object, otherwise
	 *                                 a default input object is created.
	 *
	 * @since   1.0
	 */
	public function __construct(Input\Cli $input = null, Registry $config = null, CliOutput $output = null, CliInput $cliInput = null)
	{
		// Close the application if we are not executed from the command line.
		if (!\defined('STDOUT') || !\defined('STDIN') || !isset($_SERVER['argv']))
		{
			$this->close();
		}

		$this->input  = $input ?: new Cli;
		$this->output = $output ?: new Stdout;

		// Set the CLI input object.
		$this->cliInput = $cliInput ?: new CliInput;

		// Call the constructor as late as possible (it runs `initialise`).
		parent::__construct($config);

		// Set the current directory.
		$this->set('cwd', getcwd());

		$this->commandOptions[] = new TrackerCommandOption(
			'quiet',
			'q',
			'Be quiet - suppress output.'
		);

		$this->commandOptions[] = new TrackerCommandOption(
			'verbose',
			'v',
			'Verbose output for debugging purpose.'
		);

		$this->commandOptions[] = new TrackerCommandOption(
			'nocolors',
			'',
			'Suppress ANSI colours on unsupported terminals.'
		);

		$this->commandOptions[] = new TrackerCommandOption(
			'log',
			'',
			'Optionally log output to the specified log file.'
		);

		$this->usePBar = $this->get('cli-application.progress-bar');

		if ($this->input->get('noprogress'))
		{
			$this->usePBar = false;
		}
	}

	/**
	 * Get an output object.
	 *
	 * @return  CliOutput
	 *
	 * @since   1.0
	 */
	public function getOutput()
	{
		return $this->output;
	}

	/**
	 * Get a CLI input object.
	 *
	 * @return  CliInput
	 *
	 * @since   1.0
	 */
	public function getCliInput()
	{
		return $this->cliInput;
	}

	/**
	 * Method to run the application routines.  Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function doExecute()
	{
		$this->quiet   = $this->input->get('quiet', $this->input->get('q'));
		$this->verbose = $this->input->get('verbose', $this->input->get('v'));

		$composerCfg = json_decode(file_get_contents(JPATH_ROOT . '/composer.json'));

		$this->outputTitle('Joomla! Tracker CLI Application', $composerCfg->version);

		$args = $this->input->args;

		if (!$args || (isset($args[0]) && $args[0] == 'help'))
		{
			$command = 'help';
			$action  = 'help';
		}
		else
		{
			$command = $args[0];

			$action = (isset($args[1])) ? $args[1] : $command;
		}

		$className = 'Application\\Command\\' . ucfirst($command) . '\\' . ucfirst($action);

		if (class_exists($className) === false)
		{
			$this->out()
				->out(sprintf('Invalid command: <error>%s</error>', (($command == $action) ? $command : $command . ' ' . $action)))
				->out();

			$alternatives = $this->getAlternatives($command, $action);

			if (\count($alternatives))
			{
				$this->out('<b>Did you mean one of this?</b>')
					->out('    <question> ' . implode(' </question>    <question> ', $alternatives) . ' </question>');

				return;
			}

			$className = 'Application\\Command\\Help\\Help';
		}

		if (method_exists($className, 'execute') === false)
		{
			throw new \RuntimeException(sprintf('Missing method %1$s::%2$s', $className, 'execute'));
		}

		try
		{
			/** @var TrackerCommand $command */
			$command = new $className;

			if ($command instanceof ContainerAwareInterface)
			{
				$command->setContainer($this->container);
			}

			$this->checkCommandOptions($command);

			$command->execute();
		}
		catch (AbortException $e)
		{
			$this->out('')
				->out('<comment>Process aborted.</comment>');
		}

		$this->out()
			->out(str_repeat('_', 40))
			->out(
				sprintf(
					'Execution time: <b>%d sec.</b>',
					time() - $this->get('execution.timestamp')
				)
			)
			->out(str_repeat('_', 40));
	}

	/**
	 * Get alternatives for a not found command or action.
	 *
	 * @param   string  $command  The command.
	 * @param   string  $action   The action.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	protected function getAlternatives($command, $action)
	{
		$commands = (new Help)->setContainer($this->getContainer())->getCommands();

		$alternatives = [];

		if (\array_key_exists($command, $commands) === false)
		{
			// Unknown command
			foreach (array_keys($commands) as $cmd)
			{
				if (levenshtein($cmd, $command) <= \strlen($cmd) / 3 || strpos($cmd, $command) !== false)
				{
					$alternatives[] = $cmd;
				}
			}
		}
		else
		{
			// Known command - unknown action
			$actions = (new Help)->setContainer($this->getContainer())->getActions($command);

			foreach (array_keys($actions) as $act)
			{
				if (levenshtein($act, $action) <= \strlen($act) / 3 || strpos($act, $action) !== false)
				{
					$alternatives[] = $command . ' ' . $act;
				}
			}
		}

		return $alternatives;
	}

	/**
	 * Get a value from standard input.
	 *
	 * @return  string  The input string from standard input.
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	public function in()
	{
		return $this->getCliInput()->in();
	}

	/**
	 * Write a string to standard output.
	 *
	 * @param   string   $text     The text to display.
	 * @param   boolean  $newline  True (default) to append a new line at the end of the output string.
	 *
	 * @return  $this
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	public function out($text = '', $newline = true)
	{
		if (!$this->quiet)
		{
			$this->getOutput()->out($text, $newline);
		}

		return $this;
	}

	/**
	 * Write a string to standard output in "verbose" mode.
	 *
	 * @param   string  $text  The text to display.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function debugOut($text)
	{
		return ($this->verbose) ? $this->out('DEBUG ' . $text) : $this;
	}

	/**
	 * Output a nicely formatted title for the application.
	 *
	 * @param   string   $title     The title to display.
	 * @param   string   $subTitle  A subtitle.
	 * @param   integer  $width     Total width in chars.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function outputTitle($title, $subTitle = '', $width = 60)
	{
		$this->out(str_repeat('-', $width));

		$this->out(str_repeat(' ', $width / 2 - (\strlen($title) / 2)) . '<title>' . $title . '</title>');

		if ($subTitle)
		{
			$this->out(str_repeat(' ', $width / 2 - (\strlen($subTitle) / 2)) . '<b>' . $subTitle . '</b>');
		}

		$this->out(str_repeat('-', $width));

		return $this;
	}

	/**
	 * Get the command options.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getCommandOptions()
	{
		return $this->commandOptions;
	}

	/**
	 * Check if command options conflict with application options.
	 *
	 * @param   TrackerCommand  $command  The command.
	 *
	 * @return $this
	 */
	private function checkCommandOptions(TrackerCommand $command)
	{
		// This error should only happen during development so the message might not be translated.
		$message = 'The command "%s" option "%s" already defined in the application.';

		// Check command options against application options.
		foreach ($command->getOptions() as $option)
		{
			foreach ($this->commandOptions as $commandOption)
			{
				if ($commandOption->longArg == $option->longArg)
				{
					throw new \UnexpectedValueException(sprintf($message, \get_class($command), $option->longArg));
				}

				if ($commandOption->shortArg && $commandOption->shortArg == $option->shortArg)
				{
					throw new \UnexpectedValueException(sprintf($message, \get_class($command), $option->shortArg));
				}
			}
		}

		// Check for unknown arguments from user input.
		$allOptions = array_merge($command->getOptions(), $this->commandOptions);

		foreach ($this->input->getArguments() as $argument)
		{
			foreach ($allOptions as $option)
			{
				if ($option->longArg == $argument || $option->shortArg == $argument)
				{
					continue 2;
				}
			}

			throw new \UnexpectedValueException(sprintf('The argument "%s" is not recognized.', $argument));
		}

		return $this;
	}

	/**
	 * Get a user object.
	 *
	 * Some methods check for an authenticated user...
	 *
	 * @return  GitHubUser
	 *
	 * @since   1.0
	 */
	public function getUser()
	{
		// Urgh..
		$user = new GitHubUser(
			new TrackerProject($this->container->get('db')),
			$this->container->get('db')
		);
		$user->isAdmin = true;

		return $user;
	}

	/**
	 * Display the GitHub rate limit.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function displayGitHubRateLimit()
	{
		$this->out()
			->out('<info>GitHub rate limit:...</info> ', false);

		$rate = $this->container->get('gitHub')->authorization->getRateLimit()->resources->core;

		$this->out(sprintf('%1$d (remaining: <b>%2$d</b>)', $rate->limit, $rate->remaining))
			->out();

		return $this;
	}

	/**
	 * Get a progress bar object.
	 *
	 * @param   integer  $targetNum  The target number.
	 *
	 * @return  ConsoleProgressBar
	 *
	 * @since   1.0
	 */
	public function getProgressBar($targetNum)
	{
		return ($this->usePBar)
			? new ConsoleProgressBar($this->pBarFormat, '=>', ' ', 60, $targetNum)
			: null;
	}

	/**
	 * This is a useless legacy function.
	 *
	 * Actually it's accessed by the \JTracker\Model\AbstractTrackerListModel
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @todo    Remove
	 */
	public function getUserStateFromRequest()
	{
		return '';
	}

	/**
	 * Add a profiler mark. This is being called in the stack, stopping cli updates. This bodges a fix
	 * but should be either removed or fully implemented in the future
	 *
	 * @param   string  $text  The message for the mark.
	 *
	 * @return  static  Method allows chaining
	 *
	 * @since   1.0
	 * @todo    Remove
	 */
	public function mark($text)
	{
		return $this;
	}
}
