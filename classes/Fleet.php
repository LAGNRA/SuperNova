<?php
use DBStatic\DBStaticFleetACS;
use DBStatic\DBStaticNote;
use DBStatic\DBStaticPlanet;
use DBStatic\DBStaticUnit;
use DBStatic\DBStaticUser;
use Exception\ExceptionFleetInvalid;
use Exception\ExceptionPropertyAccess;
use Vector\Vector;

/**
 * Class Fleet
 *
 * @property int dbId
 * @property int playerOwnerId
 * @property int group_id
 * @property int mission_type
 * @property int target_owner_id
 * @property int is_returning
 *
 * @property int time_launch
 * @property int time_arrive_to_target
 * @property int time_mission_job_complete
 * @property int time_return_to_source
 *
 * @property int fleet_start_planet_id
 * @property int fleet_start_galaxy
 * @property int fleet_start_system
 * @property int fleet_start_planet
 * @property int fleet_start_type
 *
 * @property int fleet_end_planet_id
 * @property int fleet_end_galaxy
 * @property int fleet_end_system
 * @property int fleet_end_planet
 * @property int fleet_end_type
 *
 */
class Fleet extends UnitContainer {

  // DBRow inheritance *************************************************************************************************

  /**
   * Table name in DB
   *
   * @var string
   */
  protected static $_table = 'fleets';
  /**
   * Name of ID field in DB
   *
   * @var string
   */
  protected static $_dbIdFieldName = 'fleet_id';
  /**
   * DB_ROW to Class translation scheme
   *
   * @var array
   */
  protected $_properties = array(
    'dbId'          => array(
      P_DB_FIELD => 'fleet_id',
    ),
    'playerOwnerId' => array(
      P_METHOD_EXTRACT => 'ownerExtract',
      P_METHOD_INJECT  => 'ownerInject',
//      P_DB_FIELD => 'fleet_owner',
    ),
    'mission_type'  => array(
      P_DB_FIELD   => 'fleet_mission',
      P_FUNC_INPUT => 'intval',
    ),

    'target_owner_id' => array(
      P_DB_FIELD => 'fleet_target_owner',
    ),
    'group_id'        => array(
      P_DB_FIELD => 'fleet_group',
    ),
    'is_returning'    => array(
      P_DB_FIELD   => 'fleet_mess',
      P_FUNC_INPUT => 'intval',
    ),

    'shipCount' => array(
      P_DB_FIELD  => 'fleet_amount',
// TODO - CHECK !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//      P_FUNC_OUTPUT => 'get_ship_count',
//      P_DB_FIELDS_LINKED => array(
//        'fleet_amount',
//      ),
      P_READ_ONLY => true,
    ),

    'time_launch' => array(
      P_DB_FIELD => 'start_time',
    ),

    'time_arrive_to_target'     => array(
      P_DB_FIELD => 'fleet_start_time',
    ),
    'time_mission_job_complete' => array(
      P_DB_FIELD => 'fleet_end_stay',
    ),
    'time_return_to_source'     => array(
      P_DB_FIELD => 'fleet_end_time',
    ),

    'fleet_start_planet_id' => array(
      P_DB_FIELD   => 'fleet_start_planet_id',
      P_FUNC_INPUT => 'nullIfEmpty',
    ),

    'fleet_start_galaxy' => array(
      P_DB_FIELD => 'fleet_start_galaxy',
    ),
    'fleet_start_system' => array(
      P_DB_FIELD => 'fleet_start_system',
    ),
    'fleet_start_planet' => array(
      P_DB_FIELD => 'fleet_start_planet',
    ),
    'fleet_start_type'   => array(
      P_DB_FIELD => 'fleet_start_type',
    ),

    'fleet_end_planet_id' => array(
      P_DB_FIELD   => 'fleet_end_planet_id',
      P_FUNC_INPUT => 'nullIfEmpty',
    ),
    'fleet_end_galaxy'    => array(
      P_DB_FIELD => 'fleet_end_galaxy',
    ),
    'fleet_end_system'    => array(
      P_DB_FIELD => 'fleet_end_system',
    ),
    'fleet_end_planet'    => array(
      P_DB_FIELD => 'fleet_end_planet',
    ),
    'fleet_end_type'      => array(
      P_DB_FIELD => 'fleet_end_type',
    ),

    'resource_list' => array(
      P_METHOD_EXTRACT   => 'resourcesExtract',
      P_METHOD_INJECT    => 'resourcesInject',
      P_DB_FIELDS_LINKED => array(
        'fleet_resource_metal',
        'fleet_resource_crystal',
        'fleet_resource_deuterium',
      ),
    ),
  );


  // UnitContainer inheritance *****************************************************************************************
  /**
   * Type of this location
   *
   * @var int $locationType
   */
  protected static $locationType = LOC_FLEET;


  // New properties ****************************************************************************************************
  /**
   * `fleet_owner`
   *
   * @var int
   */
  protected $_playerOwnerId = 0;
  /**
   * `fleet_group`
   *
   * @var int
   */
  protected $_group_id = 0;
  public $acs = array();

  /**
   * `fleet_mission`
   *
   * @var int
   */
  protected $_mission_type = 0;

  /**
   * `fleet_target_owner`
   *
   * @var int
   */
  protected $_target_owner_id = null;

  /**
   * @var array
   */
  public $resource_list = array(
    RES_METAL     => 0,
    RES_CRYSTAL   => 0,
    RES_DEUTERIUM => 0,
  );


  /**
   * `fleet__mess` - Флаг возвращающегося флота
   *
   * @var int
   */
  protected $_is_returning = 0;
  /**
   * `start_time` - Время отправления - таймштамп взлёта флота из точки отправления
   *
   * @var int $_time_launch
   */
  protected $_time_launch = 0; // `start_time` = SN_TIME_NOW
  /**
   * `fleet_start_time` - Время прибытия в точку миссии/время начала выполнения миссии
   *
   * @var int $_time_arrive_to_target
   */
  protected $_time_arrive_to_target = 0; // `fleet_start_time` = SN_TIME_NOW + $time_travel
  /**
   * `fleet_end_stay` - Время окончания миссии в точке назначения
   *
   * @var int $_time_mission_job_complete
   */
  protected $_time_mission_job_complete = 0; // `fleet_end_stay`
  /**
   * `fleet_end_time` - Время возвращения флота после окончания миссии
   *
   * @var int $_time_return_to_source
   */
  protected $_time_return_to_source = 0; // `fleet_end_time`


  protected $_fleet_start_planet_id = null;
  protected $_fleet_start_galaxy = 0;
  protected $_fleet_start_system = 0;
  protected $_fleet_start_planet = 0;
  protected $_fleet_start_type = PT_ALL;

  protected $_fleet_end_planet_id = null;
  protected $_fleet_end_galaxy = 0;
  protected $_fleet_end_system = 0;
  protected $_fleet_end_planet = 0;
  protected $_fleet_end_type = PT_ALL;

  // Missile properties
  public $missile_target = 0;

  // Fleet event properties
  public $fleet_start_name = '';
  public $fleet_end_name = '';
  public $ov_label = '';
  public $ov_this_planet = '';
  public $event_time = 0;

  protected $resource_delta = array();
  protected $resource_replace = array();


//


  /**
   * @var array $allowed_missions
   */
  public $allowed_missions = array();
  /**
   * @var array $exists_missions
   */
  public $exists_missions = array();
  public $allowed_planet_types = array(
    // PT_NONE => PT_NONE,
    PT_PLANET => PT_PLANET,
    PT_MOON   => PT_MOON,
    PT_DEBRIS => PT_DEBRIS
  );

  // TODO - Move to Player
  public $dbOwnerRow = array();
  public $dbSourcePlanetRow = array();

