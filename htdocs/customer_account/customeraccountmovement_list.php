<?php
/* Copyright (C) 2007-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2016      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2017      Nicolas ZABOURI	<info@inovea-conseil.com>
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
 *   	\file       customer_account_movement/customeraccountmovement_list.php
 *		\ingroup    customer_account_movement
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
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
//dol_include_once('/customer_account/class/customeraccountmovement.class.php');
require_once DOL_DOCUMENT_ROOT.'/customer_account/class/customeraccountmovement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

// Load traductions files requiredby by page
$langs->load("customeraccount@customer_account");
$langs->load("other");

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

$socid			= GETPOST('socid','int');
$backtopage = GETPOST('backtopage');
$myparam	= GETPOST('myparam','alpha');

$search_all=trim(GETPOST("sall"));

$search_ref=GETPOST('search_ref','alpha');
$search_dt_start = dol_mktime(0, 0, 0, GETPOST('search_start_dtmonth', 'int'), GETPOST('search_start_dtday', 'int'), GETPOST('search_start_dtyear', 'int'));
$search_dt_end = dol_mktime(0, 0, 0, GETPOST('search_end_dtmonth', 'int'), GETPOST('search_end_dtday', 'int'), GETPOST('search_end_dtyear', 'int'));
$search_amount=GETPOST('search_amount','alpha');
$search_description=GETPOST('search_description','alpha');


$optioncss = GETPOST('optioncss','alpha');

// Load variable for pagination
$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="t.rowid"; // Set here default search field
if (! $sortorder) $sortorder="ASC";

// Protection if external user
/*$socid=0;
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
	//accessforbidden();
}*/

// Load object if id or ref is provided as parameter
//$object=new customeraccountmovement($db);
$object = new Societe($db);
/*if (($id > 0 || ! empty($ref)) && $action != 'add')
{
	$result=$object->fetch($id,$ref);
	if ($result < 0) dol_print_error($db);
}*/

if ($socid > 0 && empty($object->id))
{
	// Load data of third party
	$res=$object->fetch($socid);
	if ($object->id <= 0) dol_print_error($db,$object->error);
}

// Initialize technical object to manage context to save list fields
//$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'customer_account_movementlist';
$contextpage='customer_account_movementlist'.(empty($object->ref)?'':'-'.$object->id);


// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('customer_account_movementlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('customer_account_movement');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    't.ref'=>'Ref',
    't.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["t.note_private"]="NotePrivate";

// Definition of fields for list
$arrayfields=array(
    't.rowid'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    't.dateo'=>array('label'=>$langs->trans("CustomerAccountFieldDateOperationShort"), 'checked'=>1),
    't.amount'=>array('label'=>$langs->trans("CustomerAccountFieldamount"), 'checked'=>1),
    't.label'=>array('label'=>$langs->trans("CustomerAccountFieldlabel"), 'checked'=>1),
    't.datec'=>array('label'=>$langs->trans("CustomerAccountDateCreationShort"), 'checked'=>0, 'position'=>500)
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
        $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
    }
}


/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") ||GETPOST("button_removefilter")) // All tests are required to be compatible with all browsers
    {

        $search_ref="";        
        $search_dt_start='';
        $search_dt_end='';
        $search_amount='';
        $search_description='';
        
    	$search_date_creation='';
    	$search_date_update='';
        $toselect='';
        $search_array_options=array();
    }

    // Mass actions
    $objectclass='Skeleton';
    $objectlabel='Skeleton';
    $permtoread = $user->rights->customeraccountmovement->read;
    $permtodelete = $user->rights->customeraccountmovement->delete;
    $uploaddir = $conf->customeraccountmovement->dir_output;
    //include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

$now=dol_now();

$form=new Form($db);

if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name;
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';

llxHeader('',$title,$help_url);

