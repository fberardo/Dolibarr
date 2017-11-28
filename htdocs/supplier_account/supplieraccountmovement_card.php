<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file      supplier_account_movement/supplieraccountmovet.php
 *		\ingroup   supplier_account_movement
 *		\brief      This file is an example of a php page
 *					Initialy built by build_class_from_table on 2017-08-30 21:53
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
dol_include_once('/supplier_account/class/supplieraccountmovement.class.php');
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/cheque.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

// Load traductions files requiredby by page
$langs->load("supplieraccount@supplier_account");
$langs->load("other");
$langs->load('banks');
$langs->load('bills');

// Get parameters
$id			= GETPOST('id','int');
$socid			= GETPOST('socid','int');
$action		= GETPOST('action','alpha');
$cancel     = GETPOST('cancel');
$backtopage = GETPOST('backtopage');
$myparam	= GETPOST('myparam','alpha');
$paymentnum	= GETPOST('num_paiement');
$accountid	= GETPOST('accountid');


$search_amount=GETPOST('search_amount','alpha');
$search_description=GETPOST('search_description','alpha');


$societe = new Societe($db);
//if ($socid > 0 && empty($object->id))
//{
    // Load data of third party
    $res = $societe->fetch($socid);
    $thirdpartylabel = $societe->nom;
//}

if (empty($action) && empty($id) && empty($ref)) $action='view';

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}
//$result = restrictedArea($user, 'supplier_account', $id);