  /**
   * GSPT coordinates of target
   *
   * @var Vector
   */
  public $targetVector = array();
  /**
   * Target planet row
   *
   * @var array
   */
  public $dbTargetRow = array();
  public $dbTargetOwnerRow = array();

  /**
   * Fleet speed - old in 1/10 of 100%
   *
   * @var int
   */
  public $oldSpeedInTens = 0;

  public $tempPlayerMaxFleets = 0;
  public $travelData = array();

  public $isRealFlight = false;

  /**
   * @var int $targetedUnitId
   */
  public $targetedUnitId = 0;

  /**
   * @var array $captain
   */
  public $captain = array();
  /**
   * @var int $captainId
   */
  public $captainId = 0;

  /**
   * @var FleetValidator $validator
   */
  public $validator;


  /**
   * @var UnitList $unitList
   */
  protected $unitList = null;

  /**
   * @var \Planet\PlanetRenderer $planetRenderer
   */
  protected $planetRenderer;

  /**
   * @var FleetRenderer
   */
  protected $fleetRenderer;


  /**
   * Fleet constructor.
   */
  public function __construct() {
    parent::__construct();
    $this->exists_missions = sn_get_groups('missions');
    $this->validator = new FleetValidator($this);

    $this->planetRenderer = classSupernova::$gc->planetRenderer;
    $this->fleetRenderer = classSupernova::$gc->fleetRenderer;
  }

  /**
   * @return UnitList
   */
  public function getUnitList() {
    return $this->unitList;
  }

  public function isEmpty() {
    return !$this->resourcesGetTotal() && !$this->shipsGetTotal();
  }

//  public function getPlayerOwnerId() {
//    return $this->playerOwnerId;
//  }

  /**
   * Initializes Fleet from user params and posts it to DB
   */
  public function dbInsert() {
    // WARNING! MISSION TIMES MUST BE SET WITH set_times() method!
    // TODO - more checks!
    if (empty($this->_time_launch)) {
      die('Fleet time not set!');
    }

    parent::dbInsert();
  }


  /* FLEET DB ACCESS =================================================================================================*/

  /**
   * LOCK - Lock all records which can be used with mission
   *
   * @param $mission_data
   *
   * @return array|bool|mysqli_result|null
   */
  public function dbLockFlying(&$mission_data) {
    // Тупо лочим всех юзеров, чьи флоты летят или улетают с координат отбытия/прибытия $fleet_row
    // Что бы делать это умно - надо учитывать fleet__mess во $fleet_row и в таблице fleets

    $fleet_id_safe = idval($this->_dbId);

    return classSupernova::$db->doSelect(
    // Блокировка самого флота
      "SELECT 1 FROM {{fleets}} AS f " .

      // Блокировка всех юнитов, принадлежащих этому флоту
      "LEFT JOIN {{unit}} as unit ON unit.unit_location_type = " . static::$locationType . " AND unit.unit_location_id = f.fleet_id " .

      // Блокировка всех прилетающих и улетающих флотов, если нужно
      // TODO - lock fleets by COORDINATES
      ($mission_data['dst_fleets'] ? "LEFT JOIN {{fleets}} AS fd ON fd.fleet_end_planet_id = f.fleet_end_planet_id OR fd.fleet_start_planet_id = f.fleet_end_planet_id " : '') .
      // Блокировка всех юнитов, принадлежащих прилетающим и улетающим флотам - ufd = unit_fleet_destination
      ($mission_data['dst_fleets'] ? "LEFT JOIN {{unit}} AS ufd ON ufd.unit_location_type = " . static::$locationType . " AND ufd.unit_location_id = fd.fleet_id " : '') .

      ($mission_data['dst_user'] || $mission_data['dst_planet'] ? "LEFT JOIN {{users}} AS ud ON ud.id = f.fleet_target_owner " : '') .
      // Блокировка всех юнитов, принадлежащих владельцу планеты-цели
      ($mission_data['dst_user'] || $mission_data['dst_planet'] ? "LEFT JOIN {{unit}} AS unit_player_dest ON unit_player_dest.unit_player_id = ud.id " : '') .
      // Блокировка планеты-цели
      ($mission_data['dst_planet'] ? "LEFT JOIN {{planets}} AS pd ON pd.id = f.fleet_end_planet_id " : '') .
      // Блокировка всех юнитов, принадлежащих планете-цели - НЕ НУЖНО. Уже залочили ранее, как принадлежащие игроку-цели
//      ($mission_data['dst_planet'] ? "LEFT JOIN {{unit}} AS upd ON upd.unit_location_type = " . LOC_PLANET . " AND upd.unit_location_id = pd.id " : '') .


      ($mission_data['src_user'] || $mission_data['src_planet'] ? "LEFT JOIN {{users}} AS us ON us.id = f.fleet_owner " : '') .
      // Блокировка всех юнитов, принадлежащих владельцу флота
      ($mission_data['src_user'] || $mission_data['src_planet'] ? "LEFT JOIN {{unit}} AS unit_player_src ON unit_player_src.unit_player_id = us.id " : '') .
      // Блокировка планеты отправления
      ($mission_data['src_planet'] ? "LEFT JOIN {{planets}} AS ps ON ps.id = f.fleet_start_planet_id " : '') .
      // Блокировка всех юнитов, принадлежащих планете с которой юниты были отправлены - НЕ НУЖНО. Уже залочили ранее, как принадлежащие владельцу флота
//      ($mission_data['src_planet'] ? "LEFT JOIN {{unit}} AS ups ON ups.unit_location_type = " . LOC_PLANET . " AND ups.unit_location_id = ps.id " : '') .

      "WHERE f.fleet_id = {$fleet_id_safe} GROUP BY 1 FOR UPDATE"
    );
  }