if ($socid > 0)
{

    $head = societe_prepare_head($object);

    dol_fiche_head($head, 'customeraccountmovement', $langs->trans("ThirdParty"),0,'company');

    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php">'.$langs->trans("BackToList").'</a>';
	
    dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');
    
    
    
//$help_url="EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
$help_url='';
$title = $langs->trans('CustomerAccountListTitle');

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


$sql = "SELECT";
$sql.= " t.rowid,";
$sql .= " t.entity,";
$sql .= " t.datec,";
$sql .= " t.tms,";
$sql .= " t.dateo,";
$sql .= " t.amount,";
$sql .= " t.label,";
$sql .= " t.fk_customer_account,";
$sql .= " t.fk_user_author,";
$sql .= " t.fk_user_modif,";
$sql .= " t.active";

// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."customer_account_movement as t";

$sql.= " INNER JOIN ".MAIN_DB_PREFIX."customer_account as a ON (t.fk_customer_account = a.rowid)";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON (a.fk_societe = s.rowid)";

if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."customer_account_movement_extrafields as ef on (t.rowid = ef.fk_object)";
//$sql.= " WHERE 1 = 1";
$sql.= " WHERE s.rowid = ".$socid;
//$sql.= " WHERE u.entity IN (".getEntity('mytable',1).")";

if ($search_ref) $sql.=natural_search("t.rowid", $search_ref);
if (dol_strlen($search_dt_start)>0) $sql .= " AND t.dateo >= '" . $db->idate($search_dt_start) . "'";
if (dol_strlen($search_dt_end)>0) $sql .= " AND t.dateo <= '" . $db->idate($search_dt_end) . "'";
if ($search_amount) $sql.= natural_search("amount",$search_amount);
if ($search_description) $sql.= natural_search("label",$search_description);


if ($sall)          $sql.= natural_search(array_keys($fieldstosearchall), $sall);
// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    $typ=$extrafields->attribute_type[$tmpkey];
    $mode=0;
    if (in_array($typ, array('int','double'))) $mode=1;    // Search on a numeric
    if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit))) 
    {
        $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
    }
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.=$db->order($sortfield,$sortorder);
//$sql.= $db->plimit($conf->liste_limit+1, $offset);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}	

$sql.= $db->plimit($limit+1, $offset);

dol_syslog($script_file, LOG_DEBUG);
$resql=$db->query($sql);
if (! $resql)
{
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

// Direct jump if only one record found
if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all)
{
    $obj = $db->fetch_object($resql);
    
    $hiddenobjvaluesedit='&amp;entity='.$obj->entity.
    '&amp;fk_customer_account='.$obj->fk_customer_account.
    '&amp;active='.$obj->active;
    
    header("Location: ".DOL_URL_ROOT.'/customer_account/customeraccountmovement_card.php?id='.$obj->rowid.'&socid='.$socid.$hiddenobjvaluesedit);
    exit;
}

$arrayofselected=is_array($toselect)?$toselect:array();

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
if ($search_field1 != '') $param.= '&amp;search_field1='.urlencode($search_field1);
if ($search_field2 != '') $param.= '&amp;search_field2='.urlencode($search_field2);
if ($optioncss != '') $param.='&optioncss='.$optioncss;
// Add $param from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
} 

$arrayofmassactions =  array(
    'presend'=>$langs->trans("SendByMail"),
    'builddoc'=>$langs->trans("PDFMerge"),
);
if ($user->rights->customer_account_movement->supprimer) $arrayofmassactions['delete']=$langs->trans("Delete");
if ($massaction == 'presend') $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

// Get Customer account ID
$sql2 = "SELECT";
$sql2.= " rowid";
$sql2.= " FROM ".MAIN_DB_PREFIX."customer_account";
$sql2.= " WHERE fk_societe = ".$socid;

$resql2=$db->query($sql2);
if (! $resql2)
{
    dol_print_error($db);
    exit;
}

$obj2 = $db->fetch_object($resql2);

$newcardbutton='';
$hiddenobjvaluesnew='&amp;entity=1'.
        '&amp;fk_customer_account='.$obj2->rowid.
        '&amp;active=1';

//if ($user->rights->banque->configurer)
//{
	$newbutton.='<a class="butAction" href="customeraccountmovement_card.php?action=create&socid='.$socid.$hiddenobjvaluesnew.'">'.$langs->trans("CustomerAccountNewCustomerAccountMovement").'</a>';
//}

print '<form method="POST" id="searchFormList" action="'.$_SERVER['PHP_SELF'].'?socid='.$socid.'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="socid" value="'.$socid.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_companies', 0, $newbutton, '', $limit);

if ($sall)
{
    foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
    print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
}

$moreforfilter.='<div class="divsearchfield">';
$moreforfilter .= $langs->trans('CustomerAccountFieldDateOperationShort').' : ';
$moreforfilter .= '<div class="nowrap'.($conf->browser->layout=='phone'?' centpercent':'').' inline-block">'.$langs->trans('From') . ' ';
$moreforfilter .= $form->select_date($search_dt_start, 'search_start_dt', 0, 0, 1, "search_form", 1, 0, 1).'</div>';
//$moreforfilter .= ' - ';
$moreforfilter .= '<div class="nowrap'.($conf->browser->layout=='phone'?' centpercent':'').' inline-block">'.$langs->trans('to') . ' ' . $form->select_date($search_dt_end, 'search_end_dt', 0, 0, 1, "search_form", 1, 0, 1).'</div>';
$moreforfilter .= '</div>';

