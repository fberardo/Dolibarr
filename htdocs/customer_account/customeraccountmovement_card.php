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
 *   	\file      customer_account_movement/customeraccountmovet.php
 *		\ingroup   customer_account_movement
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
dol_include_once('/customer_account/class/customeraccountmovement.class.php');

// Load traductions files requiredby by page
$langs->load("customeraccount@customer_account");
$langs->load("other");

// Get parameters
$id			= GETPOST('id','int');
$socid			= GETPOST('socid','int');
$action		= GETPOST('action','alpha');
$cancel     = GETPOST('cancel');
$backtopage = GETPOST('backtopage');
$myparam	= GETPOST('myparam','alpha');


$search_amount=GETPOST('search_amount','alpha');
$search_description=GETPOST('search_description','alpha');



if (empty($action) && empty($id) && empty($ref)) $action='view';

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}
//$result = restrictedArea($user, 'customer_accountt', $id);


$object = new customeraccountmovement($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// Initialize technical object to manage hooks of modules. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('customeraccount'));



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
			$urltogo=$backtopage?$backtopage:dol_buildpath('/customer_account/customeraccountmovement_list.php?socid='.$socid,1);
                        
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
			$urltogo=$backtopage?$backtopage:dol_buildpath('/customer_account/customeraccountmovement_list.php?socid='.$socid,1);
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
                $object->fk_customer_account=GETPOST('fk_customer_account','int');
                $object->fk_user_author=GETPOST('fk_user_author','int');
                $object->fk_user_modif=GETPOST('fk_user_modif','int');
                $object->active=GETPOST('active','int');

		

		/*if (empty($object->ref))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref")), null, 'errors');
		}*/
                if (! isset($object->amount) || empty($object->amount))
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CustomerAccountFieldamount")), null, 'errors');
                }

		if (! $error)
			
		{
			$result=$object->create($user);
			if ($result > 0)
			{
				// Creation OK
				$urltogo=$backtopage?$backtopage:dol_buildpath('/customer_account/customeraccountmovement_list.php?socid='.$socid,1);
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

	// Action to update record
	if ($action == 'update')
	{
		$error=0;

		
                $object->entity=GETPOST('entity','int');
                
                //$object->dateo=GETPOST('dateo','int');
                
                $month=$_POST["dateomonth"];
                $day=$_POST["dateoday"];
                $year=$_POST["dateoyear"];
                
                $dateop = dol_mktime(12,0,0,$month,$day,$year);
                $object->dateo=$dateop;
                
                $object->amount=GETPOST('amount','alpha');
                $object->label=GETPOST('label','alpha');
                $object->fk_customer_account=GETPOST('fk_customer_account','int');
                $object->fk_user_author=GETPOST('fk_user_author','int');
                $object->fk_user_modif=GETPOST('fk_user_modif','int');
                $object->active=GETPOST('active','int');

		

		/*if (empty($object->ref))
		{
			$error++;
			setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref")), null, 'errors');
		}*/
                if (! isset($object->amount) || empty($object->amount))
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CustomerAccountFieldamount")), null, 'errors');
                }

		if (! $error)
		{
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

	// Action to delete
	if ($action == 'confirm_delete')
	{
		$result=$object->delete($user);
		if ($result > 0)
		{
			// Delete OK
			setEventMessages("RecordDeleted", null, 'mesgs');
                        $socid = GETPOST('socid');
                        header("Location: ".dol_buildpath('/customer_account/customeraccountmovement_list.php?socid='.$socid,1));
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

$title = $langs->trans("CustomerAccountMovementTitle");

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
	});
});
</script>';


// Part to create
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("CustomerAccountMovementNuevoTitle"));

	print '<form method="POST" name="createForm" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
        print '<input type="hidden" name="socid" value="'.$socid.'">';
        print '<input type="hidden" name="entity" value="'.GETPOST('entity').'">';
        print '<input type="hidden" name="fk_customer_account" value="'.GETPOST('fk_customer_account').'">';
        print '<input type="hidden" name="fk_user_author" value="'.GETPOST('fk_user_author').'">';
        print '<input type="hidden" name="fk_user_modif" value="'.GETPOST('fk_user_modif').'">';
        print '<input type="hidden" name="active" value="'.GETPOST('active').'">';
        
        
	dol_fiche_head();

	print '<table class="border centpercent">'."\n";
	// print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input class="flat" type="text" size="36" name="label" value="'.$label.'"></td></tr>';
	// 
        
        // Date ope
        print '<tr><td class="fieldrequired">'.$langs->trans("CustomerAccountFieldDateOperationShort").'</td>';
        print '<td>';
        print $form->select_date(GETPOST('dateo'),'dateo','','','','createForm',1,1,1);
        
        // For Only View
        /*
        //print dol_print_date($object->dateo, "day");
        */
        
        print '</td>';
        print '</tr>';
        
        print '<tr><td class="fieldrequired">'.$langs->trans("CustomerAccountFieldamount").'</td><td><input class="flat" type="text" name="amount" value="'.GETPOST('amount').'"></td></tr>';
        print '<tr><td class="fieldrequired">'.$langs->trans("CustomerAccountFieldlabel").'</td><td><input class="flat" type="text" name="label" value="'.GETPOST('label').'"></td></tr>';

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="add" value="'.$langs->trans("CustomerAccountCreateButton").'"> &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("CustomerAccountCancelButton").'"></div>';

	print '</form>';
}



// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print load_fiche_titre($langs->trans("CustomerAccountMovementEditarTitle"));
    
	print '<form method="POST" name="updateForm" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
        print '<input type="hidden" name="socid" value="'.$socid.'">';
        print '<input type="hidden" name="entity" value="'.GETPOST('entity').'">';
        print '<input type="hidden" name="fk_customer_account" value="'.GETPOST('fk_customer_account').'">';
        print '<input type="hidden" name="fk_user_author" value="'.GETPOST('fk_user_author').'">';
        print '<input type="hidden" name="fk_user_modif" value="'.GETPOST('fk_user_modif').'">';
        print '<input type="hidden" name="active" value="'.GETPOST('active').'">';
	
	dol_fiche_head();

	print '<table class="border centpercent">'."\n";
	// print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input class="flat" type="text" size="36" name="label" value="'.$label.'"></td></tr>';
	// 
        
        // Date ope
        print '<tr><td class="fieldrequired">'.$langs->trans("CustomerAccountFieldDateOperationShort").'</td>';
        print '<td>';
        print $form->select_date($object->dateo,'dateo','','','','updateForm',1,1,1);
        print '</td>';
        print '</tr>';
        
        print '<tr><td class="fieldrequired">'.$langs->trans("CustomerAccountFieldamount").'</td><td><input class="flat" type="text" name="amount" value="'.$object->amount.'"></td></tr>';
        print '<tr><td class="fieldrequired">'.$langs->trans("CustomerAccountFieldlabel").'</td><td><input class="flat" type="text" name="label" value="'.$object->label.'"></td></tr>';

	print '</table>';
	
	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans("CustomerAccountUpdateButton").'">';
	print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("CustomerAccountCancelButton").'">';
	print '</div>';

	print '</form>';
}



// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
    $res = $object->fetch_optionals($object->id, $extralabels);

			
	print load_fiche_titre($langs->trans("CustomerAccountMovementVerTitle"));
    
	dol_fiche_head();

	if ($action == 'delete') {
                $socid = GETPOST('socid');
                $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&socid=' . $socid, $langs->trans('CustomerAccountMovementEliminar'), $langs->trans('CustomerAccountMovementConfirmarEliminar'), 'confirm_delete', '', 0, 1);
		print $formconfirm;
	}
	
	print '<table class="border centpercent">'."\n";
	// print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';
	// 

        $id = GETPOST('id','int');
        $ref = GETPOST('ref','alpha');
        $rowid=GETPOST("rowid",'int');
        $linkback = '<a href="'.DOL_URL_ROOT.'/customer_account/customeraccountmovement_list.php?socid='.$socid.'">'.$langs->trans("BackToList").'</a>';

        $customerAccountMovement = new customeraccountmovement($db);
        //$customerAccountMovement->fetch($id,$ref);
        $customerAccountMovement->fetch($id);
        
        $morefilters=' AND fk_customer_account = '.$customerAccountMovement->fk_customer_account;
        $moreparams='&amp;socid='.$socid.
        '&amp;entity='.$customerAccountMovement->entity.
        '&amp;fk_customer_account='.$customerAccountMovement->fk_customer_account.
        '&amp;active='.$customerAccountMovement->active;
        
        $customerAccountMovement->next_prev_filter=$morefilters;
        $customerAccountMovement->ref=$id;

        // Ref
        print '<tr><td class="titlefield">'.$langs->trans("Ref")."</td>";
        print '<td>';
        
        $refnav = $form->showrefnav($customerAccountMovement, 'rowid', $linkback, 1, 'rowid', 'id', '', $moreparams);
        $refnav = str_replace('rowid', 'id', $refnav);
        print $refnav;
        
        print '</td>';
        print '</tr>';
        
        // Date ope
        print '<tr><td>'.$langs->trans("CustomerAccountFieldDateOperationShort").'</td>';
        print '<td>';
        print dol_print_date($object->dateo, "day");
        print '</td>';
        print '</tr>';

        print '<tr><td class="fieldrequired">'.$langs->trans("CustomerAccountFieldamount").'</td><td>'.$object->amount.'</td></tr>';
        print '<tr><td class="fieldrequired">'.$langs->trans("CustomerAccountFieldlabel").'</td><td>'.$object->label.'</td></tr>';

	print '</table>';
	
	dol_fiche_end();


	// Buttons
	print '<div class="tabsAction">'."\n";
	$parameters=array();
	$reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	if (empty($reshook))
	{
		if ($user->rights->customer_accountt->write)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>'."\n";
		}

		if ($user->rights->customer_accountt->delete)
		{
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a></div>'."\n";
		}
	}
	print '</div>'."\n";


	// Example 2 : Adding links to objects
	// Show links to link elements
	//$linktoelem = $form->showLinkToObjectBlock($object, null, array('customeraccount'));
	//$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

}


// End of page
llxFooter();
$db->close();