  /**
   * Lock all fields that belongs to operation
   *
   * @param $dbId
   */
  // TODO = make static
  public function dbGetLockById($dbId) {
    classSupernova::$db->doSelect(
    // Блокировка самого флота
      "SELECT 1 FROM {{fleets}} AS FLEET0 " .
      // Lock fleet owner
      "LEFT JOIN {{users}} as USER0 on USER0.id = FLEET0.fleet_owner " .
      // Блокировка всех юнитов, принадлежащих этому флоту
      "LEFT JOIN {{unit}} as UNIT0 ON UNIT0.unit_location_type = " . LOC_FLEET . " AND UNIT0.unit_location_id = FLEET0.fleet_id " .

      // Без предварительной выборки неизвестно - куда летит этот флот.
      // Поэтому надо выбирать флоты, чьи координаты прибытия ИЛИ отбытия совпадают с координатами прибытия ИЛИ отбытия текущего флота.
      // Получаем матрицу 2х2 - т.е. 4 подзапроса.
      // При блокировке всегда нужно выбирать И лпанету, И луну - поскольку при бое на орбите луны обломки падают на орбиту планеты.
      // Поэтому тип планеты не указывается

      // Lock fleet heading to destination planet. Only if FLEET0.fleet_mess == 0
      "LEFT JOIN {{fleets}} AS FLEET1 ON
        FLEET1.fleet_mess = 0 AND FLEET0.fleet_mess = 0 AND
        FLEET1.fleet_end_galaxy = FLEET0.fleet_end_galaxy AND
        FLEET1.fleet_end_system = FLEET0.fleet_end_system AND
        FLEET1.fleet_end_planet = FLEET0.fleet_end_planet
      " .
      // Блокировка всех юнитов, принадлежащих этим флотам
      "LEFT JOIN {{unit}} as UNIT1 ON UNIT1.unit_location_type = " . LOC_FLEET . " AND UNIT1.unit_location_id = FLEET1.fleet_id " .
      // Lock fleet owner
      "LEFT JOIN {{users}} as USER1 on USER1.id = FLEET1.fleet_owner " .

      "LEFT JOIN {{fleets}} AS FLEET2 ON
        FLEET2.fleet_mess = 1   AND FLEET0.fleet_mess = 0 AND
        FLEET2.fleet_start_galaxy = FLEET0.fleet_end_galaxy AND
        FLEET2.fleet_start_system = FLEET0.fleet_end_system AND
        FLEET2.fleet_start_planet = FLEET0.fleet_end_planet
      " .
      // Блокировка всех юнитов, принадлежащих этим флотам
      "LEFT JOIN {{unit}} as UNIT2 ON
        UNIT2.unit_location_type = " . LOC_FLEET . " AND
        UNIT2.unit_location_id = FLEET2.fleet_id
      " .
      // Lock fleet owner
      "LEFT JOIN {{users}} as USER2 on
        USER2.id = FLEET2.fleet_owner
      " .

      // Lock fleet heading to source planet. Only if FLEET0.fleet_mess == 1
      "LEFT JOIN {{fleets}} AS FLEET3 ON
        FLEET3.fleet_mess = 0 AND FLEET0.fleet_mess = 1 AND
        FLEET3.fleet_end_galaxy = FLEET0.fleet_start_galaxy AND
        FLEET3.fleet_end_system = FLEET0.fleet_start_system AND
        FLEET3.fleet_end_planet = FLEET0.fleet_start_planet
      " .
      // Блокировка всех юнитов, принадлежащих этим флотам
      "LEFT JOIN {{unit}} as UNIT3 ON
        UNIT3.unit_location_type = " . LOC_FLEET . " AND
        UNIT3.unit_location_id = FLEET3.fleet_id
      " .
      // Lock fleet owner
      "LEFT JOIN {{users}} as USER3 on USER3.id = FLEET3.fleet_owner " .

      "LEFT JOIN {{fleets}} AS FLEET4 ON
        FLEET4.fleet_mess = 1   AND FLEET0.fleet_mess = 1 AND
        FLEET4.fleet_start_galaxy = FLEET0.fleet_start_galaxy AND
        FLEET4.fleet_start_system = FLEET0.fleet_start_system AND
        FLEET4.fleet_start_planet = FLEET0.fleet_start_planet
      " .
      // Блокировка всех юнитов, принадлежащих этим флотам
      "LEFT JOIN {{unit}} as UNIT4 ON
        UNIT4.unit_location_type = " . LOC_FLEET . " AND
        UNIT4.unit_location_id = FLEET4.fleet_id
      " .
      // Lock fleet owner
      "LEFT JOIN {{users}} as USER4 on
        USER4.id = FLEET4.fleet_owner
      " .


      // Locking start planet
      "LEFT JOIN {{planets}} AS PLANETS5 ON
        FLEET0.fleet_mess = 1 AND
        PLANETS5.galaxy = FLEET0.fleet_start_galaxy AND
        PLANETS5.system = FLEET0.fleet_start_system AND
        PLANETS5.planet = FLEET0.fleet_start_planet
      " .
      // Lock planet owner
      "LEFT JOIN {{users}} as USER5 on
        USER5.id = PLANETS5.id_owner
      " .
      // Блокировка всех юнитов, принадлежащих этой планете
      "LEFT JOIN {{unit}} as UNIT5 ON
        UNIT5.unit_location_type = " . LOC_PLANET . " AND
        UNIT5.unit_location_id = PLANETS5.id
      " .


      // Locking destination planet
      "LEFT JOIN {{planets}} AS PLANETS6 ON
        FLEET0.fleet_mess = 0 AND
        PLANETS6.galaxy = FLEET0.fleet_end_galaxy AND
        PLANETS6.system = FLEET0.fleet_end_system AND
        PLANETS6.planet = FLEET0.fleet_end_planet
      " .
      // Lock planet owner
      "LEFT JOIN {{users}} as USER6 on
        USER6.id = PLANETS6.id_owner
      " .
      // Блокировка всех юнитов, принадлежащих этой планете
      "LEFT JOIN {{unit}} as UNIT6 ON
        UNIT6.unit_location_type = " . LOC_PLANET . " AND
        UNIT6.unit_location_id = PLANETS6.id
      " .
      "WHERE FLEET0.fleet_id = {$dbId} GROUP BY 1 FOR UPDATE"
    );
  }


  public function dbRowParse($db_row) {
    parent::dbRowParse($db_row); // TODO: Change the autogenerated stub
    $player = new Player();
    $player->dbLoad($db_row['fleet_owner']);
    $this->setLocatedAt($player);
  }

  /* FLEET HELPERS =====================================================================================================*/
  /**
   * Forcibly returns fleet before time outs
   */
  public function commandReturn() {
    $ReturnFlyingTime = ($this->_time_mission_job_complete != 0 && $this->_time_arrive_to_target < SN_TIME_NOW ? $this->_time_arrive_to_target : SN_TIME_NOW) - $this->_time_launch + SN_TIME_NOW + 1;

    // Считаем, что флот уже долетел TODO
    $this->time_arrive_to_target = SN_TIME_NOW;
    // Убираем флот из группы
    $this->group_id = 0;
    // Отменяем работу в точке назначения
    $this->time_mission_job_complete = 0;
    // TODO - правильно вычслять время возвращения - по проделанному пути, а не по старому времени возвращения
    $this->time_return_to_source = $ReturnFlyingTime;

    // Записываем изменения в БД
    $this->markReturnedAndSave();

    if ($this->_group_id) {
      // TODO: Make here to delete only one AKS - by adding aks_fleet_count to AKS table
      DBStaticFleetACS::db_fleet_aks_purge();
    }
  }


  /**
   * @return array
   */
  public function target_coordinates_without_type() {
    return array(
      'galaxy' => $this->_fleet_end_galaxy,
      'system' => $this->_fleet_end_system,
      'planet' => $this->_fleet_end_planet,
    );
  }

  /**
   * @return array
   */
  public function target_coordinates_typed() {
    return array(
      'galaxy' => $this->_fleet_end_galaxy,
      'system' => $this->_fleet_end_system,
      'planet' => $this->_fleet_end_planet,
      'type'   => $this->_fleet_end_type,
    );
  }

  /**
   * @return array
   */
  public function launch_coordinates_typed() {
    return array(
      'galaxy' => $this->_fleet_start_galaxy,
      'system' => $this->_fleet_start_system,
      'planet' => $this->_fleet_start_planet,
      'type'   => $this->_fleet_start_type,
    );
  }

  public function isReturning() {
    return FLEET_FLAG_RETURNING == $this->_is_returning;
  }

  /**
   * Sets object fields for fleet return
   */
  public function markReturnedAndSave() {
    // TODO - Проверка - а не возвращается ли уже флот?
    $this->is_returning = FLEET_FLAG_RETURNING;
    $this->dbSave();
  }

  /**
   * Parses extended unit_array which can include not only ships but resources, captains etc
   *
   * @param $unit_array
   *
   * @throws Exception
   */
  // TODO - separate shipList and unitList
  public function unitsSetFromArray($unit_array) {
    if (empty($unit_array) || !is_array($unit_array)) {
      return;
    }
    foreach ($unit_array as $unit_id => $unit_count) {
      $unit_count = floatval($unit_count);
      if (!$unit_count) {
        continue;
      }

      if ($this->isShip($unit_id) || $this->isMissile($unit_id)) {
        $this->unitList->unitSetCount($unit_id, $unit_count);
      } elseif ($this->isResource($unit_id)) {
        $this->resource_list[$unit_id] = $unit_count;
      } else {
        throw new Exception('Trying to pass to fleet non-resource and non-ship ' . var_export($unit_array, true), FLIGHT_SHIPS_UNIT_WRONG);
      }
    }
  }