$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (! empty($moreforfilter))
{
    print '<div class="liste_titre liste_titre_bydiv centpercent">';
    print $moreforfilter;
    print '</div>';
}

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

// Fields title
print '<tr class="liste_titre">';
// 
if (! empty($arrayfields['t.rowid']['checked'])) print_liste_field_titre($arrayfields['t.rowid']['label'],$_SERVER['PHP_SELF'],'t.rowid','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['t.dateo']['checked'])) print_liste_field_titre($arrayfields['t.dateo']['label'],$_SERVER['PHP_SELF'],'t.dateo','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['t.amount']['checked'])) print_liste_field_titre($arrayfields['t.amount']['label'],$_SERVER['PHP_SELF'],'t.amount','',$params,'',$sortfield,$sortorder);
if (! empty($arrayfields['t.label']['checked'])) print_liste_field_titre($arrayfields['t.label']['label'],$_SERVER['PHP_SELF'],'t.label','',$params,'',$sortfield,$sortorder);
if (! empty($arrayfields['t.datec']['checked']))  print_liste_field_titre($arrayfields['t.datec']['label'],$_SERVER["PHP_SELF"],"t.datec","",$param,'class="nowrap"',$sortfield,$sortorder);

//if (! empty($arrayfields['t.field1']['checked'])) print_liste_field_titre($arrayfields['t.field1']['label'],$_SERVER['PHP_SELF'],'t.field1','',$param,'',$sortfield,$sortorder);
//if (! empty($arrayfields['t.field2']['checked'])) print_liste_field_titre($arrayfields['t.field2']['label'],$_SERVER['PHP_SELF'],'t.field2','',$param,'',$sortfield,$sortorder);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val) 
    {
        if (! empty($arrayfields["ef.".$key]['checked'])) 
        {
            $align=$extrafields->getAlignFlag($key);
            print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"ef.".$key,"",$param,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
        }
    }
}
// Hook fields
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print_liste_field_titre('', $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
        
print '</tr>'."\n";

// Fields title search
print '<tr class="liste_titre">';
// 
if (! empty($arrayfields['t.rowid']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="10"></td>';
if (! empty($arrayfields['t.dateo']['checked'])) print '<td class="liste_titre">&nbsp;</td>';
if (! empty($arrayfields['t.amount']['checked'])) print '<td class="liste_titre" width="50"><input type="text" class="flat" name="search_amount" value="'.$search_amount.'" size="10"></td>';
if (! empty($arrayfields['t.label']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_description" value="'.$search_description.'" size="10"></td>';

//if (! empty($arrayfields['t.field1']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_field1" value="'.$search_field1.'" size="10"></td>';
//if (! empty($arrayfields['t.field2']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_field2" value="'.$search_field2.'" size="10"></td>';
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val) 
    {
        if (! empty($arrayfields["ef.".$key]['checked']))
        {
            $align=$extrafields->getAlignFlag($key);
            $typeofextrafield=$extrafields->attribute_type[$key];
            print '<td class="liste_titre'.($align?' '.$align:'').'">';
            if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select')))
            {
                $crit=$val;
                $tmpkey=preg_replace('/search_options_/','',$key);
                $searchclass='';
                if (in_array($typeofextrafield, array('varchar', 'select'))) $searchclass='searchstring';
                if (in_array($typeofextrafield, array('int', 'double'))) $searchclass='searchnum';
                print '<input class="flat'.($searchclass?' '.$searchclass:'').'" size="4" type="text" name="search_options_'.$tmpkey.'" value="'.dol_escape_htmltag($search_array_options['search_options_'.$tmpkey]).'">';
            }
            print '</td>';
        }
    }
}

// Date creation
if (! empty($arrayfields['t.datec']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}

print '<td class="liste_titre">';
print '</td>';

// Action column
print '<td class="liste_titre" align="right">';
$searchpitco=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
print $searchpitco;
print '</td>';
print '</tr>'."\n";
    

$i=0;
$var=true;
$totalarray=array();
while ($i < min($num, $limit))
{
    $obj = $db->fetch_object($resql);
    if ($obj)
    {
        $var = !$var;
        
        // Show here line of result
        print '<tr '.$bc[$var].'>';
        // LIST_OF_TD_FIELDS_LIST
        foreach ($arrayfields as $key => $value) {
            if (!empty($arrayfields[$key]['checked'])) {
                
                if (!$i) $totalarray['nbfield'] ++;
                
                $key2 = str_replace('t.', '', $key);
                if ($key2 == 'rowid')
                {
                    $hiddenobjvaluesview='&amp;entity='.$obj->entity.
                    '&amp;fk_customer_account='.$obj->fk_customer_account.
                    '&amp;active='.$obj->active;
    
                    //header("Location: ".DOL_URL_ROOT.'/customer_account/customeraccountmovement_card.php?id='.$id);
                    print '<td align="left" class="nowrap">';
                    print "<a href=\"customeraccountmovement_card.php?id=".$obj->rowid.'&socid='.$socid.$hiddenobjvaluesview.'">'.img_object($langs->trans("CustomerAccountShowCustomerAccountMovement").': '.$obj->rowid, 'account', 'class="classfortooltip"').' '.$obj->rowid."</a> &nbsp; ";
                    print '</td>';
                }
                else if ($key2 == 'amount')
                {
                    print '<td align="right" style="padding-right: 30px">' . price($obj->$key2) . '</td>';
                    $totalarray['totalht'] += $obj->$key2;
                    if (!$i) $totalarray['totalhtfield'] = $totalarray['nbfield'];
                }
                else
                {
                    print '<td>' . $obj->$key2 . '</td>';
                }
            }
        }
    	// Extra fields
        if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
        {
            foreach($extrafields->attribute_label as $key => $val) 
            {
                if (! empty($arrayfields["ef.".$key]['checked'])) 
                {
                        print '<td';
                        $align=$extrafields->getAlignFlag($key);
                        if ($align) print ' align="'.$align.'"';
                        print '>';
                        $tmpkey='options_'.$key;
                        print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
                        print '</td>';
                        if (! $i) $totalarray['nbfield']++;
                }
            }
        }
        // Fields from hook
	$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
	$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
    	
        // Action column
        print '<td class="nowrap" align="center">';
	if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
        {
	        $selected=0;
		if (in_array($obj->rowid, $arrayofselected)) $selected=1;
		print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
        }
        
        $hiddenobjvaluesedit='&amp;entity='.$obj->entity.
        '&amp;fk_customer_account='.$obj->fk_customer_account.
        '&amp;active='.$obj->active;
        
        print '<a href="customeraccountmovement_card.php?action=edit&socid='.$socid.'&amp;id='.$obj->rowid.'&amp;page='.$page.$hiddenobjvaluesedit.'">';
        print img_edit();
        print '</a>';

        print '<a href="customeraccountmovement_card.php?action=delete&socid='.$socid.'&amp;id='.$obj->rowid.'&amp;page='.$page.'">';
        print img_delete();
        print '</a>';
        
        
	print '</td>';
        if (! $i) $totalarray['nbfield']++;

        print '</tr>';
    }
    $i++;
}

// Show total line
if (isset($totalarray['totalhtfield']))
{
    print '<tr class="liste_total">';
    $i=0;
    while ($i < $totalarray['nbfield'])
    {
        $i++;
        if ($i == 1)
        {
            if ($num < $limit && empty($offset)) print '<td align="left"><span style="font-weight:bold">'.$langs->trans("CustomerAccountTotal").'</span></td>';
            else print '<td align="left">'.$langs->trans("CustomerAccountTotalforthispage").'</td>';
        }
        elseif ($totalarray['totalhtfield'] == $i) print '<td align="right" style="padding-right:30px"><span style="font-weight:bold"><font color="green">'.price($totalarray['totalht']).'</font></span></td>';
        else print '<td></td>';
    }
    print '</tr>';
}

/* Saldo de la cuenta del cliente */
$saldocuenta = 0;
$sql = "SELECT SUM(";
$sql .= "t.amount)";
$sql.= " FROM ".MAIN_DB_PREFIX."customer_account_movement as t";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."customer_account as a ON (t.fk_customer_account = a.rowid)";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON (a.fk_societe = s.rowid)";
$sql.= " WHERE s.rowid = ".$socid;
$result = $db->query($sql);
if ($result) {
    $row = $db->fetch_row($result);
    $saldocuenta = $row[0];
    $db->free($result);
}

/* Total a pagar en facturas pendientes */
$restapagarfacturas = 0;
$sql = 'SELECT f.rowid as facid, f.facnumber, f.total_ttc, f.multicurrency_code, f.multicurrency_total_ttc, f.type, ';
$sql.= ' f.datef as df, f.fk_soc as socid';
$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
if(!empty($conf->global->FACTURE_PAYMENTS_ON_DIFFERENT_THIRDPARTIES_BILLS)) {
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON (f.fk_soc = s.rowid)';
}
$sql.= ' WHERE f.entity = '.$conf->entity;
$sql.= ' AND (f.fk_soc = '.$socid;
if(!empty($conf->global->FACTURE_PAYMENTS_ON_DIFFERENT_THIRDPARTIES_BILLS) && !empty($facture->thirdparty->parent)) {
    $sql.= ' OR f.fk_soc IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'societe WHERE parent = '.$facture->thirdparty->parent.')';
}
$sql.= ') AND f.paye = 0';
$sql.= ' AND f.fk_statut = 1'; // Statut=0 => not validated, Statut=2 => canceled
/*
if ($facture->type != 2)
{
    $sql .= ' AND type IN (0,1,3,5)';	// Standard invoice, replacement, deposit, situation
}
else
{
    $sql .= ' AND type = 2';		// If paying back a credit note, we show all credit notes
}
*/
$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    if ($num > 0)
    {
        $i = 0;
        $total=0;
        $totalrecu=0;
        $totalrecucreditnote=0;
        $totalrecudeposits=0;

        while ($i < $num)
        {
            $objp = $db->fetch_object($resql);
            $invoice=new Facture($db);
            $invoice->fetch($objp->facid);
            $paiement = $invoice->getSommePaiement();
            $creditnotes=$invoice->getSumCreditNotesUsed();
            $deposits=$invoice->getSumDepositsUsed();
            $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
            $remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT'); 
            
            $total+=$objp->total;
            $total_ttc+=$objp->total_ttc;
            $totalrecu+=$paiement;
            $totalrecucreditnote+=$creditnotes;
            $totalrecudeposits+=$deposits;
            
            $i++;
        }
        $restapagarfacturas = $total_ttc - $totalrecu - $totalrecucreditnote - $totalrecudeposits;
    }
}

/* Saldo cliente */
$saldocliente = ($saldocuenta - $restapagarfacturas);
$pintarcolor = "black"; // green?
$descripcion = "(al día)";
if ($saldocliente < 0) {
    $pintarcolor = "red";
    $descripcion = "(debe)";
    $saldocliente = $saldocliente * (-1); // (para que se vea positivo, sin el signo)
} else if ($saldocliente > 0) {
    $pintarcolor = "green";
    $descripcion = "(a favor)";
} // else $saldocliente == 0

print '</tr>';

$db->free($resql);

$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";


print '<table class="tagtable liste">'."\n";
// Fields title
print '<tr class="liste_titre">';
print '<th class="liste_titre"></th>';
print '<th class="liste_titre">'.$langs->trans("CustomerAccountSaldoCuentaCorriente").'</th>';
print '<th class="liste_titre">'.$langs->trans("CustomerAccountRestaPagarFacturas").'</th>';
print '<th class="liste_titre">'.$langs->trans("CustomerAccountSaldoCliente").'</th>';
print '</tr>';
// Row values
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("CustomerAccountBalanceCuentadelCliente").'</td>';
print '<td><span style="font-weight:bold">'.price($saldocuenta).'</font></span></td>';
print '<td><span style="font-weight:bold"><font color="red">'.price($restapagarfacturas).'</font></span></td>';
print '<td><span style="font-weight:bold"><font color="'.$pintarcolor.'">'.price($saldocliente).' ' .$descripcion. '</font></span></td>';

print '</tr>';
print '</table>'."\n";


print '</div>'."\n";

print '</form>'."\n";


/*if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files)
{
    // Show list of available documents
    $urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
    $urlsource.=str_replace('&amp;','&',$param);

    $filedir=$diroutputmassaction;
    $genallowed=$user->rights->facture->lire;
    $delallowed=$user->rights->facture->lire;

    print $formfile->showdocuments('massfilesarea_customer_account_movement','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'');
}
else
{
    print '<br><a name="show_files"></a><a href="'.$_SERVER["PHP_SELF"].'?show_files=1'.$param.'#show_files">'.$langs->trans("ShowTempMassFilesArea").'</a>';
}*/

}
else
{
	dol_print_error($db,'Bad value for socid parameter');
}

// End of page
llxFooter();
$db->close();
