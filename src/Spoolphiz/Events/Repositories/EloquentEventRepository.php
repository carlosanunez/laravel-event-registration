<?php
namespace Spoolphiz\Events\Repositories;
use \App;
use \Auth;
use \DB;
use Spoolphiz\Events\Interfaces\EventRepository;
use Spoolphiz\Events\Models\Eloquent\Event;

class EloquentEventRepository extends BaseRepository implements EventRepository {
	
	protected $repoModel = 'Spoolphiz\Events\Models\Eloquent\Event';
	
	/**
	 * get a single event by id, first checks to make sure the current auth'd user has permission to view
	 *
	 * @param $eventId  	The id of the event
	 * @param $user  		Spoolphiz\Events\Models\Eloquent\User object
	 * @param $accessType  	string - 'create', 'read', 'update', 'delete'
	 *
	 * @return array
	 */
	public function findWithAccess($eventId, $user, $accessType = 'read') 
	{	
		//get events
		$events = Event::with('instructors', 'attendees', 'venue')->where('id', '=', $eventId)->get();
		
		if( $events->isEmpty() )
		{
			App::abort(404, 'Resource not found.');
		}
		else
		{
			$event = $events->first();
			
			if( !$event->allowAccess($accessType, $user) )
			{
				App::abort(401, 'You are not allowed to access this event.');
			}
		}
		
		return $event;
	}
	
	
	
	
	/**
	 * returns a new Event object
	 *
	 * @param $eventId  The id of the event
	 *
	 * @return array
	 */
	public function newEvent() 
	{	
		$event = new Event;
		
		return $event;
	}
	
	
	
	/**
	 * get all events or all of an instructor's events
	 *
	 * @param user  User
	 * @param filters  array - conditions for event retrieval 
	 *
	 * @return array
	 */
	public function all( $user )
	{	
		if( $user->isAdmin() || $user->isSalesRep() )
		{
			$events = Event::with('instructors')->get();
		}
		else
		{
			//get only this instructor's events
			$events = $user->events;
		}
		
		//$events = $this->addInsturctorIdsArray($events);
		
		return $events;
	}
	
	
	/**
	 * get events based on filters
	 *
	 * @param user		Spoolphiz\Events\Models\Eloquent\User intance
	 * @param filters	array - conditions for event retrieval 
	 *
	 * @return array
	 */
	public function filtered( $user, $filters = array() )
	{	
		//filters come in an array containing json strings, parse to all array
		$filters = $this->parseFilters($filters);
				
		//instantiate a collection object based on the current user's role
		if( $user->isAdmin() || $user->isSalesRep() )
		{
			$instance = new $this->repoModel;
			$collection = $instance->newQuery()->with('instructors');
		}
		else
		{
			$collection = $user->events();
		}
		
		$collection = $this->buildFilteredCollection($filters, $collection);
		
		//add the instructor_ids attribute
		//$collection = $this->addInsturctorIdsArray($collection);
		
		return $collection;
	}
	
	
	/**
	 * Adds a "instructor_id" attribute to each event in given collection
	 *
	 * @param collection	Illuminate\Database\Eloquent\Collection instance
	 *
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function addInsturctorIdsArray( $collection )
	{
		$collection = $collection->each(function($event)
		{
			$event->instructor_ids = $event->instructors()->lists('id');
		});
		
		return $collection;
	}
	
	
	/**
	 * delete a single event by id
	 * also deletes all associated attendees
	 *
	 * @param $eventId  The id of the event
	 *
	 * @return bool
	 */
	/*
	public function delete($eventId)
	{
		$event = Event::find($eventId);
		
		if( empty($event) )
		{
			App::abort(404, 'Resource not found');
		}
		
		if( $event->delete() )
		{
			return true;
		}
		
		return false;
	}
	*/
	
}