  /**
   * Sets fleet timers based on flight duration, time on mission (HOLD/EXPLORE) and fleet departure time.
   *
   * @param int $time_to_travel - flight duration in seconds
   * @param int $time_on_mission - time on mission in seconds
   * @param int $flight_departure - fleet departure from source planet timestamp. Allows to send fleet in future or in past
   */
  public function set_times($time_to_travel, $time_on_mission = 0, $flight_departure = SN_TIME_NOW) {
    $this->_time_launch = $flight_departure;

    $this->_time_arrive_to_target = $this->_time_launch + $time_to_travel;
    $this->_time_mission_job_complete = $time_on_mission ? $this->_time_arrive_to_target + $time_on_mission : 0;
    $this->_time_return_to_source = ($this->_time_mission_job_complete ? $this->_time_mission_job_complete : $this->_time_arrive_to_target) + $time_to_travel;
  }


  public function parse_missile_db_row($missile_db_row) {
//    $this->_reset();

    if (empty($missile_db_row) || !is_array($missile_db_row)) {
      return;
    }

//      $planet_start = db_planet_by_vector($irak_original, FLEET_START_PREFIX, false, 'name');
//      $irak_original['fleet_start_name'] = $planet_start['name'];
    $this->missile_target = $missile_db_row['primaer'];

    $this->_dbId = -$missile_db_row['id'];
    $this->_playerOwnerId = $missile_db_row['fleet_owner'];
    $this->_mission_type = MT_MISSILE;

    $this->_target_owner_id = $missile_db_row['fleet_target_owner'];

    $this->_group_id = 0;
    $this->_is_returning = 0;

    $this->_time_launch = 0; // $irak['start_time'];
    $this->_time_arrive_to_target = 0; // $irak['fleet_start_time'];
    $this->_time_mission_job_complete = 0; // $irak['fleet_end_stay'];
    $this->_time_return_to_source = $missile_db_row['fleet_end_time'];

    $this->_fleet_start_planet_id = !empty($missile_db_row['fleet_start_planet_id']) ? $missile_db_row['fleet_start_planet_id'] : null;
    $this->_fleet_start_galaxy = $missile_db_row['fleet_start_galaxy'];
    $this->_fleet_start_system = $missile_db_row['fleet_start_system'];
    $this->_fleet_start_planet = $missile_db_row['fleet_start_planet'];
    $this->_fleet_start_type = $missile_db_row['fleet_start_type'];

    $this->_fleet_end_planet_id = !empty($missile_db_row['fleet_end_planet_id']) ? $missile_db_row['fleet_end_planet_id'] : null;
    $this->_fleet_end_galaxy = $missile_db_row['fleet_end_galaxy'];
    $this->_fleet_end_system = $missile_db_row['fleet_end_system'];
    $this->_fleet_end_planet = $missile_db_row['fleet_end_planet'];
    $this->_fleet_end_type = $missile_db_row['fleet_end_type'];

    $this->unitList->unitSetCount(UNIT_DEF_MISSILE_INTERPLANET, $missile_db_row['fleet_amount']);
  }


  /**
   * @param $from
   */
  public function set_start_planet($from) {
    $this->fleet_start_planet_id = intval($from['id']) ? $from['id'] : null;
    $this->fleet_start_galaxy = $from['galaxy'];
    $this->fleet_start_system = $from['system'];
    $this->fleet_start_planet = $from['planet'];
    $this->fleet_start_type = $from['planet_type'];
  }

  /**
   * @param $to
   */
  public function set_end_planet($to) {
    $this->target_owner_id = intval($to['id_owner']) ? $to['id_owner'] : 0;
    $this->fleet_end_planet_id = intval($to['id']) ? $to['id'] : null;
    $this->fleet_end_galaxy = $to['galaxy'];
    $this->fleet_end_system = $to['system'];
    $this->fleet_end_planet = $to['planet'];
    $this->fleet_end_type = $to['planet_type'];
  }

  /**
   * @param Vector $to
   */
  public function setTargetFromVectorObject($to) {
    $this->_fleet_end_galaxy = $to->galaxy;
    $this->_fleet_end_system = $to->system;
    $this->_fleet_end_planet = $to->planet;
    $this->_fleet_end_type = $to->type;
  }

  /**
   * @param array $db_row
   */
  protected function ownerExtract(array &$db_row) {
    $player = new Player();
    $player->dbLoad($db_row['fleet_owner']);
    $this->setLocatedAt($player);
  }

  /**
   * @param array $db_row
   */
  protected function ownerInject(array &$db_row) {
    $db_row['fleet_owner'] = $this->getPlayerOwnerId();
  }




  // UnitList/Ships access ***************************************************************************************************

  // TODO - перекрывать пожже - для миссайл-флотов и дефенс-флотов
  protected function isShip($unit_id) {
    return UnitShip::is_in_group($unit_id);
  }

  protected function isMissile($unit_id) {
    return isInGroup(GROUP_STR_MISSILES, $unit_id);
  }

  /**
   * Set unit count of $unit_id to $unit_count
   * If there is no $unit_id - it will be created and saved to DB on dbSave
   *
   * @param int $unit_id
   * @param int $unit_count
   */
  public function shipSetCount($unit_id, $unit_count = 0) {
    pdump(__CLASS__ . '->' . __FUNCTION__);
    $this->shipAdjustCount($unit_id, $unit_count, true);
  }

  /**
   * Adjust unit count of $unit_id by $unit_count - or just replace value
   * If there is no $unit_id - it will be created and saved to DB on dbSave
   *
   * @param int  $unit_id
   * @param int  $unit_count
   * @param bool $replace_value
   */
  public function shipAdjustCount($unit_id, $unit_count = 0, $replace_value = false) {
    $this->unitList->unitAdjustCount($unit_id, $unit_count, $replace_value);
  }

  public function shipGetCount($unit_id) {
    return $this->unitList->unitGetCount($unit_id);
  }

  public function shipsCountApplyLossMultiplier($ships_lost_multiplier) {
    $this->unitList->unitsCountApplyLossMultiplier($ships_lost_multiplier);
  }

  /**
   * Returns fleet ships cost in metal
   *
   * @param array $shipCostInMetalPerPiece
   *
   * @return float[]
   */
  public function shipsCostInMetal($shipCostInMetalPerPiece) {
    return $this->unitList->unitsCostInMetal($shipCostInMetalPerPiece);
  }

  /**
   * @return UnitIterator
   */
  public function shipsIterator() {
    return $this->unitList->getUnitIterator();
  }

  public function shipsGetTotal() {
    return $this->unitList->unitsCount();
  }

  public function shipsGetCapacity() {
    return $this->unitList->shipsCapacity();
  }

  public function shipsGetHoldFree() {
    return max(0, $this->shipsGetCapacity() - $this->resourcesGetTotal());
  }

  /**
   * Get count of ships with $ship_id
   *
   * @param int $ship_id
   *
   * @return int
   */
  public function shipsGetTotalById($ship_id) {
    return $this->unitList->unitsCountById($ship_id);
  }

  /**
   * Возвращает ёмкость переработчиков во флоте
   *
   * @param array $recycler_info
   *
   * @return int
   *
   */
  public function shipsGetCapacityRecyclers($recycler_info) {
    $recyclers_incoming_capacity = 0;
    foreach ($this->shipsIterator() as $unitId => $unit) {
      if (!empty(classSupernova::$gc->groupRecyclers[$unitId]) && $unit->count >= 1) {
        $recyclers_incoming_capacity += $unit->count * $recycler_info[$unitId]['capacity'];
      }
    }

    return $recyclers_incoming_capacity;
  }

  /**
   * @return bool
   */
  // TODO - А если не на планете????
  public function shipsIsEnoughOnPlanet() {
    return $this->unitList->shipsIsEnoughOnPlanet($this->dbOwnerRow, $this->dbSourcePlanetRow);
  }

  /**
   * @return bool
   */
  public function shipsAllPositive() {
    return $this->unitList->unitsPositive();
  }

