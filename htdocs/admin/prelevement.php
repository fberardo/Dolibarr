<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2013 Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/admin/prelevement.php
 *	\ingroup    prelevement
 *	\brief      Page configuration des prelevements
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("admin");
$langs->load("withdrawals");

// Security check
if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$type = 'paymentorder';


/*
 * Actions
 */

if ($action == "set")
{
    $db->begin();

    $id=GETPOST('PRELEVEMENT_ID_BANKACCOUNT','int');
    $account = new Account($db);
    if($account->fetch($id)>0)
    {
        $res = dolibarr_set_const($db, "PRELEVEMENT_ID_BANKACCOUNT", $id,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        /*
        $res = dolibarr_set_const($db, "PRELEVEMENT_CODE_BANQUE", $account->code_banque,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_CODE_GUICHET", $account->code_guichet,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_NUMERO_COMPTE", $account->number,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_NUMBER_KEY", $account->cle_rib,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_IBAN", $account->iban,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_BIC", $account->bic,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_RAISON_SOCIALE", $account->proprio,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        */
    }
    else $error++;

    $res = dolibarr_set_const($db, "PRELEVEMENT_ICS", GETPOST("PRELEVEMENT_ICS"),'chaine',0,'',$conf->entity);
    if (! $res > 0) $error++;
    
    if (GETPOST("PRELEVEMENT_USER") > 0)
    {
        $res = dolibarr_set_const($db, "PRELEVEMENT_USER", GETPOST("PRELEVEMENT_USER"),'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
    }
    
    if (! $error)
	{
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == "addnotif")
{
    $bon = new BonPrelevement($db);
    $bon->AddNotification($db,GETPOST('user','int'),$action);

    header("Location: prelevement.php");
    exit;
}

if ($action == "deletenotif")
{
    $bon = new BonPrelevement($db);
    $bon->DeleteNotificationById(GETPOST('notif','int'));

    header("Location: prelevement.php");
    exit;
}

/*
if ($action == 'specimen')
{
    $modele=GETPOST('module','alpha');

    $commande = new Commande($db);
    $commande->initAsSpecimen();

    // Search template files
    $file=''; $classname=''; $filefound=0;
    $dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
    foreach($dirmodels as $reldir)
    {
        $file=dol_buildpath($reldir."core/modules/paymentorders/doc/pdf_".$modele.".modules.php",0);
        if (file_exists($file))
        {
            $filefound=1;
            $classname = "pdf_".$modele;
            break;
        }
    }

    if ($filefound)
    {
        require_once $file;

        $module = new $classname($db);

        if ($module->write_file($commande,$langs) > 0)
        {
            header("Location: ".DOL_URL_ROOT."/document.php?modulepart=paymentorders&file=SPECIMEN.pdf");
            return;
        }
        else
        {
            setEventMessages($module->error, null, 'errors');
            dol_syslog($module->error, LOG_ERR);
        }
    }
    else
    {
        setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
        dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}    

// Set default model
else if ($action == 'setdoc')
{
    if (dolibarr_set_const($db, "PAYMENTORDER_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
    {
        // The constant that was read before the new set
        // We therefore requires a variable to have a coherent view
        $conf->global->PAYMENTORDER_ADDON_PDF = $value;
    }

    // On active le modele
    $ret = delDocumentModel($value, $type);
    if ($ret > 0)
    {
        $ret = addDocumentModel($value, $type, $label, $scandir);
    }
}
*/


/*
 *	View
 */

$form=new Form($db);

$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);

llxHeader('',$langs->trans("WithdrawalsSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("WithdrawalsSetup"),$linkback,'title_setup');
print '<br>';

print '<form method="post" action="prelevement.php?action=set">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td width="30%">'.$langs->trans("Parameter").'</td>';
print '<td width="40%">'.$langs->trans("Value").'</td>';
print "</tr>";

// Bank account (from Banks module)
print '<tr class="impair"><td class="fieldrequired">'.$langs->trans("BankToReceiveWithdraw").'</td>';
print '<td align="left">';
$form->select_comptes($conf->global->PRELEVEMENT_ID_BANKACCOUNT,'PRELEVEMENT_ID_BANKACCOUNT',0,"courant=1",1);
print '</td></tr>';

// ICS
print '<tr class="pair"><td class="fieldrequired">'.$langs->trans("ICS").'</td>';
print '<td align="left">';
print '<input type="text" name="PRELEVEMENT_ICS" value="'.$conf->global->PRELEVEMENT_ICS.'" size="15" ></td>';
print '</td></tr>';

//User
print '<tr class="impair"><td class="fieldrequired">'.$langs->trans("ResponsibleUser").'</td>';
print '<td align="left">';
print $form->select_dolusers($conf->global->PRELEVEMENT_USER, 'PRELEVEMENT_USER', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
print '</td>';
print '</tr>';

print '</table>';
print '<br>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';

print '</form>';


print '<br>';


/*
 * Document templates generators
 */
/*
print load_fiche_titre($langs->trans("OrdersModelModule"),'','');

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
$sql.= " AND entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql)
{
    $i = 0;
    $num_rows=$db->num_rows($resql);
    while ($i < $num_rows)
    {
        $array = $db->fetch_array($resql);
        array_push($def, $array[0]);
        $i++;
    }
}
else
{
    dol_print_error($db);
}


print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="38">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="38">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

$var=true;
foreach ($dirmodels as $reldir)
{
    foreach (array('','/doc') as $valdir)
    {
        $dir = dol_buildpath($reldir."core/modules/paymentorders".$valdir);

        if (is_dir($dir))
        {
            $handle=opendir($dir);
            if (is_resource($handle))
            {
                while (($file = readdir($handle))!==false)
                {
                    $filelist[]=$file;
                }
                closedir($handle);
                arsort($filelist);

                foreach($filelist as $file)
                {
                    if (preg_match('/\.modules\.php$/i',$file) && preg_match('/^(pdf_|doc_)/',$file))
                    {

                        if (file_exists($dir.'/'.$file))
                        {
                            $name = substr($file, 4, dol_strlen($file) -16);
                            $classname = substr($file, 0, dol_strlen($file) -12);

                            require_once $dir.'/'.$file;
                            $module = new $classname($db);

                            $modulequalified=1;
                            if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
                            if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;

                            if ($modulequalified)
                            {
                                $var = !$var;
                                print '<tr '.$bc[$var].'><td width="100">';
                                print (empty($module->name)?$name:$module->name);
                                print "</td><td>\n";
                                if (method_exists($module,'info')) print $module->info($langs);
                                else print $module->description;
                                print '</td>';

                                // Active
                                if (in_array($name, $def))
                                {
                                    print '<td align="center">'."\n";
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'">';
                                    print img_picto($langs->trans("Enabled"),'switch_on');
                                    print '</a>';
                                    print '</td>';
                                }
                                else
                                {
                                    print '<td align="center">'."\n";
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
                                    print "</td>";
                                }

                                // Default
                                print '<td align="center">';
                                if ($conf->global->PAYMENTORDER_ADDON_PDF == $name)
                                {
                                    print img_picto($langs->trans("Default"),'on');
                                }
                                else
                                {
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
                                }
                                print '</td>';

                                // Info
                                $htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
                                $htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
                                if ($module->type == 'pdf')
                                {
                                    $htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
                                }
                                $htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
                                $htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
                                $htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg,1,1);
                                $htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg,1,1);
                                $htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);
                                //$htmltooltip.='<br>'.$langs->trans("Discounts").': '.yn($module->option_escompte,1,1);
                                //$htmltooltip.='<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
                                $htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftOrders").': '.yn($module->option_draft_watermark,1,1);


                                print '<td align="center">';
                                print $form->textwithpicto('',$htmltooltip,1,0);
                                print '</td>';

                                // Preview
                                print '<td align="center">';
                                if ($module->type == 'pdf')
                                {
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
                                }
                                else
                                {
                                    print img_object($langs->trans("PreviewNotAvailable"),'generic');
                                }
                                print '</td>';

                                print "</tr>\n";
                            }
                        }
                    }
                }
            }
        }
    }
}

*/


dol_fiche_end();

print '<br>';


/*
 * Notifications
 */

/* Disable this, there is no trigger with elementtype 'withdraw'
if (! empty($conf->global->MAIN_MODULE_NOTIFICATION))
{
    $langs->load("mails");
    print load_fiche_titre($langs->trans("Notifications"));

    $sql = "SELECT u.rowid, u.lastname, u.firstname, u.fk_soc, u.email";
    $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
    $sql.= " WHERE entity IN (0,".$conf->entity.")";

    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $var = true;
        $i = 0;
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            $var=!$var;
            if (!$obj->fk_soc)
            {
                $username=dolGetFirstLastname($obj->firstname,$obj->lastname);
                $internalusers[$obj->rowid] = $username;
            }

            $i++;
        }
        $db->free($resql);
    }

    // Get list of triggers for module withdraw
    $sql = "SELECT rowid, code, label";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_action_trigger";
    $sql.= " WHERE elementtype = 'withdraw'";
    $sql.= " ORDER BY rang ASC";

    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        $var = false;
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            $label=($langs->trans("Notify_".$obj->code)!="Notify_".$obj->code?$langs->trans("Notify_".$obj->code):$obj->label);
            $actions[$obj->rowid]=$label;
            $i++;
        }
        $db->free($resql);
    }


    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=addnotif">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("User").'</td>';
    print '<td>'.$langs->trans("Value").'</td>';
    print '<td align="right">'.$langs->trans("Action").'</td>';
    print "</tr>\n";

    print '<tr class="impair"><td align="left">';
    print $form->selectarray('user',$internalusers);//  select_dolusers(0,'user',0);
    print '</td>';

    print '<td>';
    print $form->selectarray('action',$actions);//  select_dolusers(0,'user',0);
    print '</td>';

    print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td></tr>';

	// List of current notifications for objet_type='withdraw'
	$sql = "SELECT u.lastname, u.firstname,";
	$sql.= " nd.rowid, ad.code, ad.label";
	$sql.= " FROM ".MAIN_DB_PREFIX."user as u,";
	$sql.= " ".MAIN_DB_PREFIX."notify_def as nd,";
	$sql.= " ".MAIN_DB_PREFIX."c_action_trigger as ad";
	$sql.= " WHERE u.rowid = nd.fk_user";
	$sql.= " AND nd.fk_action = ad.rowid";
	$sql.= " AND u.entity IN (0,".$conf->entity.")";

	$resql = $db->query($sql);
	if ($resql)
	{
	    $num = $db->num_rows($resql);
	    $i = 0;
	    $var = false;
	    while ($i < $num)
	    {
	        $obj = $db->fetch_object($resql);
	        $var=!$var;

	        print "<tr ".$bc[$var].">";
	        print '<td>'.dolGetFirstLastname($obj->firstname,$obj->lastname).'</td>';
	        $label=($langs->trans("Notify_".$obj->code)!="Notify_".$obj->code?$langs->trans("Notify_".$obj->code):$obj->label);
	        print '<td>'.$label.'</td>';
	        print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=deletenotif&amp;notif='.$obj->rowid.'">'.img_delete().'</a></td>';
	        print '</tr>';
	        $i++;
	    }
	    $db->free($resql);
	}

	print '</table>';
	print '</form>';
}
*/

llxFooter();
$db->close();
