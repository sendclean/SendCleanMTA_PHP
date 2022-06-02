<?php

class SendClean_Accounts {

    public function __construct(SendClean $master) {
        $this->master = $master;
    } 
	public function viewUserDetail() {
        $_params = array();
        return $this->master->call('account/viewUserDetail', $_params);
    }
    
}