  /**
   * @return bool
   */
  public function shipsAllFlying() {
    return $this->unitList->unitsInGroup(classSupernova::$gc->groupFleetAndMissiles);
  }

  /**
   * @return bool
   */
  public function shipsAllMovable() {
    return $this->unitList->unitsIsAllMovable($this->dbOwnerRow);
  }

  /**
   * Restores fleet or resources to planet
   *
   * @param bool $start
   * @param int  $result
   */
  // TODO - split to functions
  public function shipsLand($start = true) {
    sn_db_transaction_check(true);

    // Если флот уже обработан - не существует или возращается - тогда ничего не делаем
    if ($this->isEmpty()) {
      return;
    }

    $coordinates = $start ? $this->launch_coordinates_typed() : $this->target_coordinates_typed();

    // Поскольку эта функция может быть вызвана не из обработчика флотов - нам надо всё заблокировать вроде бы НЕ МОЖЕТ!!!
    // TODO Проверить от многократного срабатывания !!!
    // Тут не блокируем пока - сначала надо заблокировать пользователя, что бы не было дедлока
    // TODO поменять на владельца планеты - когда его будут возвращать всегда !!!

    // Узнаем ИД владельца планеты.
    // С блокировкой, поскольку эта функция может быть вызвана только из менеджера летящих флотов.
    // А там уже всё заблокировано как надо и повторная блокировка не вызовет дедлок.
    $planet_arrival = DBStaticPlanet::db_planet_by_vector($coordinates, '', true);
    // Блокируем пользователя
    // TODO - вообще-то нам уже известен пользователь в МЛФ - так что можно просто передать его сюда
    $user = DBStaticUser::db_user_by_id($planet_arrival['id_owner'], true);

    // TODO - Проверка, что планета всё еще существует на указанных координатах, а не телепортировалась, не удалена хозяином, не уничтожена врагом
    // Флот, который возвращается на захваченную планету, пропадает
    // Ship landing is possible only to fleet owner's planet
    if ($this->getPlayerOwnerId() == $planet_arrival['id_owner']) {
      // Adjusting ship amount on planet
      foreach ($this->shipsIterator() as $ship_id => $ship) {
        if ($ship->count) {
          DBStaticUnit::dbUpdateOrInsertUnit($ship_id, $ship->count, $user, $planet_arrival['id']);
        }
      }

      // Restoring resources to planet
      $this->resourcesUnload($start);
    }

    RestoreFleetToPlanet($this, $start);

    $this->dbDelete();
  }


  // Resources access ***************************************************************************************************

  /**
   * Extracts resources value from db_row
   *
   * @param array $db_row
   */
  protected function resourcesExtract(array &$db_row) {
    $this->resource_list = array(
      RES_METAL     => !empty($db_row['fleet_resource_metal']) ? floor($db_row['fleet_resource_metal']) : 0,
      RES_CRYSTAL   => !empty($db_row['fleet_resource_crystal']) ? floor($db_row['fleet_resource_crystal']) : 0,
      RES_DEUTERIUM => !empty($db_row['fleet_resource_deuterium']) ? floor($db_row['fleet_resource_deuterium']) : 0,
    );
  }

  protected function resourcesInject(array &$db_row) {
    $db_row['fleet_resource_metal'] = $this->resource_list[RES_METAL];
    $db_row['fleet_resource_crystal'] = $this->resource_list[RES_CRYSTAL];
    $db_row['fleet_resource_deuterium'] = $this->resource_list[RES_DEUTERIUM];
  }

  /**
   * Set current resource list from array of units
   *
   * @param array $resource_list
   */
  public function resourcesSet($resource_list) {
    if (!empty($this->propertiesAdjusted['resource_list'])) {
      throw new ExceptionPropertyAccess('Property "resource_list" already was adjusted so no SET is possible until dbSave in ' . get_called_class() . '::unitSetResourceList', ERR_ERROR);
    }
    $this->resourcesAdjust($resource_list, true);
  }

  /**
   * Updates fleet resource list with deltas
   *
   * @param array $resource_delta_list
   * @param bool  $replace_value
   *
   * @throws Exception
   */
  public function resourcesAdjust($resource_delta_list, $replace_value = false) {
    !is_array($resource_delta_list) ? $resource_delta_list = array() : false;

    foreach ($resource_delta_list as $resource_id => $unit_delta) {
      if (!UnitResourceLoot::is_in_group($resource_id) || !($unit_delta = floor($unit_delta))) {
        // Not a resource or no resources - continuing
        continue;
      }

      if ($replace_value) {
        $this->resource_list[$resource_id] = $unit_delta;
      } else {
        $this->resource_list[$resource_id] += $unit_delta;
        // Preparing changes
        $this->resource_delta[$resource_id] += $unit_delta;
        $this->propertiesAdjusted['resource_list'] = 1;
      }

      // Check for negative unit value
      if ($this->resource_list[$resource_id] < 0) {
        // TODO
        throw new Exception('Resource ' . $resource_id . ' will become negative in ' . get_called_class() . '::unitAdjustResourceList', ERR_ERROR);
      }
    }
  }

  public function resourcesGetTotal() {
    return empty($this->resource_list) || !is_array($this->resource_list) ? 0 : array_sum($this->resource_list);
  }

  /**
   * @param array $rate
   *
   * @return float
   */
  public function resourcesGetTotalInMetal(array $rate) {
    return
      $this->resource_list[RES_METAL] * $rate[RES_METAL]
      + $this->resource_list[RES_CRYSTAL] * $rate[RES_CRYSTAL] / $rate[RES_METAL]
      + $this->resource_list[RES_DEUTERIUM] * $rate[RES_DEUTERIUM] / $rate[RES_METAL];
  }

  /**
   * Returns resource list in fleet
   */
  // TODO
  public function resourcesGetList() {
    return $this->resource_list;
  }

  public function resourcesReset() {
    $this->resourcesSet(array(
      RES_METAL     => 0,
      RES_CRYSTAL   => 0,
      RES_DEUTERIUM => 0,
    ));
  }

  /**
   * Restores fleet or resources to planet
   *
   * @param bool $start
   * @param bool $only_resources
   * @param int  $result
   */
  public function resourcesUnload($start = true) {
    sn_db_transaction_check(true);

    // Если флот уже обработан - не существует или возращается - тогда ничего не делаем
    if (!$this->resourcesGetTotal()) {
      return;
    }

    $coordinates = $start ? $this->launch_coordinates_typed() : $this->target_coordinates_typed();

    // Поскольку эта функция может быть вызвана не из обработчика флотов - нам надо всё заблокировать вроде бы НЕ МОЖЕТ!!!
    // TODO Проверить от многократного срабатывания !!!
    // Тут не блокируем пока - сначала надо заблокировать пользователя, что бы не было дедлока
    // TODO поменять на владельца планеты - когда его будут возвращать всегда !!!


    // Узнаем ИД владельца планеты.
    // С блокировкой, поскольку эта функция может быть вызвана только из менеджера летящих флотов.
    // А там уже всё заблокировано как надо и повторная блокировка не вызовет дедлок.
    $planet_arrival = DBStaticPlanet::db_planet_by_vector($coordinates, '', true);

    // TODO - Проверка, что планета всё еще существует на указанных координатах, а не телепортировалась, не удалена хозяином, не уничтожена врагом

    // Restoring resources to planet
    if ($this->resourcesGetTotal()) {
      $fleet_resources = $this->resourcesGetList();
      DBStaticPlanet::db_planet_update_adjust_by_id(
        $planet_arrival['id'],
        array(
          'metal'     => +$fleet_resources[RES_METAL],
          'crystal'   => +$fleet_resources[RES_CRYSTAL],
          'deuterium' => +$fleet_resources[RES_DEUTERIUM],
        )
      );
    }

    $this->resourcesReset();
  }


