<?php
namespace Spoolphiz\Events\Models\Eloquent;
use \Eloquent;
use \Validator;
use Attendee;

class Event extends Eloquent {
	
	protected $userRoleIds = array('INSTRUCTOR'=>3);

	 /**
	 * A white-list of fillable attributes - not really needed for this model but included for completeness
	 *
	 * @var array
	 */
	protected $fillable = array('event_type_id', 'venue_id', 'start_date', 'end_date', 'title', 'contact_phone', 'seminar_price', 'full_price', 'capacity', 'status', 'create_seminaronly', 'create_fullevent');

	 /**
	 * Validator rules
	 *
	 * @var array
	 */
	/*protected $validators = array('name' => array('required'), 
								'address1' => array('required'), 
								'city' => array('required'), 
								'state' => array('required'), 
								'zip' => array('required'), 
								'country_id' => array('required'), 
								);
	*/
	 /**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'events';
	
	 
	 /**
	 * Relationships
	 */
	public function attendees()
    {
        return $this->hasMany('Attendee');
    }

	public function instructors()
    {
        return $this->belongsToMany('User', 'event_instructor', 'event_id', 'user_id')->where('role_id', '=', $this->userRoleIds['INSTRUCTOR']);
    }

	
	/**
	 * Validate the model's attributes.
	 *
	 * @return void
	 */
	public function validate() 
	{
		/*$val = Validator::make($this->attributes, array('name' => 'required',));

		if ($val->fails())
		{
			throw new ValidationException($val);
		}*/
	}
	
	
	/**
	 * deletes a event and its associated attendee records
	 *
	 * @return bool
	 */
	public function delete() 
	{
		$attendees = $this->attendees;
		
		foreach( $attendees as $attendee )
		{
			$attendee->delete();
		}
		
		return parent::delete();
	}
	
	
	/**
	 * decides if a user is allowed CRUD access to this resource - only happens if
	 * the user is admin, sales rep or listed as an instructor on the event
	 *
	 * @return bool
	 */
	public function allowAccess( $type, $user ) 
	{	
		switch( $type )
		{
			case 'read':
				if( $user->isAdmin() || $user->isSalesRep() )
				{
					return true;
				}
				else
				{
					foreach( $this->instructors as $instructor )
					{
						if( $instructor->id == $user->id )
						{
							return true;
						}
					}
				}
			default:
				return false;
		}
		
		
		
		return false;
	}
}