<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Cli;

/**
 * Class ColorStyle
 *
 * @since       1.0
 * @deprecated  2.0  Use the `joomla/console` package instead
 */
final class ColorStyle
{
	/**
	 * Known colors
	 *
	 * @var    array
	 * @since  1.0
	 */
	private static $knownColors = [
		'black'   => 0,
		'red'     => 1,
		'green'   => 2,
		'yellow'  => 3,
		'blue'    => 4,
		'magenta' => 5,
		'cyan'    => 6,
		'white'   => 7,
	];

	/**
	 * Known styles
	 *
	 * @var    array
	 * @since  1.0
	 */
	private static $knownOptions = [
		'bold'       => 1,
		'underscore' => 4,
		'blink'      => 5,
		'reverse'    => 7,
	];

	/**
	 * Foreground base value
	 *
	 * @var    integer
	 * @since  1.0
	 */
	private static $fgBase = 30;

	/**
	 * Background base value
	 *
	 * @var    integer
	 * @since  1.0
	 */
	private static $bgBase = 40;

	/**
	 * Foreground color
	 *
	 * @var    integer
	 * @since  1.0
	 */
	private $fgColor = 0;

	/**
	 * Background color
	 *
	 * @var    integer
	 * @since  1.0
	 */
	private $bgColor = 0;

	/**
	 * Array of style options
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $options = [];

	/**
	 * Constructor
	 *
	 * @param   string  $fg       Foreground color.
	 * @param   string  $bg       Background color.
	 * @param   array   $options  Style options.
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function __construct($fg = '', $bg = '', $options = [])
	{
		if ($fg)
		{
			if (\array_key_exists($fg, static::$knownColors) == false)
			{
				throw new \InvalidArgumentException(
					sprintf('Invalid foreground color "%1$s" [%2$s]',
						$fg,
						implode(', ', $this->getKnownColors())
					)
				);
			}

			$this->fgColor = static::$fgBase + static::$knownColors[$fg];
		}

		if ($bg)
		{
			if (\array_key_exists($bg, static::$knownColors) == false)
			{
				throw new \InvalidArgumentException(
					sprintf('Invalid background color "%1$s" [%2$s]',
						$bg,
						implode(', ', $this->getKnownColors())
					)
				);
			}

			$this->bgColor = static::$bgBase + static::$knownColors[$bg];
		}

		foreach ($options as $option)
		{
			if (\array_key_exists($option, static::$knownOptions) == false)
			{
				throw new \InvalidArgumentException(
					sprintf('Invalid option "%1$s" [%2$s]',
						$option,
						implode(', ', $this->getKnownOptions())
					)
				);
			}

			$this->options[] = $option;
		}
	}

	/**
	 * Convert to a string.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function __toString()
	{
		return $this->getStyle();
	}

	/**
	 * Create a color style from a parameter string.
	 *
	 * Example: fg=red;bg=blue;options=bold,blink
	 *
	 * @param   string  $string  The parameter string.
	 *
	 * @return  ColorStyle  Instance of $this to allow chaining.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public static function fromString($string)
	{
		$fg      = '';
		$bg      = '';
		$options = [];

		$parts = explode(';', $string);

		foreach ($parts as $part)
		{
			$subParts = explode('=', $part);

			if (\count($subParts) < 2)
			{
				continue;
			}

			switch ($subParts[0])
			{
				case 'fg':
					$fg = $subParts[1];

					break;

				case 'bg':
					$bg = $subParts[1];

					break;

				case 'options':
					$options = explode(',', $subParts[1]);

					break;

				default:
					throw new \RuntimeException('Invalid option');

					break;
			}
		}

		return new self($fg, $bg, $options);
	}

	/**
	 * Get the translated color code.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getStyle()
	{
		$values = [];

		if ($this->fgColor)
		{
			$values[] = $this->fgColor;
		}

		if ($this->bgColor)
		{
			$values[] = $this->bgColor;
		}

		foreach ($this->options as $option)
		{
			$values[] = static::$knownOptions[$option];
		}

		return implode(';', $values);
	}

	/**
	 * Get the known colors.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getKnownColors()
	{
		return array_keys(static::$knownColors);
	}

	/**
	 * Get the known options.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getKnownOptions()
	{
		return array_keys(static::$knownOptions);
	}
}