  protected function isResource($unit_id) {
    return UnitResourceLoot::is_in_group($unit_id);
  }

  /**
   * @param int $speed_percent
   *
   * @return array
   */
  protected function flt_travel_data($speed_percent = 10) {
    $distance = $this->targetVector->distanceFromCoordinates($this->dbSourcePlanetRow);

    return $this->unitList->travelData($speed_percent, $distance, $this->dbOwnerRow);
  }


  /**
   * @param array  $dbPlayerRow
   * @param array  $dbPlanetRow
   * @param Vector $targetVector
   *
   */
  public function initDefaults($dbPlayerRow, $dbPlanetRow, $targetVector, $mission, $ships, $fleet_group_mr, $oldSpeedInTens = 10, $targetedUnitId = 0, $captainId = 0, $resources = array()) {
    $objFleet5Player = new Player();
    $objFleet5Player->dbRowParse($dbPlayerRow);
    $this->setLocatedAt($objFleet5Player);

    $this->mission_type = $mission;

    $this->dbOwnerRow = $dbPlayerRow;

    $this->set_start_planet($dbPlanetRow);
    $this->dbSourcePlanetRow = $dbPlanetRow;

    $this->setTargetFromVectorObject($targetVector);
    $this->targetVector = $targetVector;

//    if ($this->mission_type != MT_NONE) {
//      $this->restrictTargetTypeByMission();
//
//      // TODO - Нельзя тут просто менять тип планеты или координат!
//      // If current planet type is not allowed on mission - switch planet type
//      if (empty($this->allowed_planet_types[$this->targetVector->type])) {
//        $targetPlanetCoords->type = reset($this->allowed_planet_types);
//      }
//    }

    $this->populateTargetPlanetAndOwner();

    $this->unitsSetFromArray($ships);

    $this->_group_id = intval($fleet_group_mr);

    $this->oldSpeedInTens = $oldSpeedInTens;

    $this->targetedUnitId = $targetedUnitId;

    $this->captainId = $captainId;

    $this->_time_launch = SN_TIME_NOW;

    $this->fleetRenderer->renderParamCoordinates($this);

  }

  protected function restrictTargetTypeByMission() {
    if ($this->_mission_type == MT_MISSILE) {
      $this->allowed_planet_types = array(PT_PLANET => PT_PLANET);
    } elseif ($this->_mission_type == MT_COLONIZE || $this->_mission_type == MT_EXPLORE) {
      // TODO - PT_NONE
      $this->allowed_planet_types = array(PT_PLANET => PT_PLANET);
    } elseif ($this->_mission_type == MT_RECYCLE) {
      $this->allowed_planet_types = array(PT_DEBRIS => PT_DEBRIS);
    } elseif ($this->_mission_type == MT_DESTROY) {
      $this->allowed_planet_types = array(PT_MOON => PT_MOON);
    } else {
      $this->allowed_planet_types = array(PT_PLANET => PT_PLANET, PT_MOON => PT_MOON);
    }
  }

  protected function populateTargetPlanetAndOwner() {
    // If vector points to no exact object OR debris - then getting planet on coordinates
    $targetVector = clone $this->targetVector;
    if ($targetVector->type == PT_DEBRIS || $targetVector == PT_NONE) {
      $targetVector->type = PT_PLANET;
    }

    $this->dbTargetRow = DBStaticPlanet::db_planet_by_vector_object($targetVector);
    if (!empty($this->dbTargetRow['id_owner'])) {
      $this->dbTargetOwnerRow = DBStaticUser::db_user_by_id($this->dbTargetRow['id_owner'], true);
    }
  }

  /**
   *
   */
  public function fleetPage0(array $template_result) {
    lng_include('overview');

    if (empty($this->dbSourcePlanetRow)) {
      message(classLocale::$lang['fl_noplanetrow'], classLocale::$lang['fl_error']);
    }

    // TODO - redo to unitlist render/unit render
    $template_result['.']['ships'] = $this->planetRenderer->renderAvailableShips($this->dbOwnerRow, $this->dbSourcePlanetRow);

    $this->fleetRenderer->renderShipSortOptions($template_result);

    /**
     * @var Player $playerOwner
     */
    $playerOwner = $this->getLocatedAt();

    $template_result += array(
      'FLYING_FLEETS'      => $playerOwner->fleetsFlying(),
      'MAX_FLEETS'         => $playerOwner->fleetsMax(),
      'FREE_FLEETS'        => $playerOwner->fleetsMax() - $playerOwner->fleetsFlying(),
      'FLYING_EXPEDITIONS' => $playerOwner->expeditionsFlying(),
      'MAX_EXPEDITIONS'    => $playerOwner->expeditionsMax(),
      'FREE_EXPEDITIONS'   => $playerOwner->expeditionsMax() - $playerOwner->expeditionsFlying(),
      'COLONIES_CURRENT'   => $playerOwner->coloniesCurrent(),
      'COLONIES_MAX'       => $playerOwner->coloniesMax(),

      'TYPE_NAME' => classLocale::$lang['fl_planettype'][$this->targetVector->type],

      'speed_factor' => flt_server_flight_speed_multiplier(),

      'PLANET_RESOURCES' => pretty_number($this->dbSourcePlanetRow['metal'] + $this->dbSourcePlanetRow['crystal'] + $this->dbSourcePlanetRow['deuterium']),
      'PLANET_DEUTERIUM' => pretty_number($this->dbSourcePlanetRow['deuterium']),

      'PLAYER_OPTION_FLEET_SHIP_SELECT_OLD'       => classSupernova::$user_options[PLAYER_OPTION_FLEET_SHIP_SELECT_OLD],
      'PLAYER_OPTION_FLEET_SHIP_HIDE_SPEED'       => classSupernova::$user_options[PLAYER_OPTION_FLEET_SHIP_HIDE_SPEED],
      'PLAYER_OPTION_FLEET_SHIP_HIDE_CAPACITY'    => classSupernova::$user_options[PLAYER_OPTION_FLEET_SHIP_HIDE_CAPACITY],
      'PLAYER_OPTION_FLEET_SHIP_HIDE_CONSUMPTION' => classSupernova::$user_options[PLAYER_OPTION_FLEET_SHIP_HIDE_CONSUMPTION],
    );

    $template = gettemplate('fleet0', true);
    $template->assign_recursive($template_result);
    display($template, classLocale::$lang['fl_title']);
  }

  /**
   *
   */
  public function fleetPage1() {
    global $template_result;

    $template_result['.']['fleets'][] = $this->fleetRenderer->renderFleet($this, SN_TIME_NOW);
    $template_result['.']['possible_planet_type_id'] = $this->fleetRenderer->renderAllowedPlanetTypes($this->allowed_planet_types);
    $template_result['.']['colonies'] = $this->planetRenderer->renderOwnPlanets($this->dbOwnerRow, $this->dbSourcePlanetRow);
    $template_result['.']['shortcut'] = $this->planetRenderer->renderPlanetShortcuts($this->dbOwnerRow);
    $template_result['.']['acss'] = $this->planetRenderer->renderACSList($this->dbOwnerRow);

    $template_result += array(
      'speed_factor' => flt_server_flight_speed_multiplier(),

      'fleet_speed'    => $this->fleetSpeed(),
      'fleet_capacity' => $this->shipsGetCapacity(),

      'PLANET_DEUTERIUM' => pretty_number($this->dbSourcePlanetRow['deuterium']),

      'PAGE_HINT' => classLocale::$lang['fl_page1_hint'],
    );

    $template = gettemplate('fleet1', true);
    $template->assign_recursive($template_result);
    display($template, classLocale::$lang['fl_title']);
  }

