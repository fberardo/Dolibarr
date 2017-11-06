<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    customer_account_movement/cheque.class.php
 * \ingroup customer_account_movement
 * \brief   This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *          Put some comments here
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class cheque
 *
 * Put here description of your class
 *
 * @see CommonObject
 */
class cheque extends CommonObject
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'cheque';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'cheque';

	/**
	 */
	
        public $num_paiement;
	public $chqemetteur = '';
	public $chqbank;
        public $datecheck;
        public $amountcheck;
        public $fk_customer_account_movement;
	public $fk_user_author;
	public $fk_user_modif;
	public $active;
        public $customer_used;
        public $supplier_used;
        
	/**
	 */
	

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		// Clean parameters
		
		if (isset($this->num_paiement)) {
			 $this->num_paiement = trim($this->num_paiement);
		}
		if (isset($this->chqemetteur)) {
			 $this->chqemetteur = trim($this->chqemetteur);
		}
                if (isset($this->chqbank)) {
			 $this->chqbank = trim($this->chqbank);
		}
                if (isset($this->datecheck)) {
			 $this->datecheck = trim($this->datecheck);
		}
                if (isset($this->amountcheck)) {
			 $this->amountcheck = trim($this->amountcheck);
		}
                if (isset($this->fk_customer_account_movement)) {
			 $this->fk_customer_account_movement = trim($this->fk_customer_account_movement);
		}
		if (isset($this->fk_user_author) && !empty($this->fk_user_author)) {
			 $this->fk_user_author = trim($this->fk_user_author);
		}
		if (isset($this->fk_user_modif) && !empty($this->fk_user_modif)) {
			 $this->fk_user_modif = trim($this->fk_user_modif);
		}
		if (isset($this->active) && !empty($this->active)) {
			 $this->active = trim($this->active);
		}
                if (isset($this->customer_used) && !empty($this->customer_used)) {
			 $this->customer_used = trim($this->customer_used);
		}
                if (isset($this->supplier_used) && !empty($this->supplier_used)) {
			 $this->supplier_used = trim($this->supplier_used);
		}
                
		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
 		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . '(';
		
		$sql.= 'num_paiement,';
		$sql.= 'emetteur_chq,';
		$sql.= 'bank_chq,';
                $sql.= 'date_chq,';
                $sql.= 'amount_chq,';
                $sql.= 'fk_customer_account_movement,';
		$sql.= 'fk_user_author,';
		$sql.= 'fk_user_modif,';
		$sql.= 'active,';
                $sql.= 'customer_used,';
                $sql.= 'supplier_used';
                
		
		$sql .= ') VALUES (';
		
		$sql .= ' '.(! isset($this->num_paiement)?'NULL':"'".$this->num_paiement."'").',';
                $sql .= ' '.(! isset($this->chqemetteur)?'NULL':"'".$this->chqemetteur."'").',';
                $sql .= ' '.(! isset($this->chqbank)?'NULL':"'".$this->chqbank."'").',';
		$sql .= ' '.(! isset($this->datecheck) || dol_strlen($this->datecheck)==0?'NULL':"'".$this->db->idate($this->datecheck)."'").',';
                $sql .= ' '.(! isset($this->amountcheck)?'NULL':"'".$this->amountcheck."'").',';
		$sql .= ' '.(! isset($this->fk_customer_account_movement)?'NULL':$this->fk_customer_account_movement).',';
		$sql .= ' '.$user->id.',';
		$sql .= ' '.((isset($this->fk_user_modif) && !empty($this->fk_user_modif))?$this->fk_user_modif:'NULL').',';
                $sql .= ' '.(! isset($this->active)?'NULL':$this->active).',';
                $sql .= ' '.(! isset($this->customer_used)?'NULL':$this->customer_used).',';
                $sql .= ' '.(! isset($this->supplier_used)?'NULL':$this->supplier_used);
                
		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

			if (!$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action to call a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_CREATE',$user);
				//if ($result < 0) $error++;
				//// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id  Id object
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		
		$sql .= " t.num_paiement,";
		$sql .= " t.emetteur_chq,";
		$sql .= " t.bank_chq,";
		$sql .= " t.date_chq,";
                $sql .= " t.amount_chq,";
		$sql .= " t.fk_customer_account_movement,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.active,";
                $sql .= " t.customer_used,";
                $sql .= " t.supplier_used";
                
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		$sql.= ' WHERE 1 = 1';
		$sql .= ' AND t.rowid = ' . $id;

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
                            $obj = $this->db->fetch_object($resql);

                            $this->id = $obj->rowid;

                            $this->num_paiement = $obj->num_paiement;
                            $this->chqemetteur = $obj->emetteur_chq;
                            $this->chqbank = $obj->bank_chq;
                            $this->datecheck = $this->db->jdate($obj->date_chq);
                            $this->amountcheck = $obj->amount_chq;
                            $this->fk_customer_account_movement = $obj->fk_customer_account_movement;
                            $this->fk_user_author = $obj->fk_user_author;
                            $this->fk_user_modif = $obj->fk_user_modif;
                            $this->active = $obj->active;
                            $this->customer_used = $obj->customer_used;
                            $this->supplier_used = $obj->supplier_used;
			}
			
			// Retrieve all extrafields for invoice
			// fetch optionals attributes and labels
			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
			$extrafields=new ExtraFields($this->db);
			$extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
			$this->fetch_optionals($this->id,$extralabels);

			// $this->fetch_lines();
			
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int    $limit     offset limit
	 * @param int    $offset    offset limit
	 * @param array  $filter    filter array
	 * @param string $filtermode filter mode (AND or OR)
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder='', $sortfield='', $limit=0, $offset=0, array $filter = array(), $filtermode='AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		
		$sql .= " t.num_paiement,";
		$sql .= " t.emetteur_chq,";
		$sql .= " t.bank_chq,";
		$sql .= " t.date_chq,";
                $sql .= " t.amount_chq,";
		$sql .= " t.fk_customer_account_movement,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.active,";
                $sql .= " t.customer_used,";
                $sql .= " t.supplier_used";
		
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element. ' as t';

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				$sqlwhere [] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
			}
		}
		$sql.= ' WHERE 1 = 1';
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ' . implode(' '.$filtermode.' ', $sqlwhere);
		}
		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield,$sortorder);
		}
		if (!empty($limit)) {
		 $sql .=  ' ' . $this->db->plimit($limit + 1, $offset);
		}

		$this->lines = array();

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new chequeLine();

				$line->id = $obj->rowid;
				
                                $line->num_paiement = $obj->num_paiement;
                                $line->chqemetteur = $obj->emetteur_chq;
                                $line->chqbank = $obj->bank_chq;
                                $line->datecheck = $this->db->jdate($obj->date_chq);
                                $this->amountcheck = $obj->amount_chq;
                                $line->fk_customer_account_movement = $obj->fk_customer_account_movement;
                                $line->fk_user_author = $obj->fk_user_author;
                                $line->fk_user_modif = $obj->fk_user_modif;
                                $line->active = $obj->active;
                                $line->customer_used = $obj->customer_used;
                                $line->supplier_used = $obj->supplier_used;
                                
				$this->lines[$line->id] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		// Clean parameters
		
		if (isset($this->num_paiement)) {
			 $this->num_paiement = trim($this->num_paiement);
		}
		if (isset($this->chqemetteur)) {
			 $this->chqemetteur = trim($this->chqemetteur);
		}
                if (isset($this->chqbank)) {
			 $this->chqbank = trim($this->chqbank);
		}
                if (isset($this->datecheck)) {
			 $this->datecheck = trim($this->datecheck);
		}
                if (isset($this->amountcheck)) {
			 $this->amountcheck = trim($this->amountcheck);
		}
                if (isset($this->fk_customer_account_movement)) {
			 $this->fk_customer_account_movement = trim($this->fk_customer_account_movement);
		}
		if (isset($this->fk_user_author) && !empty($this->fk_user_author)) {
			 $this->fk_user_author = trim($this->fk_user_author);
		}
		if (isset($this->fk_user_modif) && !empty($this->fk_user_modif)) {
			 $this->fk_user_modif = trim($this->fk_user_modif);
		}
		if (isset($this->active) && !empty($this->active)) {
			 $this->active = trim($this->active);
		}
                if (isset($this->customer_used) && !empty($this->customer_used)) {
			 $this->customer_used = trim($this->customer_used);
		}
                if (isset($this->supplier_used) && !empty($this->supplier_used)) {
			 $this->supplier_used = trim($this->supplier_used);
		}
                
		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
		
		$sql .= ' num_paiement = '.(isset($this->num_paiement)?"'".$this->num_paiement."'":"null").',';
                
		$sql .= ' emetteur_chq = '.(isset($this->chqemetteur)?"'".$this->chqemetteur."'":"null").',';
		$sql .= ' bank_chq = '.(isset($this->chqbank)?"'".$this->chqbank."'":"null").',';
		
                //$sql .= ' date_chq = '.(! isset($this->datecheck) || dol_strlen($this->datecheck) != 0 ? "'".$this->db->idate($this->datecheck)."'" : 'null').',';
                $sql .= ' date_chq = '.(! isset($this->datecheck) || dol_strlen($this->datecheck) == 0 ? 'NULL' : "'".$this->db->idate($this->datecheck)."'").',';
                
                $sql .= ' amount_chq = '.(isset($this->amountcheck)?$this->amountcheck:"null").',';
                
		//$sql .= ' fk_customer_account_movement = '.(isset($this->fk_customer_account_movement)?$this->fk_customer_account_movement:"null").',';
		//$sql .= ' fk_user_author = '.(isset($this->fk_user_author)?$this->fk_user_author:"null").',';
		//$sql .= ' fk_user_modif = '.(isset($this->fk_user_modif)?$this->fk_user_modif:"null").',';
                $sql .= ' fk_user_modif = '.$user->id.',';
		$sql .= ' active = '.(isset($this->active)?$this->active:"null").',';
                $sql .= ' customer_used = '.(isset($this->customer_used)?$this->customer_used:"null").',';
                $sql .= ' supplier_used = '.(isset($this->supplier_used)?$this->supplier_used:"null");
                
		$sql .= ' WHERE rowid = ' . $this->id;

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
		}

		if (!$error && !$notrigger) {
			// Uncomment this and change MYOBJECT to your own tag if you
			// want this action calls a trigger.

			//// Call triggers
			//$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
			//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
			//// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user      User that deletes
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		if (!$error) {
			if (!$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_DELETE',$user);
				//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
				//// End call triggers
			}
		}

		// If you need to delete child tables to, you can insert them here
		
		if (!$error) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element;
			$sql .= ' WHERE rowid=' . $this->id;

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error ++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $fromid Id of object to clone
	 *
	 * @return int New id of clone
	 */
	public function createFromClone($fromid)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		global $user;
		$error = 0;
		$object = new cheque($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		// Reset object
		$object->id = 0;

		// Clear fields
		// ...

		// Create clone
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$error ++;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
		}

		// End
		if (!$error) {
			$this->db->commit();

			return $object->id;
		} else {
			$this->db->rollback();

			return - 1;
		}
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto			Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option				On what the link point to
     *  @param	int  	$notooltip			1=Disable tooltip
     *  @param	int		$maxlen				Max length of visible user name
     *  @param  string  $morecss            Add more css on link
	 *	@return	string						String with URL
	 */
	function getNomUrl($withpicto=0, $option='', $notooltip=0, $maxlen=24, $morecss='')
	{
		global $db, $conf, $langs;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips
        
        $result = '';
        $companylink = '';

        $label = '<u>' . $langs->trans("MyModule") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
        
        // TODO
        $url = DOL_URL_ROOT.'/compta/bank/'.$this->table_name.'_card.php?id='.$this->id;
        
        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowProject");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
        }
        else $linkclose = ($morecss?' class="'.$morecss.'"':'');
        
		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

        if ($withpicto)
        {
            $result.=($linkstart.img_object(($notooltip?'':$label), 'label', ($notooltip?'':'class="classfortooltip"')).$linkend);
            if ($withpicto != 2) $result.=' ';
		}
		$result.= $linkstart . $this->ref . $linkend;
		return $result;
	}

	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status,$mode);
	}

	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 5=Long label + Picto
	 *  @return string 			       	Label of status
	 */
	static function LibStatut($status,$mode=0)
	{
		global $langs;

		if ($mode == 0)
		{
			$prefix='';
			if ($status == 1) return $langs->trans('Enabled');
			if ($status == 0) return $langs->trans('Disabled');
		}
		if ($mode == 1)
		{
			if ($status == 1) return $langs->trans('Enabled');
			if ($status == 0) return $langs->trans('Disabled');
		}
		if ($mode == 2)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 3)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5');
		}
		if ($mode == 4)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 5)
		{
			if ($status == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5');
		}
		if ($mode == 6)
		{
			if ($status == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5');
		}
	}


	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;
		
		$this->num_paiement = '';
		$this->chqemetteur = '';
		$this->chqbank = '';
		$this->datecheck = '';
                $this->amountcheck = '';
		$this->fk_customer_account_movement = '';
		$this->fk_user_author = '';
		$this->fk_user_modif = '';
		$this->active = '';
                $this->customer_used = '';
                $this->supplier_used = '';
	}

}

/**
 * Class chequeLine
 */
class chequeLine
{
	/**
	 * @var int ID
	 */
	public $id;
	/**
	 * @var mixed Sample line property 1
	 */
	
        public $num_paiement;
	public $chqemetteur = '';
	public $chqbank;
        public $datecheck;
        public $amountcheck;
        public $fk_customer_account_movement;
	public $fk_user_author;
	public $fk_user_modif;
	public $active;
        public $customer_used;
        public $supplier_used;

	/**
	 * @var mixed Sample line property 2
	 */
	
}