$object = new supplieraccountmovement($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// Initialize technical object to manage hooks of modules. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('supplieraccount'));



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancel) 
	{
		if ($action != 'addlink')
		{
			$urltogo=$backtopage?$backtopage:dol_buildpath('/supplier_account/supplieraccountmovement_list.php?socid='.$socid,1);
                        
			header("Location: ".$urltogo);
			exit;
		}		
		if ($id > 0 || ! empty($ref)) $ret = $object->fetch($id,$ref);
		$action='';
	}
	
	// Action to add record
	if ($action == 'add')
	{
		if (GETPOST('cancel'))
		{
			$urltogo=$backtopage?$backtopage:dol_buildpath('/supplier_account/supplieraccountmovement_list.php?socid='.$socid,1);
			header("Location: ".$urltogo);
			exit;
		}

		$error=0;

		/* object_prop_getpost_prop */
		
                $object->entity=GETPOST('entity','int');
                
                //$object->dateo=GETPOST('dateo','int');
                
                $month=$_POST["dateomonth"];
                $day=$_POST["dateoday"];
                $year=$_POST["dateoyear"];
                
                $dateop = dol_mktime(12,0,0,$month,$day,$year);
                $object->dateo=$dateop;
                
                $object->amount=GETPOST('amount','alpha');
                $object->label=GETPOST('label','alpha');
                $object->paiementcode=GETPOST('paiementcode','alpha');
                $object->fk_supplier_account=GETPOST('fk_supplier_account','int');
                $object->fk_user_author=GETPOST('fk_user_author','int');
                $object->fk_user_modif=GETPOST('fk_user_modif','int');
                $object->active=GETPOST('active','int');
                $object->fk_account_id=GETPOST('accountid','int');

		/*if (empty($object->ref))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref")), null, 'errors');
		}*/
                if (! isset($object->dateo) || empty($object->dateo))
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("SupplierAccountFieldDateOperationShort")), null, 'errors');
                }
                
                if (! isset($object->amount) || empty($object->amount))
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("SupplierAccountFieldamount")), null, 'errors');
                }
                /*else if (!is_numeric($object->amount))
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldMustBeNumber",$langs->transnoentitiesnoconv("SupplierAccountFieldamount")), null, 'errors');
                }
                */else
                {
                    $object->amount = price2num($object->amount);
                    if ($object->amount <= 0)
                    {
                        $error++;
                        setEventMessages($langs->trans("ErrorFieldCantBeNegative",$langs->transnoentitiesnoconv("SupplierAccountFieldamount")), null, 'errors');
                    }
                }
                
                if (! isset($object->label) || empty($object->label))
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("SupplierAccountFieldlabel")), null, 'errors');
                }
                if (! isset($object->paiementcode) || empty($object->paiementcode))
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("PaymentMode")), null, 'errors');
                }
                
                if (! empty($conf->banque->enabled))
                {
                    // If bank module is on, account is required to enter a payment
                    if (GETPOST('accountid') <= 0)
                    {
                        setEventMessages($langs->transnoentities('ErrorFieldRequired',$langs->transnoentities('AccountToDebit')), null, 'errors');
                        $error++;
                    }
                }

		if (! $error)
			
		{
			
                        $paiementcode = GETPOST('paiementcode','alpha');
                        $idcheque = null;
                        
                        //$label='(SupplierInvoicePayment)';
                        $label='(SupplierAccountMovement)';
                       
                        $paiement = new PaiementFourn($db);
                        $paiement->datepaye = $dateop;
                        //$paiement->paiementid = dol_getIdFromCode($db, GETPOST('paiementcode'), 'c_paiement');
                        $paiement->paiementid = $paiementcode;
                        $paiement->amount = $object->amount;
                        $paiement->total = $object->amount;
                        $paiement->num_paiement = $paymentnum;

                        $amounts = array();
                        $amounts[$societe->id] = $societe->nom;
                        $paiement->amounts = $amounts;
                        
                        $_chqemetteur = '';
                        $_chqbank = '';
                        if ($paiementcode == 'CHQ' || $paiementcode == 'VIR') // || $paiementcode == 'CHT'. En el caso de cheques, validar e ingresar únicamente si es cheque propio, si es de terceros se va a asentar en el pago.
                        {
                            $_chqemetteur = GETPOST('chqemetteur');
                            $_chqbank = GETPOST('chqbank');
                        }

                        $bank_line_id = $paiement->addPaymentToBank($user, 'payment_supplier', $label, GETPOST('accountid'), $_chqemetteur, $_chqbank);
                        if ($bank_line_id < 0)
                        {
                            setEventMessages($paiement->error, $paiement->errors, 'errors');
                            $error++;
                        }
                        
                        /*
                         * <option value="CHQ">Cheque</option>
                         * <option value="CHT">Cheque Terceros</option>
                         */
                        if ($paiementcode == 'CHQ') { // || $paiementcode == 'CHT'. Validar e ingresar únicamente si es cheque propio, si es de terceros se va a asentar en el pago.
                            
                            $objectcheque = new cheque($db);
                            $objectcheque->num_paiement = $paymentnum;
                            $objectcheque->chqemetteur = GETPOST('chqemetteur','alpha');
                            $objectcheque->chqbank = GETPOST('chqbank','alpha');
                            
                            $month=$_POST["datecheckmonth"];
                            $day=$_POST["datecheckday"];
                            $year=$_POST["datecheckyear"];

                            $datecheckp = dol_mktime(12,0,0,$month,$day,$year);
                            $objectcheque->datecheck = $datecheckp;
                            
                            $objectcheque->amountcheck = GETPOST('amount','alpha');
                            
                            $objectcheque->fk_user_author=GETPOST('fk_user_author','int');
                            $objectcheque->fk_user_modif=GETPOST('fk_user_modif','int');
                            $objectcheque->active=GETPOST('active','int');
                            $objectcheque->customer_used=0;
                            $objectcheque->supplier_used=0;
                            
                            if (! isset($objectcheque->chqemetteur) || empty($objectcheque->chqemetteur))
                            {
                                $error++;
                                setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CheckTransmitter")), null, 'errors');
                            }
                            if (! isset($objectcheque->datecheck) || empty($objectcheque->datecheck))
                            {
                                $error++;
                                setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("SupplierAccountFieldDateCheckShort")), null, 'errors');
                            }
                            
                            if (! $error)
                            {
                                $idcheque = $objectcheque->create($user);

                                if ($idcheque < 0) {
                                    // Creation KO
                                    if (! empty($objectcheque->errors)) setEventMessages(null, $objectcheque->errors, 'errors');
                                    else  setEventMessages($objectcheque->error, null, 'errors');
                                    $error++;
                                }
                            }
                            else
                            {
                                $action='create';
                            }
                        }
                        
                        if (! $error)
                        {
                            $object->paiementid = dol_getIdFromCode($db,GETPOST('paiementcode'),'c_paiement');
                            $object->fk_cheque = $idcheque;
                            $object->acc_line_id = $bank_line_id;
                            
                            // invierto el signo para guardarlo negativo, porque se guarda como un movimiento negativo pero acà se muestra como un valor positivo
                            $object->amount *= (-1); 

                            $result=$object->create($user);

                            if ($result > 0)
                            {
                                    // Creation OK
                                    $urltogo=$backtopage?$backtopage:dol_buildpath('/supplier_account/supplieraccountmovement_list.php?socid='.$socid,1);
                                    header("Location: ".$urltogo);
                                    exit;
                            }
                            {
                                    // Creation KO
                                    if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
                                    else  setEventMessages($object->error, null, 'errors');
                                    $action='create';
                            }
                        }
                        else
                        {
                            $action='create';
                        }
		}
		else
		{
			$action='create';
		}
	}

	// Action to update record
	if ($action == 'update')
	{
		$error=0;
                
                $object->fetch($id);
                
                $object->entity=GETPOST('entity','int');
                
                //$object->dateo=GETPOST('dateo','int');
                
                $month=$_POST["dateomonth"];
                $day=$_POST["dateoday"];
                $year=$_POST["dateoyear"];
                
                $dateop = dol_mktime(12,0,0,$month,$day,$year);
                $object->dateo=$dateop;
                
                $object->amount=GETPOST('amount','alpha');
                $object->label=GETPOST('label','alpha');
                $object->paiementcode=GETPOST('paiementcode','alpha');
                $object->fk_supplier_account=GETPOST('fk_supplier_account','int');
                $object->fk_user_author=GETPOST('fk_user_author','int');
                $object->fk_user_modif=GETPOST('fk_user_modif','int');
                $object->active=GETPOST('active','int');
                $object->fk_account_id=GETPOST('accountid','int');

		/*if (empty($object->ref))
		{
			$error++;
			setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref")), null, 'errors');
		}*/
                if (! isset($object->dateo) || empty($object->dateo))
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("SupplierAccountFieldDateOperationShort")), null, 'errors');
                }
                
                if (! isset($object->amount) || empty($object->amount))
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("SupplierAccountFieldamount")), null, 'errors');
                }
                /*else if (!is_numeric($object->amount))
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldMustBeNumber",$langs->transnoentitiesnoconv("SupplierAccountFieldamount")), null, 'errors');
                }
                */else
                {
                    $object->amount = price2num($object->amount);
                    if ($object->amount <= 0)
                    {
                        $error++;
                        setEventMessages($langs->trans("ErrorFieldCantBeNegative",$langs->transnoentitiesnoconv("SupplierAccountFieldamount")), null, 'errors');
                    }
                }
                
                if (! isset($object->label) || empty($object->label))
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("SupplierAccountFieldlabel")), null, 'errors');
                }
                if (! isset($object->paiementcode) || empty($object->paiementcode))
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("PaymentMode")), null, 'errors');
                }
                if (! empty($conf->banque->enabled))
                {
                    // If bank module is on, account is required to enter a payment
                    if (GETPOST('accountid') <= 0)
                    {
                        setEventMessages($langs->transnoentities('ErrorFieldRequired',$langs->transnoentities('AccountToDebit')), null, 'errors');
                        $error++;
                    }
                }

		if (! $error)
		{
			$objectcheque = new cheque($db);
                        $idcheque = null;
                        
                        if ($object->fk_cheque != null)
                        {
                            $objectcheque->fetch($object->fk_cheque);
                            $idcheque = $object->fk_cheque;
                        }
                        
                        $paiementcode = GETPOST('paiementcode','alpha');
                        
                        //$label='(SupplierInvoicePayment)';
                        $label='(SupplierAccountMovement)';
                        
                        $paiement = new PaiementFourn($db);
                        $paiement->datepaye = $dateop;
                        //$paiement->paiementid = dol_getIdFromCode($db, GETPOST('paiementcode'), 'c_paiement');
                        $paiement->paiementid = $paiementcode;
                        $paiement->amount = $object->amount;
                        $paiement->total = $object->amount;
                        $paiement->num_paiement = $paymentnum;

                        $amounts = array();
                        $amounts[$societe->id] = $societe->nom;
                        $paiement->amounts = $amounts;

                        $_chqemetteur = '';
                        $_chqbank = '';
                        if ($paiementcode == 'CHQ' || $paiementcode == 'VIR') // || $paiementcode == 'CHT'. En el caso de cheques, validar e ingresar únicamente si es cheque propio, si es de terceros se va a asentar en el pago.
                        {
                            $_chqemetteur = GETPOST('chqemetteur');
                            $_chqbank = GETPOST('chqbank');
                        }
                        
                        $bank_line_id = $paiement->addPaymentToBank($user, 'payment_supplier', $label, GETPOST('accountid'), $_chqemetteur, $_chqbank, 0, $object->acc_line_id);
                        if ($bank_line_id < 0)
                        {
                            setEventMessages($paiement->error, $paiement->errors, 'errors');
                            $error++;
                        }
                        
                        /*
                         * <option value="CHQ">Cheque</option>
                         * <option value="CHT">Cheque Terceros</option>
                         */
                        if ($paiementcode == 'CHQ') { // Validar e ingresar únicamente si es cheque propio, si es de terceros se va a asentar en el pago.
                            
                            $objectcheque->num_paiement = $paymentnum;
                            $objectcheque->chqemetteur = GETPOST('chqemetteur','alpha');
                            $objectcheque->chqbank = GETPOST('chqbank','alpha');
                            
                            $month=$_POST["datecheckmonth"];
                            $day=$_POST["datecheckday"];
                            $year=$_POST["datecheckyear"];

                            $datecheckp = dol_mktime(12,0,0,$month,$day,$year);
                            $objectcheque->datecheck = $datecheckp;
                            
                            $objectcheque->amountcheck = GETPOST('amount','alpha');
                            
                            $objectcheque->fk_user_author=GETPOST('fk_user_author','int');
                            $objectcheque->fk_user_modif=GETPOST('fk_user_modif','int');
                            $objectcheque->active=GETPOST('active','int');
                            //$objectcheque->customer_used=0;
                            //$objectcheque->supplier_used=0;
                            
                            if (! isset($objectcheque->chqemetteur) || empty($objectcheque->chqemetteur))
                            {
                                $error++;
                                setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CheckTransmitter")), null, 'errors');
                            }
                            if (! isset($objectcheque->datecheck) || empty($objectcheque->datecheck))
                            {
                                $error++;
                                setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("SupplierAccountFieldDateCheckShort")), null, 'errors');
                            }
                            
                            if (! $error)
                            {
                                $result = 1;
                                if ($object->fk_cheque != null)
                                {
                                    $result = $objectcheque->update($user);
                                }
                                else
                                {
                                    $idcheque = $objectcheque->create($user);
                                }

                                if ($idcheque < 0 || $result < 0) {
                                    // Creation KO
                                    if (! empty($objectcheque->errors)) setEventMessages(null, $objectcheque->errors, 'errors');
                                    else  setEventMessages($objectcheque->error, null, 'errors');
                                    $error++;
                                }
                            }
                            else
                            {
                                $action='edit';
                            }

                        }
                        else
                        {
                            if ($object->fk_cheque != null)
                            {
                                // Eliminar el cheque existente
                                $objectcheque->fetch($object->fk_cheque);
                                $objectcheque->delete($user);
                                
                            }
                            $idcheque = null;
                        }
                        
                        if (! $error)
                        {
                            $object->paiementid = dol_getIdFromCode($db,GETPOST('paiementcode'),'c_paiement');
                            $object->fk_cheque = $idcheque;
                            $object->acc_line_id = $bank_line_id;
                            
                            // invierto el signo para guardarlo negativo, porque se guarda como un movimiento negativo pero acà se muestra como un valor positivo
                            $object->amount *= (-1); 
                        
                            $result=$object->update($user);
                            if ($result > 0)
                            {
                                    $action='view';
                            }
                            else
                            {
                                    // Creation KO
                                    if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
                                    else setEventMessages($object->error, null, 'errors');
                                    $action='edit';
                            }
                        }
                        else
                        {
                            $action='edit';
                        }
		}
		else
		{
			$action='edit';
		}
	}

	// Action to delete
	if ($action == 'confirm_delete')
	{
		$result=$object->delete($user);
                
                // Eliminar el movimiento existente en la caja
                $paiement = new Paiement($db);
                $paiement->bank_line = $object->acc_line_id;
                $paiement->delete(0, true);
                
		if ($result > 0)
		{
			
                        if ($object->fk_cheque != null)
                        {
                            // Eliminar el cheque existente
                            $objectcheque = new cheque($db);
                            $objectcheque->fetch($object->fk_cheque);
                            $objectcheque->delete($user);

                        }
                        
                        // Delete OK
			setEventMessages("RecordDeleted", null, 'mesgs');
                        $socid = GETPOST('socid');
                        header("Location: ".dol_buildpath('/supplier_account/supplieraccountmovement_list.php?socid='.$socid,1));
			exit;
		}
		else
		{
			if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else setEventMessages($object->error, null, 'errors');
		}
	}
}


