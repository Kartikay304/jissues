<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Model;

use App\Text\Table\ArticlesTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use Joomla\Model\DatabaseModelInterface;
use Joomla\Model\DatabaseModelTrait;
use Joomla\Model\StatefulModelInterface;
use Joomla\Model\StatefulModelTrait;
use JTracker\Model\ListfulModelInterface;
use JTracker\Pagination\TrackerPagination;

/**
 * Articles model class.
 *
 * @since  1.0
 */
class ArticlesModel implements ListfulModelInterface, DatabaseModelInterface
{
	use StatefulModelTrait, DatabaseModelTrait;

	/**
	 * The pagination object.
	 *
	 * @var    TrackerPagination|null
	 * @since  1.0
	 */
	private $pagination;

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object.
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		$this->setDb($database);
	}

	/**
	 * Set the pagination object.
	 *
	 * @param   TrackerPagination  $pagination  The pagination object.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setPagination(TrackerPagination $pagination): void
	{
		// Setup the values to paginate over.
		$limit = (int) $this->state->get('list.limit') - (int) $this->state->get('list.links');

		$pagination->setValues($this->getTotal(), $this->getStart(), $limit);

		$this->pagination = $pagination;
	}

	/**
	 * Get the pagination object for the data set.
	 *
	 * @return  TrackerPagination
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException if the pagination object has not been set to the model
	 */
	public function getPagination(): TrackerPagination
	{
		if ($this->pagination === null)
		{
			throw new \UnexpectedValueException('Pagination not set');
		}

		return $this->pagination;
	}

	/**
	 * Get the starting number of items for the data set.
	 *
	 * @return  integer  The starting number of items available in the data set.
	 *
	 * @since   1.0
	 */
	public function getStart(): int
	{
		$start = (int) $this->getState()->get('list.start');
		$limit = (int) $this->getState()->get('list.limit');
		$total = $this->getTotal();

		if ($start > $total - $limit)
		{
			$start = max(0, (int) (ceil($total / $limit) - 1) * $limit);
		}

		return $start;
	}

	/**
	 * Get the total number of items for the data set.
	 *
	 * @return  integer  The total number of items available in the data set.
	 *
	 * @since   1.0
	 */
	public function getTotal(): int
	{
		// Store the total to the model state if not already defined
		if (!$this->state->exists('list.total'))
		{
			$this->state->set('list.total', (int) $this->getListCount($this->getListQuery()));
		}

		return $this->state->get('list.total');
	}

	/**
	 * Get an array of data items with pagination filters applied.
	 *
	 * @return  object[]
	 *
	 * @since   1.0
	 */
	public function getItems(): array
	{
		$this->db->setQuery($this->getListQuery(), $this->getStart(), $this->state->get('list.limit'));

		return $this->db->loadObjectList();
	}

	/**
	 * Returns a record count for the list query
	 *
	 * @param   DatabaseQuery  $query  The list query.
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	protected function getListCount(DatabaseQuery $query): int
	{
		// Create COUNT(*) query to allow database engine to optimize the query.
		$query = clone $query;
		$query->clear('select')
			->clear('order')
			->select('COUNT(*)');
		$this->db->setQuery($query);

		return (int) $this->db->loadResult();
	}

	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DatabaseQuery  A DatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0
	 */
	protected function getListQuery(): DatabaseQuery
	{
		return $this->db->getQuery(true)
			->select($this->db->quoteName(['article_id', 'title', 'alias', 'text']))
			->from($this->db->quoteName('#__articles'))
			->where($this->db->quoteName('is_file') . ' = 0');
	}

	/**
	 * Find an article by its alias
	 *
	 * @param   string  $alias  The item alias.
	 *
	 * @return  ArticlesTable
	 *
	 * @since   1.0
	 */
	public function findByAlias(string $alias): ArticlesTable
	{
		return (new ArticlesTable($this->db))
			->load(['alias' => $alias]);
	}

	/**
	 * Find an article by its ID
	 *
	 * @param   integer  $alias  The item ID.
	 *
	 * @return  ArticlesTable
	 *
	 * @since   1.0
	 */
	public function findById(int $id): ArticlesTable
	{
		return (new ArticlesTable($this->db))
			->load($id);
	}

	/**
	 * Delete an article
	 *
	 * @param   integer  $id  The article ID to be deleted
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function delete(int $id): void
	{
		(new ArticlesTable($this->db))
			->delete($id);
	}

	/**
	 * Save an article
	 *
	 * @param   array  $data  The article data
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function save(array $data): void
	{
		(new ArticlesTable($this->db))
			->save($data);
	}
}
