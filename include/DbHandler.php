<?php
 
/**
 * Class to handle all db operations
 */

class DbHandler {
 
    private $conn;
 
    private function getArrayFromStmt($stmt) {
        $res = [];
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $res[] = $row;
            }
           
        }
        return $res;
    }
    function __construct() {
        
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
 
    /* ------------- `users` table method ------------------ */
 
    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($name, $email, $password, $gender) {
        require_once 'PassHash.php';
        $response = array();
 
        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();
 
            // insert query
            $stmt = $this->conn->prepare('INSERT INTO `users`(`name`, `email`, `gender`, `password_hash`, `key`)
                                         VALUES(:name, :email, :gender, :password_hash, :key)');

            $stmt->execute(array(
                    'name' => $name,
                    'email' => $email,
                    'gender' => $gender,
                    'password_hash' => $password_hash,
                    'key' => $api_key
            ));
 
     
            // Check for successful insertion
            if ($stmt->rowCount() > 0) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }
 
        return $response;
    }
 
    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT `password_hash` FROM `users` WHERE `email` = :email");
 
        $stmt->execute(array('email' => $email));

   
        if ($stmt->rowCount() > 0) {
  
            $password_hash = $stmt->fetchColumn();
 
            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            }

        } 
  
        return FALSE;
    }
 
    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare('SELECT id 
                                   FROM users
                                   WHERE email = :email');
        
        $stmt->execute(array('email' => $email));
        
        return $stmt->rowCount() > 0;
        
    }
 
    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare('SELECT * FROM `users`
                                      WHERE `email` = :email');
        
        $stmt->execute(array('email' => $email));
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    
        return NULL;
    }
 
    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare('SELECT `key` FROM `users`
                                      WHERE `id` = :id');
        $stmt->execute(array('id' => $user_id));
        if ($stmt->rowCount() > 0) {
            return $stmt->fetchColumn();
        }
        return NULL;
    }
 
    public function getUserById($user_id) {
        $stmt = $this->conn->prepare('SELECT * FROM `users`
                                      WHERE `id` = :id');
        $stmt->execute(array('id' => $user_id));
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return NULL;
    }
    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare('SELECT `id` FROM `users` 
                                      WHERE `key` = :key');
        $stmt->execute(array('key' => $api_key));
        if ($stmt->rowCount() > 0) {
            return $stmt->fetchColumn();
        }
        return NULL;
    }
 
    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $user_id = $this->getUserId($api_key);
        if ($user_id != NULL) {
            return TRUE;
        }
        return FALSE;
    }
 
    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }
 

    
    /* ------------- `events` table method ------------------ */
 
    /**
     * Creating new event
     */
    
    public function createEvent($owner_id, $event, $details, 
            $location, $start_time, $duration) {
        if ($this->getUserById($owner_id) != NULL) {
            $stmt = $this->conn->prepare('INSERT INTO `events` (`owner_id`, `event`, 
                                                              `details`, `location`,
                                                              `start_time`, `duration`)
                                          VALUES (:owner_id, :event, :details, :location,
                                                  :start_time, :duration)');
            $stmt->execute(array(
                'owner_id' => $owner_id,
                'event' => $event,
                'details' => $details,
                'location' => $location,
                'start_time' => $start_time,
                'duration' => $duration
                                
            ));
            
            if ($stmt->rowCount() > 0) {
                $eventId = $this->conn->lastInsertId();
                $this->addUserToEvent($owner_id, $eventId, TRUE);
                
                return $eventId;
            }
        }
        return NULL;
    }
    
    public function getEventById($eventId) {
        $stmt = $this->conn->prepare('SELECT * FROM `events`
                                      WHERE `id` = :eventId');
        $stmt->execute(array("eventId" => $eventId));
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return NULL;
    }
    
    public function getAllUserEvents($userId) {
        $stmt = $this->conn->prepare('SELECT e.* FROM `events` as `e`
                                      JOIN `user_events` as ue
                                          ON e.id = ue.event_id
                                      WHERE ue.user_id = :userId');
        $stmt->execute(array("userId" => $userId));
        
        return $this->getArrayFromStmt($stmt);
    }
    
    public function getEvents($userId, $is_delivered) {
        $stmt = $this->conn->prepare('SELECT e.* FROM `events` as `e`
                                      JOIN `user_events` as ue
                                        ON e.`id` = ue.`event_id`
                                      WHERE  ue.`user_id` = :userId
                                        AND  ue.`is_delivered` = :is_delivered');
        $stmt->execute(array(
            "userId" => $userId,
            "is_delivered" => $is_delivered ? 1 : 0
        ));
        return $this->getArrayFromStmt($stmt);
    }
    
    public function getUndeliveredEvents($userId) {
        return $this->getEvents($userId, 0);
    }
    
    public function getDeliveredEvents($userId) {
        return $this->getEvents($userId, 1);
    }
    
    public function userEventPairExists($userId, $eventId) {
        $stmt = $this->conn->prepare('SELECT * FROM `user_events` 
                                      WHERE `user_id` = :userId 
                                        AND `event_id` = :eventId');
        $stmt->execute(array(
            "userId" => $userId,
            "eventId" => $eventId
        ));
        if ($stmt->rowCount() > 0) {
            return TRUE;
        }
        return False;
    }
    
    public function canAddUserToEvent($userId, $eventId) {
        if ($this->getUserById($userId) && 
                $this->getEventById($eventId) &&
                !$this->userEventPairExists($userId, $eventId)){
            return TRUE;
        }
    }
    
    public function addPrivUser($privUserID, $userID) {
        if ($this->getUserById($userID) && $this->getUserById($privUserID)) {
            $stmt = $this->conn->prepare('INSERT INTO `has_privilege` (`user_id`, `privuser_id`)
                                          VALUES (:user_id, :privuser_id)');

            $stmt->execute(array(
                'user_id' => $userID,
                'privuser_id' => $privUserID
            ));

            if ($stmt->rowCount() > 0) {
                return true;
            }
        }

        return false;
            
    }
        
    public function hasUserPriv($privUserID, $userID) {
        $stmt = $this->conn->prepare('SELECT * FROM `has_privilege` 
                                      WHERE `user_id` = :user_id AND 
                                      `privuser_id` = :privuser_id');
        $stmt->execute(array(
            'user_id' => $userID,
            'privuser_id' => $privUserID
        ));
        
        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }
    
    public function hasMultiUserPriv($privUserID, $usersIDArray) {
        foreach ($usersIDArray as $userID) {
            if (!$this->hasUserPriv($privUserID, $userID)) {
                return false;
            }
        }
        return true;
    }
    
    public function getPrivForUser($privUserID) {
        $stmt = $this->conn->prepare('SELECT u.id, u.name, u.email, u.gender
                                     FROM `users` as u
                                     JOIN `has_privilege` AS hp 
                                     where u.id = hp.user_id AND 
                                         hp.privuser_id = :privuser_id');
        $stmt->execute(array(
            'privuser_id' => $privUserID
        ));
        
        return $this->getArrayFromStmt($stmt);
    }
    
    public function addUserToEvent($userId, $eventId, $is_delivered=FALSE) {
        if ($this->canAddUserToEvent($userId, $eventId)) {
            $stmt = $this->conn->prepare('INSERT INTO `user_events`
                                            (`user_id`, `event_id`,
                                            `is_delivered`, `delivery_time`)
                                            
                                          VALUES
                                            (:userId, :eventId,
                                             :is_delivered,
                                             CASE :is_delivered 
                                               WHEN 1 THEN CURRENT_TIMESTAMP
                                               ELSE NULL END)');
            $stmt->execute(array(
                "userId" => $userId,
                "eventId" => $eventId,
                "is_delivered" => $is_delivered ? 1 : 0,
            ));
            
            if ($stmt->rowCount() > 0) {
                return TRUE;
            }
            
        }
        return FALSE;
    }
    
    public function isUserBelongsToEvent($userId, $eventId) {
        $stmt = $this->conn->prepare('SELECT * FROM `user_events` 
                                      WHERE `user_id` = :user_id AND
                                            `event_id` = :event_id');
        $stmt->execute(array(
            'user_id' => $userId,
            'event_id' => $eventId
        ));
        
        if ($stmt->rowCount() > 0) {
            return TRUE;
        }
        return FALSE;
    }
    
    public function addMultiUserToEvent($eventId, $usersArray, $is_delivered=FALSE) {
        foreach ($usersArray as $userId) {
            $this->addUserToEvent($userId, $eventId, $is_delivered);
        }
    }
    
    public function addCommentToEvent($eventId, $authorId, $content) {
       
        $stmt = $this->conn->prepare('INSERT INTO `comments` (`author_id`, 
                                        `event_id`, `content`, `created_at`)
                                      VALUES (:author_id, :event_id, :content,
                                         UNIX_TIMESTAMP())');
        $stmt->execute(array(
            'event_id' => $eventId,
            'author_id' => $authorId,
            'content' => $content
        ));
        
        if ($stmt->rowCount() > 0) {
            $commentID = $this->conn->lastInsertId(); 
            return $commentID;
        }
        return NULL;
        
    }
    
    public function getCommentByID($commentID) {
        $stmt = $this->conn->prepare('SELECT c.*, u.name AS `author_name`
                                      FROM `comments` AS c
                                      JOIN `users` AS u ON u.`id` = c.`author_id`
                                      WHERE c.`id` = :comment_id');
        $stmt->execute(array('comment_id' => $commentID));
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return NULL;
    }
    
    public function getCommentsForEvent($eventID) {
        $stmt = $this->conn->prepare('SELECT c.*, u.name AS `author_name`
                                      FROM `comments` AS c 
                                      JOIN `users` AS u ON u.`id` = c.`author_id` 
                                      WHERE `event_id` = :event_id');
        $stmt->execute(array('event_id' => $eventID));
        
        return $this->getArrayFromStmt($stmt);
    }
    
    
    public function getEventsInTimeRange($users_ids, $date_start, $date_end,
            $time_start, $time_end) {
        $users_ids_str = implode(',', $users_ids);
        $stmt = $this->conn->prepare("SELECT ev.start_time, ev.duration
                                      FROM `events` AS `ev`
                                      JOIN `user_events` AS `ue`
                                      ON ue.user_id IN ($users_ids_str) 
                                        AND ue.event_id = ev.id
                                        AND DATE(FROM_UNIXTIME(ev.start_time))
                                            BETWEEN :date_start AND :date_end
                                        AND TIME(FROM_UNIXTIME(ev.start_time)) 
                                            BETWEEN :time_start AND :time_end
                                      GROUP BY ev.id");
        $stmt->execute(array(
            "time_start" => $time_start,
            "time_end" => $time_end,
            "date_start" => $date_start,
            "date_end" => $date_end
        ));
        
        
      
        return $this->getArrayFromStmt($stmt);
    }
    
    
    
    
    public function createTask($user_id, $task) {       
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
        $stmt->bind_param("s", $task);
        $result = $stmt->execute();
        $stmt->close();
 
        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }
 
    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $task = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $task;
        } else {
            return NULL;
        }
    }
 
    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }
 
    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
 
    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
 
    /* ------------- `user_tasks` table method ------------------ */
 
    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
 
}
 
?>