/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

$title = $langs->trans("SupplierAccountMovementTitle");

llxHeader('',$title,'');

$form=new Form($db);


// Put here content of your page

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});';

// Add realtime total information
if ($conf->use_javascript_ajax)
{
    print '	function setPaiementCode()
                {
                    var code = $("#selectpaiementcode option:selected").val();

                    if (code == \'CHQ\' || code == \'CHT\' || code == \'VIR\')
                    {
                        if (code == \'CHQ\' || code == \'CHT\')
                        {
                            $(\'.fieldrequireddyn\').addClass(\'fieldrequired\');
                            
                            if (code == \'CHQ\')
                            {
                                var emetteur = \''.dol_escape_js(dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM)).'\'
                                $(\'#fieldchqemetteur\').val(emetteur);
                            }
                            else
                            {
                                $(\'#fieldchqemetteur\').val(\'\');
                            }
                        }
                        
                    }
                    else
                    {
                        $(\'.fieldrequireddyn\').removeClass(\'fieldrequired\');
                        $(\'#fieldchqemetteur\').val(\'\');
                    }
                }
                
                setPaiementCode();

                $("#selectpaiementcode").change(function() {
                    setPaiementCode();
                });';
}

print '	});'."\n";
print '	</script>'."\n";


// Part to create
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("SupplierAccountMovementNuevoTitle"));

	print '<form method="POST" name="createForm" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
        print '<input type="hidden" name="socid" value="'.$socid.'">';
        print '<input type="hidden" name="entity" value="'.GETPOST('entity').'">';
        print '<input type="hidden" name="fk_supplier_account" value="'.GETPOST('fk_supplier_account').'">';
        print '<input type="hidden" name="fk_user_author" value="'.GETPOST('fk_user_author').'">';
        print '<input type="hidden" name="fk_user_modif" value="'.GETPOST('fk_user_modif').'">';
        print '<input type="hidden" name="active" value="'.GETPOST('active').'">';
        
	dol_fiche_head();

	print '<table class="border centpercent">'."\n";
	// print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input class="flat" type="text" size="36" name="label" value="'.$label.'"></td></tr>';
	// 
        
        // Date ope
        print '<tr><td class="fieldrequired">'.$langs->trans("SupplierAccountFieldDateOperationShort").'</td>';
        print '<td>';
        print $form->select_date($object->dateo,'dateo','','','','createForm',1,1,1);
        
        // For Only View
        /*
        //print dol_print_date($object->dateo, "day");
        */
        
        print '</td>';
        print '</tr>';
        
        // Monto
        print '<tr><td class="fieldrequired">'.$langs->trans("SupplierAccountFieldamount").'</td><td><input class="flat" type="text" name="amount" value="'.((isset($object->amount) && !empty($object->amount)) ? price($object->amount) : '').'"></td></tr>';
        
        // Descripcion
        print '<tr><td class="fieldrequired">'.$langs->trans("SupplierAccountFieldlabel").'</td><td><input class="flat" type="text" name="label" value="'.$object->label.'"></td></tr>';
        
        // Payment mode
        print '<tr><td><span class="fieldrequired">'.$langs->trans('PaymentMode').'</span></td><td>';
        //$form->select_types_paiements((GETPOST('paiementcode')?GETPOST('paiementcode'):$facture->mode_reglement_code),'paiementcode','',2);
        $form->select_types_paiements((GETPOST('paiementcode')),'paiementcode','',2);
        print "</td>\n";
        print '</tr>';
        
        // Bank account
        print '<tr>';
        if (! empty($conf->banque->enabled))
        {
            print '<td><span class="fieldrequired">'.$langs->trans('AccountToDebit').'</span></td>';
            print '<td>';
            $form->select_comptes($accountid,'accountid',0,'',2);
            print '</td>';
        }
        else
        {
            print '<td>&nbsp;</td>';
        }
        print "</tr>\n";
        
        // Cheque number
        print '<tr><td>'.$langs->trans('Numero');
        print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
        print '</td>';
        print '<td><input name="num_paiement" type="text" value="'.GETPOST('num_paiement').'"></td></tr>';

        // Check transmitter
        print '<tr><td class="'.((GETPOST('paiementcode')=='CHQ' || GETPOST('paiementcode')=='CHT') ? 'fieldrequired ' : '').'fieldrequireddyn">'.$langs->trans('CheckTransmitter');
        print ' <em>('.$langs->trans("ChequeMaker").')</em>';
        print '</td>';
        print '<td><input id="fieldchqemetteur" name="chqemetteur" size="30" type="text" value="'.GETPOST('chqemetteur').'"></td></tr>';

        // Bank name
        print '<tr><td>'.$langs->trans('Bank');
        print ' <em>('.$langs->trans("ChequeBank").')</em>';
        print '</td>';
        print '<td><input name="chqbank" size="30" type="text" value="'.GETPOST('chqbank').'"></td></tr>';
        
        $month=$_POST["datecheckmonth"];
        $day=$_POST["datecheckday"];
        $year=$_POST["datecheckyear"];
        
        $datecheckp = (GETPOST('paiementcode')=='CHQ' || GETPOST('paiementcode')=='CHT') ? dol_mktime(12,0,0,$month,$day,$year) : '';
                            
        // Check date
        print '<tr><td class="'.((GETPOST('paiementcode')=='CHQ' || GETPOST('paiementcode')=='CHT') ? 'fieldrequired ' : '').'fieldrequireddyn">'.$langs->trans("SupplierAccountFieldDateCheckShort").'</td>';
        print '<td>';
        print $form->select_date($datecheckp,'datecheck','','','','createForm',1,1,1);
        
        // For Only View
        /*
        //print dol_print_date($object->dateo, "day");
        */
        
        print '</td>';
        print '</tr>';
        
	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="add" value="'.$langs->trans("SupplierAccountCreateButton").'"> &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("SupplierAccountCancelButton").'"></div>';

	print '</form>';
}



// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print load_fiche_titre($langs->trans("SupplierAccountMovementEditarTitle"));
    
	print '<form method="POST" name="updateForm" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
        print '<input type="hidden" name="socid" value="'.$socid.'">';
        print '<input type="hidden" name="entity" value="'.GETPOST('entity').'">';
        print '<input type="hidden" name="fk_supplier_account" value="'.GETPOST('fk_supplier_account').'">';
        print '<input type="hidden" name="fk_user_author" value="'.GETPOST('fk_user_author').'">';
        print '<input type="hidden" name="fk_user_modif" value="'.GETPOST('fk_user_modif').'">';
        print '<input type="hidden" name="active" value="'.GETPOST('active').'">';
        
        $objectcheque = new cheque($db);
        if ($object->fk_cheque != null)
        {
            $paiementcode = dol_getIdFromCode($db,$object->paiementid,'c_paiement','id','code');
            
            $objectcheque->fetch($object->fk_cheque);
        }
	
	dol_fiche_head();

	print '<table class="border centpercent">'."\n";
	// print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input class="flat" type="text" size="36" name="label" value="'.$label.'"></td></tr>';
	// 
        
        // Date ope
        print '<tr><td class="fieldrequired">'.$langs->trans("SupplierAccountFieldDateOperationShort").'</td>';
        print '<td>';
        print $form->select_date($object->dateo,'dateo','','','','updateForm',1,1,1);
        print '</td>';
        print '</tr>';
        
        // Monto
        // invierto el signo para verlo positivo, porque se guarda como un movimiento negativo pero acà se muestra como un valor positivo
        $object->amount *= (-1);
        print '<tr><td class="fieldrequired">'.$langs->trans("SupplierAccountFieldamount").'</td><td><input class="flat" type="text" name="amount" value="'.price($object->amount).'"></td></tr>';
        
        // Descripcion
        print '<tr><td class="fieldrequired">'.$langs->trans("SupplierAccountFieldlabel").'</td><td><input class="flat" type="text" name="label" value="'.$object->label.'"></td></tr>';
        
        // Payment mode
        $paiementcode = dol_getIdFromCode($db,$object->paiementid,'c_paiement','id','code');
        
        // Payment mode
        print '<tr><td><span class="fieldrequired">'.$langs->trans('PaymentMode').'</span></td><td>';
        //$form->select_types_paiements((GETPOST('paiementcode')?GETPOST('paiementcode'):$facture->mode_reglement_code),'paiementcode','',2);
        $form->select_types_paiements($paiementcode,'paiementcode','',2);
        print "</td>\n";
        print '</tr>';
        
        // Bank account
        print '<tr>';
        if (! empty($conf->banque->enabled))
        {
            print '<td><span class="fieldrequired">'.$langs->trans('AccountToDebit').'</span></td>';
            print '<td>';
            $form->select_comptes($object->fk_account_id,'accountid',0,'',2);
            print '</td>';
        }
        else
        {
            print '<td>&nbsp;</td>';
        }
        print "</tr>\n";
        
        // Cheque number
        print '<tr><td>'.$langs->trans('Numero');
        print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
        print '</td>';
        print '<td><input name="num_paiement" type="text" value="'.$objectcheque->num_paiement.'"></td></tr>';

        // Check transmitter
        print '<tr><td class="'.(GETPOST('paiementcode')=='CHQ'?'fieldrequired ':'').'fieldrequireddyn">'.$langs->trans('CheckTransmitter');
        print ' <em>('.$langs->trans("ChequeMaker").')</em>';
        print '</td>';
        print '<td><input id="fieldchqemetteur" name="chqemetteur" size="30" type="text" value="'.$objectcheque->chqemetteur.'"></td></tr>';

        // Bank name
        print '<tr><td>'.$langs->trans('Bank');
        print ' <em>('.$langs->trans("ChequeBank").')</em>';
        print '</td>';
        print '<td><input name="chqbank" size="30" type="text" value="'.$objectcheque->chqbank.'"></td></tr>';
        
        $month=$_POST["datecheckmonth"];
        $day=$_POST["datecheckday"];
        $year=$_POST["datecheckyear"];
        
        $datecheckp = $objectcheque->datecheck;
        if (GETPOST('paiementcode')!='')
        {
            if (GETPOST('paiementcode')=='CHQ' || GETPOST('paiementcode')=='CHT')
            {
                $datecheckp = dol_mktime(12,0,0,$month,$day,$year);
            }
            else 
            {
                $datecheckp = '';
            }
        }
        
        // Check date
        print '<tr><td class="fieldrequired">'.$langs->trans("SupplierAccountFieldDateCheckShort").'</td>';
        print '<td>';
        print $form->select_date($datecheckp,'datecheck','','','','updateForm',1,1,1);
        
        // For Only View
        /*
        //print dol_print_date($object->datecheck, "day");
        */
        
        print '</td>';
        print '</tr>';

	print '</table>';
	
	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans("SupplierAccountUpdateButton").'">';
	print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("SupplierAccountCancelButton").'">';
	print '</div>';

	print '</form>';
}



// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
    $res = $object->fetch_optionals($object->id, $extralabels);

			
	print load_fiche_titre($langs->trans("SupplierAccountMovementVerTitle"));
    
	dol_fiche_head();

	if ($action == 'delete') {
                $socid = GETPOST('socid');
                $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&socid=' . $socid, $langs->trans('SupplierAccountMovementEliminar'), $langs->trans('SupplierAccountMovementConfirmarEliminar'), 'confirm_delete', '', 0, 1);
		print $formconfirm;
	}
	
	print '<table class="border centpercent">'."\n";
	// print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';
	// 

        $id = GETPOST('id','int');
        $ref = GETPOST('ref','alpha');
        $rowid=GETPOST("rowid",'int');
        $linkback = '<a href="'.DOL_URL_ROOT.'/supplier_account/supplieraccountmovement_list.php?socid='.$socid.'">'.$langs->trans("BackToList").'</a>';

        $supplierAccountMovement = new supplieraccountmovement($db);
        //$supplierAccountMovement->fetch($id,$ref);
        $supplierAccountMovement->fetch($id);
        
        $morefilters=' AND fk_supplier_account = '.$supplierAccountMovement->fk_supplier_account;
        $moreparams='&amp;socid='.$socid.
        '&amp;entity='.$supplierAccountMovement->entity.
        '&amp;fk_supplier_account='.$supplierAccountMovement->fk_supplier_account.
        '&amp;active='.$supplierAccountMovement->active;
        
        $supplierAccountMovement->next_prev_filter=$morefilters;
        $supplierAccountMovement->ref=$id;
        
        // Descripcion
        $label = $object->label;
        $objectlabel = $object->label;
        //Pago de Factura ID['.$key.']
        if (substr($label,0,19) == 'Pago de Factura ID[')
        {
            $cursorfacid = substr($label,19, dol_strlen($label)-19-1);
            $facture = new Facture($db);
            $result = $facture->fetch($cursorfacid);

            if ($result >= 0)
            {
                $objectlabel = 'Pago de Factura ' . $facture->getNomUrl(1,'');
            }
        }
        
        $objectcheque = new cheque($db);
        if (($object->fk_cheque != null) && ($object->paiementid != null))
        {
            //$paiementcode = dol_getIdFromCode($db,$object->paiementid,'c_paiement','id','code');
            $form->load_cache_types_paiements();
            $paiementdesc = $form->cache_types_paiements[$object->paiementid]['label'];
            
            $objectcheque->fetch($object->fk_cheque);
        }
                    
        // Ref
        print '<tr><td class="titlefield">'.$langs->trans("Ref")."</td>";
        print '<td>';
        
        $refnav = $form->showrefnav($supplierAccountMovement, 'rowid', $linkback, 1, 'rowid', 'id', '', $moreparams);
        $refnav = str_replace('rowid', 'id', $refnav);
        print $refnav;
        
        print '</td>';
        print '</tr>';
        
        // Date ope
        print '<tr><td>'.$langs->trans("SupplierAccountFieldDateOperationShort").'</td>';
        print '<td>';
        print dol_print_date($object->dateo, "day");
        print '</td>';
        print '</tr>';
        
        // Monto
        // invierto el signo para verlo positivo, porque se guarda como un movimiento negativo pero acà se muestra como un valor positivo
        $object->amount *= (-1);
        print '<tr><td>'.$langs->trans("SupplierAccountFieldamount").'</td><td>'.price($object->amount).'</td></tr>';
        
        print '<tr><td>'.$langs->trans("SupplierAccountFieldlabel").'</td><td>'.$objectlabel.'</td></tr>';
        print '<tr><td>'.$langs->trans("PaymentMode").'</td><td>'.$paiementdesc.'</td></tr>';
        
        $accountname = '';
        $acc = new Account($db);
        $result = $acc->fetch($supplierAccountMovement->fk_account_id);
        if ($result > 0) {
            $accountname = $acc->label;
        }
            
        print '<tr><td>'.$langs->trans("AccountToDebit").'</td><td>'.$accountname.'</td></tr>';
        
        print '<tr><td>'.$langs->trans('Numero').'<em>('.$langs->trans("ChequeOrTransferNumber").')</em>.</td><td>'.$objectcheque->num_paiement.'</td></tr>';
        print '<tr><td>'.$langs->trans("ChequeMaker").'</td><td>'.$objectcheque->chqemetteur.'</td></tr>';
        print '<tr><td>'.$langs->trans("ChequeBank").'</td><td>'.$objectcheque->chqbank.'</td></tr>';
        print '<tr><td>'.$langs->trans("SupplierAccountFieldDateCheckShort").'</td><td>'.dol_print_date($objectcheque->datecheck, "day").'</td></tr>';
        
	print '</table>';
	
	dol_fiche_end();


	// Buttons
	print '<div class="tabsAction">'."\n";
	$parameters=array();
	$reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	if (empty($reshook))
	{
		$hiddenobjvaluesedit='&amp;entity='.GETPOST('entity').
                '&amp;fk_supplier_account='.GETPOST('fk_supplier_account').
                '&amp;active='.GETPOST('active');
                
                if ($user->rights->supplieraccount->supplieraccountmovement->write)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&socid='.$socid.$hiddenobjvaluesedit.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>'."\n";
		}

		if ($user->rights->supplieraccount->supplieraccountmovement->delete)
		{
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&socid='.$socid.$hiddenobjvaluesedit.'&amp;action=delete">'.$langs->trans('Delete').'</a></div>'."\n";
		}
	}
	print '</div>'."\n";


	// Example 2 : Adding links to objects
	// Show links to link elements
	//$linktoelem = $form->showLinkToObjectBlock($object, null, array('supplieraccount'));
	//$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

}


// End of page
llxFooter();
$db->close();