  public function fleetSpeed() {
    $maxSpeed = array();
    foreach ($this->shipsIterator() as $ship_id => $unit) {
      if ($unit->count > 0 && !empty(classSupernova::$gc->groupFleetAndMissiles[$ship_id])) {
        $single_ship_data = get_ship_data($ship_id, $this->dbOwnerRow);
        $maxSpeed[$ship_id] = $single_ship_data['speed'];
      }
    }

    return empty($maxSpeed) ? 0 : min($maxSpeed);
  }

  /**
   * @param array $planetResourcesWithoutConsumption
   */
  public function fleetPage2Prepare($planetResourcesWithoutConsumption) {
    $this->travelData = $this->flt_travel_data($this->oldSpeedInTens);

//    /**
//     * @var array $allowed_missions
//     */
//    public $allowed_missions = array();
//    /**
//     * @var array $exists_missions
//     */
//    public $exists_missions = array();
//    public $allowed_planet_types = array(
//      // PT_NONE => PT_NONE,
//      PT_PLANET => PT_PLANET,
//      PT_MOON   => PT_MOON,
//      PT_DEBRIS => PT_DEBRIS
//    );

//    $this->exists_missions = array(
////      MT_EXPLORE => MT_EXPLORE,
////      MT_MISSILE => MT_MISSILE,
//      MT_COLONIZE => MT_COLONIZE,
//    );  // TODO
    $this->allowed_missions = array();

    if ($this->mission_type != MT_NONE && empty($this->exists_missions[$this->mission_type])) {
      throw new ExceptionFleetInvalid(FLIGHT_MISSION_UNKNOWN, FLIGHT_MISSION_UNKNOWN);
    }

    $this->validator->validateGlobals();

    $validateResult = array();
    foreach ($this->exists_missions as $missionType => $missionData) {
//print('qwe');
      $mission = \Mission\MissionFactory::build($missionType, $this);
//print('wer');
      $validateResult[$missionType] = $mission->validate();
      if (FLIGHT_ALLOWED == $validateResult[$missionType]) {
        $this->allowed_missions[$missionType] = $mission;
      } else {
        if($missionType == $this->mission_type) {
        }
        unset($this->allowed_missions[$missionType]);
      }
    }

    if(empty($this->allowed_missions)) {
      if($this->mission_type != MT_NONE && isset($validateResult[$this->mission_type])) {
        throw new ExceptionFleetInvalid($validateResult[$this->mission_type], $validateResult[$this->mission_type]);
      } else {
        throw new ExceptionFleetInvalid(FLIGHT_MISSION_IMPOSSIBLE, FLIGHT_MISSION_IMPOSSIBLE);
      }
    }

//    $this->validator->validate();
  }

  /**
   * @param array $planetResourcesWithoutConsumption
   */
  public function fleetPage3Prepare($planetResourcesWithoutConsumption) {
    $this->travelData = $this->flt_travel_data($this->oldSpeedInTens);

    if (empty($this->exists_missions[$this->mission_type])) {
      throw new ExceptionFleetInvalid(FLIGHT_MISSION_UNKNOWN, FLIGHT_MISSION_UNKNOWN);
    }

    $this->validator->validateGlobals();

    $mission = \Mission\MissionFactory::build($this->mission_type, $this);
    $result = $mission->validate();
    if (FLIGHT_ALLOWED != $result) {
      throw new ExceptionFleetInvalid($result, $result);
    }

  }

  /**
   *
   */
  public function fleetPage2() {
    global $template_result;

    $planetResourcesTotal = DBStaticPlanet::getResources($this->dbOwnerRow, $this->dbSourcePlanetRow);
    $planetResourcesWithoutConsumption = $this->resourcesSubstractConsumption($planetResourcesTotal);

    try {
      $this->fleetPage2Prepare($planetResourcesWithoutConsumption);
    } catch (Exception $e) {
      // TODO - MESSAGE BOX
      if ($e instanceof ExceptionFleetInvalid) {
        sn_db_transaction_rollback();
        pdie(classLocale::$lang['fl_attack_error'][$e->getCode()]);
      } else {
        throw $e;
      }
    }

    // Flight allowed here
    pdump('FLIGHT_ALLOWED', FLIGHT_ALLOWED);
//    pdump('// TODO - Сделать flletvalidator DI - внутре контейнер для методов, а методы - анонимные функции, вызывающие другие методы же', FLIGHT_ALLOWED);

    ksort($this->allowed_missions);
    // If mission is not set - setting first mission from allowed
    if (empty($this->_mission_type) && is_array($this->allowed_missions)) {
      reset($this->allowed_missions);
      $this->_mission_type = key($this->allowed_missions);
    }
    $template_result['.']['missions'] = $this->fleetRenderer->renderAllowedMissions($this->allowed_missions);

    $template_result['.']['fleets'][] = $this->fleetRenderer->renderFleet($this, SN_TIME_NOW);

    $max_duration =
      $this->_mission_type == MT_EXPLORE
        ? get_player_max_expedition_duration($this->dbOwnerRow)
        : (isset($this->allowed_missions[MT_HOLD]) ? 12 : 0);
    $template_result['.']['duration'] = $this->fleetRenderer->renderDuration($this->_mission_type, $max_duration);

    $this->captain = $this->captainGet();
    $template_result += $this->renderCaptain($this->captain);

    $template_result['.']['resources'] = $this->fleetRenderer->renderPlanetResources($planetResourcesWithoutConsumption);

    $template_result += array(
      'planet_metal'     => $planetResourcesWithoutConsumption[RES_METAL],
      'planet_crystal'   => $planetResourcesWithoutConsumption[RES_CRYSTAL],
      'planet_deuterium' => $planetResourcesWithoutConsumption[RES_DEUTERIUM],

      'fleet_capacity' => $this->shipsGetCapacity() - $this->travelData['consumption'],
      'speed'          => $this->oldSpeedInTens,
      'fleet_group'    => $this->_group_id,

      'MAX_DURATION'          => $max_duration,

      // TODO - remove
//      'IS_TRANSPORT_MISSIONS' => !empty($this->allowed_missions[$this->_mission_type]['transport']),
      'IS_TRANSPORT_MISSIONS' => true,

      'PLAYER_COLONIES_CURRENT' => get_player_current_colonies($this->dbOwnerRow),
      'PLAYER_COLONIES_MAX'     => get_player_max_colonies($this->dbOwnerRow),
    );

    $template = gettemplate('fleet2', true);
    $template->assign_recursive($template_result);
    display($template, classLocale::$lang['fl_title']);
  }

