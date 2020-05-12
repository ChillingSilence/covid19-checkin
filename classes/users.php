<?php
/*
Copyright (c) 2019 Josiah Spackman

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE
*/

require_once dirname(__FILE__) . "/../config.php";

const PENDING_USER = 0;
const AUTHORIZED_USER = 1;
const REJECTED_USER = 2;
const REGULAR_USER = 0;
const ADMIN_USER = 1;

// Store and manage users info
class token_user {

    private $_mysqli;
    private $addr;
    public function __construct($addr = null, $host = DIGIID_DB_HOST, $user = DIGIID_DB_USER, $pass = DIGIID_DB_PASS, $name = DIGIID_DB_NAME) {
        @$this->_mysqli = new mysqli($host, $user, $pass, $name);
        if ($this->_mysqli->connect_errno) die ($this->_mysqli->connect_error);
        $this->addr = $addr;

        $this->checkInstalled();
    }

    /**
      * Create tables if not exists
      * @return bool
      */
    public function checkInstalled() {
        $required_tables = array (
            DIGIID_TBL_PREFIX . 'users' => '
                CREATE TABLE `' . DIGIID_TBL_PREFIX . "users` (
                    `addr` varchar(46) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                    `fio` varchar(60) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                    `isadmin` int(1) NOT NULL DEFAULT '0' COMMENT 'User is an Admin?',
                    `ispermitted` int(1) NOT NULL DEFAULT '0' COMMENT 'User is permitted to access?',
                    PRIMARY KEY (`addr`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        foreach ($required_tables as $name => $sql) {
            $table_exists = ($test = $this->_mysqli->query("SHOW TABLES LIKE '$name'")) && $test->num_rows == 1;
            if (!$table_exists) $this->_mysqli->query($sql);
        }
    }

    /**
     * Insert user detail in the database
     *
     * @param $addr
     * @param array $info
     * @return bool|mysqli_result
     */
    public function insert($info) {
        return $this->_mysqli->query(sprintf("INSERT INTO " . DIGIID_TBL_PREFIX . "users"
            . " (`addr`, `fio`) VALUES ('%s', '%s')",
            $this->_mysqli->real_escape_string($this->addr), 
            $this->_mysqli->real_escape_string($info['fio']))
            );
    }

    /**
     * Update table with user info
     *
     * @param $nonce
     * @param $address
     * @return bool|mysqli_result
     */
    public function update($info) {
        return $this->_mysqli->query(sprintf("UPDATE " . DIGIID_TBL_PREFIX . "users"
            . " SET `fio` = '%s' WHERE `addr` = '%s' ",
            $this->_mysqli->real_escape_string($info['fio']),
            $this->_mysqli->real_escape_string($this->addr))
            );
    }

    /**
     * Grant or deny access to the system for the shown
     *
     * @param $address
     * @param $ispermitted
     * @return bool|mysqli_result
     * ispermitted uses 0 for unapproved, 1 for approved, 2 for rejected
     */
    public function grantaccess($addr, $ispermitted) {
        return $this->_mysqli->query(
            sprintf("UPDATE " . DIGIID_TBL_PREFIX . "users SET `ispermitted` = %d WHERE `addr` = '%s'",
            intval($ispermitted),
            $this->_mysqli->real_escape_string($addr))
            );
    }

    /**
     * Grant or deny admin rights to user
     *
     * @param $address
     * @param $ispermitted
     * @return bool|mysqli_result
     * ispermitted uses 0 for unapproved, 1 for approved, 2 for rejected
     */
    public function grantadmin($addr, $isadmin) {
        return $this->_mysqli->query(
            sprintf("UPDATE " . DIGIID_TBL_PREFIX . "users SET `isadmin` = %d WHERE `addr` = '%s'",
            intval($isadmin),
            $this->_mysqli->real_escape_string($addr))
            );
    }

    /**
     * Forget current user
     *
     * @return bool|mysqli_result
     */
    public function delete() {
        return $this->_mysqli->query(
            sprintf("DELETE FROM " . DIGIID_TBL_PREFIX . "users WHERE `addr` = '%s'",
            $this->_mysqli->real_escape_string($this->addr))
            );
    }

    /**
     * Get current user info
     *
     * @return array
     */
    public function get_info() {
        $result = $this->_mysqli->query(
            sprintf("SELECT `fio`, `ispermitted`, `isadmin` FROM " . DIGIID_TBL_PREFIX . "users"
            . " WHERE `addr` = '%s'",
            $this->_mysqli->real_escape_string($this->addr))
            );
        if($result) {
            $row = $result->fetch_assoc();
            if(count($row)) return $row;
        }
        return false;
    }

    /**
     * Get user permissions
     *
     * @return array
     */
    public function get_permissions() {
        $result = $this->_mysqli->query(
            sprintf("SELECT `isadmin`, `ispermitted` FROM " . DIGIID_TBL_PREFIX . "users"
            . " WHERE `addr` = '%s'",
            $this->_mysqli->real_escape_string($this->addr))
            );
        if($result) {
            $row = $result->fetch_assoc();
            if(count($row)) return $row;
        }
        return false;
    }

    /**
     * Is current user banned
     *
     * @return bool
     */
    public function is_banned() {
        $perm = $this->get_permissions();
        return ($perm['ispermitted'] == REJECTED_USER);
    }

    /**
     * Get pending users that need to be allowed / denied
     *
     * @return array
     */
    public function get_pending_requests() {
        return $this->get_users_list(PENDING_USER, REGULAR_USER);
    }

    /**
     * Get non rejected users list
     *
     * @return array
     */
    public function get_nonrejected_list() {
        return $this->get_users_list(null, null, REJECTED_USER);
    }

    /**
     * Get all users
     *
     * @return array
     */
    public function get_users_list($permitted=null, $admin=null, $notpermitted=null) {
        // Query all
        $result = array ();
        $sql = "SELECT `addr`, `fio`, `isadmin`, `ispermitted` FROM " . DIGIID_TBL_PREFIX . "users";

        // under conditions (if it set)
        $conditions = array ();
        if ($permitted !== null) $conditions[] = '`ispermitted` = ' . intval($permitted);
        if ($admin !== null) $conditions[] = '`isadmin` = ' . intval($admin);
        if ($notpermitted !== null) $conditions[] = '`ispermitted` != ' . intval($notpermitted);
        $where = implode (' AND ', $conditions);
        if ($where != '') $sql .= " WHERE $where";

        // Collect
        $query = $this->_mysqli->query($sql);
        while ($line = $query->fetch_assoc()) $result[] = $line;
        return $result;
    }

    /**
     * Get the first Admin user
     *
     * @return array
     */
    public function in_empty_list() {
        try {
            $result = $this->_mysqli->query(
                "SELECT (count(*)+1) AS cnt FROM " . DIGIID_TBL_PREFIX . "users"
                );
            if ($result) {
                $row = $result->fetch_assoc();
                return (intval($row['cnt']) == 1);
            }
            return true;
        }
        catch (Exception $e) {
            return false;
        }
    }

    /**
     * Make this user the first admin user
     *
     * @param $address
     * @return bool|mysqli_result
     * isadmin uses 0 for normal user and 1 for administrative user
     */
    public function initial_admin() {
        return $this->grantadmin($this->addr, ADMIN_USER) && $this->grantaccess($this->addr, AUTHORIZED_USER);
    }


}
