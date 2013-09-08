<?php namespace StickyNotes\Auth;

/**
 * Sticky Notes
 *
 * An open source lightweight pastebin application
 *
 * @package     StickyNotes
 * @author      Sayak Banerjee
 * @copyright   (c) 2013 Sayak Banerjee <mail@sayakbanerjee.com>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 * @link        http://sayakbanerjee.com/sticky-notes
 * @since       Version 1.0
 * @filesource
 */

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\UserProviderInterface;
use Illuminate\Database\Connection;
use Illuminate\Hashing\HasherInterface;
use Session;

/**
 * StickyNotesDBUserProvider Class
 *
 * This class handles database authentication.
 *
 * @package     StickyNotes
 * @subpackage  Drivers
 * @author      Sayak Banerjee
 */
class StickyNotesDBUserProvider implements UserProviderInterface {

	/**
	 * The Eloquent user model.
	 *
	 * @var Illuminate\Database\Eloquent\Model
	 */
	protected $model;

	/**
	 * The password hashing library instance
	 *
	 * @var PHPass
	 */
	protected $crypt;

	/**
	 * Contains the retrieved user details
	 *
	 * @var object
	 */
	protected $user;

	/**
	 * Initializes the provider and sets the model instance
	 *
	 * @param  Illuminate\Database\Eloquent\Model  $model
	 * @param  PHPass                              $crypt
	 * @return void
	 */
	public function __construct($model, $crypt)
	{
		$this->model = $model;

		$this->crypt = $crypt;
	}

	/**
	 * Retrieve a user by their unique identifier.
	 *
	 * @param  mixed  $identifier
	 * @return \Illuminate\Auth\UserInterface|null
	 */
	public function retrieveById($identifier)
	{
		if (Session::get('global.installed') === TRUE)
		{
			return $this->createModel()->newQuery()->find($identifier);
		}
	}

	/**
	 * Retrieve a user by the given credentials.
	 *
	 * @param  array  $credentials
	 * @return \Illuminate\Auth\UserInterface|null
	 */
	public function retrieveByCredentials(array $credentials)
	{
		// First we will add each credential element to the query as a where clause.
		// Then we can execute the query and, if we found a user, return it in a
		// Eloquent User "model" that will be utilized by the Guard instances.
		$query = $this->createModel()->newQuery();

		foreach ($credentials as $key => $value)
		{
			if ( ! str_contains($key, 'password')) $query->where($key, $value);
		}

		// We keep it locally as we need it later to get the user salt
		// A filter for type=db is added to avoid getting users created by
		// other auth methods
		$this->user = $query->where('type', 'db')->first();

		return $this->user;
	}

	/**
	 * Validate a user against the given credentials.
	 *
	 * @param  \Illuminate\Auth\UserInterface  $user
	 * @param  array                           $credentials
	 * @return bool
	 */
	public function validateCredentials(UserInterface $user, array $credentials)
	{
		// Collect user data
		$password = $credentials['password'];

		$salt = $this->user->salt;

		$hash = $user->getAuthPassword();

		// Check if user is banned
		if ( ! $this->user->active)
		{
			App::error(401);
		}

		return $this->crypt->check('User', $password, $salt, $hash);
	}

	/**
	 * Create a new instance of the model.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function createModel()
	{
		$class = '\\'.ltrim($this->model, '\\');

		return new $class;
	}

}