  /**
   *
   */
  public function fleetPage3() {
    global $template_result;

    $this->isRealFlight = true;

    sn_db_transaction_start();

    DBStaticUser::db_user_lock_with_target_owner_and_acs($this->dbOwnerRow, $this->dbTargetRow);

    // Checking for group
    $this->groupCheck();

    $this->dbOwnerRow = DBStaticUser::db_user_by_id($this->dbOwnerRow['id'], true);
    $this->dbSourcePlanetRow = DBStaticPlanet::db_planet_by_id($this->dbSourcePlanetRow['id'], true);
    if (!empty($this->dbTargetRow['id'])) {
      $this->dbTargetRow = DBStaticPlanet::db_planet_by_id($this->dbTargetRow['id'], true);
    }
    // TODO - deprecated! Filled in populateTargetPlanetAndOwner
    if (!empty($this->dbTargetRow['id_owner'])) {
      $this->dbTargetOwnerRow = DBStaticUser::db_user_by_id($this->dbTargetRow['id_owner'], true);
    }

    $this->resource_list = array(
      RES_METAL     => max(0, floor(sys_get_param_float('resource0'))),
      RES_CRYSTAL   => max(0, floor(sys_get_param_float('resource1'))),
      RES_DEUTERIUM => max(0, floor(sys_get_param_float('resource2'))),
    );

    $this->captain = $this->captainGet();

    $this->travelData = $this->flt_travel_data($this->oldSpeedInTens);

    $planetResourcesTotal = DBStaticPlanet::getResources($this->dbOwnerRow, $this->dbSourcePlanetRow);
    $planetResourcesWithoutConsumption = $this->resourcesSubstractConsumption($planetResourcesTotal);

    try {
      $this->fleetPage3Prepare($planetResourcesWithoutConsumption);
    } catch (Exception $e) {
      // TODO - MESSAGE BOX
      if ($e instanceof ExceptionFleetInvalid) {
        sn_db_transaction_rollback();
        pdie(classLocale::$lang['fl_attack_error'][$e->getCode()]);
      } else {
        throw $e;
      }
    }

//    try {
//      $validator = new FleetValidator($this);
//      $validator->validate();
//    } catch (Exception $e) {
//      // TODO - MESSAGE BOX
//      if($e instanceof Exception\ExceptionFleetInvalid) {
//        sn_db_transaction_rollback();
//        pdie(classLocale::$lang['fl_attack_error'][$e->getCode()]);
//      } else {
//        throw $e;
//      }
//    }

    // TODO - check if mission is not 0 and in ALLOWED_MISSIONS

    // Flight allowed here
    pdump('FLIGHT_ALLOWED', FLIGHT_ALLOWED);
//    pdump('// TODO - Сделать flletvalidator DI - внутре контейнер для методов, а методы - анонимные функции, вызывающие другие методы же', FLIGHT_ALLOWED);


    $timeMissionJob = 0;
    // TODO check for empty mission AKA mission allowed
    /*
        if ($this->_mission_type == MT_ACS && $aks) {
          $acsTimeToArrive = $aks['ankunft'] - SN_TIME_NOW;
          if ($acsTimeToArrive < $this->travelData['duration']) {
            message(classLocale::$lang['fl_aks_too_slow'] . 'Fleet arrival: ' . date(FMT_DATE_TIME, SN_TIME_NOW + $this->travelData['duration']) . " AKS arrival: " . date(FMT_DATE_TIME, $aks['ankunft']), classLocale::$lang['fl_error']);
          }
          // Set time to travel to ACS' TTT
          $this->travelData['duration'] = $acsTimeToArrive;
    */
    if ($this->_mission_type != MT_ACS) {
      if ($this->_mission_type == MT_EXPLORE || $this->_mission_type == MT_HOLD) {
        $max_duration = $this->_mission_type == MT_EXPLORE ? get_player_max_expedition_duration($this->dbOwnerRow) : ($this->_mission_type == MT_HOLD ? 12 : 0);
        if ($max_duration) {
          $mission_time_in_hours = sys_get_param_id('missiontime');
          if ($mission_time_in_hours > $max_duration || $mission_time_in_hours < 1) {
            classSupernova::$debug->warning('Supplying wrong mission time', 'Hack attempt', 302, array('base_dump' => true));
            die();
          }
          $timeMissionJob = ceil($mission_time_in_hours * 3600 / ($this->_mission_type == MT_EXPLORE && classSupernova::$config->game_speed_expedition ? classSupernova::$config->game_speed_expedition : 1));
        }
      }
    }

    //
    //
    //
    //
    //
    //
    //
    //
    // ---------------- END OF CHECKS ------------------------------------------------------

    $this->set_times($this->travelData['duration'], $timeMissionJob);
    $this->dbInsert();
    $this->unitList->dbSubstractUnitsFromPlanet($this->dbOwnerRow, $this->dbSourcePlanetRow['id']);

    DBStaticPlanet::db_planet_update_adjust_by_id(
      $this->dbSourcePlanetRow['id'],
      array(
        'metal'     => -$this->resource_list[RES_METAL],
        'crystal'   => -$this->resource_list[RES_CRYSTAL],
        'deuterium' => -$this->resource_list[RES_DEUTERIUM] - $this->travelData['consumption'],
      )
    );

    if (!empty($this->captain['unit_id'])) {
      DBStaticUnit::db_unit_set_by_id(
        $this->captain['unit_id'],
        array(
          'unit_location_type' => LOC_FLEET,
          'unit_location_id'   => $this->_dbId,
        ),
        array()
      );
    }

//    return $this->fleet->acs['ankunft'] - $this->fleet->time_launch >= $this->fleet->travelData['duration'];
//
//    // Set time to travel to ACS' TTT
//    $this->fleet->travelData['duration'] = $acsTimeToArrive;


    $template_result['.']['fleets'][] = $this->fleetRenderer->renderFleet($this, SN_TIME_NOW, $timeMissionJob);

    $template_result += array(
      'mission'         => classLocale::$lang['type_mission'][$this->_mission_type] . ($this->_mission_type == MT_EXPLORE || $this->_mission_type == MT_HOLD ? ' ' . pretty_time($timeMissionJob) : ''),
      'dist'            => pretty_number($this->travelData['distance']),
      'speed'           => pretty_number($this->travelData['fleet_speed']),
      'deute_need'      => pretty_number($this->travelData['consumption']),
      'from'            => "{$this->dbSourcePlanetRow['galaxy']}:{$this->dbSourcePlanetRow['system']}:{$this->dbSourcePlanetRow['planet']}",
      'time_go'         => date(FMT_DATE_TIME, $this->_time_arrive_to_target),
      'time_go_local'   => date(FMT_DATE_TIME, $this->_time_arrive_to_target + SN_CLIENT_TIME_DIFF),
      'time_back'       => date(FMT_DATE_TIME, $this->_time_return_to_source),
      'time_back_local' => date(FMT_DATE_TIME, $this->_time_return_to_source + SN_CLIENT_TIME_DIFF),
    );

    $this->dbSourcePlanetRow = DBStaticPlanet::db_planet_by_id($this->dbSourcePlanetRow['id']);

    pdie('Stop for debug');

    sn_db_transaction_commit();

    $template = gettemplate('fleet3', true);
    $template->assign_recursive($template_result);
    display($template, classLocale::$lang['fl_title']);
  }

  protected function groupCheck() {
    if (empty($this->_group_id)) {
      return;
    }

    // ACS attack must exist (if acs fleet has arrived this will also return false (2 checks in 1!!!)
    $this->acs = DBStaticFleetACS::db_acs_get_by_group_id($this->_group_id);
    if (empty($this->acs)) {
      $this->_group_id = 0;
    } else {
      $this->targetVector->convertToVector($this->acs);
    }
  }

  /**
   * @param array $planetResources
   *
   * @return array
   */
  protected function resourcesSubstractConsumption($planetResources) {
    !isset($planetResources[RES_DEUTERIUM]) ? $planetResources[RES_DEUTERIUM] = 0 : false;

    if ($this->travelData['consumption'] >= 0) {
      $planetResources[RES_DEUTERIUM] -= ceil($this->travelData['consumption']);
    }

    return $planetResources;
  }

  /**
   */
  public function captainGet() {
    $result = array();

    /**
     * @var unit_captain $moduleCaptain
     */
    if (sn_module::isModuleActive('unit_captain')) {
      $moduleCaptain = sn_module::getModule('unit_captain');
      $result = $moduleCaptain->unit_captain_get($this->dbSourcePlanetRow['id']);
    }

    return $result;
  }

  /**
   * @return array
   */
  protected function renderCaptain($captainUnit) {
    $result = array();

    if (!empty($captainUnit['unit_id']) && $captainUnit['unit_location_type'] == LOC_PLANET) {
      $result = array(
        'CAPTAIN_ID'     => $captainUnit['unit_id'],
        'CAPTAIN_LEVEL'  => $captainUnit['captain_level'],
        'CAPTAIN_SHIELD' => $captainUnit['captain_shield'],
        'CAPTAIN_ARMOR'  => $captainUnit['captain_armor'],
        'CAPTAIN_ATTACK' => $captainUnit['captain_attack'],
      );
    }

    return $result;
  }

}
