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

// QUICK AND DIRTY DAO CLASS
class DAO {

    private $_mysqli;
    public function __construct($host = DIGIID_DB_HOST, $user = DIGIID_DB_USER, $pass = DIGIID_DB_PASS, $name = DIGIID_DB_NAME) {
        @$this->_mysqli = new mysqli($host, $user, $pass, $name);
        if ($this->_mysqli->connect_errno) die ($this->_mysqli->connect_error);

        $this->checkInstalled();
    }

    /**
      * Create tables if not exists
      * @return bool
      */
    public function checkInstalled() {
        $required_tables = array (
            DIGIID_TBL_PREFIX . 'nonces' => '
                CREATE TABLE `' . DIGIID_TBL_PREFIX . 'nonces` (
                    `s_ip` varchar(46) COLLATE utf8_bin NOT NULL,
                    `dt_datetime` datetime NOT NULL,
                    `s_nonce` varchar(32) COLLATE utf8_bin NOT NULL,
                    `s_address` varchar(34) COLLATE utf8_bin DEFAULT NULL,
                    UNIQUE KEY `s_nonce` (`s_nonce`),
                    KEY `dt_datetime` (`dt_datetime`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        foreach ($required_tables as $name => $sql) {
            $table_exists = ($test = $this->_mysqli->query("SHOW TABLES LIKE '$name'")) && $test->num_rows == 1;
            if (!$table_exists) $this->_mysqli->query($sql);
        }
    }


    /**
     * Insert nonce + IP in the database to avoid an attacker go and try several nonces
     * This will only allow one nonce per IP, but it could be easily modified to allow severals per IP
     * (this is deleted after an user successfully log in the system, so only will collide if two or more users try to log in at the same time)
     *
     * @param $nonce
     * @param $ip
     * @return bool|mysqli_result
     */
    public function insert($nonce, $ip) {
        $this->deleteIP($ip);
        return $this->_mysqli->query(sprintf("INSERT INTO " . DIGIID_TBL_PREFIX . "nonces (`s_ip`, `dt_datetime`, `s_nonce`) VALUES ('%s', '%s', '%s')", $this->_mysqli->real_escape_string($ip), date('Y-m-d H:i:s'), $this->_mysqli->real_escape_string($nonce)));
    }

    /**
     * Update table once the message is signed correctly to allow login
     *
     * @param $nonce
     * @param $address
     * @return bool|mysqli_result
     */
    public function update($nonce, $address) {
        return $this->_mysqli->query(sprintf("UPDATE " . DIGIID_TBL_PREFIX . "nonces SET s_address = '%s' WHERE s_nonce = '%s' ", $this->_mysqli->real_escape_string($address), $this->_mysqli->real_escape_string($nonce)));
    }

    /**
     * Clean table from used nonces/address
     *
     * @param $nonce
     * @return bool|mysqli_result
     */
    public function delete($nonce) {
        return $this->_mysqli->query(sprintf("DELETE FROM " . DIGIID_TBL_PREFIX . "nonces WHERE s_nonce = '%s' ", $this->_mysqli->real_escape_string($nonce)));
    }

    /**
     * Clean table by IP
     *
     * @param $ip
     * @return bool|mysqli_result
     */
    public function deleteIP($ip) {
        return $this->_mysqli->query(sprintf("DELETE FROM " . DIGIID_TBL_PREFIX . "nonces WHERE s_ip = '%s' ", $this->_mysqli->real_escape_string($ip)));
    }

    /**
     * Check if user is logged
     *
     * @param $nonce
     * @param $ip
     * @return bool
     */
    public function address($nonce, $ip) {
        $result = $this->_mysqli->query(sprintf("SELECT * FROM " . DIGIID_TBL_PREFIX . "nonces WHERE s_nonce = '%s' AND s_ip = '%s' LIMIT 1 ", $this->_mysqli->real_escape_string($nonce), $this->_mysqli->real_escape_string($ip)));
        if($result) {
            $row = $result->fetch_assoc();
            if(isset($row['s_address']) && $row['s_address']!='') {
                $this->delete($nonce);
                return $row['s_address'];
            }
        }
        return false;
    }

	/**
	 * Check if a nonce exists
	 * @param $nonce
	 * @return bool
	 */
	public function checkNonce($nonce) {
		if($this->_mysqli->query(sprintf("SELECT * FROM " . DIGIID_TBL_PREFIX . "nonces WHERE s_nonce = '%s'", $this->_mysqli->real_escape_string($nonce))))
			return true;
		return false;
	}

    /**
     * Return IP by nonce, if you want to check that an IP could use this nonce
     *
     * @param $nonce
     * @return bool
     */
    public function ip($nonce) {
        $result = $this->_mysqli->query(sprintf("SELECT * FROM " . DIGIID_TBL_PREFIX . "nonces WHERE s_nonce = '%s' LIMIT 1 ", $this->_mysqli->real_escape_string($nonce)));
        if($result) {
            $row = $result->fetch_assoc();
            if(isset($row['s_ip'])) {
                return $row['s_ip'];
            }
        }
        return false;
    }

}
